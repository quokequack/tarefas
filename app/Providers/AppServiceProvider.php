<?php

namespace App\Providers;

use App\Http\Controllers\TaskController;
use App\Models\Task;
use App\Services\RequestTrace;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgetFailed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // TaskService é concreto: o container o resolve sem binding explícito.
        $this->app->scoped(RequestTrace::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            app(RequestTrace::class)->matched($event->route->getName() ?? '', $event->route->getActionName());
        });

        $this->app->resolving(TaskController::class, function (TaskController $controller): void {
            app(RequestTrace::class)->record('container', 'resolve', 'O container resolveu TaskController e injetou TaskService.', 'Dependency Injection');
        });

        DB::listen(function (QueryExecuted $query): void {
            $trace = app(RequestTrace::class);
            if (! $trace->isRecording()) {
                return;
            }

            if (request()->route('task') !== null && ! request()->route('task') instanceof Task) {
                $trace->record('binding', 'handle', 'O Route Model Binding está buscando a tarefa pelo identificador.');
            }

            $trace->record('model', null, 'Task representa os registros persistidos na tabela tasks.', 'Active Record');
            $method = match (strtolower(strtok(ltrim($query->sql), ' '))) {
                'insert', 'update' => 'save',
                'delete' => 'delete',
                default => 'query',
            };
            $trace->record('eloquent', $method, 'O Eloquent executou uma operação de persistência.', 'Active Record');
            $trace->record('database', null, 'Consulta realmente executada no SQLite.', details: [
                'SQL' => $query->sql,
                'parâmetros' => $query->bindings,
                'tempo SQL (ms)' => $query->time,
            ]);
        });

        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $trace = app(RequestTrace::class);
            $trace->record('facade', '__callStatic', 'Log encaminha a chamada para o serviço real de logging.', 'Proxy');
            $trace->record('log', $event->level, 'O serviço de log emitiu um registro.', 'Proxy', ['mensagem' => $event->message]);
        });

        foreach ([CacheHit::class, CacheMissed::class, KeyWritten::class, KeyForgotten::class, KeyForgetFailed::class] as $eventClass) {
            Event::listen($eventClass, function (CacheHit|CacheMissed|KeyWritten|KeyForgotten|KeyForgetFailed $event): void {
                if ($event->key !== 'tasks.total') {
                    return;
                }

                $operation = match (true) {
                    $event instanceof CacheHit => ['remember', 'Cache hit: o total foi reutilizado, sem consulta COUNT.'],
                    $event instanceof CacheMissed => ['remember', 'Cache miss: o total ainda precisa ser consultado.'],
                    $event instanceof KeyWritten => ['put', 'O total foi armazenado em cache por 60 segundos.'],
                    $event instanceof KeyForgetFailed => ['forget', 'forget() retornou false; nenhum valor foi removido do cache.'],
                    default => ['forget', 'O cache do total foi invalidado.'],
                };
                $trace = app(RequestTrace::class);
                $trace->record('facade', '__callStatic', 'Cache encaminha a chamada ao serviço resolvido pelo container.', 'Proxy');
                $trace->record('cache', $operation[0], $operation[1], 'Proxy', ['chave' => 'tasks.total']);
            });
        }

        View::composer(['tasks.index', 'tasks.edit', 'layouts.app'], function (\Illuminate\View\View $view): void {
            $file = match ($view->name()) {
                'tasks.index' => 'index-view',
                'tasks.edit' => 'edit-view',
                default => 'layout',
            };
            app(RequestTrace::class)->record($file, null, 'Blade está preparando a renderização de '.$view->name().'.');
        });
    }
}
