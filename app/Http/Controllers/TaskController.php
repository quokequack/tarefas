<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Services\RequestTrace;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    // Dependency Injection: o container constrói e injeta TaskService automaticamente.
    public function __construct(private TaskService $taskService) {}

    public function index(Request $request): View
    {
        app(RequestTrace::class)->record('controller', __FUNCTION__, 'A ação index coordena a listagem de tarefas.');
        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['pending', 'completed'])],
        ]);
        $status = $filters['status'] ?? null;

        Log::info('Iniciando consulta de tarefas', ['status' => $status]);
        $tasks = $this->taskService->listTasks($status);
        Log::info('Consulta concluída', ['quantidade' => $tasks->count()]);

        return view('tasks.index', [
            'tasks' => $tasks,
            'status' => $status,
            'total' => $this->taskService->totalTasks(),
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        app(RequestTrace::class)->record('controller', __FUNCTION__, 'A ação store recebe os dados já validados.');
        $task = $this->taskService->createTask($request->validated());
        Log::info('Task created', ['task_id' => $task->id]);

        return to_route('tasks.index')->with('success', 'Tarefa cadastrada com sucesso.');
    }

    public function edit(Task $task): View
    {
        app(RequestTrace::class)->record('controller', __FUNCTION__, 'A ação edit recebe a tarefa encontrada pelo Model Binding.');

        return view('tasks.edit', ['task' => $task]);
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        app(RequestTrace::class)->record('controller', __FUNCTION__, 'A ação update coordena a edição da tarefa.');
        $this->taskService->updateTask($task, $request->validated());
        Log::info('Task updated', ['task_id' => $task->id]);

        return to_route('tasks.index')->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function toggle(Task $task): RedirectResponse
    {
        app(RequestTrace::class)->record('controller', __FUNCTION__, 'A ação toggle coordena a alteração de estado.');
        $this->taskService->toggleTask($task);
        Log::info('Task status changed', ['task_id' => $task->id, 'completed' => $task->completed]);

        return to_route('tasks.index')->with(
            'success', $task->completed ? 'Tarefa concluída com sucesso.' : 'Tarefa reaberta com sucesso.',
        );
    }

    public function destroy(Task $task): RedirectResponse
    {
        app(RequestTrace::class)->record('controller', __FUNCTION__, 'A ação destroy coordena a exclusão da tarefa.');
        $id = $task->id;
        $this->taskService->deleteTask($task);
        Log::info('Task deleted', ['task_id' => $id]);

        return to_route('tasks.index')->with('success', 'Tarefa excluída com sucesso.');
    }
}
