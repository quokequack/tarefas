# Laravel Task Manager

Prova de conceito para a disciplina de **Análise e Projeto de Sistemas de Informação do IFAL**. O projeto implementa um gerenciador de tarefas e torna visíveis o MVC com uma camada Service, o fluxo HTTP do Laravel e três mecanismos: **Dependency Injection / IoC**, **Active Record** e **Proxy por meio das Facades**.

A aplicação usa Blade no servidor, SQLite e CSS próprio. Não é necessário instalar Node ou compilar assets para executá-la. Os documentos de entrada `spec.md` e `roteiro-apresentacao.md` foram preservados.

## Tecnologias e requisitos

Versões verificadas durante a implementação:

| Componente | Versão |
| --- | --- |
| PHP | 8.5.8 |
| Laravel, Eloquent e Blade | 13.44.1 |
| SQLite, biblioteca do PHP | 3.53.4 |
| Composer | 2.10.2 |
| PHPUnit | 12.5.45 |
| Laravel Pint | 1.32.3 |

Requisitos: **PHP 8.3 ou superior**, Composer 2 e extensões exigidas pelo Laravel, incluindo PDO SQLite, SQLite3, mbstring, DOM/XML, ctype, curl, fileinfo, filter, hash, openssl, pcre, session e tokenizer. Verifique o ambiente com `composer check-platform-reqs`.

O Composer está configurado com `config.platform.php=8.3.0` para selecionar dependências compatíveis com o mínimo especificado. A execução e os testes foram realizados no PHP 8.5.8 disponível neste ambiente, sem execução adicional em PHP 8.3. O `composer.lock` fixa as versões reproduzíveis.

Laravel Boost 2.10.4 foi instalado como dependência de desenvolvimento por exigência do `AGENTS.md` fornecido pelo esqueleto oficial. Não é utilizado pelo fluxo de tarefas.

A injeção do logger de navegador do Boost está desativada por `BOOST_BROWSER_LOGS_WATCHER=false`. A confirmação de exclusão e o painel de fluxo utilizam JavaScript próprio, sem bibliotecas adicionais.

## Executar neste diretório

O ambiente local já foi configurado, com chave gerada, migrations executadas e cinco tarefas de demonstração:

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

Abra **http://127.0.0.1:8000/tasks**. A raiz `/` redireciona para a listagem. Outra opção é `composer run dev`.

Se a porta 8000 estiver ocupada, use `php artisan serve --host=127.0.0.1 --port=8765` e abra http://127.0.0.1:8765/tasks. A verificação HTTP desta implementação foi realizada nessa porta alternativa.

## Instalar em outro ambiente

Na raiz do projeto:

```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

O arquivo `.env.example` já configura SQLite, cache e sessões em arquivos, fila síncrona, logs locais, idioma `pt_BR` e fuso `America/Maceio`. Não há serviços externos para configurar. Sem `DB_DATABASE`, o Laravel utiliza `database/database.sqlite`.

Para uma instalação inicial automatizada, use `composer run setup`, que instala dependências, cria `.env` se necessário, gera a chave, prepara o SQLite e executa migrations e seeders. A geração de chave faz esse comando ser destinado à configuração inicial; use `composer install` para atualizar uma instalação existente.

`.env`, o banco SQLite, os logs e `vendor/` são ignorados pelo Git. Não publique a chave local.

## Migrations e dados de demonstração

```sh
php artisan migrate
php artisan db:seed
php artisan migrate:status
```

O seeder usa `TaskFactory` e títulos fixos. Em um banco limpo são criadas cinco tarefas: duas concluídas e três pendentes. Executá-lo novamente não duplica os exemplos cujos títulos continuam iguais nem redefine seus estados. O cache do total é invalidado ao final.

Para restaurar exatamente o cenário inicial da apresentação, **apagando todas as tarefas atuais**:

```sh
php artisan migrate:fresh --seed
```

Somente a tabela de domínio `tasks` é criada, além da tabela `migrations` administrada pelo Laravel. Cache e sessões ficam em `storage/framework/`.

## Testes e verificações

```sh
php artisan test
# Somente os testes de gerenciamento:
php artisan test --compact tests/Feature/TaskManagementTest.php
# Alternativas e verificações:
composer test
composer validate --strict
composer check-platform-reqs
php artisan route:list --path=tasks
```

Os **50 casos de teste, com 276 verificações**, cobrem listagem e ordem, cadastro pendente, limites e tipos inválidos do título, edição, conclusão e reabertura, exclusão, filtros, estado vazio, 404, mensagens flash, escape de HTML, rejeição de escritas sem token no cenário testado, logs, duração e invalidação do cache e repetição do seeder. Incluem 16 testes do painel: captura de etapas e SQL, redirecionamentos separados, validação interrompida, cache hit/miss, isolamento por sessão, expiração, limite do histórico, leitura restrita de fontes e preservação das mensagens flash durante o polling.

A suíte usa PHPUnit, `RefreshDatabase`, SQLite em memória, cache e sessões em memória. O banco da apresentação permanece separado. O teste de CSRF remove explicitamente o desvio que o framework aplica ao ambiente de testes.

Também foram verificadas respostas HTTP reais: redirecionamento da raiz (302), listagem, filtros, edição e assets (200), tarefa inexistente (404) e cadastro sem token CSRF (419). O painel foi exercitado no Firefox 160 em duas abas: chegada automática de requisições, código-fonte, SQL, pausa, avanço, repetição e layouts de 1440 e 390 pixels, sem erros de JavaScript.

Para formatar o PHP:

```sh
vendor/bin/pint
```

## Estrutura principal

```text
app/
├── Http/
│   ├── Controllers/TaskController.php
│   ├── Controllers/RequestFlowController.php
│   ├── Middleware/TraceTaskRequest.php
│   └── Requests/TaskRequest.php
├── Models/Task.php
├── Providers/AppServiceProvider.php
└── Services/
    ├── TaskService.php
    └── RequestTrace.php
bootstrap/
├── app.php
└── providers.php
database/
├── factories/TaskFactory.php
├── migrations/2026_11_25_164538_create_tasks_table.php
└── seeders/DatabaseSeeder.php
docs/
└── APRESENTACAO.md
public/
├── css/ (app.css e flow.css)
├── js/ (app.js e flow.js)
└── index.php
resources/views/
├── layouts/app.blade.php
├── flow/ (index.blade.php e tree.blade.php)
└── tasks/
    ├── index.blade.php
    └── edit.blade.php
routes/web.php
tests/Feature/TaskManagementTest.php
tests/Feature/RequestFlowTest.php
```

## Arquitetura e padrões de projeto

**Controller:** recebe HTTP, utiliza `TaskRequest`, coordena o serviço, registra logs e devolve Views ou redirecionamentos. Não contém consultas Eloquent.

**Service:** concentra `listTasks`, `createTask`, `findTask`, `updateTask`, `toggleTask` e `deleteTask`. O método adicional `totalTasks` demonstra cache do contador. Não há Repository: o Eloquent já oferece o mecanismo de persistência necessário para esta prova de conceito.

**Model:** `Task` estende o Model do Eloquent, define os atributos permitidos em `$fillable` e converte `completed` para booleano. A migration define o estado inicial como pendente.

**View:** Blade renderiza HTML com `{{ }}` para escapar dados. Formulários usam `@csrf` e `@method` quando necessário. O CRUD mantém requisições convencionais e renderização no servidor; o painel de fluxo usa JavaScript puro para acompanhar e reproduzir as etapas.

1. **Dependency Injection / IoC:** o construtor de `TaskController` declara `TaskService`. O Service Container o resolve automaticamente, pois é uma classe concreta sem dependências especiais. `AppServiceProvider` não precisa registrar um binding. DI é o mecanismo de fornecimento da dependência; IoC descreve o controle entregue ao framework.
2. **Active Record:** em `TaskService`, `Task::query()`, `Task::create()`, `$task->save()` e `$task->delete()` mostram que o modelo representa os dados e oferece operações de persistência. Filtros usam `where('completed', ...)` antes de `get()`.
3. **Proxy com Facades:** `Log` e `Cache` encaminham chamadas aos serviços administrados pelo container. O método `__callStatic` de `vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php` permite inspecionar esse encaminhamento. O nome Facade no Laravel não implica uma correspondência direta com o padrão Facade do catálogo GoF; aqui o foco é o mecanismo de proxy estático.

O total global é armazenado em `tasks.total` por **60 segundos**, inclusive quando a lista está filtrada. Cadastro e exclusão pelo Service invalidam essa chave. Alterações diretas no SQLite ou pelo Model fora do Service podem aparecer no contador somente após a expiração; a listagem não é cacheada. A escala prevista é uma demonstração local, com todas as tarefas carregadas e sem paginação.

## Rotas

| Método | URI | Nome |
| --- | --- | --- |
| GET | /tasks | tasks.index |
| POST | /tasks | tasks.store |
| GET | /tasks/{task}/edit | tasks.edit |
| PUT | /tasks/{task} | tasks.update |
| PATCH | /tasks/{task}/toggle | tasks.toggle |
| DELETE | /tasks/{task} | tasks.destroy |

Filtros: `/tasks`, `/tasks?status=pending` e `/tasks?status=completed`. Valores inválidos de status são rejeitados pela validação. As tarefas são ordenadas por `created_at DESC, id DESC`.

Route Model Binding fornece `Task $task` nas ações de registros existentes e retorna 404 quando necessário. Por isso o Controller não precisa chamar `findTask`; esse método permanece disponível no Service conforme a especificação.

## Fluxo de GET /tasks

1. O servidor encaminha a requisição para `public/index.php`, que carrega o autoload do Composer e `bootstrap/app.php`.
2. O framework inicializa providers e processa os middlewares globais.
3. O Router identifica a rota em `routes/web.php` e aplica os middlewares da rota/grupo `web`, incluindo sessão. A proteção contra falsificação de requisição se aplica às escritas.
4. O container fornece o Controller e sua dependência `TaskService`.
5. `index()` valida o filtro, registra o início e chama `listTasks()`.
6. O Eloquent consulta o SQLite e hidrata os modelos. O Controller registra a quantidade retornada. `totalTasks()` obtém o total do cache ou executa `Task::count()`.
7. O Controller devolve `tasks.index`; Blade renderiza os dados no layout.
8. A resposta HTML atravessa a saída do pipeline de middleware e chega ao navegador.

```mermaid
flowchart TD
    A["Navegador: GET /tasks"] --> B["public/index.php"]
    B --> C["bootstrap/app.php e providers"]
    C --> D["Middlewares globais"]
    D --> E["Router: routes/web.php"]
    E --> F["Middlewares web: sessão e binding"]
    F --> G["Service Container: resolução do Controller e TaskService"]
    G --> H["TaskController::index"]
    H --> I["TaskService::listTasks"]
    I --> J["Task / Eloquent"]
    J --> K[("SQLite: tasks")]
    K --> J
    J --> I
    I --> H
    H --> L["TaskService::totalTasks"]
    L --> M["Cache::remember: tasks.total"]
    M --> N["Cache em arquivo / COUNT em cache miss"]
    N --> L
    L --> H
    H --> O["Blade: tasks.index e layouts.app"]
    O --> P["Resposta HTML e middlewares de saída"]
    P --> Q["Navegador"]
```

## Acompanhar a execução

### Painel visual em duas abas

1. Abra **`/fluxo`**, disponível na navegação como **Fluxo da requisição**.
2. Clique em **Abrir tarefas em outra aba** e mantenha ambas no mesmo navegador, na mesma origem (mesmo host e porta).
3. Crie, edite, conclua, reabra ou exclua uma tarefa. O painel consulta as novas requisições a cada segundo e destaca os arquivos percorridos. Ações de escrita têm prioridade sobre o GET de redirecionamento.
4. Use **Pausar**, **Próxima etapa**, **Repetir** e a seleção de ritmo. Clique em uma etapa para inspecioná-la ou em um arquivo para ler seu código real. A exploração manual desmarca **Acompanhar novas requisições**, preservando a etapa em análise.
5. Selecione requisições anteriores no histórico para comparar, por exemplo, a criação com a listagem ou um cache miss com um cache hit.

O painel exibe método, URL, rota, status, tempo observado, trechos reais de classes e métodos, parâmetros SQL e os três padrões. Captura até 20 requisições por sessão, com retenção de uma hora desde a última gravação. `/fluxo`, suas consultas periódicas e os assets não geram percursos. `php artisan cache:clear` remove o histórico e o contador em cache.

**Como funciona:** `TraceTaskRequest` inicia e finaliza o registro no pipeline global. `RequestTrace` mantém as etapas e o histórico em cache. Chamadas didáticas no Controller, no Service e na Form Request marcam a execução; listeners em `AppServiceProvider` acompanham `RouteMatched`, `QueryExecuted`, cache, logs, resolução do Controller e composição de Views. A leitura periódica usa apenas o cookie de sessão descriptografado, sem iniciar ou salvar a sessão, para não consumir mensagens flash de outra aba.

**Limite didático:** a animação reproduz os marcos capturados depois que a resposta termina; não é um debugger nem registra todas as chamadas internas. Entrada e bootstrap, anteriores ao middleware de captura, aparecem identificados como ilustrativos. O tempo observado começa nesse middleware e não representa uma medição completa de desempenho. Um POST/PUT/PATCH/DELETE com resposta 302 e o GET seguinte aparecem como requisições distintas. Um erro de validação não inventa uma passagem pelo Service ou pelo banco.

O código é lido somente de uma lista fixa de arquivos relevantes do projeto e do framework; caminhos arbitrários e `.env` não são aceitos. O SQLite é mostrado como nó de dados com SQL, sem tentar renderizar seu conteúdo binário.

### Logs no terminal

```sh
tail -f storage/logs/laravel.log
```

Ao abrir `/tasks`, os eventos `Iniciando consulta de tarefas` e `Consulta concluída` mostram o filtro e a quantidade. As escritas geram `Task created`, `Task updated`, `Task status changed` e `Task deleted` com o identificador da tarefa.

Para inspecionar os dados persistidos, se o utilitário SQLite estiver instalado:

```sh
sqlite3 -header -column database/database.sqlite "SELECT id, title, completed, created_at FROM tasks ORDER BY created_at DESC, id DESC;"
```

O roteiro de aproximadamente quatro minutos, com arquivos, ações e falas, está em [docs/APRESENTACAO.md](docs/APRESENTACAO.md).

## Referências oficiais

Documentação da versão 13 utilizada na implementação:

- [Instalação](https://laravel.com/docs/13.x/installation)
- [Ciclo da requisição](https://laravel.com/docs/13.x/lifecycle)
- [Service Container e resolução automática](https://laravel.com/docs/13.x/container)
- [Eloquent ORM](https://laravel.com/docs/13.x/eloquent)
- [Facades e proxies estáticos](https://laravel.com/docs/13.x/facades)
- [Rotas e Model Binding](https://laravel.com/docs/13.x/routing)
- [Validação e Form Requests](https://laravel.com/docs/13.x/validation)
- [Testes HTTP](https://laravel.com/docs/13.x/http-tests)

O projeto é destinado à apresentação local, sem autenticação. O servidor Artisan e o modo debug do `.env.example` são configurações de desenvolvimento.
