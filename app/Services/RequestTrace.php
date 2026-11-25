<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestTrace
{
    /** Arquivos permitidos para a demonstração; nunca aceitar caminhos enviados pelo navegador. */
    public const FILES = [
        'entry' => ['path' => 'public/index.php', 'group' => 'Aplicação'],
        'bootstrap' => ['path' => 'bootstrap/app.php', 'group' => 'Aplicação'],
        'routes' => ['path' => 'routes/web.php', 'group' => 'Aplicação'],
        'controller' => ['path' => 'app/Http/Controllers/TaskController.php', 'group' => 'Aplicação'],
        'request' => ['path' => 'app/Http/Requests/TaskRequest.php', 'group' => 'Aplicação'],
        'service' => ['path' => 'app/Services/TaskService.php', 'group' => 'Aplicação'],
        'model' => ['path' => 'app/Models/Task.php', 'group' => 'Aplicação'],
        'index-view' => ['path' => 'resources/views/tasks/index.blade.php', 'group' => 'Aplicação'],
        'edit-view' => ['path' => 'resources/views/tasks/edit.blade.php', 'group' => 'Aplicação'],
        'layout' => ['path' => 'resources/views/layouts/app.blade.php', 'group' => 'Aplicação'],
        'database' => ['path' => 'database/database.sqlite', 'group' => 'Dados'],
        'kernel' => ['path' => 'vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php', 'group' => 'Framework'],
        'router' => ['path' => 'vendor/laravel/framework/src/Illuminate/Routing/Router.php', 'group' => 'Framework'],
        'container' => ['path' => 'vendor/laravel/framework/src/Illuminate/Container/Container.php', 'group' => 'Framework'],
        'binding' => ['path' => 'vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php', 'group' => 'Framework'],
        'eloquent' => ['path' => 'vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php', 'group' => 'Framework'],
        'facade' => ['path' => 'vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php', 'group' => 'Framework'],
        'log' => ['path' => 'vendor/laravel/framework/src/Illuminate/Log/Logger.php', 'group' => 'Framework'],
        'cache' => ['path' => 'vendor/laravel/framework/src/Illuminate/Cache/Repository.php', 'group' => 'Framework'],
        'response' => ['path' => 'vendor/symfony/http-foundation/Response.php', 'group' => 'Framework'],
    ];

    private bool $active = false;

    private float $startedAt = 0;

    private array $trace = [];

    public static function files(): array
    {
        $files = self::FILES;
        $sqlite = config('database.default') === 'sqlite';
        $files['database']['path'] = $sqlite ? 'database/database.sqlite' : 'config/database.php';
        $files['database']['binary'] = $sqlite;

        return $files;
    }

    public function begin(Request $request): void
    {
        $this->active = true;
        $this->startedAt = microtime(true);
        $this->trace = [
            'id' => (string) Str::uuid(),
            'method' => $request->method(),
            'url' => '/'.$request->path().(is_string($request->query('status')) ? '?status='.urlencode($request->query('status')) : ''),
            'time' => now()->format('H:i:s'),
            'route' => null,
            'steps' => [],
        ];

        $this->record('entry', 'handleRequest', 'O servidor entrega a requisição ao ponto de entrada.', kind: 'illustrated');
        $this->record('bootstrap', 'withRouting', 'O bootstrap configura a aplicação, os providers e as rotas.', kind: 'illustrated');
        $this->record('kernel', 'handle', 'A requisição entrou no pipeline HTTP; a instrumentação começa aqui.');
    }

    public function isRecording(): bool
    {
        return $this->active;
    }

    public function record(
        string $file,
        ?string $method,
        string $description,
        ?string $pattern = null,
        array $details = [],
        string $kind = 'observed',
    ): void {
        if (! $this->active || count($this->trace['steps']) >= 80) {
            return;
        }

        $this->trace['steps'][] = compact('file', 'method', 'description', 'pattern', 'details', 'kind');
    }

    public function matched(string $route, string $action): void
    {
        if (! $this->active) {
            return;
        }

        $this->trace['route'] = $route;
        $this->record('router', 'runRoute', 'O Router encontrou a rota correspondente.', details: ['rota' => $route]);
        $this->record('routes', null, 'A declaração da rota associa o endereço à ação.', details: ['ação' => $action]);
    }

    public function finish(Request $request, Response $response): void
    {
        if (! $this->active) {
            return;
        }

        $redirect = $response->isRedirection();
        $description = $redirect
            ? 'O servidor responde com redirecionamento. O GET seguinte é outra requisição.'
            : 'O servidor conclui a resposta e a devolve pelo pipeline de middleware.';
        $details = ['HTTP' => $response->getStatusCode()];

        if ($redirect) {
            $details['destino'] = $response->headers->get('Location');
        }
        if ($request->method() !== 'GET' && $request->hasSession() && $request->session()->has('errors')) {
            $details['validação'] = 'A entrada foi rejeitada; consulte os erros no formulário.';
        }

        $this->record('response', 'setStatusCode', $description, details: $details);
        $this->active = false;
        $this->trace['status'] = $response->getStatusCode();
        $this->trace['duration_ms'] = round((microtime(true) - $this->startedAt) * 1000, 2);

        if ($request->hasSession()) {
            $key = $this->key($request);
            Cache::lock($key.'.lock', 10)->block(5, function () use ($key): void {
                $history = Cache::get($key, []);
                array_unshift($history, $this->trace);
                Cache::put($key, array_slice($history, 0, 20), now()->addHour());
            });
        }
    }

    /** @return list<array<string, mixed>> */
    public function recent(Request $request): array
    {
        $sessionId = $request->cookie(config('session.cookie'));
        if (! is_string($sessionId) || ! preg_match('/^[a-zA-Z0-9]{40}$/', $sessionId)) {
            return [];
        }

        return Cache::get('demo.requests.'.hash('sha256', $sessionId), []);
    }

    public function source(string $file, ?string $method): array
    {
        $files = self::files();
        abort_unless(isset($files[$file]) && ! ($files[$file]['binary'] ?? false), 404);
        $path = $files[$file]['path'];
        abort_unless(is_file(base_path($path)), 404);
        $lines = file(base_path($path), FILE_IGNORE_NEW_LINES);
        $target = null;

        if ($method !== null && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $method)) {
            foreach ($lines as $index => $line) {
                if (preg_match('/function\\s+'.preg_quote($method, '/').'\\s*\\(/', $line)) {
                    $target = $index;
                    break;
                }
            }

            if ($target === null) {
                foreach ($lines as $index => $line) {
                    if (str_contains($line, '->'.$method.'(') || str_contains($line, '::'.$method.'(')) {
                        $target = $index;
                        break;
                    }
                }
            }
        }

        $start = max(0, ($target ?? 0) - 5);

        return [
            'path' => $path,
            'start_line' => $start + 1,
            'highlight_line' => $target === null ? null : $target + 1,
            'code' => implode("\n", array_slice($lines, $start, 100)),
        ];
    }

    private function key(Request $request): string
    {
        return 'demo.requests.'.hash('sha256', $request->session()->getId());
    }
}
