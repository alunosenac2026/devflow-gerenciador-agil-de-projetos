# DevFlow — Gerenciador Ágil de Projetos

<img src="public/assets/favicon.svg" alt="" width="96" height="96">

## Estrutura inicial

| Caminho | Uso previsto |
|---|---|
| `app/Infra/`, `app/Projetos/`, `app/Tarefas/` | Infraestrutura e módulos de negócio. |
| `config/` e `database/` | Configuração local e scripts do esquema. |
| `public/api/` e `public/assets/` | Diretórios reservados à API e aos arquivos estáticos, sob a raiz pública do Apache. |
| `tests/` | Verificações da aplicação e da persistência. |
| `var/log/` | Logs locais. O diretório é ignorado pelo Git e deve ser criado em cada checkout quando necessário. |

## Acesso local

Com o VirtualHost configurado e o Apache em execução, use [http://127.0.0.1:8082/](http://127.0.0.1:8082/) para criar, listar, editar e excluir projetos. Ao excluir, a interface pede confirmação. Projetos com tarefas vinculadas ficam protegidos pela API, que retorna HTTP 409. O [guia de ambiente](docs/ambiente.md#rota-de-projetos) descreve o roteamento e as verificações locais.

Em **Tarefas do projeto**, escolha um projeto para ver o quadro Kanban e o progresso, com total de tarefas, contagens por estado e percentual de conclusão. Cada cartão mostra a prioridade e oferece as ações **Editar**, **Excluir** e **Mover**. Para mover uma tarefa, escolha o destino em **Mover para** e confirme em **Mover**. Em cada coluna, o quadro respeita posição e ID. O quadro e o progresso são atualizados após uma operação bem-sucedida. A seleção do projeto é recuperada após recarregar a página. O guia de ambiente descreve as [rotas de tarefas e sua validação](docs/ambiente.md#rotas-de-tarefas).

Use **Modo escuro** no cabeçalho para alternar o tema. A preferência fica salva no navegador quando o armazenamento local está disponível. Na primeira visita vale o tema do sistema.

O prazo oficial e os marcos de entrega estão no [cronograma do MVP](PLANO.md#cronograma).

O MVP está apto para entrega acadêmica no ambiente de referência. A Sprint 07 aprovou as três suítes de regressão e 89 casos de contrato. Os aceites manuais, as verificações simuladas e as pendências constam no [relatório de validação](docs/testes.md#regressão-integrada-da-sprint-07).

## Persistência local

O DevFlow usa a instância dedicada MariaDB 10.4.32 em `127.0.0.1:3307`. Após iniciar a instância, siga o [procedimento de instalação e testes](docs/ambiente.md#persistência-na-instância-dedicada) para aplicar `database/schema.sql`, criar as contas restritas e gerar `config/local.php` e `config/local.test.php`. Ambos os arquivos são ignorados pelo Git e ficam fora de `public/`.

Em outra máquina, prepare o Windows com XAMPP, o VirtualHost local e a instância MariaDB dedicada já inicializada, com conta administrativa local. O diretório de dados é validado pelo provisionador. Consulte os [pré-requisitos e caminhos](docs/ambiente.md#estrutura-e-instalação-em-outra-máquina).

No ambiente acadêmico de referência, o provisionamento local concluiu a instalação de `devflow_test`, validou a persistência e criou `devflow` sem dados de teste. As configurações locais foram verificadas por metadados e permanecem protegidas por NTFS e ignoradas pelo Git.

Para reexecutar os testes, use `C:/xampp/php/php.exe tests/persistencia.php`, `C:/xampp/php/php.exe tests/projetos.php --integracao` e `C:/xampp/php/php.exe tests/tarefas.php --integracao` no PowerShell do usuário responsável. As integrações usam o banco exclusivo de testes.
