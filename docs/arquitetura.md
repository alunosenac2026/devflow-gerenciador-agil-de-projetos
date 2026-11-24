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

## Exposição HTTP no XAMPP

Antes de propor mudanças, inspecionar `DocumentRoot`, hosts virtuais, aliases, permissões de diretório e interfaces de escuta. O checkout sob `htdocs` pode continuar acessível pelo host padrão mesmo com um host virtual servido por `public/`. Escolher a configuração mais simples que elimine esse acesso alternativo e restrinja o DevFlow à máquina local. Validar a configuração proposta com requisições aos caminhos público e privados. A configuração efetiva e o formato final das URLs dependem dessa inspeção.
