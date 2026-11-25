<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TaskService
{
    /** @return Collection<int, Task> */
    public function listTasks(?string $status): Collection
    {
        app(RequestTrace::class)->record('service', __FUNCTION__, 'O Service prepara a consulta, o filtro e a ordenação.');

        // Active Record: o próprio Model oferece a consulta e a persistência.
        return Task::query()
            ->when($status === 'pending', fn (Builder $query): Builder => $query->where('completed', false))
            ->when($status === 'completed', fn (Builder $query): Builder => $query->where('completed', true))
            ->latest()
            ->orderByDesc('id')
            ->get();
    }

    public function totalTasks(): int
    {
        app(RequestTrace::class)->record('service', __FUNCTION__, 'O Service solicita o total global de tarefas.');

        // Facades são proxies estáticos para serviços resolvidos pelo Service Container.
        return Cache::remember('tasks.total', 60, fn (): int => Task::count());
    }

    /** @param array{title: string} $data */
    public function createTask(array $data): Task
    {
        app(RequestTrace::class)->record('service', __FUNCTION__, 'O Service cadastra uma tarefa pendente por meio de Task::create().');
        $task = Task::create(['title' => $data['title'], 'completed' => false]);

        Cache::forget('tasks.total');

        return $task;
    }

    public function findTask(int $id): Task
    {
        app(RequestTrace::class)->record('service', __FUNCTION__, 'O Service procura uma tarefa com findOrFail().');

        return Task::findOrFail($id);
    }

    /** @param array{title: string} $data */
    public function updateTask(Task $task, array $data): Task
    {
        app(RequestTrace::class)->record('service', __FUNCTION__, 'O Service altera o título e chama save().');
        $task->title = $data['title'];
        $task->save();

        return $task;
    }

    public function toggleTask(Task $task): Task
    {
        app(RequestTrace::class)->record('service', __FUNCTION__, 'O Service alterna completed e chama save().');
        $task->completed = ! $task->completed;
        $task->save();

        return $task;
    }

    public function deleteTask(Task $task): void
    {
        app(RequestTrace::class)->record('service', __FUNCTION__, 'O Service solicita a exclusão com delete().');
        $task->delete();

        Cache::forget('tasks.total');
    }
}
