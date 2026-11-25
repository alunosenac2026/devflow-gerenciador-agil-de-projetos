# DevFlow — Gerenciador Ágil de Projetos

<img src="public/assets/favicon.svg" alt="" width="96" height="96">

O DevFlow é uma aplicação local para organizar projetos e tarefas, acompanhar o trabalho em um quadro Kanban e visualizar o progresso de cada projeto. Foi desenvolvido como atividade final da UC6 do **Programa Jovem Programador — SENAC**.

**[Guia de instalação e uso no Windows](docs/instalacao.md)** · [Abrir o DevFlow local](http://127.0.0.1:8082/)

## O que você pode fazer

- Criar, consultar, editar e excluir projetos e tarefas. A exclusão de um projeto com tarefas vinculadas é bloqueada.
- Definir prioridades baixa, média e alta e mover tarefas entre **A fazer**, **Em andamento** e **Concluído** com teclado ou mouse.
- Consultar o total, as contagens por estado e o percentual de conclusão do projeto selecionado.
- Alternar entre os temas claro e escuro. Quando disponível, o navegador salva a preferência.

O MVP é acadêmico, individual e restrito ao próprio computador. A aplicação opera sem login de usuários, equipes ou integrações externas. A movimentação usa controles do cartão.

## Ambiente de referência

| Componente | Tecnologia e versão |
|---|---|
| Ambiente web | XAMPP para Windows x64 8.2.12 e Apache 2.4.58. |
| Servidor da aplicação | PHP 8.2.12, PDO com `pdo_mysql` e `mbstring`. |
| Banco de dados | MariaDB 10.4.32, instância dedicada em `127.0.0.1:3307`. |
| Interface | HTML5, CSS3, JavaScript nativo e Bootstrap 5.3.8 local. |

## Obter o código

- **Git.** No PowerShell, abra a pasta onde deseja guardar o projeto e execute:

  ```powershell
  git clone https://github.com/alunosenac2026/devflow-gerenciador-agil-de-projetos.git
  Set-Location devflow-gerenciador-agil-de-projetos
  ```

- **ZIP.** Abra o [repositório no GitHub](https://github.com/alunosenac2026/devflow-gerenciador-agil-de-projetos), escolha **Code → Download ZIP** e extraia o arquivo. A raiz do projeto é a pasta que contém `README.md`, `public/` e `database/`. Git é opcional nesse caso.

Siga a [primeira instalação](docs/instalacao.md#primeira-instalação) antes de usar o endereço local. Com o MariaDB dedicado e o Apache em execução, acesse [http://127.0.0.1:8082/](http://127.0.0.1:8082/). Em **Tarefas do projeto**, selecione um projeto para ver seu quadro e os indicadores. Para mover uma tarefa, escolha **Mover para** e confirme em **Mover**.

## Validação e documentação

No ambiente de referência, as três suítes de regressão e **89 casos de contrato** passaram. As integrações foram executadas em `devflow_test`. Os registros distinguem o aceite manual no navegador das verificações automatizadas com API simulada. A instalação limpa em outro computador e o teste de isolamento a partir de outro dispositivo da rede ainda estão pendentes.

- [Instalação, configuração e uso cotidiano](docs/instalacao.md)
- [Referência técnica e histórico do ambiente](docs/ambiente.md)
- [Requisitos](docs/requisitos.md) · [Arquitetura](docs/arquitetura.md) · [Testes e evidências](docs/testes.md)
- [Plano e cronograma da entrega](PLANO.md#cronograma)
