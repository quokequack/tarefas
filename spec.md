# Especificação de implementação — Laravel Task Manager

## Contexto

Desenvolva uma aplicação chamada **Laravel Task Manager** para uma apresentação acadêmica sobre o framework Laravel, na disciplina de Análise e Projeto de Sistemas de Informação.

O professor exige uma demonstração prática da estrutura de pastas, do fluxo de uma requisição pelo framework e da aplicação de pelo menos três Design Patterns.

A aplicação deve servir como prova de conceito dos seguintes mecanismos:

1. **Dependency Injection / Inversion of Control:** demonstrada pela injeção de `TaskService` em `TaskController`, resolvida pelo Service Container do Laravel.
2. **Active Record:** demonstrado pelo Eloquent ORM, utilizando a classe `Task` para consultar e persistir registros.
3. **Proxy:** demonstrado pelas Facades `Log` e `Cache`, que encaminham chamadas aos serviços reais gerenciados pelo Laravel.

O objetivo principal é produzir um projeto simples, funcional, compreensível e adequado para explicar o funcionamento interno do Laravel em uma apresentação.

## 1. Stack tecnológica

- Laravel 13, com PHP 8.3 ou superior.
- SQLite como banco de dados.
- Blade para renderização no servidor (SSR).
- CSS simples ou Tailwind CSS, caso já esteja configurado no projeto.
- Eloquent ORM para persistência.
- PHPUnit ou Pest para testes.
- Composer para dependências PHP.
- Artisan para geração e execução de comandos.

Não utilizar React, Vue, Inertia, Livewire, autenticação, Docker, bibliotecas JavaScript complexas ou serviços externos.

Usar a documentação oficial da versão instalada como referência.

## 2. Requisitos funcionais

### RF01 — Listagem de tarefas

Criar uma página principal acessível em `/tasks`.

Exibir as tarefas cadastradas contendo:

- Identificador, quando útil.
- Título da tarefa.
- Estado: pendente ou concluída.
- Data de criação.
- Ações de editar, concluir/reabrir e excluir.

Ordenar as tarefas da mais recente para a mais antiga.

Caso não existam tarefas, exibir uma mensagem de estado vazio.

### RF02 — Cadastro de tarefa

Disponibilizar um formulário com o campo obrigatório `title`.

Regras:

- O título é obrigatório.
- Deve ser uma string.
- Deve possuir no máximo 255 caracteres.
- Uma nova tarefa deve ser criada como pendente.

Após o cadastro, redirecionar para a listagem e exibir uma mensagem de sucesso.

### RF03 — Edição de tarefa

Permitir alterar o título de uma tarefa existente, utilizando uma página ou formulário dedicado.

Aplicar as mesmas regras de validação do cadastro.

Após a edição, redirecionar para a listagem.

### RF04 — Alteração de status

Criar uma ação para alternar o status da tarefa entre pendente e concluída.

A operação deve ser persistida no SQLite por meio do Eloquent.

### RF05 — Exclusão de tarefa

Permitir excluir uma tarefa.

Exigir confirmação na interface antes de enviar a solicitação.

Utilizar o método HTTP DELETE por meio de formulário Blade com `@method('DELETE')`.

### RF06 — Filtros

Implementar filtros simples na listagem:

- Todas.
- Pendentes.
- Concluídas.

Usar um parâmetro de consulta, por exemplo:

`GET /tasks?status=completed`

A filtragem deve acontecer na consulta ao banco, não apenas visualmente.

### RF07 — Feedback

Mostrar mensagens de sucesso utilizando sessões flash do Laravel.

Mostrar erros de validação próximos dos respectivos campos.

## 3. Modelo de dados

Criar a entidade `Task` com:

| Campo | Tipo | Descrição |
|---|---|---|
| id | bigint, chave primária | Identificador |
| title | varchar(255) | Título da tarefa |
| completed | boolean, padrão false | Indica conclusão |
| created_at | timestamp | Criação |
| updated_at | timestamp | Atualização |

Criar uma migration para a tabela `tasks`.

Criar o Model em `app/Models/Task.php`, estendendo `Illuminate\Database\Eloquent\Model`.

Definir `fillable` para os campos apropriados e um cast booleano para `completed`.

Utilizar convenções de nomenclatura do Eloquent.

## 4. Organização do projeto

Utilizar a seguinte organização:

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── TaskController.php
│   └── Requests/
│       └── TaskRequest.php
├── Models/
│   └── Task.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    └── TaskService.php

database/
├── factories/
│   └── TaskFactory.php
├── migrations/
└── seeders/
    └── DatabaseSeeder.php

resources/
└── views/
    ├── layouts/
    │   └── app.blade.php
    └── tasks/
        ├── index.blade.php
        └── edit.blade.php

routes/
└── web.php

tests/
└── Feature/
    └── TaskManagementTest.php
```

Manter o padrão de organização convencional do Laravel.

Criar arquivos adicionais somente quando necessários.

## 5. Responsabilidades das camadas

### TaskController

Responsável por:

- Receber requisições HTTP.
- Receber `TaskService` por injeção de dependência no construtor.
- Utilizar `TaskRequest` para validar entrada de dados nos métodos de cadastro e edição.
- Chamar os métodos de `TaskService`.
- Retornar Views Blade.
- Redirecionar após ações de escrita.
- Emitir registros de log usando a Facade `Log`.

Não implementar consultas Eloquent diretamente no Controller.

### TaskService

Responsável por centralizar os casos de uso:

- `listTasks(?string $status)`
- `createTask(array $data)`
- `findTask(int $id)`
- `updateTask(Task $task, array $data)`
- `toggleTask(Task $task)`
- `deleteTask(Task $task)`

Utilizar Eloquent diretamente nesta classe.

Não criar uma camada Repository apenas por formalidade. O Eloquent já demonstra Active Record e uma camada adicional pode dificultar a explicação acadêmica.

Adicionar tipos de retorno e parâmetros sempre que adequado.

### Task Model

Responsável pela representação e persistência dos dados.

Demonstrar operações de Active Record com o próprio Model.

Evitar colocar regras HTTP ou renderização dentro do Model.

### TaskRequest

Utilizar uma Form Request para centralizar as regras de validação dos campos.

Configurar autorização para permitir a execução, pois não haverá autenticação neste projeto.

## 6. Design Pattern 1 — Dependency Injection

A classe `TaskController` deve receber `TaskService` pelo construtor:

```php
public function __construct(
    private TaskService $taskService
) {}
```

O Laravel deve resolver essa dependência automaticamente por meio do Service Container.

Não utilizar `new TaskService()` dentro do Controller.

Não é necessário registrar um binding explícito para a classe concreta se ela puder ser resolvida automaticamente.

Adicionar comentários didáticos, concisos, identificando onde a injeção de dependência acontece.

## 7. Design Pattern 2 — Active Record

Demonstrar o uso do Eloquent dentro de `TaskService`.

O código deve utilizar operações como:

```php
Task::query()->get();

Task::create($data);

$task->save();

$task->delete();
```

A implementação deve permitir demonstrar que o objeto Eloquent representa uma entidade persistente e possui operações próprias de acesso e gravação de dados.

Usar consultas com `where()` para os filtros.

## 8. Design Pattern 3 — Proxy com Facades

Utilizar a Facade `Log` em ações importantes:

```php
Log::info('Task created', [
    'task_id' => $task->id
]);
```

Registrar eventos de cadastro, atualização de status e exclusão.

Utilizar a Facade `Cache` para armazenar temporariamente a quantidade total de tarefas:

```php
$total = Cache::remember(
    'tasks.total',
    60,
    fn () => Task::count()
);
```

Esse total deve aparecer na interface.

Invalidar a chave `tasks.total` sempre que uma tarefa for criada ou excluída.

Não cachear inicialmente a listagem completa de tarefas, pois o objetivo é manter o exemplo fácil de entender.

Adicionar um comentário conciso explicando que as Facades funcionam como proxies estáticos para serviços resolvidos pelo Service Container.

## 9. Rotas

Criar rotas nomeadas e utilizar métodos HTTP adequados:

| Método | URI | Nome |
|---|---|---|
| GET | /tasks | tasks.index |
| POST | /tasks | tasks.store |
| GET | /tasks/{task}/edit | tasks.edit |
| PUT | /tasks/{task} | tasks.update |
| PATCH | /tasks/{task}/toggle | tasks.toggle |
| DELETE | /tasks/{task} | tasks.destroy |

Redirecionar `/` para `/tasks`.

Preferir Route Model Binding nas ações que recebem tarefas existentes, permitindo o retorno HTTP 404 para identificadores inexistentes.

Todas as ações de escrita devem usar proteção CSRF.

## 10. Interface

Construir uma interface simples, limpa, responsiva e adequada para uma apresentação em sala.

A tela principal deve possuir:

- Cabeçalho "Laravel Task Manager".
- Texto identificando a aplicação como demonstração acadêmica.
- Formulário de cadastro.
- Contador do total de tarefas.
- Filtros de status.
- Listagem das tarefas.
- Botões para editar, concluir/reabrir e excluir.
- Mensagens de sucesso.
- Mensagem quando não houver tarefas.

Utilizar Blade e CSS. Não criar uma SPA.

A interface deve permitir enxergar claramente o funcionamento do CRUD.

## 11. Seeder e dados de demonstração

Criar dados iniciais para facilitar a apresentação.

Exemplos:

- Estudar arquitetura MVC.
- Implementar Dependency Injection.
- Compreender o Eloquent ORM.
- Investigar as Laravel Facades.
- Preparar apresentação do IFAL.

Algumas tarefas devem começar concluídas e outras pendentes.

Criar uma Factory e configurar o DatabaseSeeder para popular o banco de forma reproduzível.

## 12. Testes

Implementar testes de integração HTTP cobrindo:

1. A listagem de tarefas retorna HTTP 200.
2. Uma tarefa pode ser cadastrada.
3. Um título vazio é rejeitado.
4. Uma tarefa pode ser editada.
5. Uma tarefa pode ser marcada como concluída.
6. Uma tarefa concluída pode voltar ao estado pendente.
7. Uma tarefa pode ser excluída.
8. O filtro de concluídas retorna os registros corretos.
9. Uma tarefa inexistente retorna HTTP 404.
10. O contador é atualizado após criação e exclusão.

Utilizar `RefreshDatabase` para isolar os testes.

Não exigir conexão com serviços externos.

## 13. Requisitos de segurança e qualidade

- Utilizar validação server-side.
- Utilizar CSRF nos formulários.
- Escapar a saída de dados com `{{ }}` no Blade.
- Utilizar consultas parametrizadas por meio do Eloquent.
- Não colocar credenciais no código.
- Seguir PSR-12 e convenções do Laravel.
- Evitar duplicação excessiva.
- Não criar abstrações sem justificativa.
- Adicionar comentários apenas quando ajudarem a explicar um conceito importante.

## 14. Documentação para apresentação

Criar `README.md` com:

1. Objetivo do projeto.
2. Tecnologias utilizadas e versões efetivamente instaladas.
3. Requisitos para instalação.
4. Comandos para configurar e executar o projeto.
5. Comandos para migrations e seeders.
6. Comandos para executar os testes.
7. Estrutura de pastas.
8. Explicação da arquitetura MVC com a camada Service.
9. Explicação dos três Design Patterns.
10. Fluxo de uma requisição GET `/tasks`.

Criar também `docs/APRESENTACAO.md`, contendo um roteiro de demonstração de aproximadamente quatro minutos.

Esse roteiro deve indicar:

- Qual arquivo abrir.
- Qual método destacar.
- Que ação executar no navegador.
- Qual Design Pattern está sendo demonstrado.
- Como acompanhar a execução por meio de logs.
- Como mostrar a consulta ao banco e a renderização da View.

Incluir um diagrama Mermaid do fluxo de requisição.

## 15. Ordem de implementação

Executar o trabalho nesta ordem:

1. Verificar ambiente e criar/configurar o projeto Laravel.
2. Configurar SQLite.
3. Criar migration e Model Task.
4. Criar TaskFactory e DatabaseSeeder.
5. Implementar TaskService.
6. Implementar TaskRequest.
7. Implementar TaskController com Dependency Injection.
8. Configurar rotas.
9. Criar layout e Views Blade.
10. Implementar filtros e CRUD.
11. Integrar Facades Log e Cache.
12. Adicionar testes automatizados.
13. Executar migrations, seeders e testes.
14. Corrigir os problemas identificados.
15. Documentar o projeto e o roteiro de demonstração.

## 16. Critérios de aceitação

A implementação estará concluída quando:

- A aplicação executar localmente sem erros.
- As operações CRUD funcionarem.
- Os dados persistirem no SQLite.
- Os filtros funcionarem.
- A página utilizar Blade.
- O Controller receber TaskService via Dependency Injection.
- O Eloquent demonstrar claramente Active Record.
- O projeto utilizar as Facades Log e Cache.
- Os formulários estiverem protegidos por CSRF.
- Os testes automatizados passarem.
- O README contiver as instruções necessárias.
- O documento de apresentação explicar o fluxo completo de GET `/tasks`.

## 17. Orientação final

Priorizar clareza, organização e valor didático.

O projeto será apresentado em uma disciplina de Análise e Projeto de Sistemas de Informação. Portanto, os aspectos mais importantes são a arquitetura, os padrões de projeto e o fluxo de requisições.

Não aumentar artificialmente a complexidade da aplicação.

Antes de finalizar, revisar se os exemplos dos Design Patterns realmente correspondem ao comportamento implementado e se o roteiro de demonstração pode ser executado em aproximadamente quatro minutos.

Ao concluir, apresentar um resumo dos arquivos criados, decisões arquiteturais, comandos executados, testes e eventuais limitações.
