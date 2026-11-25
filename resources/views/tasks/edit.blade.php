@extends('layouts.app')

@section('title', 'Editar tarefa')

@section('content')
    <section class="edit-section" aria-labelledby="edit-title">
        <a class="back-link" href="{{ route('tasks.index') }}">Voltar para as tarefas</a>
        <p class="eyebrow">Tarefa #{{ $task->id }}</p>
        <h1 id="edit-title">Editar tarefa.</h1>
        <p class="intro-description">Ajuste o título e continue de onde parou.</p>

        <form action="{{ route('tasks.update', $task) }}" method="POST" class="edit-form">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="title">Título da tarefa</label>
                <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}" required maxlength="255" autofocus
                       @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                @error('title')
                    <p class="field-error" id="title-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div class="edit-actions">
                <button class="button button-primary" type="submit">Salvar alterações</button>
                <a class="button" href="{{ route('tasks.index') }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
