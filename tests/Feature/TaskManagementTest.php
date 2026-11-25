<?php

namespace Tests\Feature;

use App\Models\Task;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_tasks(): void
    {
        $this->get('/')->assertRedirect('/tasks');
    }

    public function test_tasks_are_listed_from_newest_to_oldest(): void
    {
        Task::factory()->create(['title' => 'Tarefa antiga', 'created_at' => now()->subDay()]);
        Task::factory()->create(['title' => 'Tarefa recente', 'created_at' => now()]);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.index')
            ->assertSeeInOrder(['Tarefa recente', 'Tarefa antiga'])
            ->assertViewHas('total', 2);
    }

    public function test_empty_list_is_explained(): void
    {
        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Nenhuma tarefa por aqui.')
            ->assertViewHas('total', 0);
    }

    public function test_task_is_created_as_pending_and_a_flash_message_is_shown(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Estudar Laravel', 'completed' => true])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Tarefa cadastrada com sucesso.');

        $this->assertDatabaseHas('tasks', ['title' => 'Estudar Laravel', 'completed' => false]);
        $this->get(route('tasks.index'))->assertSee('Tarefa cadastrada com sucesso.');
        $this->get(route('tasks.index'))->assertDontSee('Tarefa cadastrada com sucesso.');
    }

    #[DataProvider('invalidTitles')]
    public function test_invalid_titles_cannot_be_created(mixed $title): void
    {
        $this->from(route('tasks.index'))
            ->post(route('tasks.store'), ['title' => $title])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('tasks', 0);
    }

    public static function invalidTitles(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'missing' => [null],
            'too long' => [str_repeat('a', 256)],
            'array' => [['invalid']],
            'number' => [123],
        ];
    }

    public function test_validation_error_and_old_input_are_rendered_near_title(): void
    {
        $title = str_repeat('a', 256);

        $this->from(route('tasks.index'))->post(route('tasks.store'), ['title' => $title]);
        $this->get(route('tasks.index'))
            ->assertSee('O título deve ter no máximo 255 caracteres.')
            ->assertSee('id="title-error"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('value="'.$title.'"', false);
    }

    public function test_title_with_255_characters_is_accepted(): void
    {
        $title = str_repeat('á', 255);

        $this->post(route('tasks.store'), ['title' => $title])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['title' => $title]);
    }

    public function test_edit_page_displays_existing_title(): void
    {
        $task = Task::factory()->create(['title' => 'Título original']);

        $this->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertViewIs('tasks.edit')
            ->assertSee('Título original');
    }

    public function test_title_can_be_updated_without_changing_status(): void
    {
        $task = Task::factory()->completed()->create();

        $this->put(route('tasks.update', $task), ['title' => 'Título atualizado', 'completed' => false])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id, 'title' => 'Título atualizado', 'completed' => true,
        ]);
    }

    #[DataProvider('invalidTitles')]
    public function test_invalid_titles_cannot_be_updated(mixed $title): void
    {
        $task = Task::factory()->create(['title' => 'Título preservado']);

        $this->from(route('tasks.edit', $task))
            ->put(route('tasks.update', $task), ['title' => $title])
            ->assertRedirect(route('tasks.edit', $task))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Título preservado']);
    }

    public function test_pending_task_can_be_completed(): void
    {
        $task = Task::factory()->create();

        $this->patch(route('tasks.toggle', $task))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Tarefa concluída com sucesso.');

        $this->assertTrue($task->fresh()->completed);
    }

    public function test_completed_task_can_be_reopened(): void
    {
        $task = Task::factory()->completed()->create();

        $this->patch(route('tasks.toggle', $task))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Tarefa reaberta com sucesso.');

        $this->assertFalse($task->fresh()->completed);
    }

    public function test_task_can_be_deleted(): void
    {
        $task = Task::factory()->create();

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Tarefa excluída com sucesso.');

        $this->assertModelMissing($task);
    }

    public function test_completed_filter_only_returns_completed_tasks_and_preserves_global_total(): void
    {
        Task::factory()->create(['title' => 'Tarefa pendente']);
        $completed = Task::factory()->completed()->create(['title' => 'Tarefa concluída']);

        $this->get(route('tasks.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('Tarefa concluída')
            ->assertDontSee('Tarefa pendente')
            ->assertViewHas('total', 2)
            ->assertViewHas('tasks', fn ($tasks): bool => $tasks->modelKeys() === [$completed->id]);
    }

    public function test_pending_filter_only_returns_pending_tasks(): void
    {
        $pending = Task::factory()->create(['title' => 'Tarefa pendente']);
        Task::factory()->completed()->create(['title' => 'Tarefa concluída']);

        $this->get(route('tasks.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Tarefa pendente')
            ->assertDontSee('Tarefa concluída')
            ->assertViewHas('tasks', fn ($tasks): bool => $tasks->modelKeys() === [$pending->id]);
    }

    public function test_empty_filtered_list_has_its_own_message(): void
    {
        Task::factory()->create();

        $this->get(route('tasks.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('Nenhuma tarefa neste filtro.')
            ->assertViewHas('total', 1);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->getJson(route('tasks.index', ['status' => 'invalid']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_missing_task_returns_404_for_every_bound_route(): void
    {
        $this->get('/tasks/999/edit')->assertNotFound();
        $this->put('/tasks/999', ['title' => 'Inexistente'])->assertNotFound();
        $this->patch('/tasks/999/toggle')->assertNotFound();
        $this->delete('/tasks/999')->assertNotFound();
    }

    public function test_counter_cache_is_invalidated_after_creation_and_deletion(): void
    {
        $this->get(route('tasks.index'))->assertViewHas('total', 0);
        $this->assertSame(0, Cache::get('tasks.total'));

        $this->post(route('tasks.store'), ['title' => 'Atualizar contador']);
        $this->assertFalse(Cache::has('tasks.total'));
        $this->get(route('tasks.index'))->assertViewHas('total', 1);
        $this->assertSame(1, Cache::get('tasks.total'));

        $task = Task::query()->sole();
        $this->delete(route('tasks.destroy', $task));
        $this->assertFalse(Cache::has('tasks.total'));
        $this->get(route('tasks.index'))->assertViewHas('total', 0);
        $this->assertSame(0, Cache::get('tasks.total'));
    }

    public function test_total_is_reused_until_cache_expires(): void
    {
        $this->get(route('tasks.index'))->assertViewHas('total', 0);
        Task::factory()->create();

        $this->get(route('tasks.index'))->assertViewHas('total', 0);
        $this->travel(61)->seconds();
        $this->get(route('tasks.index'))->assertViewHas('total', 1);
    }

    public function test_task_titles_are_escaped_in_html(): void
    {
        $title = '<script>alert("teste")</script>';
        $task = Task::factory()->create(['title' => $title]);

        $this->get(route('tasks.index'))->assertSee($title)->assertDontSee($title, false);
        $this->get(route('tasks.edit', $task))->assertSee($title)->assertDontSee($title, false);
    }

    public function test_writes_without_csrf_token_are_rejected_outside_test_bypass(): void
    {
        $task = Task::factory()->create();
        $this->app['env'] = 'local';

        $this->post(route('tasks.store'), ['title' => 'Sem token'])->assertStatus(419);
        $this->put(route('tasks.update', $task), ['title' => 'Sem token'])->assertStatus(419);
        $this->patch(route('tasks.toggle', $task))->assertStatus(419);
        $this->delete(route('tasks.destroy', $task))->assertStatus(419);
        $this->assertDatabaseCount('tasks', 1);
        $this->assertFalse($task->fresh()->completed);
    }

    public function test_creation_status_change_and_deletion_are_logged(): void
    {
        Log::spy();

        $this->post(route('tasks.store'), ['title' => 'Tarefa com log']);
        $task = Task::query()->sole();
        $this->patch(route('tasks.toggle', $task));
        $this->delete(route('tasks.destroy', $task));

        Log::shouldHaveReceived('info')->with('Task created', ['task_id' => $task->id])->once();
        Log::shouldHaveReceived('info')->with('Task status changed', [
            'task_id' => $task->id, 'completed' => true,
        ])->once();
        Log::shouldHaveReceived('info')->with('Task deleted', ['task_id' => $task->id])->once();
    }

    public function test_seeder_provides_five_examples_without_duplicates_on_repeat(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->get(route('tasks.index'))->assertViewHas('total', 5);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('tasks', 5);
        $this->assertSame(2, Task::query()->where('completed', true)->count());
        $this->assertSame(3, Task::query()->where('completed', false)->count());
        $this->assertFalse(Cache::has('tasks.total'));
    }
}
