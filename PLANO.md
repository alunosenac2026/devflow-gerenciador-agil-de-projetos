# Plano do MVP do DevFlow

O DevFlow é o projeto final da UC6 do SENAC. O objetivo é permitir que uma pessoa organize projetos e tarefas, acompanhe o andamento em um Kanban e preserve seu trabalho em um banco relacional no ambiente local XAMPP.

## Escopo MoSCoW

| Classe | Escopo |
|---|---|
| Must have | CRUD de projetos e tarefas, prioridades e estados com padrões definidos, ordenação estável, HTTP 409 na exclusão de projeto com tarefas, Kanban acessível, progresso, persistência, validação no servidor e isolamento local. A interface deve comunicar falhas de gravação e preservar o estado anterior. |
| Should have | Estados vazios claros e orientações adicionais para corrigir erros de formulário. |
| Could have | Nenhum item reservado para esta entrega. |
| Won’t have | Autenticação, equipes, integrações externas e arrastar e soltar como requisito obrigatório. |

## Arquitetura

Monólito modular em PHP 8.2 com Projetos, Tarefas e infraestrutura compartilhada. A API JSON usa PDO com `pdo_mysql`. O esquema foi provisionado e validado em uma instância dedicada do MariaDB 10.4.32 do XAMPP, com integração executada em `devflow_test`. A interface usa HTML5, CSS3, Bootstrap 5 local e JavaScript nativo. Consulte [requisitos](docs/requisitos.md), [arquitetura](docs/arquitetura.md) e [testes](docs/testes.md).

## Incrementos e aceite

| Incremento da Sprint 02 | Progresso |
|---|---|
| 02.1 — Estrutura inicial | Concluído. Diretórios, regras do Git e exemplo de configuração local. |
| 02.2 — Isolamento do Apache | VirtualHost aplicado no XAMPP de referência. Testes locais do 02.2C passaram e a entrada pública da UC4 preservou HTTP 200. O 02.2D documenta a reprodução, a reversão e as ressalvas no [guia de ambiente](docs/ambiente.md). |
| 02.3 — Persistência MariaDB | Concluído no ambiente local. Schema provisionado na instância dedicada em `127.0.0.1:3307`. Testes de persistência aprovados na execução local. `devflow` foi criado sem dados de teste. Arquivos locais protegidos por NTFS e ignorados pelo Git. |

O teste por outro dispositivo da rede permanece pendente. A Sprint 03.1 validou a criação e a listagem de projetos no navegador e na API, incluindo nova leitura após a gravação. A Sprint 03.2 concluiu o CRUD de projetos, com consulta individual pela API e aceite da edição, da persistência após recarga e da exclusão pelo navegador. A integração em `devflow_test` confirmou o HTTP 409 quando há tarefas vinculadas. O [guia de ambiente](docs/ambiente.md#rota-de-projetos) registra a ativação do roteamento e a regressão de isolamento.

A Sprint 04 concluiu o CRUD de tarefas no projeto selecionado, com prioridades, estados e ordenação por posição e ID. O contrato e a integração em `devflow_test` passaram. Após a ativação das rotas, o responsável confirmou cadastro, listagem, edição, persistência após recarga e exclusão pelo navegador. As verificações técnicas confirmaram o isolamento HTTP/HTTPS e a disponibilidade da entrada pública da UC4.

A Sprint 05 concluiu o quadro Kanban com três colunas, prioridade identificada por texto e cor e movimentação por teclado. Os testes automatizados confirmaram as seis transições em `devflow_test`, com persistência em nova leitura, e verificaram o comportamento da interface no Chrome com respostas simuladas. O responsável confirmou separadamente o aceite no navegador com a API real.

A Sprint 06 concluiu os indicadores de progresso do projeto selecionado. No ambiente real, o responsável confirmou o total de tarefas, as contagens por estado, a atualização após movimentação, o arredondamento para 67%, a conclusão de 100% e o retorno a 0% após excluir todas as tarefas. Os [registros de teste](docs/testes.md#interface-e-regressão) distinguem o aceite manual das verificações automatizadas com API simulada.

A Sprint 07 aprovou as três suítes de regressão e os 89 casos de contrato. O aceite complementar confirmou a alternância entre projetos e a atualização dos indicadores correspondentes. O MVP está apto para entrega acadêmica no ambiente de referência, sem defeitos bloqueantes identificados. O aceite manual da recarga dos indicadores e da edição pelo formulário aguarda confirmação explícita. O teste de isolamento em outro dispositivo permanece pendente. Consulte a [regressão e a matriz de cobertura](docs/testes.md#regressão-integrada-da-sprint-07).

## Cronograma

O prazo oficial de entrega informado pelo professor é **26/11/2026 às 23h59**.

| Data de 2026 | Entrega | Critério de aceite |
|---|---|---|
| Até 24/11 | Concluir ambiente, persistência, CRUD de projetos e tarefas e Kanban. | Aceites e verificações registrados em Incrementos e aceite. |
| Até 25/11 | Concluir e validar os indicadores de progresso. | Concluído na Sprint 06, com aceite manual e evidências dos testes automatizados registrados. |
| 26/11, até 23h59 | Entregar o MVP. | Regressão e matriz de rastreabilidade consolidadas na Sprint 07. Versão de entrega pendente de aprovação do versionamento. |

A publicação da versão de entrega depende da aprovação do versionamento.

## Riscos

| Risco | Tratamento |
|---|---|
| Exposição pelo host padrão, por alias ou por interface de rede | Validar HTTP e HTTPS, bloquear acesso ao checkout e restringir o banco. Testar via loopback e rede antes de considerar o ambiente pronto. |
| Diferenças entre a versão disponível do banco e o esquema planejado | Identificar produto e versão no XAMPP e testar as restrições no servidor efetivo. |
| Perda de dados por exclusão de projeto | Adotar chave estrangeira restritiva e traduzir o conflito em HTTP 409, inclusive se uma tarefa for criada durante a exclusão. |
| Quadro divergente após falha da API | Atualizar a coluna e os indicadores após confirmação do servidor e manter uma mensagem de erro acessível. |
