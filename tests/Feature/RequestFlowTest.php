<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Services\RequestTrace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withCookie(config('session.cookie'), str_repeat('a', 40))->withCredentials();
    }

    public function test_monitor_renders_the_tree_and_does_not_record_itself(): void
    {
        $this->get(route('flow.index'))
            ->assertOk()
            ->assertSee('Por dentro da requisição.')
            ->assertSee('TaskController.php')
            ->assertSee('Facade.php')
            ->assertSee('Abrir tarefas em outra aba');

        $this->getJson(route('flow.events'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['requests' => []]);
    }

    public function test_creation_records_real_sql_and_the_executed_layers(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Percorrer o Laravel'])->assertRedirect();
        $trace = $this->latestTrace();

        $this->assertSame('POST', $trace['method']);
        $this->assertSame('/tasks', $trace['url']);
        $this->assertSame('tasks.store', $trace['route']);
        $this->assertSame(302, $trace['status']);
        $files = array_column($trace['steps'], 'file');
        foreach (['entry', 'bootstrap', 'routes', 'container', 'request', 'controller', 'service', 'model', 'eloquent', 'database', 'cache', 'facade', 'log', 'response'] as $file) {
            $this->assertContains($file, $files);
        }
        $this->assertNotContains('index-view', $files);
        $this->assertSame('illustrated', $trace['steps'][0]['kind']);
        $this->assertSame('observed', $trace['steps'][2]['kind']);
        $queries = array_values(array_filter($trace['steps'], fn (array $step): bool => $step['file'] === 'database'));
        $this->assertStringContainsString('insert into', $queries[0]['details']['SQL']);
        $this->assertContains('Percorrer o Laravel', $queries[0]['details']['parâmetros']);
        $this->assertGreaterThanOrEqual(0, $trace['duration_ms']);
        $this->assertSame('response', end($trace['steps'])['file']);
    }

    public function test_listing_records_blade_and_keeps_redirect_follow_up_separate(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Separar requisições']);
        $this->get(route('tasks.index'))->assertOk();

        $traces = $this->getJson(route('flow.events'))->json('requests');
        $this->assertCount(2, $traces);
        $this->assertSame('GET', $traces[0]['method']);
        $this->assertSame('POST', $traces[1]['method']);
        $this->assertNotSame($traces[0]['id'], $traces[1]['id']);
        $files = array_column($traces[0]['steps'], 'file');
        $this->assertContains('index-view', $files);
        $this->assertContains('layout', $files);
    }

    public function test_edit_binding_update_toggle_and_delete_are_recorded(): void
    {
        $task = Task::factory()->create(['title' => 'Tarefa do percurso']);

        $this->get(route('tasks.edit', $task))->assertOk();
        $edit = $this->latestTrace();
        $this->assertContains('binding', array_column($edit['steps'], 'file'));
        $this->assertContains('edit-view', array_column($edit['steps'], 'file'));

        $this->put(route('tasks.update', $task), ['title' => 'Título editado'])->assertRedirect();
        $this->assertSame('tasks.update', $this->latestTrace()['route']);

        $this->patch(route('tasks.toggle', $task))->assertRedirect();
        $this->assertSame('tasks.toggle', $this->latestTrace()['route']);

        $this->delete(route('tasks.destroy', $task))->assertRedirect();
        $delete = $this->latestTrace();
        $this->assertSame('tasks.destroy', $delete['route']);
        $this->assertTrue(collect($delete['steps'])->contains(
            fn (array $step): bool => $step['file'] === 'database' && str_starts_with($step['details']['SQL'], 'delete'),
        ));
        $this->assertModelMissing($task);
    }

    public function test_invalid_input_stops_before_controller_action_and_service(): void
    {
        $this->from(route('tasks.index'))->post(route('tasks.store'), ['title' => ''])
            ->assertSessionHasErrors('title');

        $trace = $this->latestTrace();
        $files = array_column($trace['steps'], 'file');
        $this->assertSame(302, $trace['status']);
        $this->assertContains('request', $files);
        $this->assertNotContains('controller', $files);
        $this->assertNotContains('service', $files);
        $this->assertNotContains('database', $files);
        $this->assertArrayHasKey('validação', end($trace['steps'])['details']);
    }

    public function test_missing_task_records_404_without_running_the_service(): void
    {
        $this->get('/tasks/999999/edit')->assertNotFound();

        $trace = $this->latestTrace();
        $this->assertSame(404, $trace['status']);
        $this->assertContains('binding', array_column($trace['steps'], 'file'));
        $this->assertNotContains('service', array_column($trace['steps'], 'file'));
    }

    public function test_cache_hit_is_visible_and_does_not_invent_a_count_query(): void
    {
        Task::factory()->create();
        $this->get(route('tasks.index'));
        $first = $this->latestTrace();
        $this->assertTrue(collect($first['steps'])->contains(
            fn (array $step): bool => $step['file'] === 'cache' && str_starts_with($step['description'], 'Cache miss'),
        ));

        $this->get(route('tasks.index'));
        $second = $this->latestTrace();
        $this->assertTrue(collect($second['steps'])->contains(
            fn (array $step): bool => $step['file'] === 'cache' && str_starts_with($step['description'], 'Cache hit'),
        ));
        $this->assertFalse(collect($second['steps'])->contains(
            fn (array $step): bool => $step['file'] === 'database' && str_contains($step['details']['SQL'], 'count('),
        ));
    }

    public function test_filter_url_and_query_are_preserved(): void
    {
        Task::factory()->completed()->create();
        $this->get(route('tasks.index', ['status' => 'completed']));

        $trace = $this->latestTrace();
        $this->assertSame('/tasks?status=completed', $trace['url']);
        $this->assertTrue(collect($trace['steps'])->contains(
            fn (array $step): bool => $step['file'] === 'database' && str_contains($step['details']['SQL'], 'where "completed" = ?'),
        ));
    }

    public function test_array_filter_is_rejected_without_breaking_capture(): void
    {
        $this->getJson(route('tasks.index', ['status' => ['completed']]))->assertUnprocessable();
        $this->assertSame(422, $this->latestTrace()['status']);
    }

    public function test_monitor_polling_and_source_reads_do_not_add_traces(): void
    {
        $this->get(route('tasks.index'));
        $this->getJson(route('flow.events'))->assertJsonCount(1, 'requests');
        $this->getJson(route('flow.source', ['file' => 'controller']))->assertOk();
        $this->getJson(route('flow.events'))->assertJsonCount(1, 'requests');
        $this->get(route('flow.index'))->assertOk();
        $this->getJson(route('flow.events'))->assertJsonCount(1, 'requests');
        $this->assertFalse(app(RequestTrace::class)->isRecording());
    }

    public function test_history_is_limited_to_twenty_requests_and_expires(): void
    {
        for ($index = 0; $index < 23; $index++) {
            $this->get(route('tasks.index'));
        }
        $this->getJson(route('flow.events'))->assertJsonCount(20, 'requests');

        $this->travel(61)->minutes();
        $this->getJson(route('flow.events'))->assertExactJson(['requests' => []]);
    }

    public function test_background_monitor_reads_do_not_consume_task_flash_messages(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Preservar feedback']);
        $this->getJson(route('flow.events'))->assertJsonCount(1, 'requests');
        $this->getJson(route('flow.source', ['file' => 'controller']))->assertOk();
        $this->get(route('tasks.index'))->assertSee('Tarefa cadastrada com sucesso.');
        $this->getJson(route('flow.events'))->assertOk();
        $this->get(route('tasks.index'))->assertDontSee('Tarefa cadastrada com sucesso.');
    }

    public function test_history_is_separated_by_browser_session(): void
    {
        $this->get(route('tasks.index'));
        $this->getJson(route('flow.events'))->assertJsonCount(1, 'requests');

        $this->withCookie(config('session.cookie'), str_repeat('b', 40));
        $this->getJson(route('flow.events'))->assertExactJson(['requests' => []]);
    }

    public function test_source_endpoint_reads_the_real_controller_and_method_line(): void
    {
        $response = $this->getJson(route('flow.source', ['file' => 'controller', 'method' => 'store']))
            ->assertOk()
            ->assertJsonPath('path', 'app/Http/Controllers/TaskController.php');

        $source = $response->json();
        $line = $source['highlight_line'];
        $lines = file(base_path($source['path']), FILE_IGNORE_NEW_LINES);
        $this->assertStringContainsString('function store(', $lines[$line - 1]);
        $this->assertStringContainsString('function store(', $source['code']);
        $this->assertLessThanOrEqual(100, count(explode("\n", $source['code'])));
    }

    public function test_real_framework_facade_source_is_available(): void
    {
        $this->getJson(route('flow.source', ['file' => 'facade', 'method' => '__callStatic']))
            ->assertOk()
            ->assertJsonPath('path', 'vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php')
            ->assertJsonFragment(['code' => app(RequestTrace::class)->source('facade', '__callStatic')['code']]);
    }

    public function test_only_allowlisted_text_files_can_be_read(): void
    {
        foreach (['env', 'composer', 'database', 'unknown'] as $file) {
            $this->getJson(route('flow.source', ['file' => $file]))->assertNotFound();
        }

        $this->getJson('/fluxo/arquivos/entry?method[]=store')->assertUnprocessable();
        $this->getJson('/fluxo/arquivos/entry?path=.env')->assertOk()->assertJsonPath('path', 'public/index.php');
    }

    private function latestTrace(): array
    {
        $response = $this->getJson(route('flow.events'))->assertOk();
        $this->assertNotEmpty($response->json('requests'));

        return $response->json('requests.0');
    }
}
