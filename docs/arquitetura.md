# Arquitetura do DevFlow

## Estrutura planejada

| Caminho | Responsabilidade |
|---|---|
| `public/index.php` | Entrada da interface de projetos e Kanban. |
| `public/api/index.php` | Entrada da API, despacho por método e rota, leitura de JSON e resposta HTTP. |
| `public/assets/` | JavaScript nativo, CSS e Bootstrap 5 local. |
| `app/Projetos/` | Validação, persistência e regras de projeto, incluindo progresso e exclusão. |
| `app/Tarefas/` | Validação, persistência, regras de estado e ordenação de tarefas. |
| `app/Infra/` | Conexão PDO e mecanismos compartilhados de resposta e erro. |
| `config/` | Configuração local do banco. Valores sensíveis ficam fora do Git. |
| `database/` | Esquema e instruções de inicialização do banco. |
| `tests/` | Verificações da API e do banco. |

Interface e API operam na mesma origem. A API valida a entrada antes de acionar as regras de negócio. A persistência usa PDO, parâmetros e conexão com `utf8mb4`. Os erros externos são genéricos quando houver falha interna. Registros de diagnóstico ficam em local privado.

O banco de desenvolvimento identificado no XAMPP é MariaDB 10.4.32. O acesso permanece via PDO com `pdo_mysql`. A compatibilidade dos scripts SQL será confirmada no banco de teste antes do aceite.

## Modelagem

| Entidade | Campos |
|---|---|
| Projeto | `id` chave primária, `nome` obrigatório, `descricao` opcional, `criado_em`, `atualizado_em`. |
| Tarefa | `id` chave primária, `projeto_id` obrigatório, `titulo` obrigatório, `descricao` opcional, `prioridade` com padrão `baixa`, `status` com padrão `a_fazer`, `posicao INT NOT NULL DEFAULT 0`, `criado_em`, `atualizado_em`. |

A relação Projeto–Tarefa é de um para muitos. A chave estrangeira restringe a exclusão de projetos com tarefas. O índice proposto em `(projeto_id, status, posicao, id)` atende à leitura ordenada por coluna. A ordenação explícita usa `posicao ASC, id ASC`. O progresso deriva das tarefas e não exige armazenamento próprio.

## API JSON

| Método e rota lógica | Sucesso | Operação |
|---|---|---|
| GET /api/projetos | 200 | Listar projetos com progresso. |
| POST /api/projetos | 201 | Criar projeto. |
| GET /api/projetos/{id} | 200 | Consultar projeto e progresso. |
| PATCH /api/projetos/{id} | 200 | Editar projeto. |
| DELETE /api/projetos/{id} | 204 | Excluir projeto vazio. |
| GET /api/projetos/{id}/tarefas | 200 | Listar tarefas do projeto ordenadas por posição e ID. |
| POST /api/projetos/{id}/tarefas | 201 | Criar tarefa no projeto. |
| GET /api/tarefas/{id} | 200 | Consultar tarefa. |
| PATCH /api/tarefas/{id} | 200 | Editar tarefa ou mudar seu estado. Campos omitidos ficam com os valores existentes. |
| DELETE /api/tarefas/{id} | 204 | Excluir tarefa. |

O contrato reserva 400 para JSON malformado, 404 para recurso inexistente, 409 para exclusão de projeto com tarefas e 422 para dados inválidos. Erros usam uma estrutura JSON estável com código e mensagem, com indicação de campo quando aplicável. Respostas 204 são sem corpo. O vínculo da tarefa vem da rota na criação e permanece o mesmo na edição.

Na Sprint 03.1, `GET /api/projetos` devolve um array de projetos, do ID mais recente ao mais antigo, e `POST /api/projetos` devolve o projeto criado. Ambos retornam `id`, `nome`, `descricao`, `criado_em` e `atualizado_em`. O resumo de progresso entra com a fatia de indicadores. O objeto `erro` contém `codigo`, `mensagem` e, quando aplicável, `campo`. Métodos não atendidos na rota retornam 405 com `Allow: GET, POST`.

Na Sprint 03.2, as rotas individuais aceitam IDs de 1 a 4294967295, conforme a chave `INT UNSIGNED`. `GET` e `PATCH` devolvem o projeto no mesmo formato da criação. `DELETE` devolve 204 sem corpo ou 409 quando a chave estrangeira impede a exclusão por existirem tarefas. Métodos não atendidos na rota individual retornam 405 com `Allow: GET, PATCH, DELETE`.

Na Sprint 04, os mesmos limites de ID se aplicam a tarefas e ao projeto informado na rota de coleção. Cada tarefa devolvida contém `id`, `projeto_id`, `titulo`, `descricao`, `prioridade`, `status`, `posicao`, `criado_em` e `atualizado_em`. A lista distingue um projeto existente sem tarefas (`200` com `[]`) de um projeto inexistente (`404`). O banco confirma a existência do projeto pela chave estrangeira na criação e aplica os padrões dos campos omitidos. A edição mantém vínculo, posição e os demais campos omitidos. Métodos não atendidos recebem `Allow: GET, POST` na coleção ou `Allow: GET, PATCH, DELETE` na rota individual.

### Campos de entrada

| Recurso e campo | Tipo e validação | POST | PATCH |
|---|---|---|---|
| Projeto `nome` | String de 1 a 120 caracteres após aparar espaços. `null` é inválido. | Obrigatório | Opcional |
| Tarefa `titulo` | String de 1 a 120 caracteres após aparar espaços. `null` é inválido. | Obrigatório | Opcional |
| Projeto ou tarefa `descricao` | String de até 2.000 caracteres ou `null`. Texto vazio após aparar espaços equivale a `null`. | Opcional, padrão `null` | Opcional. Enviar `null` limpa a descrição. |
| Tarefa `prioridade` | String com valor exato `baixa`, `media` ou `alta`. `null` é inválido. | Opcional, padrão `baixa` | Opcional |
| Tarefa `status` | String com valor exato `a_fazer`, `em_andamento` ou `concluido`. `null` é inválido. | Opcional, padrão `a_fazer` | Opcional |

As rotas POST e PATCH recebem um objeto JSON em UTF-8. Os limites são contados em caracteres Unicode, sem coerção de tipos. Campos desconhecidos ou gerenciados pelo servidor, incluindo `id`, `projeto_id`, datas e `posicao`, geram 422. O PATCH precisa de pelo menos um campo aceito e preserva campos omitidos. Uma entrada inválida rejeita a operação inteira. IDs na rota são inteiros decimais positivos dentro do intervalo do identificador no banco. IDs inválidos ou ausentes no banco retornam 404. `posicao` é interna, começa em 0 e permanece intacta ao mover a tarefa.

### Política de escrita

- POST e PATCH exigem `Content-Type: application/json`. Aceitar o parâmetro `charset=UTF-8` quando informado. Tipo ausente ou diferente retorna 415. JSON malformado retorna 400 e um valor JSON que não seja objeto retorna 422. DELETE opera sem corpo e rejeita corpo com 400.
- Exigir em POST, PATCH e DELETE o cabeçalho `Origin` com correspondência exata a `app.origin` da configuração local. Projetos e tarefas compartilham essa validação. Origem ausente, `null` ou divergente retorna 403 antes de qualquer gravação. A integração usa `app.origin` da configuração exclusiva de testes.
- Manter frontend e API na mesma origem e evitar permissões CORS para origens externas. CORS regula acesso entre origens no navegador. O isolamento local depende das restrições de rede e do servidor, inclusive porque clientes fora do navegador podem forjar cabeçalhos. O MVP opera sem autenticação.

## Exposição no XAMPP

No ambiente de referência, o DevFlow tem um VirtualHost vinculado a `127.0.0.1:8082`, com `public/` como raiz. Uma regra global bloqueia o caminho físico do checkout. A exceção para clientes locais existe apenas no VirtualHost dedicado, que exige o cabeçalho `Host` esperado, desativa listagem e overrides e limita as URLs às entradas previstas e aos assets permitidos.

Os testes locais confirmaram o bloqueio do checkout pelos hosts compartilhados HTTP e HTTPS e dos mapeamentos herdados na porta dedicada. A configuração aplicada, os resultados e o teste pendente a partir de outro dispositivo estão no [guia de ambiente](ambiente.md).

O VirtualHost dedicado encaminha `/api/projetos`, `/api/projetos/{id}`, `/api/projetos/{id}/tarefas` e `/api/tarefas/{id}` para `public/api/index.php`. A API valida os identificadores antes de acessar o banco. O guia de ambiente registra a [regra vigente e seu aceite local](ambiente.md#rotas-de-tarefas). O MariaDB dedicado usa `127.0.0.1:3307` com diretório de dados independente. A infraestrutura PDO aceita somente o destino e a conta de execução esperados para cada ambiente. O instalador local aplica o mesmo schema em dois bancos separados e limita as contas de execução às operações DML nas tabelas do próprio banco. O procedimento está no [guia de ambiente](ambiente.md#persistência-na-instância-dedicada).
