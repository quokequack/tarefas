<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gerenciador de tarefas para demonstração acadêmica do framework Laravel no IFAL.">
    <title>@yield('title', 'Tarefas') · Laravel Task Manager</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
    <div class="page">
        <header class="site-header">
            <a class="brand" href="{{ route('tasks.index') }}">Laravel Task Manager<span class="brand-mark" aria-hidden="true"></span></a>
            <nav class="site-nav" aria-label="Navegação principal">
                <a href="{{ route('tasks.index') }}" @if (request()->routeIs('tasks.*')) aria-current="page" @endif>Tarefas</a>
                <a href="{{ route('flow.index') }}" @if (request()->routeIs('flow.*')) aria-current="page" @endif>Fluxo da requisição</a>
            </nav>
            <span class="institution">IFAL · Demonstração acadêmica</span>
        </header>

        <main id="conteudo">
            @if (session('success'))
                <div class="flash" role="status">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>

        <footer class="site-footer">
            <span>Análise e Projeto de Sistemas de Informação</span>
            <span>Laravel · {{ ['sqlite' => 'SQLite', 'pgsql' => 'PostgreSQL'][config('database.default')] ?? config('database.default') }} · Blade</span>
        </footer>
    </div>
</body>
</html>
