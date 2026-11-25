<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Services\RequestTrace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CloudPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'database', 'session.driver' => 'database', 'session.encrypt' => true]);
        $this->app['cache']->forgetDriver();
        $this->app['session']->forgetDrivers();
        $this->withCookie(config('session.cookie'), str_repeat('c', 40))->withCredentials();
    }

    public function test_tasks_sessions_and_history_survive_new_store_instances(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Persistir entre instâncias'])->assertRedirect();
        $this->assertDatabaseHas('sessions', ['id' => str_repeat('c', 40)]);
        $this->assertDatabaseHas('tasks', ['title' => 'Persistir entre instâncias']);

        $this->app['cache']->forgetDriver();
        $this->app['session']->forgetDrivers();

        $this->getJson(route('flow.events'))
            ->assertOk()
            ->assertJsonCount(1, 'requests')
            ->assertJsonPath('requests.0.method', 'POST');
        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Persistir entre instâncias')
            ->assertSee('Tarefa cadastrada com sucesso.');
        $this->assertSame(1, Cache::get('tasks.total'));
        $this->assertGreaterThan(0, DB::table('cache')->count());
        $this->assertSame(0, DB::table('cache_locks')->count());
    }

    public function test_session_and_cache_sql_do_not_appear_as_task_operations(): void
    {
        $this->post(route('tasks.store'), ['title' => 'SQL de tarefas']);
        $this->get(route('tasks.index'));
        $trace = $this->getJson(route('flow.events'))->json('requests.0');
        $queries = collect($trace['steps'])->where('file', 'database');

        $this->assertNotEmpty($queries->all());
        foreach ($queries as $step) {
            $this->assertStringContainsString('tasks', $step['details']['SQL']);
            $this->assertStringNotContainsString('sessions', $step['details']['SQL']);
            $this->assertStringNotContainsString('cache', $step['details']['SQL']);
        }
        $driver = DB::connection()->getDriverName();
        $database = $driver === 'pgsql' ? 'PostgreSQL' : 'SQLite';
        $this->assertStringContainsString($database, $queries->first()['description']);
    }

    public function test_database_cache_is_invalidated_by_creation_and_deletion(): void
    {
        $this->get(route('tasks.index'))->assertViewHas('total', 0);
        $this->assertSame(0, Cache::get('tasks.total'));

        $this->post(route('tasks.store'), ['title' => 'Contador persistente']);
        $this->assertFalse(Cache::has('tasks.total'));
        $this->get(route('tasks.index'))->assertViewHas('total', 1);

        $task = Task::query()->sole();
        $this->delete(route('tasks.destroy', $task))->assertRedirect();
        $this->assertFalse(Cache::has('tasks.total'));
        $this->get(route('tasks.index'))->assertViewHas('total', 0);
    }

    public function test_read_only_monitor_preserves_database_session_flash(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Flash compartilhado']);
        $this->app['session']->forgetDrivers();

        $this->getJson(route('flow.events'))->assertJsonCount(1, 'requests');
        $this->getJson(route('flow.source', ['file' => 'controller']))->assertOk();
        $this->app['session']->forgetDrivers();
        $this->get(route('tasks.index'))->assertSee('Tarefa cadastrada com sucesso.');
        $this->get(route('tasks.index'))->assertDontSee('Tarefa cadastrada com sucesso.');
    }

    public function test_validation_errors_survive_database_session_reload(): void
    {
        $this->from(route('tasks.index'))->post(route('tasks.store'), ['title' => ''])
            ->assertSessionHasErrors('title');

        $this->app['session']->forgetDrivers();
        $this->getJson(route('flow.events'))->assertJsonPath('requests.0.status', 302);
        $this->get(route('tasks.index'))->assertSee('Informe o título da tarefa.');
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_postgresql_node_exposes_configuration_source_without_credentials(): void
    {
        $previous = config('database.default');
        config(['database.default' => 'pgsql']);

        try {
            $files = RequestTrace::files();
            $this->assertSame('config/database.php', $files['database']['path']);
            $this->assertFalse($files['database']['binary']);

            $source = app(RequestTrace::class)->source('database', null);
            $this->assertSame('config/database.php', $source['path']);
            $this->assertStringContainsString("env('DB_PASSWORD'", $source['code']);
            $this->assertStringNotContainsString('APP_KEY=', $source['code']);
        } finally {
            config(['database.default' => $previous]);
        }
    }

    public function test_https_proxy_headers_generate_secure_asset_and_redirect_urls(): void
    {
        TrustProxies::at('*');
        config(['session.secure' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tasks.example.test',
            'X-Forwarded-Port' => '443',
        ]);

        $response = $this->get(route('tasks.index'))->assertOk()
            ->assertSee('https://tasks.example.test/css/app.css', false);
        $this->assertTrue($response->getCookie(config('session.cookie'))->isSecure());

        $this->post(route('tasks.store'), ['title' => 'HTTPS na Vercel'])
            ->assertRedirect('https://tasks.example.test/tasks');
    }
}
