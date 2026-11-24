# Estratégia de testes e checklist

Use dados descartáveis em um banco exclusivo de testes. Execute as verificações do incremento e repita o fluxo completo antes da entrega. Registre branch, revisão, versões do ambiente, comando ou passos, resultado e pendências. Os itens abaixo são verificações planejadas.

## Banco e ambiente

- [ ] Confirmar PHP 8.2, extensão `pdo_mysql`, versão do servidor de banco e configuração efetiva do Apache.
- [ ] Criar o esquema em banco vazio e verificar conexão com `utf8mb4`.
- [ ] Confirmar que o banco impede tarefas órfãs e a exclusão de projetos com tarefas.
- [ ] Confirmar que a configuração efetiva restringe o DevFlow à máquina local.
- [ ] Verificar pelo endereço da aplicação e pelo host padrão que arquivos internos e sensíveis não são entregues via HTTP.
- [ ] Executar `php -l` nos arquivos PHP quando existirem.

## API e persistência

- [ ] Exercitar todas as rotas de projetos e tarefas e conferir códigos HTTP, JSON e ausência de corpo em 204.
- [ ] Confirmar 400 para JSON malformado, 404 para recurso inexistente e 422 para campos inválidos, sem alterações persistidas.
- [ ] Conferir que a exclusão de projeto com tarefas retorna 409 e preserva todos os registros.
- [ ] Criar tarefas omitindo prioridade, estado e posição. Confirmar `baixa`, `a_fazer` e 0.
- [ ] Exercitar os três valores de prioridade e de estado. Rejeitar valores fora das opções.
- [ ] Confirmar o isolamento da listagem por projeto e a ordenação por posição e ID, cobrindo posições iguais e distintas.
- [ ] Confirmar que campos omitidos numa edição mantêm os valores atuais e que mover uma tarefa preserva vínculo e posição.
- [ ] Conferir progresso em projeto vazio e com zero, algumas e todas as tarefas concluídas.
- [ ] Verificar que entradas com aspas e acentos são preservadas e que erros de persistência não revelam detalhes internos.

## Interface e regressão

- [ ] Criar, consultar, editar e excluir projetos e tarefas pela interface.
- [ ] Verificar as três colunas e os estados vazios. Mover tarefas entre colunas pelo teclado e conferir foco, rótulo e mensagem de resultado.
- [ ] Recarregar a página e verificar estados, ordem e indicadores.
- [ ] Simular falha de gravação. Confirmar que o quadro mantém a exibição anterior e comunica o erro.
- [ ] Confirmar que conteúdo semelhante a HTML em títulos e descrições é apresentado como texto.
