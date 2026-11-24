# Plano do MVP do DevFlow

O DevFlow é o projeto final da UC6 do SENAC. O objetivo é permitir que uma pessoa organize projetos e tarefas, acompanhe o andamento em um Kanban e preserve seu trabalho em MySQL no ambiente local XAMPP.

## Escopo MoSCoW

| Classe | Escopo |
|---|---|
| Must have | CRUD de projetos e tarefas, três prioridades e três estados, Kanban, movimentação acessível, progresso, persistência e validação no servidor. |
| Should have | Estados vazios claros e mensagens que ajudem a corrigir erros de formulário ou gravação. |
| Could have | Nenhum item reservado para esta entrega. |
| Won’t have | Autenticação, equipes, integrações externas e arrastar e soltar como requisito obrigatório. |

## Arquitetura

Monólito modular em PHP 8.2 com Projetos, Tarefas e infraestrutura compartilhada. A API JSON usa PDO e MySQL. A interface usa HTML5, CSS3, Bootstrap 5 local e JavaScript nativo. Consulte [requisitos](docs/requisitos.md), [arquitetura](docs/arquitetura.md) e [testes](docs/testes.md).

## Incrementos e aceite

| Etapa | Entrega | Critério de aceite |
|---|---|---|
| 1. Ambiente e persistência | Inspecionar XAMPP e documentar a configuração proposta. Implementar a conexão e o esquema em etapa autorizada. | Confirmar PHP 8.2, PDO MySQL e servidor de banco. O esquema inicia em banco vazio e impede tarefas órfãs. Apenas a área pública fica acessível e o acesso é local. |
| 2. Projetos | Entregar CRUD na API e na interface. | Criar, listar, consultar, editar e excluir um projeto vazio. Validar campos no servidor e retornar 404 para registro ausente. |
| 3. Tarefas | Entregar CRUD vinculado ao projeto e regras de integridade. | Aplicar os padrões de prioridade, estado e posição. Ordenar por posição e ID. Retornar 409 e preservar os registros quando o projeto tiver tarefas. |
| 4. Kanban | Exibir três colunas, movimentação acessível e progresso. | Mover por teclado, recalcular contagens e percentual após sucesso e confirmar persistência após recarga. Exibir erro e preservar a visualização anterior se a gravação falhar. |
| 5. Verificação | Rodar o checklist, corrigir falhas e documentar a execução local. | Validar o fluxo em banco de teste e em navegador. Registrar resultados e lacunas de cada incremento. |

## Riscos

| Risco | Tratamento |
|---|---|
| Exposição pelo `localhost` padrão ou por alias do Apache | Inspecionar todas as rotas até o checkout e confirmar o bloqueio dos diretórios internos antes de considerar o ambiente pronto. |
| Diferenças entre a versão disponível do banco e o esquema planejado | Identificar produto e versão no XAMPP e testar as restrições no servidor efetivo. |
| Perda de dados por exclusão de projeto | Adotar chave estrangeira restritiva e traduzir o conflito em HTTP 409, inclusive se uma tarefa for criada durante a exclusão. |
| Quadro divergente após falha da API | Atualizar a coluna e os indicadores após confirmação do servidor e manter uma mensagem de erro acessível. |
