## **Bloco 1 — Identidade e proposta (3 minutos)**

**Contextualização histórica**

O Laravel foi desenvolvido por Taylor Otwell e lançado em 2011. Sua criação ocorreu em um momento em que o desenvolvimento PHP já contava com frameworks como CodeIgniter, CakePHP e Symfony, mas ainda havia demanda por ferramentas com uma experiência de desenvolvimento mais integrada e expressiva.

O Laravel inicialmente se destacou por oferecer uma abordagem mais elegante para tarefas comuns, como autenticação, organização das rotas e construção de aplicações. Com sua evolução, passou a incorporar ORM, filas, eventos, cache, ferramentas de testes e recursos para aplicações modernas.

Atualmente, o framework é mantido pelo projeto Laravel, sob a liderança de Otwell, com participação de uma comunidade de desenvolvedores e empresas do ecossistema.

A versão principal atual é o **Laravel 13**, que exige PHP 8.3 ou superior e amplia recursos como desenvolvimento de aplicações com IA, APIs JSON e busca vetorial.

**Qual problema o Laravel resolve? (1 minuto)**

Antes de apresentar o MVC, vale explicar por que precisamos de frameworks.

Em aplicações desenvolvidas apenas com PHP, o programador precisa organizar manualmente diversas responsabilidades. Conforme o sistema cresce, misturar acesso a dados, validações, regras de negócio e HTML em um único arquivo prejudica sua manutenção.

**PHP sem framework**

```php

<?php
$db = new PDO($dsn, $user, $pass);

$stmt = $db->query(
    "SELECT * FROM tasks"
);

$tasks = $stmt->fetchAll();
```

Exemplo de consulta com PDO. É possível organizar bem PHP puro, mas a infraestrutura fica sob responsabilidade da equipe.

**PHP com Laravel**

```php

<?php

$tasks = Task::all();

return view('tasks.index', [
    'tasks' => $tasks
]);
```

O Laravel oferece uma interface de alto nível para consulta e renderização.

Essa comparação evidencia três contribuições:

- **Abstração:** esconde detalhes repetitivos de implementação.
- **Padronização:** estabelece convenções para organizar responsabilidades.
- **Produtividade:** disponibiliza funcionalidades comuns sem exigir que cada equipe as desenvolva do zero.

É importante mencionar que PHP puro não é necessariamente desorganizado, e Laravel não elimina a complexidade. Ele oferece ferramentas para administrá-la.

**Paradigma e arquitetura (1 minuto)**

Laravel é predominantemente orientado a objetos e oferece convenções arquiteturais inspiradas em MVC.

!Captura de Tela 2026-11-22 às 21.45.50.png

| Camada | Responsabilidade | Exemplo |  |
| --- | --- | --- | --- |
| Model | Representação e manipulação dos dados | `Task.php` |  |
| View | Renderização da interface | `index.blade.php` |  |
| Controller | Recebimento e coordenação das requisições | `TaskController.php` |  |

**O MVC não é toda a arquitetura do Laravel.** O framework também emprega Service Container, Service Providers, Middleware, Events, Jobs, Commands e outras abstrações.

Para aplicações maiores, podem ser acrescentadas camadas de Service, Repository ou casos de uso, dependendo das necessidades. Essas camadas não são obrigatórias.

**Laravel é opinativo ou flexível?**

É um framework com convenções fortes, mas que permite extensões e alterações arquiteturais. Por exemplo, presume que os Models fiquem em `app/Models`, mas não impede outra organização.

Um bom conceito para incluir na fala é *convention over configuration*: ao seguir convenções comuns, reduzimos configurações explícitas e decisões repetitivas.

## **Bloco 2 — Características técnicas e ecossistema (4 minutos)**

**O que o Laravel oferece na caixa? (1min30)**

Em vez de apenas listar funcionalidades, expliquem os problemas resolvidos por cada uma.

| Tecnologia | Papel na aplicação | Problema resolvido |
| --- | --- | --- |
| Router | Associar requisições HTTP a ações | Organização dos endpoints |
| Service Container | Resolver classes e dependências | Redução do acoplamento |
| Eloquent ORM | Mapear tabelas para modelos | Redução de SQL repetitivo |
| Blade | Renderizar HTML no servidor | Separação de apresentação |
| Middleware | Interceptar requisições | Autenticação, segurança e controle de acesso |
| Migrations | Versionar alterações no banco | Evolução reproduzível do esquema |
| Queue / Jobs | Processar trabalhos fora do fluxo principal | Evitar bloquear requisições longas |
| Events / Listeners | Reagir a acontecimentos | Comunicação desacoplada |
| Cache | Reutilizar resultados | Redução de latência e consultas |
| Artisan | Executar comandos de desenvolvimento | Automação de tarefas |

Duas dessas funcionalidades merecem destaque adicional.

**Service Container:** administra a construção e a resolução de dependências. Em vez de um Controller precisar conhecer como construir todos os objetos utilizados, o framework pode fornecê-los automaticamente.

**Eloquent:** funciona como um ORM baseado no padrão Active Record. Permite usar classes PHP para representar entidades persistidas e disponibiliza consultas, relacionamentos e operações de gravação.

**SSR, CSR e gerenciamento de estado (1 minuto)**

O roteiro do professor menciona SSR, CSR e gerenciamento de estado. É interessante diferenciar o que é nativo do Laravel e o que depende de ferramentas adicionais.

**Blade — Server-Side Rendering**

O servidor produz o HTML e o envia pronto ao navegador.

```

return view('tasks.index', [
    'tasks' => Task::all()
]);
```

**Inertia + React/Vue**

O Laravel atua no backend e a interface utiliza componentes JavaScript.

```

return Inertia::render(
    'Tasks/Index',
    ['tasks' => Task::all()]
);
```

O uso de React ou Vue com Inertia não implica necessariamente CSR puro: a configuração também pode oferecer SSR.

Para o gerenciamento de estado, é útil separar dois cenários: no backend, Laravel oferece sessões, cache e persistência; no frontend, ferramentas como React, Vue e Livewire podem controlar estado e interatividade.

O Laravel não possui, por si só, uma solução equivalente a Redux para gerenciamento global de estado em aplicações React.

**Curva de aprendizado, performance e mercado (1min30)**

**Curva de aprendizado**

| Nível | Conteúdo | Dificuldade relativa |
| --- | --- | --- |
| Básico | Rotas, Controllers, Blade e CRUD | Baixa a moderada |
| Intermediário | Eloquent, validações, autenticação e testes | Moderada |
| Avançado | Container, Providers, filas, eventos, arquitetura e otimização | Moderada a alta |

Essa classificação é qualitativa. Quem já domina PHP, orientação a objetos, HTTP e SQL tende a aprender os fundamentos mais rapidamente.

**Desempenho**

O Laravel possui abstrações que facilitam o desenvolvimento, mas elas têm custos de execução e podem ocultar consultas ineficientes.

Um exemplo clássico é o problema **N+1 queries**:

```
$tasks = Task::all();

foreach ($tasks as $task) {
    echo $task->user->name;
}
```

Caso `user` seja um relacionamento, cada acesso pode disparar uma consulta adicional. Com eager loading:

```
$tasks = Task::with('user')->get();
```

Os usuários relacionados podem ser carregados em uma consulta adicional, em vez de uma consulta para cada tarefa.

**Mercado**

Laravel é particularmente adequado a sistemas administrativos, plataformas SaaS, e-commerce, portais, produtos digitais e APIs.

Embora possa servir de backend para aplicativos móveis ou aplicações de IA, não é um framework mobile nativo nem substitui frameworks especializados em treinamento de modelos de machine learning.

## **Bloco 3 — Design Patterns aplicados (6 minutos)**

Esta deve ser a seção tecnicamente mais consistente da apresentação. O diferencial é não apenas identificar os padrões, mas demonstrar:

1. Qual problema de design motivou o padrão.
2. Como ele funciona conceitualmente.
3. Onde aparece no código do Laravel.
4. Quais são suas vantagens e limitações.

**Por que Design Patterns? (30 segundos)**

**Design Patterns são soluções reutilizáveis para problemas recorrentes de projeto de software.**

Eles não são bibliotecas ou trechos de código que precisam ser copiados literalmente. São estruturas e estratégias de organização que podem ser implementadas de diferentes maneiras.

No Laravel, encontramos esses conceitos em diversos níveis: na construção de objetos, na persistência de dados e no acesso aos serviços internos do framework.

Os três exemplos escolhidos são:

| Padrão | Categoria | Implementação no Laravel |
| --- | --- | --- |
| Dependency Injection | Técnica de design associada à inversão de controle | Service Container |
| Active Record | Padrão arquitetural de persistência | Eloquent ORM |
| Proxy | Padrão estrutural | Laravel Facades |

Essa distinção é importante academicamente: Dependency Injection não é um dos 23 padrões GoF, embora o roteiro do professor o aceite expressamente.

**Dependency Injection e Inversion of Control (1min45)**

**Problema:** uma classe fortemente acoplada à implementação de outra torna alterações e testes mais difíceis.

Considere um Controller que instancia diretamente seu serviço:

```

class TaskController extends Controller
{
    public function index()
    {
        $service = new TaskService();

        return $service->listAll();
    }
}
```

O Controller conhece a classe concreta e decide como ela deve ser construída.

Com Dependency Injection:

```

class TaskController extends Controller
{
    public function __construct(
        private TaskService $service
    ) {}

    public function index()
    {
        return $this->service->listAll();
    }
}
```

Agora, a responsabilidade pela construção do serviço é transferida ao mecanismo de resolução do Laravel.

O que acontece internamente?

Controller solicita TaskService

**Service Container**

Inspeciona o construtor, identifica dependências e resolve os objetos necessários

TaskController recebe TaskService

O Service Container utiliza mecanismos de inspeção de tipos e resolução de dependências. Quando uma classe concreta possui dependências que também podem ser resolvidas, o container frequentemente consegue instanciá-la sem configuração adicional.

Entretanto, para depender de uma interface, precisamos indicar qual implementação deve ser utilizada.

**Exemplo mais elaborado:**

```

interface TaskRepositoryInterface
{
    public function all(): iterable;
}

class EloquentTaskRepository
    implements TaskRepositoryInterface
{
    public function all(): iterable
    {
        return Task::all();
    }
}
```

No método `register()` de `AppServiceProvider`:

```

$this->app->bind(
    TaskRepositoryInterface::class,
    EloquentTaskRepository::class
);
```

O serviço pode então declarar uma dependência da interface:

```

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $repository
    ) {}

    public function listAll(): iterable
    {
        return $this->repository->all();
    }
}
```

**Por que isso importa?** Agora é possível substituir a implementação de acesso a dados sem modificar o `TaskService`. Isso favorece o princípio da inversão de dependência (*Dependency Inversion Principle*, o D do SOLID).

Não é necessário adicionar essa arquitetura inteira à prova de conceito; ela pode ser mostrada apenas como evolução do exemplo simples.

**Vantagens:** menor acoplamento, facilidade de testes e substituição de implementações.

**Limitações:** introduzir interfaces e camadas para funcionalidades triviais pode gerar complexidade desnecessária; também é preciso compreender como as dependências são resolvidas.

**Active Record: persistência orientada a objetos (1min45)**

**Problema:** existe uma diferença entre a representação orientada a objetos da aplicação e a representação relacional dos dados no banco.

Considere uma tabela:

| id | title | completed |
| --- | --- | --- |
| 1 | Estudar Laravel | 0 |
| 2 | Preparar apresentação | 1 |

Sem um ORM, precisamos construir consultas SQL, executá-las e transformar os resultados em estruturas utilizáveis pela aplicação.

Com Eloquent, a tabela pode ser representada por uma classe.

```

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'completed'
    ];
}
```

O model `Task` herda os mecanismos de persistência de `Illuminate\Database\Eloquent\Model`.

```

// CREATE
Task::create([
    'title' => 'Estudar Laravel',
    'completed' => false
]);

// READ
$tasks = Task::where(
    'completed', false
)->get();

// UPDATE
$task = Task::findOrFail(1);
$task->completed = true;
$task->save();

// DELETE
$task->delete();
```

**O mecanismo:**

O model encapsula dados e comportamento de persistência. Quando invocamos `save()`, o Eloquent avalia o estado do objeto e executa a operação de inserção ou atualização correspondente.

O Eloquent também fornece um Query Builder, hidratação de modelos, relacionamentos e acompanhamento de atributos modificados.

A relação entre classe e banco de dados

**Objeto PHP**

`Task`

`title`

`completed`

`save()`

**Tabela SQL**

`tasks`

`title`

`completed`

`INSERT / UPDATE`

O Eloquent faz a correspondência entre o modelo PHP e a estrutura relacional.

Um detalhe interessante para mencionar é a diferença entre Active Record e Data Mapper.

| Active Record (Eloquent) | Data Mapper |
| --- | --- |
| A entidade contém operações de persistência | A persistência fica em um componente separado |
| Interface direta e produtiva para operações CRUD | Maior separação entre domínio e infraestrutura |
| Pode aproximar regras de domínio e detalhes do banco | Pode ser mais adequado a modelos de domínio complexos |

**Análise crítica:** Active Record favorece produtividade, mas não é universalmente a melhor escolha. Em sistemas com regras de negócio complexas, pode ser conveniente separar o domínio da infraestrutura de persistência.

**Proxy: como funcionam as Facades? (2 minutos)**

Esse é um dos exemplos mais interessantes porque demonstra um comportamento interno que pode parecer invisível para quem utiliza Laravel.

**Problema:** precisamos acessar frequentemente serviços como cache, logs, armazenamento e banco de dados. Resolver essas dependências explicitamente em todos os locais pode tornar o código mais extenso.

O Laravel oferece as Facades:

```

use Illuminate\Support\Facades\Cache;

Cache::put('tasks_count', 10, 60);

$count = Cache::get('tasks_count');
```

À primeira vista, `Cache::get()` parece um método estático tradicional. Porém, o Laravel utiliza um mecanismo de encaminhamento dinâmico para um objeto real.

O fluxo simplificado é:

!Captura de Tela 2026-11-22 às 21.52.38.png

**Evidência diretamente no código-fonte do Laravel**

A classe real responsável por esse comportamento é:

`Illuminate\Support\Facades\Facade`

O método `__callStatic` obtém o objeto representado pela Facade e encaminha para ele a execução do método solicitado. Ele utiliza `getFacadeRoot()` para acessar a instância correspondente.

Assim, uma chamada como `Cache::get()` não equivale simplesmente a executar toda a lógica de cache dentro da classe `Cache`.

O método do serviço real é quem realiza a operação.

**Vantagens:** chamadas concisas, acesso conveniente aos serviços do framework e suporte a substituições por mocks e fakes nos testes.

**Limitações:** dependências menos explícitas, facilidade de criar classes que acessam muitos serviços globais e maior necessidade de compreender o funcionamento interno do container.

Outro detalhe relevante: o nome *Facade* pode causar confusão com o Design Pattern Facade do catálogo Gang of Four. Na documentação oficial, as Facades do Laravel são descritas como *static proxies*. Por isso, **Proxy é uma classificação mais defensável quando o foco é seu mecanismo de implementação**.

**Comparando os três padrões (30 segundos)**

| Padrão | Qual responsabilidade organiza? | Principal benefício |
| --- | --- | --- |
| Dependency Injection | Construção e fornecimento de dependências | Desacoplamento |
| Active Record | Representação e persistência dos dados | Produtividade |
| Proxy | Acesso indireto a serviços | Encapsulamento do acesso |

A conclusão desse bloco deve relacionar os três padrões:

> Esses mecanismos não atuam isoladamente. O Service Container gerencia dependências, o Eloquent simplifica a persistência e as Facades fornecem acesso aos serviços internos. Em conjunto, contribuem para a produtividade do Laravel e influenciam a arquitetura das aplicações.
> 

## **Bloco 4 — Demonstração prática (4 minutos)**

Para tornar a prova de conceito mais interessante, proponho apresentar a aplicação de tarefas como um pequeno sistema real, mas com a demonstração focada em uma única requisição.

**Arquitetura da prova de conceito (1 minuto)**

**Projeto:** Task Manager — sistema web de gerenciamento de tarefas.

O sistema terá uma listagem de tarefas, persistência em SQLite e renderização com Blade. O Controller utilizará um Service, enquanto o Eloquent fará a comunicação com o banco de dados.

Estrutura:

```
task-manager/
├── app/
│   ├── Http/Controllers/
│   │   └── TaskController.php
│   ├── Models/
│   │   └── Task.php
│   └── Services/
│       └── TaskService.php
├── bootstrap/
│   └── app.php
├── database/
│   └── migrations/
├── resources/views/
│   └── tasks/
│       └── index.blade.php
├── routes/
│   └── web.php
└── public/
    └── index.php
```

Destaquem que o Laravel utiliza uma organização convencionada, mas permite que a aplicação crie camadas adicionais conforme sua complexidade.

**Ciclo completo de uma requisição (2 minutos)**

Aqui, vale demonstrar algo mais profundo do que o MVC.

**Ciclo de vida HTTP no Laravel**

!Captura de Tela 2026-11-22 às 21.51.11.png

Recebe a resposta HTTPFluxo simplificado do processamento da requisição; a resposta também atravessa o pipeline de middleware de saída.

O que deve ser explicado durante a demonstração:

**1. Entrada da requisição.** O servidor web encaminha a requisição ao ponto de entrada `public/index.php`. O Laravel carrega a aplicação e executa sua inicialização.

**2. Middleware.** A requisição passa por componentes que podem verificar autenticação, configurar sessões, proteger contra CSRF e realizar outras tarefas, conforme as rotas e os middleware configurados.

**3. Roteamento.** O Router encontra a rota que corresponde ao método HTTP e à URL.

```
Route::get('/tasks', [
    TaskController::class,
    'index'
]);
```

**4. Resolução do Controller.** O Service Container instancia `TaskController` e resolve a dependência `TaskService`.

**5. Acesso aos dados.** O Service chama `Task::all()`. O Eloquent realiza a consulta ao banco e converte os registros em objetos do modelo.

**6. Renderização.** O Controller devolve a View Blade com os dados obtidos, e o HTML resultante é enviado ao navegador como uma resposta HTTP.

Essa descrição segue o ciclo oficial de requisições do framework.

**Evidência de execução (1 minuto)**

Além de mostrar a tela no navegador, vocês podem comprovar a execução das camadas.

No terminal:

```
php artisan route:list --path=tasks
```

Esse comando mostra a rota registrada e sua associação com o Controller.

No `TaskController`, acrescentem:

```
use Illuminate\Support\Facades\Log;

public function index()
{
    Log::info('Iniciando consulta de tarefas');

    $tasks = $this->service->listAll();

    Log::info('Consulta concluída', [
        'quantidade' => $tasks->count()
    ]);

    return view('tasks.index', compact('tasks'));
}
```

Depois de acessar `/tasks`, mostrem os registros em `storage/logs/laravel.log`, caso o canal de log esteja configurado para gravar nesse arquivo.

Isso permite demonstrar tanto a resolução das dependências quanto o funcionamento da Facade de logging.

Como evidência complementar, vocês podem incluir um teste automatizado:

```

<?php

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lista as tarefas cadastradas', function () {
    Task::create([
        'title' => 'Estudar Laravel',
        'completed' => false
    ]);

    $this->get('/tasks')
        ->assertOk()
        ->assertSee('Estudar Laravel');
});
```

Esse exemplo utiliza Pest e deve ser colocado em `tests/Feature/TaskListTest.php`, em um projeto configurado com Pest.

O teste verifica se o endpoint responde com sucesso e se apresenta o registro cadastrado. Ele também demonstra como os recursos de teste do Laravel podem validar o comportamento da aplicação.

**Na apresentação de quatro minutos**, não é necessário digitar código. Deixem o projeto funcionando e alternem entre navegador, Controller, Service, Model e terminal.

## **Bloco 5 — Análise crítica (2 minutos)**

**Benefícios, limitações e decisão arquitetural (2 minutos)**

A análise crítica deve ir além de simplesmente afirmar que o Laravel é bom para projetos grandes e ruim para projetos pequenos. É preciso mostrar quais características influenciam a decisão.

| Critério | Benefícios | Possíveis limitações |
| --- | --- | --- |
| Produtividade | Ferramentas e convenções integradas | Dependência das abstrações do framework |
| Arquitetura | MVC, DI, middleware e extensibilidade | Risco de Controllers ou Models com responsabilidades excessivas |
| Banco de dados | Eloquent, relacionamentos e migrations | Consultas ineficientes e abstrações inadequadas para certos domínios |
| Performance | Cache, filas e opções de otimização | Custos de inicialização, memória e abstrações |
| Manutenção | Ecossistema maduro, testes e organização | Atualizações, dependências externas e dívida técnica |

**Quando utilizar Laravel?**

Laravel costuma ser uma escolha adequada quando a equipe precisa desenvolver sistemas web com autenticação, acesso a dados, regras de negócio, processamento de tarefas e interfaces administrativas.

Ele é especialmente interessante quando a produtividade da equipe e a velocidade de desenvolvimento têm grande importância.

**Quando considerar outras soluções?**

Para uma ferramenta extremamente pequena ou um script pontual, utilizar um framework completo pode introduzir infraestrutura desnecessária.

Para aplicações dominadas por processamento computacional intensivo, podem ser mais adequados serviços ou componentes especializados. Isso não significa que Laravel não possa participar da arquitetura: ele pode continuar responsável pela API e delegar operações pesadas a workers.

Em sistemas com domínio muito complexo, o uso direto de modelos Active Record em todas as camadas também merece avaliação.

**Conclusão sugerida para a apresentação:**

> O Laravel não se destaca apenas por reduzir a quantidade de código necessária para desenvolver aplicações. Seu principal diferencial está em oferecer uma estrutura consistente, apoiada por padrões de projeto que organizam dependências, persistência e acesso a serviços. Entretanto, a produtividade oferecida pelo framework não elimina a necessidade de decisões arquiteturais conscientes.
>