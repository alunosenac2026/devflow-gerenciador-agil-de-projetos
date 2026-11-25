# Estratégia de testes e checklist

Use dados descartáveis em um banco exclusivo de testes. Execute as verificações do incremento e repita o fluxo completo antes da entrega. Registre branch, revisão, versões do ambiente, comando ou passos, resultado e pendências. Os itens abaixo são verificações planejadas.

## Banco e ambiente

Após o provisionamento local, execute o teste de integração da persistência conforme o [guia de ambiente](ambiente.md#reexecutar-os-testes). O executor usa somente o banco de testes e a conta restrita. Cobre Unicode, valores padrão, timestamps, recusas por chave estrangeira, valores inválidos de prioridade e estado, ordenação, rollback e permissões. A validação HTTP da API faz parte do incremento seguinte.

No incremento 02.3, a execução local reportou sucesso nos testes de persistência em `devflow_test` e a criação de `devflow` sem dados de teste. Consulte o [registro do ambiente](ambiente.md#resultado-do-incremento-023) para a evidência e a conferência dos arquivos locais.

- [ ] Confirmar PHP 8.2 com `pdo_mysql` sob o Apache, MariaDB 10.4.32 e configuração efetiva do XAMPP. Aplicar os scripts SQL num banco de teste vazio para verificar compatibilidade.
- [ ] Criar o esquema em banco vazio e verificar conexão com `utf8mb4`.
- [ ] Confirmar que o banco impede tarefas órfãs e a exclusão de projetos com tarefas.
- [ ] Verificar HTTP e HTTPS via `localhost` e loopback: a área pública abre apenas no endereço local previsto, e o host padrão, o endereço da aplicação e eventuais aliases bloqueiam os caminhos privados. Incluir `app/`, `config/`, `database/`, `tests/`, `.git`, credenciais e logs. Registrar códigos e ausência de conteúdo privado sem reproduzir dados sensíveis.
- [ ] Verificar as interfaces de escuta IPv4 e IPv6. A partir de outro dispositivo na rede, testar os endereços de rede da máquina para HTTP, HTTPS e porta do banco. O DevFlow e o banco devem ficar inacessíveis pela rede, e a conexão local do aplicativo ao banco deve funcionar.
- [ ] Executar `php -l` nos arquivos PHP quando existirem.

## API e persistência

Na Sprint 04, passaram 50 casos de contrato de tarefas e 39 de projetos. As integrações em `devflow_test` validaram CRUD, prioridades e estados, vínculo, ordenação, persistência em nova conexão e o conflito 409 ao tentar excluir um projeto com tarefas. Os testes de tarefas criam projetos sintéticos próprios e removem seus registros ao finalizar.

```powershell
& 'C:/xampp/php/php.exe' tests/tarefas.php
& 'C:/xampp/php/php.exe' tests/tarefas.php --integracao
& 'C:/xampp/php/php.exe' tests/projetos.php --integracao
```

Após a ativação das rotas, as sondagens confirmaram que a coleção e a consulta individual de projetos continuaram respondendo, que o isolamento HTTP/HTTPS foi preservado e que a entrada pública da UC4 continuou disponível. Consulte o [registro da Sprint 04](ambiente.md#rotas-de-tarefas).

No aceite manual, o responsável confirmou cadastro, listagem, ordenação, edição, prioridades, estados, persistência após recarga e exclusão no navegador. O projeto descartável foi excluído.

Evidências da Sprint 05

- **Integração automatizada.** `tests/tarefas.php --integracao` executou as seis transições em `devflow_test`. Após cada `PATCH`, uma nova conexão leu a coleção do projeto e confirmou estado, posição e ordenação. A movimentação preservou vínculo, título, descrição e prioridade.
- **Interface automatizada.** O Chrome headless acessou a página servida pelo Apache com respostas simuladas da API. Os testes verificaram as seis transições por teclado, a recarga após cada movimento, a devolução do foco ao cartão, a preservação da coluna diante de falha, o CRUD de tarefas e o layout em 390 e 1280 px.
- **Aceite manual.** O responsável confirmou o funcionamento do Kanban no navegador com a API real.

- [ ] Exercitar todas as rotas de projetos e tarefas e conferir códigos HTTP, JSON e ausência de corpo em 204.
- [ ] Confirmar 400 para JSON malformado, 404 para recurso inexistente e 422 para campos inválidos, sem alterações persistidas.
- [ ] Em POST e PATCH, verificar tipos, campos obrigatórios, espaços em branco, limites de 120 e 2.000 caracteres com conteúdo multibyte, nulidade, PATCH vazio, campos desconhecidos e rejeição de `posicao` e demais campos internos. Testar limpeza da descrição por `null` e preservação dos campos omitidos.
- [ ] Nas escritas, aceitar a origem local configurada e rejeitar origem ausente, nula ou divergente com 403. Verificar 415 para tipo ausente ou incompatível em POST e PATCH, 422 para JSON que não seja objeto e 400 para DELETE com corpo. Confirmar ausência de alterações rejeitadas.
- [ ] Conferir que a exclusão de projeto com tarefas retorna 409 e preserva todos os registros.
- [ ] Criar tarefas omitindo prioridade, estado e posição. Confirmar `baixa`, `a_fazer` e 0.
- [ ] Exercitar os três valores de prioridade e de estado. Rejeitar valores fora das opções.
- [ ] Confirmar o isolamento da listagem por projeto e a ordenação por posição e ID, preparando posições iguais e distintas no banco de teste.
- [ ] Confirmar que campos omitidos numa edição mantêm os valores atuais e que mover uma tarefa preserva vínculo e posição.
- [ ] Conferir contagens e arredondamento do progresso em projeto vazio, sem concluídas e com frações como 1/3 (33%), 2/3 (67%) e 1/8 (13%), além de todas concluídas (100%).
- [ ] Verificar que entradas com aspas e acentos são preservadas e que erros de persistência não revelam detalhes internos.
- [ ] Revisar todas as consultas da aplicação. Valores vindos de requisições ou dados variáveis devem ser passados como parâmetros PDO, incluindo identificadores de registros. Elementos estruturais da consulta devem ser fixos ou escolhidos em lista permitida.

## Interface e regressão

Evidências da Sprint 06

- **Aceite manual no ambiente real.** O responsável confirmou o total de tarefas, as contagens por estado e a atualização após movimentação. Também conferiu 67% de conclusão parcial com arredondamento correto, 100% de conclusão integral e o retorno a 0% após excluir todas as tarefas.
- **Teste automatizado com API simulada.** No Chrome headless, o teste verificou projetos vazios, sem concluídas, parciais e completos e os percentuais de 0%, 33%, 67%, 13%, 58% e 100%. Verificou atualização após criar, editar, mover e excluir tarefas e preservação dos indicadores quando cada operação falhou. Também verificou troca de projeto, recarga, quantidade de requisições, rótulo e valores da barra na árvore de acessibilidade e layout em 390 e 1280 px.

- [ ] Criar, consultar, editar e excluir projetos e tarefas pela interface.
- [ ] Verificar as três colunas e os estados vazios. Mover tarefas entre colunas pelo teclado e conferir foco, rótulo e mensagem de resultado.
- [ ] Executar as seis transições entre estados pelo controle: A fazer → Em andamento, A fazer → Concluído, Em andamento → A fazer, Em andamento → Concluído, Concluído → A fazer e Concluído → Em andamento. Conferir estado retornado pela API, coluna e persistência após recarga.
- [ ] Conferir atualização imediata de contagens e percentual após criar, mover e excluir tarefas, incluindo excluir a última tarefa do projeto.
- [ ] Recarregar a página e verificar estados, ordem e indicadores.
- [ ] Simular falhas de criação, edição, movimentação e exclusão. Confirmar que listas, quadro e indicadores preservam o último estado confirmado e que a falha é perceptível.
- [ ] Confirmar que conteúdo semelhante a HTML em títulos e descrições é apresentado como texto.

## Matriz de cobertura

| Requisito | Verificação |
|---|---|
| RF01 | CRUD de projeto pela API e interface, nome obrigatório, exclusão vazia e 409 com tarefas. |
| RF02 | CRUD de tarefa, título obrigatório, lista por projeto, projeto inexistente e chave estrangeira. |
| RF03 | Três prioridades, valor inválido e padrão `baixa`. |
| RF04 | Três estados, padrão `a_fazer`, seis transições e persistência. |
| RF05 | Ordem por posição e ID, padrão 0, rejeição de escrita pelo cliente e preservação ao mover. |
| RF06 | Total, contagens por estado, barra acessível, arredondamento, projeto vazio, troca de projeto, recarga e atualização após cada operação aplicável. |
| RNF01 | Versões, PHP/PDO sob Apache, aplicação do esquema no MariaDB e recarga no navegador. |
| RNF02 | Validação no servidor, revisão da parametrização, acentos e renderização como texto. |
| RNF03 | HTTP/HTTPS local e pela rede, caminhos privados, acesso ao banco e erros genéricos. |
| RNF04 | Rótulo, teclado, foco e comunicação perceptível durante movimentação. |
| RNF05 | Formato, campos, tipo de conteúdo, origem e ausência de escrita após rejeição. |
| RNF06 | Falhas de gravação comunicadas com preservação do último estado confirmado. |
