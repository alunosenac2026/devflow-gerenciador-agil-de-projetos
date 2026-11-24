# Requisitos do DevFlow

## Requisitos funcionais

| ID | Requisito | Critério de aceitação |
|---|---|---|
| RF01 | Criar, listar, consultar, editar e excluir projetos. | Nome em branco é rejeitado. Projeto vazio pode ser excluído. Exclusão com tarefas retorna HTTP 409 e preserva projeto e tarefas. |
| RF02 | Criar, listar, consultar, editar e excluir tarefas com vínculo obrigatório a um projeto. | Título em branco é rejeitado. A lista de um projeto contém apenas suas tarefas. Projetos inexistentes retornam 404 nas rotas de tarefas por projeto. |
| RF03 | Atribuir prioridade a cada tarefa. | São aceitos `baixa`, `media` e `alta`. Na criação, a ausência do campo resulta em `baixa`. |
| RF04 | Apresentar Kanban e permitir movimentação entre estados. | O quadro exibe A fazer, Em andamento e Concluído. Uma tarefa nova começa em `a_fazer` quando o estado é omitido. É possível movê-la por teclado em qualquer direção e recarregar a página para confirmar a persistência. |
| RF05 | Ordenar as tarefas de forma determinística. | Dentro de cada coluna, ordenar por `posicao` crescente e, em caso de empate, por `id` crescente. A posição padrão é 0. |
| RF06 | Mostrar indicadores de progresso por projeto. | Exibir contagens por estado e percentual de conclusão. Um projeto vazio exibe 0%. Criar, mover ou excluir uma tarefa atualiza os indicadores após sucesso. |

## Requisitos não funcionais

| ID | Requisito | Critério de aceitação |
|---|---|---|
| RNF01 | Executar localmente no XAMPP com PHP 8.2 e banco MySQL, usando JavaScript nativo e Bootstrap 5 local. | O fluxo funciona no ambiente documentado e persiste após recarga. |
| RNF02 | Validar entradas no servidor e usar PDO com consultas parametrizadas. | Dados inválidos recebem resposta JSON apropriada sem alteração persistida. Entradas textuais são renderizadas como texto. |
| RNF03 | Restringir o acesso à máquina local e proteger arquivos internos. | HTTP não entrega credenciais, logs, configuração, SQL de instalação, testes ou metadados Git pelos endereços servidos pelo Apache. Erros da API não expõem detalhes internos. |
| RNF04 | Tornar a movimentação acessível. | O controle tem rótulo identificável, foco visível e operação via teclado. O resultado ou a falha é comunicado de forma perceptível. |

## Regras de negócio

- Projeto tem nome obrigatório e descrição opcional. Tarefa tem título obrigatório e descrição opcional. Espaços isolados não satisfazem campos obrigatórios.
- Cada tarefa pertence a exatamente um projeto. A movimentação altera o estado dentro do mesmo projeto e preserva a posição.
- O estado é um entre `a_fazer`, `em_andamento` e `concluido`. Prioridade e estado fora das opções válidas são rejeitados. Os padrões se aplicam aos campos omitidos na criação.
- O progresso usa o número de tarefas concluídas dividido pelo total do projeto, multiplicado por 100. A apresentação usa 0% quando o total é zero.
