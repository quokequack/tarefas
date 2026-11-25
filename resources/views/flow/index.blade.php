@extends('layouts.app')

@section('title', 'Fluxo da requisição')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/flow.css') }}">
    <script src="{{ asset('js/flow.js') }}" defer></script>
@endpush

@section('content')
    <div id="request-flow" data-events-url="{{ route('flow.events') }}" data-source-url="{{ route('flow.source', ['file' => '__FILE__']) }}">
        <script type="application/json" id="flow-files">@json($files)</script>
        <section class="flow-intro" aria-labelledby="flow-title">
            <div>
                <p class="eyebrow">Demonstração do ciclo HTTP</p>
                <h1 id="flow-title">Por dentro da requisição.</h1>
                <p class="intro-description">Abra as tarefas em outra aba. Cada ação revela o caminho pelos arquivos, as classes e o SQL executado.</p>
            </div>
            <a class="button button-primary" href="{{ route('tasks.index') }}" target="_blank" rel="noopener">Abrir tarefas em outra aba</a>
        </section>

        <noscript><p class="flash">Ative o JavaScript para acompanhar e animar as requisições neste painel.</p></noscript>

        <div class="flow-toolbar">
            <div class="flow-request-select">
                <label for="request-select">Requisições recentes</label>
                <select id="request-select" disabled><option>Aguardando a primeira requisição…</option></select>
            </div>
            <div class="playback-controls" aria-label="Controles da animação">
                <button class="button button-small" type="button" id="play-pause" disabled>Reproduzir</button>
                <button class="button button-small" type="button" id="next-step" disabled>Próxima etapa</button>
                <button class="button button-small" type="button" id="replay" disabled>Repetir</button>
                <div class="speed-field">
                    <label for="playback-speed">Ritmo por etapa</label>
                    <select id="playback-speed">
                        <option value="1600">Lento · 1,6 s</option>
                        <option value="900" selected>Normal · 0,9 s</option>
                        <option value="350">Rápido · 0,35 s</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flow-statusbar">
            <p id="connection-status" role="status">Conectando ao painel…</p>
            <label class="follow-label"><input type="checkbox" id="follow-requests" checked> Acompanhar novas requisições</label>
        </div>

        <div class="flow-grid">
            <aside class="file-panel" aria-labelledby="tree-title">
                <div class="panel-heading"><h2 id="tree-title">Arquivos do projeto</h2><span id="tree-progress">0 visitados</span></div>
                <div class="file-tree" id="file-tree">
                    @include('flow.tree', ['nodes' => $tree])
                </div>
                <div class="tree-legend"><span class="legend-current">Etapa atual</span><span class="legend-visited">Já visitado</span></div>
            </aside>

            <section class="execution-panel" aria-labelledby="stage-title">
                <div class="request-summary" id="request-summary">
                    <span class="http-method" id="http-method">HTTP</span>
                    <span id="request-url">Aguardando uma ação nas tarefas</span>
                    <span id="response-status"></span>
                </div>
                <div class="stage-summary" aria-live="polite">
                    <div class="stage-topline"><span id="stage-number">Nenhuma etapa selecionada</span><span id="pattern-label" class="pattern-label" hidden></span></div>
                    <h2 id="stage-title">Uma requisição, vários arquivos.</h2>
                    <p id="stage-description">Crie, edite, conclua ou exclua uma tarefa. O percurso aparecerá automaticamente aqui.</p>
                    <span class="stage-kind" id="stage-kind">Você também pode explorar os arquivos pela árvore.</span>
                </div>
                <dl class="stage-details" id="stage-details" hidden></dl>

                <div class="source-heading"><h3 id="source-path">Código-fonte real</h3><span id="source-location"></span></div>
                <pre class="source-code" id="source-code" tabindex="0" aria-label="Trecho do arquivo selecionado"><code>Selecione um arquivo ou aguarde uma requisição.</code></pre>
            </section>
        </div>

        <section class="timeline-panel" aria-labelledby="timeline-title">
            <div class="panel-heading"><h2 id="timeline-title">Percurso da requisição</h2><span id="request-duration"></span></div>
            <ol class="flow-timeline" id="flow-timeline"><li class="timeline-empty">As etapas instrumentadas serão exibidas na ordem em que foram registradas.</li></ol>
        </section>

        <p class="flow-note">Requisições e consultas são reais; a animação é uma reprodução didática após a resposta. Entrada e bootstrap são etapas ilustrativas. O painel guarda até 20 requisições desta sessão por uma hora.</p>
    </div>
@endsection
