# Ambiente local do DevFlow

O VirtualHost do DevFlow está ativo no XAMPP de referência em `http://127.0.0.1:8082/`. O incremento 02.1 entregou a estrutura do repositório e o exemplo de configuração. O 02.2 aplicou e verificou localmente o isolamento web. O 02.3 fornece a infraestrutura de persistência descrita abaixo. A interface e a API REST seguem planejadas. Este procedimento se destina ao ambiente acadêmico local.

## Pré-requisitos verificados

| Componente | Evidência no ambiente de referência |
|---|---|
| Windows com XAMPP | Apache em sessão interativa, administrado pelo XAMPP Control Panel. |
| Apache 2.4.58 Win64 | Sintaxe e VirtualHosts verificados. A versão descreve o ambiente acadêmico inspecionado. |
| PHP 8.2.12 | Verificado na CLI e sob o Apache, com PDO, `pdo_mysql` e `mbstring`. |
| MariaDB 10.4.32 | Versão confirmada na primeira partida da instância dedicada, com escuta exclusiva em `127.0.0.1:3307`. |

A configuração usa os módulos de autorização do Apache, `mod_rewrite` e `mod_proxy`. O módulo PHP do XAMPP executa a entrada pública. Quando `mod_userdir` estiver carregado, ele é desabilitado no VirtualHost dedicado. Para verificar os módulos, use `httpd -M` com os mesmos argumentos de raiz e configuração da validação abaixo.

## Estrutura e instalação em outra máquina

1. Prepare um ambiente Windows com XAMPP. Use os valores verificados como referência e repita os testes na versão instalada.
2. Defina a raiz do XAMPP, por exemplo `C:/xampp`, e obtenha o checkout do DevFlow. Nos blocos Apache abaixo, substitua `CAMINHO_DO_CHECKOUT` pelo caminho absoluto, com barras `/`. O checkout contém `public/`, `app/`, `config/`, `database/`, `docs/` e `tests/`. `var/log/` é local e ignorado pelo Git.
3. Identifique os `DocumentRoot` e mapeamentos existentes, inclusive HTTP, HTTPS, aliases e caminhos que chegam ao checkout. Confirme a disponibilidade de `8082` antes de prosseguir. Registre o estado dos processos, portas e respostas dos projetos compartilhados que serão comparados.
4. Identifique o arquivo principal efetivo. No layout de referência, ele fica em `<XAMPP>/apache/conf/httpd.conf` e os arquivos complementares ficam em `<XAMPP>/apache/conf/extra/`. Faça o backup descrito abaixo antes de editar.

## VirtualHost dedicado

Adicione ao final de `httpd.conf`, fora dos blocos existentes:

```apache
Include "conf/extra/httpd-devflow.conf"
```

Crie `conf/extra/httpd-devflow.conf` com este modelo e substitua os dois caminhos do checkout:

```apache
Listen 127.0.0.1:8082

<Directory "CAMINHO_DO_CHECKOUT">
    Options None
    AllowOverride None
    AllowOverrideList None
    Require all denied
</Directory>

<VirtualHost 127.0.0.1:8082>
    ServerName 127.0.0.1:8082
    DocumentRoot "CAMINHO_DO_CHECKOUT/public"

    AllowEncodedSlashes Off
    TraceEnable Off
    ProxyRequests Off
    ProxyPassInherit Off

    <IfModule userdir_module>
        UserDir disabled
    </IfModule>

    RewriteEngine On
    RewriteCond %{HTTP_HOST} !^127\.0\.0\.1:8082$
    RewriteRule ^ - [F]

    RewriteRule !^/(?:|index\.php|api/index\.php|assets/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\.(?:css|js|png|jpe?g|gif|webp|svg|ico|woff2?))$ - [F]

    <Directory "CAMINHO_DO_CHECKOUT/public">
        Options None
        AllowOverride None
        AllowOverrideList None
        AcceptPathInfo Off
        DirectoryIndex disabled
        DirectoryIndex index.php
        AuthMerging Off
        Require ip 127.0.0.1
    </Directory>
</VirtualHost>
```

A regra global de diretório bloqueia o caminho físico do checkout pelos hosts compartilhados. A autorização para `public/` está dentro do VirtualHost dedicado, que combina escuta no loopback, cliente local e validação de `Host`. A lista de URLs bloqueia os caminhos dos mapeamentos herdados nesse VirtualHost. A regra atual permite as entradas previstas e os formatos estáticos listados. As rotas REST precisarão de um ajuste explícito acompanhado de novos testes de isolamento.

## Backup, validação, aplicação e reversão

1. Copie o `httpd.conf` original para uma pasta com acesso restrito, fora de `htdocs` e do repositório. Compare o SHA-256 do original e da cópia com `Get-FileHash -Algorithm SHA256`. Verifique se o arquivo dedicado já existe. Se existir, revise sua origem antes de editar. Preserve a codificação e as quebras de linha do arquivo principal.
2. Após gravar as alterações em disco, valide com o mesmo binário, raiz e arquivo de configuração usados pela instância existente. Exija `Syntax OK`. Compare os VirtualHosts compartilhados, host padrão, raiz principal e valores `Mutex` com a linha de base. A ordem das linhas `Mutex` pode variar. O único VirtualHost novo esperado é `127.0.0.1:8082`.
3. Após autorização, no XAMPP Control Panel, use **Stop → Start** apenas na linha Apache quando ele estiver no modo interativo. Aguarde a liberação das portas compartilhadas antes de iniciar. Se a instalação gerenciar o Apache como serviço, use o controlador correspondente àquela instância. Valide primeiro a escuta exclusiva de `8082` em loopback e o bloqueio do checkout pelos hosts HTTP e HTTPS. Em seguida, execute os testes da matriz e compare os projetos compartilhados com a linha de base.
4. Para confirmar o PHP sob Apache, se `public/index.php` estiver ausente, use um diagnóstico temporário que retorne somente `PHP_VERSION` e os resultados de `extension_loaded()` para PDO, `pdo_mysql` e `mbstring`. Confira o HTTP 200 pelo endereço dedicado e o bloqueio pelos hosts compartilhados. Remova o diagnóstico criado para o teste e confirme que ele deixou de responder. Fica esperado HTTP 403 em `/` enquanto não houver página inicial e a listagem estiver desativada.
5. Se a validação em disco falhar, restaure os arquivos antes de iniciar o Apache. Se houver falha de inicialização, escuta indevida, exposição ou regressão após a aplicação, interrompa os testes, remova o diagnóstico que tiver sido criado e restaure o arquivo principal com o backup verificado. Retire da configuração ativa apenas o arquivo dedicado criado neste procedimento. Compare o SHA-256 restaurado, repita `httpd -t` e `httpd -S` e reaplique a configuração anterior pelo controlador apropriado. Confirme o retorno dos hosts compartilhados e a ausência de escuta em `8082`. Com a política anterior restaurada, mantenha arquivos locais sensíveis fora do checkout servido.

Exemplo em PowerShell para a validação em disco:

```powershell
$xamppRoot = 'C:/xampp'
$apacheRoot = Join-Path $xamppRoot 'apache'
$httpd = Join-Path $apacheRoot 'bin/httpd.exe'

& $httpd -d $apacheRoot -f 'conf/httpd.conf' -t
& $httpd -d $apacheRoot -f 'conf/httpd.conf' -S
```

## Evidências do 02.2C

| Verificação executada | Resultado |
|---|---|
| Sintaxe e VirtualHosts | `Syntax OK`. VirtualHost dedicado identificado, com os hosts compartilhados preservados no dump. |
| Escuta e acesso dedicado | `8082` somente em `127.0.0.1`. A raiz e o diagnóstico retornaram 200 enquanto o arquivo existiu. Cabeçalhos `Host` fora da regra retornaram 403. |
| Acesso pelo host compartilhado | Checkout, `public/`, diagnóstico e arquivos internos bloqueados em HTTP e HTTPS com 403. |
| Acesso a conteúdo privado | `.git`, `app/`, `config/`, `database/`, `tests/`, `docs/` e `var/log/` bloqueados. Variações de capitalização, codificação, barras, segmentos de ponto e nome curto Windows 8.3 retornaram 403 ou 404. |
| Mapeamentos herdados | Aliases, CGI, handlers de informação, AJP e caminho de `UserDir` retornaram 403 no dedicado. |
| Interfaces e limpeza | Conexão recusada em `8082` via IPv6 loopback e via IPv4 da interface de rede, a partir da própria máquina. Após remover o diagnóstico, a raiz e os diretórios testados retornaram 403, sem listagem, e `/index.php` retornou 404. |
| Regressão compartilhada | Raízes HTTP e HTTPS e entrada pública da UC4 mantiveram 200. Os aliases de ícones, licenças, phpMyAdmin e os handlers de status e informação também responderam 200 no host compartilhado. |

Nos testes HTTPS locais, a validação do certificado foi desativada para verificar o comportamento HTTP. O teste a partir de **outro dispositivo da rede** permanece pendente. A verificação da UC4 cobriu a resposta da entrada pública, com os testes funcionais completos e os fluxos de escrita pendentes.

No host compartilhado, `/webalizer/` retornou 403 e `/examples` retornou 503. O Apache registrou falha de conexão com o destino AJP local na porta 8009, sem escuta observada. Como esses caminhos não tinham resposta registrada antes da aplicação, os resultados ficam como observações, sem regressão confirmada.

## Persistência na instância dedicada

O procedimento pressupõe uma instância dedicada já inicializada e uma conta administrativa local configurada. A instância do DevFlow usa `127.0.0.1:3307`. A porta `3306` pertence à instância compartilhada. Nos exemplos, ajuste a raiz do XAMPP para o executável PHP e os caminhos documentados conforme a instalação aprovada.

### Partida e encerramento

Em um PowerShell dedicado, mantenha o servidor em primeiro plano durante o uso:

```powershell
& 'C:/xampp/mysql/bin/mysqld.exe' '--defaults-file=C:/DevFlow-MariaDB-10.4/my.ini' --console
```

Confira no `netstat -ano -p tcp` que a única escuta na porta `3307` é `127.0.0.1:3307` e que o PID corresponde ao arquivo `mariadb.pid` da instância dedicada. Para desligá-la, use o comando abaixo e digite a senha administrativa no prompt local, ou pressione `Ctrl+C` somente no terminal que iniciou essa instância. Reconfirme que a porta `3307` deixou de escutar.

```powershell
& 'C:/xampp/mysql/bin/mysqladmin.exe' --no-defaults --protocol=TCP `
    --host=127.0.0.1 --port=3307 --user=root --password --shutdown-timeout=30 shutdown
```

### Instalação e configuração local

Execute o bloco na raiz do repositório, em um PowerShell local. Digite a senha administrativa existente e duas senhas distintas, com pelo menos 16 bytes cada, para as contas de aplicação. A entrada é mascarada e o JSON segue diretamente por stdin ao processo PHP. Evite executar o procedimento em uma sessão com registro de comandos ou inspeção do fluxo de entrada. Mantenha as configurações geradas em uma pasta com acesso local restrito.

```powershell
& {
    $OutputEncoding = [Text.UTF8Encoding]::new($false)
    $entradas = @{}
    try {
        foreach ($item in @(
            @{ Chave='admin'; Prompt='Senha administrativa existente' }
            @{ Chave='app'; Prompt='Nova senha de devflow_app' }
            @{ Chave='testes'; Prompt='Nova senha de devflow_test_app' }
        )) {
            $segredo = Read-Host $item.Prompt -AsSecureString
            try {
                $credencial = [Net.NetworkCredential]::new('', $segredo)
                $entradas[$item.Chave] = $credencial.Password
            } finally {
                $segredo.Dispose()
                $credencial = $null
            }
        }
        $entradas | ConvertTo-Json -Compress |
            & 'C:/xampp/php/php.exe' database/provisionar.php
        if ($LASTEXITCODE -ne 0) { throw 'Provisionamento interrompido.' }
    } finally {
        $entradas.Clear()
    }
}
```

O instalador exige a versão, porta, bind, datadir e conta administrativa do ambiente dedicado antes de qualquer DDL. Confere colisões de bancos, contas e arquivos locais. Aplica o schema e executa os testes funcionais primeiro em `devflow_test`. Só depois cria `devflow` vazio e verifica as permissões dos dois usuários. Cada conta recebe apenas `SELECT`, `INSERT`, `UPDATE` e `DELETE` nas próprias tabelas `projetos` e `tarefas`. O administrador é usado apenas durante a instalação.

O provisionador e as conexões de aplicação e testes exigem `STRICT_TRANS_TABLES` ou `STRICT_ALL_TABLES` em `@@SESSION.sql_mode`. A verificação apenas lê o modo da sessão. Se a condição falhar, revise o ambiente antes de prosseguir.

### Se o provisionamento parar

Uma falha após o primeiro DDL pode deixar bancos, tabelas, contas ou concessões criadas, mesmo com código de saída diferente de zero. Uma falha na etapa final pode deixar apenas um dos arquivos locais ou um arquivo incompleto. O instalador bloqueia colisões e preserva os objetos e arquivos existentes.

1. Anote a etapa sanitizada exibida no terminal e mantenha o estado existente.
2. Confirme a identidade da instância dedicada em `127.0.0.1:3307`. Em seguida, confira somente por leitura quais bancos, tabelas, contas e privilégios previstos já existem. Verifique a presença de `config/local.php` e `config/local.test.php` sem exibir seu conteúdo ou material de autenticação.
3. Compare o inventário com o schema e com a etapa reportada. Se houver estado parcial ou objetos de origem incerta, defina a recuperação antes de repetir. O procedimento não exclui nem sobrescreve automaticamente bancos, tabelas, contas ou arquivos.

### Reexecutar os testes

```powershell
& 'C:/xampp/php/php.exe' tests/persistencia.php
```

O executor abre apenas `devflow_test` com `devflow_test_app` na porta `3307`. Os registros sintéticos usam uma transação revertida no final. O teste de acesso cruzado tenta apenas uma leitura sem linhas voltadas a `devflow` e exige recusa. A sequência pode avançar mesmo após rollback porque o contador de IDs é gerenciado pelo banco.

## Rota de projetos

Após validar a configuração do VirtualHost dedicada, insira a regra abaixo depois da rejeição de `Host` e antes da regra geral de bloqueio de URLs. Use o mesmo procedimento de backup e validação do Apache descrito acima.

```apache
RewriteRule ^/api/projetos$ /api/index.php [PT,L]
```

Na Sprint 03.1, a integração passou em `devflow_test`. Após reiniciar o Apache, o cadastro feito no navegador retornou HTTP 201 no log, apareceu na página e foi confirmado por uma nova consulta HTTP 200. Os caminhos privados testados permaneceram bloqueados em HTTP e HTTPS. A entrada pública da UC4 manteve HTTP 200.

### Resultado do incremento 02.3

O provisionamento manual confirmou a identidade da instância dedicada e a disponibilidade dos nomes. A saída local registrou duas validações bem-sucedidas da persistência e a criação de `devflow` sem dados de teste. O teste independente foi confirmado pelo responsável pela execução local. O provisionamento não foi repetido para este registro.

`config/local.php` e `config/local.test.php` existem como arquivos regulares e herdam acesso somente para o usuário responsável, SYSTEM e Administradores locais. O Git ignora os dois caminhos. A conferência usou apenas metadados e DACLs.
