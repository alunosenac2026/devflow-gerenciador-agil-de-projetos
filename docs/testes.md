# Estratégia de testes e checklist

Use dados descartáveis em um banco exclusivo de testes. Execute as verificações do incremento e repita o fluxo completo antes da entrega. Registre branch, revisão, versões do ambiente, comando ou passos, resultado e pendências. Os itens abaixo são verificações planejadas.

## Banco e ambiente

Após o provisionamento local, execute o teste de integração da persistência conforme o [guia de ambiente](ambiente.md#reexecutar-os-testes). O executor usa somente o banco de testes e a conta restrita. Cobre Unicode, valores padrão, timestamps, recusas por chave estrangeira, valores inválidos de prioridade e estado, ordenação, rollback e permissões. As sondagens HTTP da API estão registradas nas evidências da Sprint 04 e na [regressão integrada da Sprint 07](#regressão-integrada-da-sprint-07).

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

## Regressão integrada da Sprint 07

A rodada consolidada foi executada na branch `desenvolvimento-validacao-final`, revisão `a544556`. As três suítes passaram, com **89 casos de contrato aprovados**. As integrações utilizaram `devflow_test`.

| Verificação executada | Resultado |
|---|---|
| `tests/persistencia.php` | Aprovado. Integridade referencial, valores padrão, timestamps, Unicode, ordenação, rollback e privilégios restritos. |
| `tests/projetos.php --integracao` | Aprovado. 39 casos de contrato, CRUD de projetos e HTTP 409 ao excluir projeto com tarefas. |
| `tests/tarefas.php --integracao` | Aprovado. 50 casos de contrato, CRUD de tarefas, vínculo, prioridades, ordenação e seis transições do Kanban, com nova leitura após cada movimento. |
| Sintaxe e Apache local | Sintaxe aprovada em 13 arquivos PHP e 2 JavaScript próprios. A página e os assets retornaram HTTP 200. As três rotas sondadas com ID inválido retornaram HTTP 404 em JSON. As portas 8082 e 3307 estavam restritas ao loopback. |

### Aceite manual complementar do RF06

No ambiente real, o responsável confirmou o projeto A com duas tarefas, uma concluída, e progresso de 50%. O projeto B tinha uma tarefa concluída e progresso de 100%. Ao alternar entre os projetos, os indicadores exibiram os valores correspondentes. Após excluir as tarefas dos dois projetos, ambos voltaram a 0%. Os projetos descartáveis foram excluídos e o projeto original **Teste DevFlow** foi preservado.

A recarga da página e a atualização dos indicadores após edição pelo formulário têm evidência automatizada com API simulada. O aceite manual desses dois cenários aguarda confirmação explícita do responsável.

O MVP está apto para entrega acadêmica no ambiente de referência. A regressão não identificou defeitos bloqueantes. As verificações locais de isolamento foram aproveitadas dos registros anteriores. O teste de isolamento a partir de outro dispositivo da rede permanece pendente.

## Matriz de cobertura

| ID | Requisito | Evidência e situação |
|---|---|---|
| RF01 | CRUD de projetos | Contrato e integração aprovados na Sprint 07. O navegador já confirmou o CRUD. A integração verificou nome obrigatório, exclusão de projeto vazio e HTTP 409 com preservação do projeto e das tarefas. |
| RF02 | CRUD de tarefas e vínculo obrigatório | Integração aprovada na Sprint 07. Lista restrita ao projeto, título obrigatório, HTTP 404 para projeto inexistente e rejeição de tarefa órfã. CRUD aceito no navegador na Sprint 04. |
| RF03 | Prioridades | Persistência e contrato confirmaram `baixa`, `media` e `alta`, o padrão `baixa` e a rejeição de valores inválidos. Prioridades aceitas no navegador na Sprint 04. |
| RF04 | Kanban e movimentação | Integração confirmou padrão `a_fazer`, seis transições e persistência em nova leitura. Kanban aceito com a API real na Sprint 05. As seis transições por teclado e recarga foram exercitadas com API simulada. |
| RF05 | Ordenação determinística | Integração confirmou padrão 0, ordem por `posicao` e `id`, empate e preservação ao mover. O contrato rejeitou escrita de `posicao`. A ordem também foi conferida no navegador. |
| RF06 | Indicadores de progresso | Aceite real confirmou contagens, movimentação, 67%, 100% e retorno a 0% na Sprint 06. A Sprint 07 confirmou 50%, 100%, alternância e retorno a 0% nos dois projetos. Recarga e atualização após edição pelo formulário têm evidência simulada, com confirmação manual pendente. A simulação também cobriu criação, arredondamento e acessibilidade da barra. |
| RNF01 | Execução local no XAMPP | Ambiente de referência validado com PHP 8.2, PDO, MariaDB 10.4.32 e Bootstrap local. A Sprint 07 aprovou a persistência e as sondagens HTTP. Os aceites anteriores registraram recarga do CRUD. |
| RNF02 | Validação, parametrização e renderização segura | Contratos e Unicode aprovados. A revisão confirmou parâmetros PDO e uso de `textContent`. A renderização literal foi exercitada na simulação. |
| RNF03 | Isolamento e proteção de arquivos | Bloqueio do checkout em HTTP e HTTPS confirmado anteriormente. Escutas locais reconfirmadas na Sprint 07 e mensagens genéricas verificadas no código. Teste por outro dispositivo da rede pendente. |
| RNF04 | Movimentação acessível | Rótulo, teclado, foco e mensagens verificados no Chrome com API simulada. O aceite do Kanban com API real foi registrado sem detalhamento individual desses controles. |
| RNF05 | Proteção das escritas e contrato | Contratos confirmaram rejeição de origem, tipo de conteúdo e campos indevidos. A integração confirmou preservação dos dados após edição inválida. |
| RNF06 | Preservação da interface diante de falhas | Com API simulada, as falhas de criação, edição, movimentação e exclusão comunicaram o erro e preservaram os indicadores e o quadro confirmado. |
