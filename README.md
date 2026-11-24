# devflow-gerenciador-agil-de-projetos

## Estrutura inicial

| Caminho | Uso previsto |
|---|---|
| `app/Infra/`, `app/Projetos/`, `app/Tarefas/` | Infraestrutura e módulos de negócio. |
| `config/` e `database/` | Configuração local e scripts do esquema. |
| `public/api/` e `public/assets/` | Entradas da API e arquivos estáticos sob a futura raiz pública. |
| `tests/` | Verificações da aplicação e da persistência. |
| `var/log/` | Logs locais. O diretório é ignorado pelo Git e deve ser criado em cada checkout quando necessário. |

## Configuração local futura

Após validar o isolamento do checkout no XAMPP e confirmar os dados do banco, copie `config/local.example.php` para `config/local.php` e substitua os valores fictícios pelos dados locais. Para um banco exclusivo de testes, use `config/local.test.php`. Mantenha esses arquivos fora de `public/` e do Git.

A origem proposta é `http://127.0.0.1:8082`. A porta do banco (`3306`), o esquema (`devflow`) e a conta (`devflow_app`) são provisórios até a confirmação no incremento de persistência. Este incremento prepara a estrutura e o exemplo de configuração. A aplicação ainda não está operacional.
