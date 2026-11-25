@extends('layouts.app')

@section('content')
    <section class="intro" aria-labelledby="page-title">
        <div>
            <p class="eyebrow">Gerenciamento de tarefas</p>
            <h1 id="page-title">Uma tarefa<br>de cada vez.</h1>
            <p class="intro-description">Cadastre, organize e acompanhe suas tarefas.<br>Um projeto para explorar o Laravel na prática.</p>
        </div>
        <div class="total">
            <span class="total-number">{{ $total }}</span>
            <span>{{ $total === 1 ? 'tarefa no total' : 'tarefas no total' }}</span>
        </div>
    </section>

    <section class="create-section" aria-labelledby="create-title">
        <div>
            <h2 id="create-title">Nova tarefa</h2>
            <p>O próximo passo começa aqui.</p>
        </div>
        <form action="{{ route('tasks.store') }}" method="POST" class="create-form">
            @csrf
            <div class="field">
                <label for="title">Título da tarefa</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="255"
                       placeholder="O que você precisa fazer?" @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                @error('title')
                    <p class="field-error" id="title-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <button class="button button-primary" type="submit">Adicionar tarefa</button>
        </form>
    </section>

    <section class="task-section" aria-labelledby="list-title">
        <div class="list-toolbar">
            <h2 id="list-title">Suas tarefas <span class="result-count">{{ $tasks->count() }}</span></h2>
            <nav class="filters" aria-label="Filtrar tarefas por estado">
                <a href="{{ route('tasks.index') }}" @if ($status === null) aria-current="page" @endif>Todas</a>
                <a href="{{ route('tasks.index', ['status' => 'pending']) }}" @if ($status === 'pending') aria-current="page" @endif>Pendentes</a>
                <a href="{{ route('tasks.index', ['status' => 'completed']) }}" @if ($status === 'completed') aria-current="page" @endif>Concluídas</a>
            </nav>
        </div>
        @error('status')
            <p class="field-error" role="alert">Filtro inválido. Selecione Todas, Pendentes ou Concluídas.</p>
        @enderror

        @if ($tasks->isEmpty())
            <div class="empty-state">
                <h3>{{ $status === null ? 'Nenhuma tarefa por aqui.' : 'Nenhuma tarefa neste filtro.' }}</h3>
                <p>{{ $status === null ? 'Use o formulário acima para cadastrar sua primeira tarefa.' : 'Escolha outro filtro ou adicione uma nova tarefa.' }}</p>
            </div>
        @else
            <ol class="task-list" aria-label="Tarefas da mais recente para a mais antiga">
                @foreach ($tasks as $task)
                    <li class="task-row {{ $task->completed ? 'is-completed' : '' }}">
                        <span class="task-id" aria-label="Identificador">#{{ $task->id }}</span>
                        <div class="task-content">
                            <h3>{{ $task->title }}</h3>
                            <div class="task-meta">
                                <span class="status {{ $task->completed ? 'status-completed' : '' }}">{{ $task->completed ? 'Concluída' : 'Pendente' }}</span>
                                <span>Criada em <time datetime="{{ $task->created_at->toIso8601String() }}">{{ $task->created_at->format('d/m/Y · H:i') }}</time></span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <form action="{{ route('tasks.toggle', $task) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button class="button button-small" type="submit" aria-label="{{ $task->completed ? 'Reabrir' : 'Concluir' }} tarefa: {{ $task->title }}">{{ $task->completed ? 'Reabrir' : 'Concluir' }}</button>
                            </form>
                            <a class="action-link" href="{{ route('tasks.edit', $task) }}" aria-label="Editar tarefa: {{ $task->title }}">Editar</a>
                            <form action="{{ route('tasks.destroy', $task) }}" method="POST" data-confirm-delete>
                                @csrf
                                @method('DELETE')
                                <button class="action-link action-delete" type="submit" aria-label="Excluir tarefa: {{ $task->title }}">Excluir</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
        <p class="list-note">Da mais recente para a mais antiga.</p>
    </section>
@endsection
