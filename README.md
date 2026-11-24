# devflow-gerenciador-agil-de-projetos

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

## Persistência local

O DevFlow usa a instância dedicada MariaDB 10.4.32 em `127.0.0.1:3307`. Após iniciar a instância, siga o [procedimento de instalação e testes](docs/ambiente.md#persistência-na-instância-dedicada) para aplicar `database/schema.sql`, criar as contas restritas e gerar `config/local.php` e `config/local.test.php`. Ambos os arquivos são ignorados pelo Git e ficam fora de `public/`.

No ambiente acadêmico de referência, o provisionamento local concluiu a instalação de `devflow_test`, validou a persistência e criou `devflow` sem dados de teste. As configurações locais foram verificadas por metadados e permanecem protegidas por NTFS e ignoradas pelo Git.

Para reexecutar os testes, use `C:/xampp/php/php.exe tests/persistencia.php` e `C:/xampp/php/php.exe tests/projetos.php --integracao` no PowerShell do usuário responsável. Ambos usam o banco exclusivo de testes.
