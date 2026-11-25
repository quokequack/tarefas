(() => {
    const root = document.getElementById('request-flow');
    if (!root) return;

    const files = JSON.parse(document.getElementById('flow-files').textContent);
    const get = (id) => document.getElementById(id);
    const select = get('request-select');
    const follow = get('follow-requests');
    const playButton = get('play-pause');
    const nextButton = get('next-step');
    const replayButton = get('replay');
    const speed = get('playback-speed');
    const timeline = get('flow-timeline');
    const code = get('source-code');
    const sourceCache = new Map();
    const known = new Set();
    const treeButtons = [...root.querySelectorAll('[data-file]')];
    let requests = [];
    let pending = [];
    let current = null;
    let stepIndex = -1;
    let playing = false;
    let animationTimer = null;
    let sourceVersion = 0;
    let initialized = false;
    let historySignature = '';

    const text = (id, value) => { get(id).textContent = value; };
    const basename = (path) => path.split('/').pop();
    const symbol = (step) => {
        const name = basename(files[step.file].path);
        const owner = ({ entry: 'Application', bootstrap: 'ApplicationBuilder' })[step.file] || name.replace(/\.php$/, '');
        return step.method ? `${owner}::${step.method}()` : name;
    };

    function reveal(container, element, horizontal = false) {
        const box = container.getBoundingClientRect();
        const item = element.getBoundingClientRect();
        if (horizontal) {
            if (item.left < box.left) container.scrollLeft -= box.left - item.left + 16;
            if (item.right > box.right) container.scrollLeft += item.right - box.right + 16;
        } else {
            if (item.top < box.top) container.scrollTop -= box.top - item.top + 16;
            if (item.bottom > box.bottom) container.scrollTop += item.bottom - box.bottom + 16;
        }
    }

    function highlightFile(file) {
        treeButtons.forEach((button) => {
            const active = button.dataset.file === file;
            button.classList.toggle('is-current', active);
            if (active) {
                let ancestor = button.parentElement;
                while (ancestor && ancestor !== root) {
                    if (ancestor.tagName === 'DETAILS') ancestor.open = true;
                    ancestor = ancestor.parentElement;
                }
                reveal(get('file-tree'), button);
            }
        });
    }

    async function openSource(file, method = null) {
        const version = ++sourceVersion;
        text('source-path', files[file].path);
        text('source-location', '');
        code.replaceChildren(document.createElement('code'));

        if (files[file].binary) {
            code.firstChild.textContent = 'SQLite é um arquivo binário. A consulta executada e seus parâmetros aparecem nos detalhes desta etapa.';
            return;
        }

        code.firstChild.textContent = 'Carregando o arquivo…';
        const key = `${file}:${method || ''}`;
        try {
            if (!sourceCache.has(key)) {
                const url = new URL(root.dataset.sourceUrl.replace('__FILE__', encodeURIComponent(file)), location.origin);
                if (method) url.searchParams.set('method', method);
                const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
                if (!response.ok) throw new Error('source');
                sourceCache.set(key, await response.json());
            }
            if (version !== sourceVersion) return;
            const source = sourceCache.get(key);
            const content = document.createElement('code');
            source.code.split('\n').forEach((line, index) => {
                const row = document.createElement('span');
                const number = document.createElement('span');
                const lineNumber = source.start_line + index;
                row.className = 'source-line';
                row.classList.toggle('is-highlighted', lineNumber === source.highlight_line);
                number.className = 'line-number';
                number.textContent = lineNumber;
                number.setAttribute('aria-hidden', 'true');
                row.append(number, document.createTextNode(line || ' '));
                content.append(row);
            });
            code.replaceChildren(content);
            code.scrollTop = 0;
            code.scrollLeft = 0;
            text('source-location', source.highlight_line ? `Linha ${source.highlight_line}` : `A partir da linha ${source.start_line}`);
        } catch {
            if (version === sourceVersion) code.firstChild.textContent = 'Não foi possível ler o arquivo. Selecione-o novamente para tentar de novo.';
        }
    }

    function showStep(index) {
        if (!current || !current.steps[index]) return;
        stepIndex = index;
        const step = current.steps[index];
        const visited = new Set(current.steps.slice(0, index + 1).map((item) => item.file));
        treeButtons.forEach((button) => button.classList.toggle('is-visited', visited.has(button.dataset.file)));
        highlightFile(step.file);
        text('tree-progress', `${visited.size} ${visited.size === 1 ? 'visitado' : 'visitados'}`);
        text('stage-number', `Etapa ${index + 1} de ${current.steps.length}`);
        text('stage-title', symbol(step));
        text('stage-description', step.description);
        text('stage-kind', step.kind === 'illustrated' ? 'Etapa ilustrativa do ciclo HTTP' : 'Execução observada na requisição');
        const pattern = get('pattern-label');
        pattern.hidden = !step.pattern;
        pattern.textContent = step.pattern || '';
        const details = get('stage-details');
        details.replaceChildren();
        const entries = Object.entries(step.details);
        details.hidden = entries.length === 0;
        entries.forEach(([label, value]) => {
            const term = document.createElement('dt');
            const definition = document.createElement('dd');
            term.textContent = label;
            definition.textContent = typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value);
            details.append(term, definition);
        });
        timeline.querySelectorAll('button').forEach((button, position) => {
            if (position === index) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
        const active = timeline.querySelector('[aria-current]');
        if (active) reveal(timeline, active, true);
        openSource(step.file, step.method);
        nextButton.disabled = index >= current.steps.length - 1;
    }

    function pause(manual = false) {
        playing = false;
        clearTimeout(animationTimer);
        playButton.textContent = 'Reproduzir';
        if (manual) follow.checked = false;
    }

    function scheduleNext() {
        animationTimer = setTimeout(() => {
            if (!playing || !current) return;
            if (stepIndex < current.steps.length - 1) {
                showStep(stepIndex + 1);
                scheduleNext();
            } else {
                pause();
                if (follow.checked && pending.length) animationTimer = setTimeout(consumePending, 1000);
            }
        }, Number(speed.value));
    }

    function play() {
        if (!current) return;
        clearTimeout(animationTimer);
        if (stepIndex >= current.steps.length - 1) showStep(0);
        playing = true;
        playButton.textContent = 'Pausar';
        scheduleNext();
    }

    function choose(request, autoplay = true) {
        pause();
        current = request;
        select.value = request.id;
        pending = pending.filter((item) => item.id !== request.id);
        text('http-method', request.method);
        text('request-url', request.url);
        text('response-status', `HTTP ${request.status}`);
        text('request-duration', `${request.time} · ${request.duration_ms} ms observados`);
        timeline.replaceChildren();
        request.steps.forEach((step, index) => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            const count = document.createElement('span');
            const name = document.createElement('strong');
            button.type = 'button';
            count.textContent = String(index + 1).padStart(2, '0');
            name.textContent = symbol(step);
            button.append(count, name);
            button.addEventListener('click', () => { pause(true); showStep(index); });
            item.append(button);
            timeline.append(item);
        });
        playButton.disabled = false;
        replayButton.disabled = false;
        showStep(0);
        if (autoplay) play();
    }

    function consumePending() {
        if (!follow.checked || !pending.length) return;
        const write = pending.findIndex((request) => request.method !== 'GET');
        const index = write === -1 ? pending.length - 1 : write;
        const candidate = pending[index];
        pending.splice(index, 1);
        choose(candidate);
    }

    async function poll() {
        let retry = 1000;
        try {
            const response = await fetch(root.dataset.eventsUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('events');
            const data = await response.json();
            requests = data.requests;
            const incoming = requests.filter((request) => !known.has(request.id)).reverse();
            incoming.forEach((request) => known.add(request.id));
            if (known.size > 200) {
                known.clear();
                requests.forEach((request) => known.add(request.id));
            }

            const signature = requests.map((request) => request.id).join(',');
            if (requests.length && signature !== historySignature) {
                historySignature = signature;
                const selected = current?.id;
                const options = requests.map((request) => {
                    const option = document.createElement('option');
                    option.value = request.id;
                    option.textContent = `${request.time} · ${request.method} ${request.url} · ${request.status}`;
                    return option;
                });
                if (current && !requests.some((request) => request.id === current.id)) {
                    const option = document.createElement('option');
                    option.value = current.id;
                    option.textContent = `${current.time} · ${current.method} ${current.url} · em reprodução`;
                    options.push(option);
                }
                select.replaceChildren(...options);
                select.disabled = false;
                if (selected) select.value = selected;
            }

            if (!initialized) {
                initialized = true;
                if (requests.length) choose(requests[0], !matchMedia('(prefers-reduced-motion: reduce)').matches);
            } else if (incoming.length) {
                pending.push(...incoming);
                pending = pending.slice(-20);
                if (follow.checked && (!playing || (current?.method === 'GET' && incoming.some((request) => request.method !== 'GET')))) consumePending();
            }
            text('connection-status', requests.length ? `Conectado · ${requests.length} ${requests.length === 1 ? 'requisição disponível' : 'requisições disponíveis'}${pending.length ? ` · ${pending.length} ${pending.length === 1 ? 'nova para acompanhar' : 'novas para acompanhar'}` : ''}` : 'Conectado · aguardando uma ação nas tarefas');
        } catch {
            text('connection-status', 'Conexão interrompida. Tentando reconectar…');
            retry = 3000;
        }
        setTimeout(poll, retry);
    }

    select.addEventListener('change', () => {
        const request = requests.find((item) => item.id === select.value);
        if (request) { follow.checked = false; choose(request, false); }
    });
    playButton.addEventListener('click', () => playing ? pause(true) : play());
    nextButton.addEventListener('click', () => { pause(true); showStep(stepIndex + 1); });
    replayButton.addEventListener('click', () => { follow.checked = false; showStep(0); play(); });
    speed.addEventListener('change', () => { if (playing) { clearTimeout(animationTimer); scheduleNext(); } });
    follow.addEventListener('change', () => { if (follow.checked) consumePending(); });
    treeButtons.forEach((button) => button.addEventListener('click', () => {
        pause(true);
        highlightFile(button.dataset.file);
        openSource(button.dataset.file);
    }));
    poll();
})();
