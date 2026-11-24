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

Monólito modular em PHP 8.2 com Projetos, Tarefas e infraestrutura compartilhada. A API JSON usa PDO com `pdo_mysql`. O banco de desenvolvimento identificado no XAMPP é MariaDB 10.4.32, sujeito à confirmação da compatibilidade dos scripts SQL. A interface usa HTML5, CSS3, Bootstrap 5 local e JavaScript nativo. Consulte [requisitos](docs/requisitos.md), [arquitetura](docs/arquitetura.md) e [testes](docs/testes.md).

## Incrementos e aceite

| Incremento da Sprint 02 | Progresso |
|---|---|
| 02.1 — Estrutura inicial | Concluído. Diretórios, regras do Git e exemplo de configuração local. |
| 02.2 — Isolamento do Apache | VirtualHost aplicado no XAMPP de referência. Testes locais do 02.2C passaram e a entrada pública da UC4 preservou HTTP 200. O 02.2D documenta a reprodução, a reversão e as ressalvas no [guia de ambiente](docs/ambiente.md). |
| 02.3 — Persistência MariaDB | Concluído no ambiente local. Schema provisionado na instância dedicada em `127.0.0.1:3307`. Testes de persistência aprovados na execução local. `devflow` foi criado sem dados de teste. Arquivos locais protegidos por NTFS e ignorados pelo Git. |

O teste por outro dispositivo da rede permanece pendente. A próxima fatia funcional integra criação e listagem de projetos na API REST e no navegador. Os demais fluxos do MVP seguem planejados.

| Prazo de 2026 | Entrega | Critério de aceite |
|---|---|---|
| Até 24/11 | Fechar contrato, confirmar ambiente e começar a persistência após autorização. | Confirmar PHP 8.2 com PDO no Apache, validar o esquema em banco vazio MariaDB 10.4.32 e demonstrar o isolamento local antes de disponibilizar a aplicação. |
| 25–26/11 | Entregar CRUD de projetos e tarefas na API e na interface. | Cobrir operações, validação, padrões, ordenação e integridade. Retornar 409 e preservar os registros quando o projeto tiver tarefas. |
| 27/11 | Entregar Kanban acessível e progresso. | Verificar as seis transições, atualizar indicadores após gravação bem-sucedida e confirmar persistência após recarga. Comunicar falhas e preservar a interface anterior. |
| 28–29/11 | Executar regressão e segurança, corrigir falhas e documentar a execução. | Revisar consultas parametrizadas, executar a matriz de testes, verificar isolamento HTTP/HTTPS e banco e registrar resultados e pendências até 29/11/2026. |

Os marcos dependem da autorização da implementação e de eventuais ajustes no ambiente. Se a publicação local segura atrasar, priorizar a validação de persistência em banco de teste e manter o aceite de implantação pendente até confirmar o isolamento.

## Riscos

| Risco | Tratamento |
|---|---|
| Exposição pelo host padrão, por alias ou por interface de rede | Validar HTTP e HTTPS, bloquear acesso ao checkout e restringir o banco. Testar via loopback e rede antes de considerar o ambiente pronto. |
| Diferenças entre a versão disponível do banco e o esquema planejado | Identificar produto e versão no XAMPP e testar as restrições no servidor efetivo. |
| Perda de dados por exclusão de projeto | Adotar chave estrangeira restritiva e traduzir o conflito em HTTP 409, inclusive se uma tarefa for criada durante a exclusão. |
| Quadro divergente após falha da API | Atualizar a coluna e os indicadores após confirmação do servidor e manter uma mensagem de erro acessível. |
