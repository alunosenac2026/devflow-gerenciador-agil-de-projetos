# Instalação e uso do DevFlow no Windows

[Visão geral do projeto](../README.md) · [Ambiente de referência e histórico](ambiente.md)

Este guia usa Windows x64, XAMPP 8.2.12, Apache 2.4.58, PHP 8.2.12 e MariaDB 10.4.32. A aplicação é acadêmica, local e individual. `127.0.0.1` é o endereço do próprio computador. O formulário do DevFlow funciona sem autenticação de usuários por decisão de escopo. O MariaDB tem autenticação própria.

O ambiente de referência foi provisionado, usado e validado. Este roteiro reúne esse procedimento e as verificações de compatibilidade dos comandos. A instalação completa em outro computador ainda não foi testada do início ao fim. O modelo de `my.ini` abaixo é destinado a uma instância nova.

- [Primeira instalação](#primeira-instalação)
- [Utilização cotidiana](#utilização-cotidiana)
- [Verificação opcional de segurança](#verificação-opcional-de-segurança)

## Primeira instalação

Use o **Windows PowerShell 5.1** e mantenha a mesma janela para as variáveis dos exemplos. Use a mesma conta do Windows que iniciará o Apache pelo painel e o MariaDB pelo terminal. Os exemplos adotam `C:\xampp` e guardam o código fora de `htdocs`. Se o XAMPP compatível estiver em outra raiz, ajuste `$xampp`, os caminhos dessa raiz no `my.ini` e os comandos com caminhos literais. Para uma máquina já provisionada, siga o uso cotidiano.

### 1. Reunir os pré-requisitos

No Windows PowerShell, em qualquer diretório, confirme o sistema e o terminal:

```powershell
[Environment]::Is64BitOperatingSystem
$PSVersionTable.PSVersion
```

Use Windows x64, Windows PowerShell 5.1, um navegador atualizado e uma conta com permissão para preparar as pastas locais. O XAMPP fornece PHP, Apache, MariaDB e os clientes de linha de comando. Git para Windows é opcional se usar ZIP. Se o sistema ou a edição do PowerShell divergir, confirme a compatibilidade antes de aplicar os comandos de ACL.

[Apache Friends — XAMPP](https://www.apachefriends.org/download.html) · [Windows PowerShell](https://learn.microsoft.com/en-us/powershell/scripting/windows-powershell/starting-windows-powershell?view=powershell-7.2) · [Git para Windows](https://git-scm.com/install/windows) · [Windows e NTFS](https://learn.microsoft.com/windows-server/administration/windows-commands/icacls)

### 2. Instalar o XAMPP compatível

No navegador, abra os [arquivos oficiais do XAMPP 8.2.12 para Windows](https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/) e baixe `xampp-windows-x64-8.2.12-0-VS16-installer.exe`. Execute o instalador pelo Explorador e selecione `C:\xampp`, com os componentes Apache, PHP e MySQL do pacote. A opção **MySQL** do painel controla a instância compartilhada, separada do DevFlow. Deixe os serviços parados durante a preparação.

No PowerShell, em qualquer diretório:

```powershell
$xampp = 'C:\xampp'
$php = Join-Path $xampp 'php\php.exe'
$mysqlBin = Join-Path $xampp 'mysql\bin'
$httpd = Join-Path $xampp 'apache\bin\httpd.exe'
Test-Path $php, $httpd, (Join-Path $mysqlBin 'mysql_install_db.exe')
```

Espere três resultados `True`. Se já houver XAMPP nessa pasta, confira as versões na etapa 4. Se houver conflito de versão, instalação existente ou aviso do antivírus, pare antes de sobrescrever arquivos ou alterar a proteção do Windows.

### 3. Obter o código-fonte

No PowerShell, em qualquer diretório:

```powershell
$projetos = Join-Path $env:USERPROFILE 'Projetos'
New-Item -ItemType Directory -Force -Path $projetos | Out-Null
Set-Location -LiteralPath $projetos
```

- **Git.** Execute `git clone https://github.com/alunosenac2026/devflow-gerenciador-agil-de-projetos.git`.
- **ZIP.** No [repositório](https://github.com/alunosenac2026/devflow-gerenciador-agil-de-projetos), escolha **Code → Download ZIP**. Extraia pelo Explorador na pasta `Projetos` e renomeie a pasta extraída para `devflow-gerenciador-agil-de-projetos`.

```powershell
$checkout = Join-Path $projetos 'devflow-gerenciador-agil-de-projetos'
Set-Location -LiteralPath $checkout
Test-Path .\README.md, .\public\index.php, .\database\schema.sql, .\database\provisionar.php
```

Espere quatro resultados `True`. Se aparecer `False`, procure a pasta que contém todos os arquivos. Em uma extração ZIP, ela pode estar um nível abaixo da pasta indicada.

### 4. Verificar versões e extensões

No PowerShell, na raiz do DevFlow:

```powershell
& $php -v
& $php -m | Select-String '^(PDO|pdo_mysql|mbstring)$'
& $httpd -v
& (Join-Path $mysqlBin 'mysqld.exe') --no-defaults --version
& (Join-Path $mysqlBin 'mysql_install_db.exe') --help
```

Confira PHP `8.2.12`, as três extensões, Apache `2.4.58`, MariaDB `10.4.32` e a opção `--datadir` na ajuda do inicializador. A verificação PHP aqui é da CLI. O acesso ao aplicativo na etapa 17 verifica a execução sob o Apache. Se faltar uma extensão ou programa, confirme o pacote e o caminho do XAMPP. O provisionador exige MariaDB 10.4.32.

### 5. Conferir as portas

No PowerShell, em qualquer diretório:

```powershell
netstat.exe -ano -p tcp | Select-String ':(80|443|8082|3307)\s+\S+:0\s+\S+\s+\d+\s*$'
```

Na primeira instalação, espere as portas 8082 e 3307 livres. O Apache do XAMPP também usa as portas 80 e 443 na configuração de referência. Se houver escuta, identifique o serviço pelo PID exibido, por exemplo com `Get-Process -Id 1234`, usando o PID encontrado. Pare se houver conflito com outro serviço. A instância compartilhada na porta 3306 pode continuar em execução.

### 6. Preparar o diretório dedicado e restringir o acesso

No PowerShell local, em qualquer diretório, crie uma pasta **nova**, fora do checkout e do XAMPP. Use o caminho abaixo porque o provisionador valida o diretório de dados de forma explícita.

```powershell
$instancia = 'C:\DevFlow-MariaDB-10.4'
$dados = Join-Path $instancia 'data'
$ini = Join-Path $instancia 'my.ini'
if (Test-Path -LiteralPath $instancia) { throw 'A pasta dedicada ja existe. Verifique a instalacao antes de continuar.' }

$usuarioWindows = [Security.Principal.WindowsIdentity]::GetCurrent().User.Value
$aclPrivada = [Security.AccessControl.DirectorySecurity]::new()
$aclPrivada.SetAccessRuleProtection($true, $false)
foreach ($sid in @($usuarioWindows, 'S-1-5-18', 'S-1-5-32-544') | Select-Object -Unique) {
    $identidade = [Security.Principal.SecurityIdentifier]::new($sid)
    $regra = [Security.AccessControl.FileSystemAccessRule]::new(
        $identidade, 'FullControl', 'ContainerInherit,ObjectInherit', 'None', 'Allow'
    )
    $aclPrivada.AddAccessRule($regra)
}
[void][IO.Directory]::CreateDirectory($instancia, $aclPrivada)
New-Item -ItemType Directory -Path (Join-Path $instancia 'logs'), (Join-Path $instancia 'tmp') | Out-Null
icacls.exe $instancia
```

Espere a pasta protegida para o usuário responsável, SYSTEM e Administradores locais, com herança para as pastas filhas. Se o Windows recusar a criação em `C:\${b}, solicite ao administrador a criação dessa pasta com acesso para a conta que vai executar o DevFlow. Interrompa se encontrar arquivos existentes, junções, links ou permissões para grupos amplos. Preserve os dados de instalações anteriores.

### 7. Criar o my.ini dedicado

No PowerShell, em qualquer diretório, execute `notepad.exe $ini`. Salve como `C:\DevFlow-MariaDB-10.4\my.ini`, tipo **Todos os arquivos**, com o conteúdo abaixo. As opções de diretório pressupõem o XAMPP em `C:\xampp`. Para outra raiz do XAMPP, ajuste `basedir` e `lc-messages-dir`.

```ini
[mysqld]
basedir=C:/xampp/mysql
lc-messages-dir=C:/xampp/mysql/share
datadir=C:/DevFlow-MariaDB-10.4/data
tmpdir=C:/DevFlow-MariaDB-10.4/tmp
pid-file=C:/DevFlow-MariaDB-10.4/mariadb.pid
log-error=C:/DevFlow-MariaDB-10.4/logs/mariadb.err
bind-address=127.0.0.1
port=3307
named-pipe=OFF
skip-name-resolve
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
sql-mode=STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
general-log=OFF
slow-query-log=OFF
skip-log-bin
local-infile=OFF
secure-file-priv=C:/DevFlow-MariaDB-10.4/tmp
```

Confira o nome e a leitura das opções sem iniciar o servidor:

```powershell
Get-Item -LiteralPath $ini | Select-Object Name,DirectoryName
& (Join-Path $mysqlBin 'mysqld.exe') "--defaults-file=$ini" --print-defaults
```

Espere ver o arquivo `my.ini` e as opções da instância dedicada. Pare se o editor salvou `my.ini.txt`, se a porta for 3306, se a escuta indicar uma interface externa ou se os caminhos apontarem para a instância compartilhada.

### 8. Inicializar as tabelas de sistema

No PowerShell local, na raiz do DevFlow, confirme que `$dados` ainda está ausente e a porta 3307 continua livre. O inicializador do pacote cria as tabelas offline. Os parâmetros do próprio inicializador que configuram senha, serviço, acesso remoto, usuário padrão ou named pipe ficam de fora.

```powershell
if (Test-Path -LiteralPath $dados) { throw 'O diretorio de dados ja existe. Interrompa.' }
if (netstat.exe -ano -p tcp | Select-String ':3307\s+\S+:0\s+\S+\s+\d+\s*$') {
    throw 'A porta 3307 ja esta em escuta.'
}
& (Join-Path $mysqlBin 'mysql_install_db.exe') "--datadir=$dados" --port=3307
if ($LASTEXITCODE -ne 0) { throw 'Falha na inicializacao. Mantenha a instancia desligada.' }
if (!(Test-Path -LiteralPath (Join-Path $dados 'mysql') -PathType Container)) { throw 'Tabelas de sistema ausentes.' }
Set-Acl -LiteralPath $dados -AclObject $aclPrivada
icacls.exe $dados /T
netstat.exe -ano -p tcp | Select-String ':3307\s+\S+:0\s+\S+\s+\d+\s*$'
```

Espere o diretório `data\mysql`, nenhum listener em 3307 e ACLs limitadas às identidades da etapa 6. O código do inicializador do Windows pode acrescentar acesso de `NetworkService` ao diretório de dados para uso como serviço. Como a execução do DevFlow é interativa, o bloco aplica novamente a ACL restrita em `data`. Se a listagem ainda mostrar identidades além das previstas, permissões divergentes ou erro de acesso, pare. Revise a pasta nova antes de repetir qualquer operação.

O inicializador também gera `data\my.ini`. As etapas seguintes usam explicitamente `C:\DevFlow-MariaDB-10.4\my.ini` como primeiro argumento. O utilitário pode limpar a pasta de destino se falhar, por isso o destino deve ser novo e exclusivo. [Documentação MariaDB para Windows](https://mariadb.com/docs/server/server-management/install-and-upgrade-mariadb/installing-mariadb/installing-system-tables-mariadb-install-db/mariadb-install-db-exe) · [Código do inicializador 10.4.32](https://github.com/MariaDB/server/blob/mariadb-10.4.32/sql/mysql_install_db.cc)

### 9. Proteger a conta administrativa offline

Use o mesmo PowerShell local, na raiz do DevFlow, com a instância dedicada ainda desligada. Cole os blocos desta etapa na janela local. O modo `--bootstrap` recebe SQL por entrada padrão e encerra ao terminar. O bootstrap desta versão ocorre sem TCP e sem named pipe. O `--console` da versão Windows redireciona as mensagens para a saída do processo, capturada em memória pelo bloco. A confirmação exige saída sem erro, código de saída zero e marcador gravado.

```powershell
function Invoke-DevFlowBootstrap([string]$sql) {
    if ($ini -ne 'C:\DevFlow-MariaDB-10.4\my.ini' -or !(Test-Path -LiteralPath (Join-Path $dados 'mysql'))) {
        throw 'Caminho dedicado ou tabelas de sistema divergentes.'
    }
    if (netstat.exe -ano -p tcp | Select-String ':3307\s+\S+:0\s+\S+\s+\d+\s*$') {
        throw 'A porta 3307 ja esta em escuta.'
    }
    $marca = Join-Path $instancia ('tmp\ok-' + [Guid]::NewGuid().ToString('N') + '.txt')
    $marcaSql = $marca.Replace('\', '/')
    $processo = [Diagnostics.Process]::new()
    $processo.StartInfo.FileName = Join-Path $mysqlBin 'mysqld.exe'
    $processo.StartInfo.Arguments = "--defaults-file=$ini --bootstrap --skip-networking --named-pipe=OFF --general-log=OFF --slow-query-log=OFF --skip-log-bin --console"
    $processo.StartInfo.WorkingDirectory = $instancia
    $processo.StartInfo.UseShellExecute = $false
    $processo.StartInfo.CreateNoWindow = $true
    $processo.StartInfo.RedirectStandardInput = $true
    $processo.StartInfo.RedirectStandardOutput = $true
    $processo.StartInfo.RedirectStandardError = $true
    $iniciado = $false
    try {
        $iniciado = $processo.Start()
        if (!$iniciado) { throw 'O bootstrap nao iniciou.' }
        $saida = $processo.StandardOutput.ReadToEndAsync()
        $erros = $processo.StandardError.ReadToEndAsync()
        $processo.StandardInput.Write("$sql`nSELECT 1 INTO OUTFILE '$marcaSql';`n-- fim.")
        $processo.StandardInput.Close()
        if (!$processo.WaitForExit(60000)) { throw 'Tempo limite do bootstrap.' }
        if ($processo.ExitCode -ne 0 -or ($saida.Result + "`n" + $erros.Result) -match '(?im)\[ERROR\]|\bERROR\s+[0-9]{4}\b|^ERROR\b|\bAborting\b' -or
            !(Test-Path -LiteralPath $marca) -or (Get-Content -LiteralPath $marca -Raw).Trim() -ne '1') {
            throw 'Bootstrap sem confirmacao. Preserve a instancia offline para diagnostico.'
        }
    } finally {
        if ($iniciado -and !$processo.HasExited) {
            $processo.Kill()
            [void]$processo.WaitForExit(5000)
        }
        $processo.Dispose()
        if (Test-Path -LiteralPath $marca) { Remove-Item -LiteralPath $marca }
    }
}

function Get-DevFlowInventario([string]$comparar = '') {
    $inventario = Join-Path $instancia ('tmp\contas-' + [Guid]::NewGuid().ToString('N') + '.tsv')
    $inventarioSql = $inventario.Replace('\', '/')
    $confere = '0'
    if ($comparar) {
        if ($comparar -cnotmatch '^\*[0-9A-F]{40}$') { throw 'Formato de verificacao inesperado.' }
        $confere = "IF(BINARY JSON_VALUE(Priv, '$.authentication_string') = BINARY '$comparar', 1, 0)"
    }
    try {
        Invoke-DevFlowBootstrap @"
SELECT CONCAT(User,'@',Host), COALESCE(JSON_VALUE(Priv,'$.plugin'),'-'),
  IF(LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(Priv,'$.account_locked')),'false')) IN ('true','1'),1,0),
  IF(COALESCE(JSON_VALUE(Priv,'$.authentication_string'),'') NOT IN ('','invalid'),1,0),
  COALESCE(JSON_UNQUOTE(JSON_EXTRACT(Priv,'$.access')),'-'),
  IF(User='root' AND Host='127.0.0.1',$confere,0)
INTO OUTFILE '$inventarioSql' FROM mysql.global_priv ORDER BY User,Host;
"@
        Get-Content -LiteralPath $inventario
    } finally {
        if (Test-Path -LiteralPath $inventario) { Remove-Item -LiteralPath $inventario }
    }
}
```

Agora confira o inventário e configure a senha. Prefira uma senha administrativa longa com caracteres ASCII para evitar divergências de codificação no cliente Windows. A comparação offline retorna somente um indicador de correspondência. O verificador fica em memória e segue por entrada padrão. Use uma sessão local sem transcrição, gravação da entrada ou depuração do bloco. Depois do inventário, os comandos nativos `ALTER USER` e `DROP USER` atuam somente nas contas administrativas identificadas. A conta interna `mariadb.sys@localhost` permanece no inventário.

```powershell
$hostInicial = $env:COMPUTERNAME.ToLowerInvariant()
if ($hostInicial -cnotmatch '^[a-z0-9-]+$' -or $hostInicial -eq 'localhost') {
    throw 'Nome de computador fora do roteiro. Confira o inventario antes de prosseguir.'
}
$esperadas = @('mariadb.sys@localhost','root@127.0.0.1','root@::1','root@localhost',"root@$hostInicial") | Sort-Object
$antes = @(Get-DevFlowInventario)
$ids = @($antes | ForEach-Object { ($_ -split "`t")[0] } | Sort-Object)
if (($ids -join '|') -cne ($esperadas -join '|')) { throw 'Inventario inicial inesperado.' }
$rootInicial = @($antes | Where-Object { $_.StartsWith("root@127.0.0.1`t") })[0] -split "`t"
$sysInicial = @($antes | Where-Object { $_.StartsWith("mariadb.sys@localhost`t") })[0]
if ($rootInicial[2] -ne '0' -or $rootInicial[3] -ne '0' -or $rootInicial[4] -ne '18446744073709551615' -or
    ($sysInicial -split "`t")[2] -ne '1') { throw 'Estado inicial das contas administrativas divergente.' }

$segredo = Read-Host 'Nova senha administrativa do MariaDB dedicado' -AsSecureString
$sha = [Security.Cryptography.SHA1]::Create()
$bytes = $primeiro = $segundo = $verificador = $null
try {
    $bytes = [Text.Encoding]::UTF8.GetBytes([Net.NetworkCredential]::new('', $segredo).Password)
    if ($bytes.Length -lt 16) { throw 'Escolha uma senha administrativa de pelo menos 16 bytes.' }
    $primeiro = $sha.ComputeHash($bytes)
    $segundo = $sha.ComputeHash($primeiro)
    $verificador = '*' + [BitConverter]::ToString($segundo).Replace('-', '')

    Invoke-DevFlowBootstrap "ALTER USER 'root'@'127.0.0.1' IDENTIFIED VIA mysql_native_password USING '$verificador';"
    $depois = @(Get-DevFlowInventario $verificador)
    $idsDepois = @($depois | ForEach-Object { ($_ -split "`t")[0] } | Sort-Object)
    $rootDepois = @($depois | Where-Object { $_.StartsWith("root@127.0.0.1`t") })[0] -split "`t"
    if (($idsDepois -join '|') -cne ($esperadas -join '|') -or
        $rootDepois[1] -ne 'mysql_native_password' -or $rootDepois[2] -ne '0' -or
        $rootDepois[3] -ne '1' -or $rootDepois[4] -ne $rootInicial[4] -or $rootDepois[5] -ne '1' -or
        $sysInicial -cnotin $depois) { throw 'Credencial, privilegios ou inventario sem confirmacao.' }

    Invoke-DevFlowBootstrap "DROP USER 'root'@'localhost', 'root'@'::1', 'root'@'$hostInicial';"
    $final = @(Get-DevFlowInventario $verificador)
    if ($final.Count -ne 2 -or $sysInicial -cnotin $final -or ($rootDepois -join "`t") -cnotin $final) {
        throw 'Inventario final divergente. Mantenha a instancia offline.'
    }
    'Bootstrap concluido. Restaram root@127.0.0.1 e mariadb.sys@localhost.'
} finally {
    if ($null -ne $bytes) { [Array]::Clear($bytes, 0, $bytes.Length) }
    if ($null -ne $primeiro) { [Array]::Clear($primeiro, 0, $primeiro.Length) }
    if ($null -ne $segundo) { [Array]::Clear($segundo, 0, $segundo.Length) }
    $verificador = $null
    $segredo.Dispose()
    $sha.Dispose()
}
```

Espere a mensagem de conclusão e a porta 3307 ainda livre. As contas adicionais de `root` só são removidas após confirmar a credencial e a preservação dos privilégios. Se qualquer guarda falhar, mantenha a instância offline. Um `ALTER USER` bem-sucedido pode ter persistido mesmo se uma etapa posterior falhar. Preserve os arquivos e revise o inventário antes de qualquer nova tentativa. Em caso de timeout, a interrupção do bloco se limita ao processo que ele iniciou.

O ambiente de referência confirmou o bootstrap offline e a autenticação administrativa após a primeira partida. A sequência didática acima foi revisada por inspeção e ainda precisa de execução integral em uma instalação limpa. [Contas iniciais do MariaDB 10.4.32](https://github.com/MariaDB/server/blob/mariadb-10.4.32/scripts/mysql_system_tables_data.sql) · [ALTER USER](https://mariadb.com/docs/server/reference/sql-statements/account-management-sql-statements/alter-user) · [Autenticação nativa](https://mariadb.com/docs/server/reference/plugins/authentication-plugins/authentication-plugin-mysql_native_password)

### 10. Restringir a pasta de configuração do aplicativo

No PowerShell local, na raiz de uma instalação nova do DevFlow, confirme a identidade Windows do operador do Apache. Para o painel executado interativamente, use a mesma conta da etapa 6. Servir pelo Apache como serviço exige identificar sua conta e conceder leitura apenas a ela, quando diferente. O guia assume a execução interativa.

```powershell
$configAplicacao = Join-Path $checkout 'config'
if ((Get-Item -LiteralPath $configAplicacao).Attributes -band [IO.FileAttributes]::ReparsePoint) {
    throw 'Pasta config em link ou juncao.'
}
if ((Test-Path -LiteralPath (Join-Path $configAplicacao 'local.php')) -or
    (Test-Path -LiteralPath (Join-Path $configAplicacao 'local.test.php'))) {
    throw 'Configuracao existente. Preserve os arquivos e revise a ACL antes de prosseguir.'
}
Set-Acl -LiteralPath $configAplicacao -AclObject $aclPrivada
icacls.exe $configAplicacao
```

Espere acesso restrito ao usuário responsável, SYSTEM e Administradores locais. Pare se grupos amplos ainda tiverem acesso ou se o usuário do Apache não conseguir ler os arquivos herdados. A etapa 14 confere as permissões dos dois arquivos criados. [Referência do icacls](https://learn.microsoft.com/windows-server/administration/windows-commands/icacls)

### 11. Configurar o VirtualHost Apache vigente

No PowerShell local, na raiz do DevFlow, mantenha o Apache parado durante a primeira preparação. Se outra aplicação estiver usando a mesma instância do Apache, combine a interrupção antes de alterar a configuração. Faça o backup do arquivo principal e preserve o arquivo dedicado caso ele já exista.

```powershell
$apacheRoot = Join-Path $xampp 'apache'
$principal = Join-Path $apacheRoot 'conf\httpd.conf'
$dedicado = Join-Path $apacheRoot 'conf\extra\httpd-devflow.conf'
if (Test-Path -LiteralPath $dedicado) { throw 'VirtualHost dedicado ja existe. Revise o arquivo antes de editar.' }
if (Select-String -LiteralPath $principal -Pattern '^\s*Include(?:Optional)?\s+.*httpd-devflow\.conf') {
    throw 'O arquivo principal ja carrega a configuracao dedicada.'
}
$backupDir = Join-Path $env:LOCALAPPDATA ('DevFlow-Apache-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
if (Test-Path -LiteralPath $backupDir) { throw 'Destino de backup ja existe.' }
[void][IO.Directory]::CreateDirectory($backupDir, $aclPrivada)
$backup = Join-Path $backupDir 'httpd.conf'
Copy-Item -LiteralPath $principal -Destination $backup
if ((Get-FileHash -LiteralPath $principal).Hash -ne (Get-FileHash -LiteralPath $backup).Hash) {
    throw 'SHA-256 do backup divergente.'
}
$checkout.Replace('\', '/')
notepad.exe $dedicado
```

Salve o arquivo com o nome `httpd-devflow.conf`, tipo **Todos os arquivos**. Substitua todas as ocorrências de `CAMINHO_DO_CHECKOUT` pelo caminho exibido, mantendo as aspas e as barras `/`. Use somente esta regra de roteamento de projetos e tarefas.

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

    RewriteRule ^/api/(?:projetos(?:/[^/]+(?:/tarefas)?)?|tarefas/[^/]+)$ /api/index.php [PT,L]

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

Abra o arquivo principal com `notepad.exe $principal`. Ao final, fora dos blocos existentes, acrescente uma única linha e preserve a codificação do arquivo.

```apache
Include "conf/extra/httpd-devflow.conf"
```

Ainda no PowerShell, valide os módulos, a sintaxe e os hosts:

```powershell
& $httpd -d $apacheRoot -f conf/httpd.conf -t
if ($LASTEXITCODE -ne 0) { throw 'Sintaxe invalida. Restaure o backup antes de iniciar o Apache.' }
& $httpd -d $apacheRoot -f conf/httpd.conf -M
& $httpd -d $apacheRoot -f conf/httpd.conf -S
```

Espere `Syntax OK`, os módulos `authz_core_module`, `authz_host_module`, `rewrite_module`, `dir_module`, `proxy_module` e `php_module`, além de `127.0.0.1:8082` em `httpd -S`. Os hosts existentes devem permanecer no resultado. Se a edição exigir permissão, peça ao administrador autorização para editar esses dois arquivos. Em caso de falha, com o Apache parado, use `Copy-Item -LiteralPath $backup -Destination $principal -Force`, confira o SHA-256 restaurado e valide novamente. O [procedimento técnico de reversão](ambiente.md#backup-validação-aplicação-e-reversão) detalha o caso de um servidor já em uso. [Referência do httpd](https://httpd.apache.org/docs/2.4/programs/httpd.html)

### 12. Iniciar e confirmar a instância dedicada

Abra **outro PowerShell** para o servidor, em qualquer diretório. Mantenha a janela aberta. A partida do servidor não solicita senha.

```powershell
& 'C:/xampp/mysql/bin/mysqld.exe' '--defaults-file=C:/DevFlow-MariaDB-10.4/my.ini' --console
```

Espere a mensagem de que o servidor está pronto para conexões. Na janela original, confira a escuta e a identidade da instância por uma conexão administrativa. O cliente solicita a senha definida na etapa 9.

```powershell
netstat.exe -ano -p tcp | Select-String ':3307\s+\S+:0\s+\S+\s+\d+\s*$'
Get-Content -LiteralPath (Join-Path $instancia 'mariadb.pid')
& (Join-Path $mysqlBin 'mysql.exe') --no-defaults --protocol=TCP --host=127.0.0.1 --port=3307 --user=root --password --batch --execute 'SELECT VERSION(), @@port, @@bind_address, @@datadir, @@named_pipe, CURRENT_USER(), @@SESSION.sql_mode;'
```

Espere somente `127.0.0.1:3307`, com o PID da instância dedicada. A consulta deve mostrar MariaDB 10.4.32, o diretório `C:/DevFlow-MariaDB-10.4/data`, named pipe igual a 0, `root@127.0.0.1` e modo SQL estrito. Pare se a escuta for externa, se houver outro PID, se a autenticação falhar ou se algum valor divergir. Em caso de erro de bind, confira a porta. Em caso de erro de acesso, confira o inventário da etapa 9 com a instância offline. A [verificação opcional sem senha](#verificação-opcional-de-segurança) também pode ser feita neste ponto.

### 13. Provisionar os bancos e as contas do DevFlow

No PowerShell original, na raiz do repositório, execute o bloco uma vez. Digite a senha administrativa existente e duas senhas distintas para as contas de aplicação, com pelo menos 16 bytes cada. As três entradas são mascaradas. O JSON segue ao PHP por entrada padrão.

```powershell
Set-Location -LiteralPath $checkout
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

O resultado esperado é a validação da persistência em `devflow_test` e a conclusão do provisionamento de `devflow` sem dados de teste. Pare se a identidade divergir ou se houver colisão de banco, conta ou arquivo. Uma falha pode deixar objetos já criados. Consulte [diagnóstico de provisionamento parcial](ambiente.md#se-o-provisionamento-parar) antes de repetir ou excluir qualquer artefato.

### 14. Verificar a configuração privada

No PowerShell, na raiz do checkout, confira metadados e permissões:

```powershell
Get-Item -LiteralPath '.\config\local.php','.\config\local.test.php' |
    Select-Object Name,Attributes
icacls.exe .\config\local.php
icacls.exe .\config\local.test.php
```

Se usou Git, execute `git check-ignore config/local.php config/local.test.php`. Espere os dois caminhos ignorados. Se usou ZIP, confirme que os arquivos continuam em `config/`, fora de `public/`. Ambos devem ser arquivos regulares com leitura restrita à conta responsável, SYSTEM, Administradores e ao Apache quando ele usar uma conta distinta. Pare se faltar um arquivo, se algum for link ou se houver acesso amplo. Use apenas metadados e ACLs nesta checagem.

### 15. Iniciar somente o Apache

No PowerShell, em qualquer diretório, execute `& (Join-Path $xampp 'xampp-control.exe')`. No painel XAMPP, clique em **Start** na linha **Apache**. O MariaDB dedicado já está no terminal da etapa 12.

```powershell
netstat.exe -ano -p tcp | Select-String ':(8082|3307)\s+\S+:0\s+\S+\s+\d+\s*$'
```

Espere **Running** no Apache e somente endereços `127.0.0.1` nas duas portas dedicadas. Se o painel parar logo após iniciar, rode `httpd -t` com os argumentos da etapa 11 e confira a disponibilidade de 80, 443 e 8082. Se alguma porta dedicada escutar externamente, interrompa o uso e revise a configuração.

### 16. Abrir o DevFlow

No navegador, acesse [http://127.0.0.1:8082/](http://127.0.0.1:8082/). No PowerShell, em qualquer diretório, você pode confirmar a página e a API sem imprimir seus conteúdos:

```powershell
curl.exe --noproxy '*' --silent --show-error --max-time 8 --output NUL --write-out '%{http_code} %{content_type}\n' http://127.0.0.1:8082/
curl.exe --noproxy '*' --silent --show-error --max-time 8 --output NUL --write-out '%{http_code} %{content_type}\n' http://127.0.0.1:8082/api/projetos
```

Espere HTTP 200 em HTML para a página e HTTP 200 em JSON para a API. Em `403`, confira se usou exatamente o endereço com `127.0.0.1:8082`. Em `500` na API, confira se o MariaDB dedicado está em execução, se o provisionamento terminou e se o Apache consegue ler a configuração privada. Se a conexão for recusada, confira o indicador **Running** e a escuta.

### 17. Fazer uma verificação básica

No navegador, crie um projeto descartável, selecione-o em **Tarefas do projeto** e crie uma tarefa. Use **Mover para → Concluído** e confirme em **Mover**. Espere 100% com uma tarefa concluída. Recarregue a página e confira o mesmo quadro. Exclua a tarefa e espere 0%. Depois exclua o projeto descartável.

Se a gravação falhar, a interface deve exibir uma mensagem e preservar os dados anteriores. Se o botão **Excluir** devolver conflito, confira se o projeto ainda tem tarefas. Se precisar executar integração, use o PowerShell da conta responsável, na raiz, e mantenha as escritas em `devflow_test`.

```powershell
& $php tests/persistencia.php
& $php tests/projetos.php --integracao
& $php tests/tarefas.php --integracao
```

Espere sucesso nas três suítes. Pare se alguma apontar destino inesperado, arquivo indisponível ou falha de banco. O [registro de testes](testes.md) reúne as evidências do ambiente de referência.

## Utilização cotidiana

1. **Abra o Windows PowerShell** pelo menu Iniciar. O VS Code, Codex, ChatGPT e MySQL Workbench são dispensáveis. Git é opcional quando o código foi obtido por ZIP.
2. **Inicie o MariaDB dedicado** com o comando abaixo. Ele não solicita senha na partida. Mantenha essa janela aberta enquanto usar a aplicação.
3. **Inicie somente o Apache** pelo XAMPP Control Panel. Abra [http://127.0.0.1:8082/](http://127.0.0.1:8082/). A autenticação no MariaDB ocorre durante a conexão. O PHP usa as credenciais de aplicação da configuração privada.
4. **Encerre o Apache** pelo botão **Stop** do painel após terminar o uso das aplicações que compartilham esse Apache. Para o MariaDB dedicado, use `mysqladmin` em outra janela e digite a senha administrativa quando solicitada. Confira que 8082 e 3307 deixaram de escutar. Preserve o MySQL ou MariaDB compartilhado.

Partida do MariaDB, em qualquer diretório:

```powershell
& 'C:/xampp/mysql/bin/mysqld.exe' '--defaults-file=C:/DevFlow-MariaDB-10.4/my.ini' --console
```

Encerramento administrativo, em outro PowerShell:

```powershell
& 'C:/xampp/mysql/bin/mysqladmin.exe' --no-defaults --protocol=TCP --host=127.0.0.1 --port=3307 --user=root --password --shutdown-timeout=30 shutdown
netstat.exe -ano -p tcp | Select-String ':(8082|3307)\s+\S+:0\s+\S+\s+\d+\s*$'
```

Se a porta continuar em escuta, confira o PID do servidor dedicado e a saída do comando antes de repetir. Se preferir, `Ctrl+C` no terminal do servidor dedicado solicita sua interrupção. Aguarde o retorno ao prompt e confira a porta.

## Verificação opcional de segurança

Com o MariaDB dedicado em execução e a escuta local confirmada, abra outro PowerShell, em qualquer diretório, e execute:

```powershell
& 'C:/xampp/mysql/bin/mysql.exe' --no-defaults --protocol=TCP --host=127.0.0.1 --port=3307 --user=root --skip-password
```

O resultado esperado é `ERROR 1045 (28000): Access denied`. Essa rejeição foi confirmada em **25/11/2026** no ambiente de referência, conforme registro do responsável. Conexão recusada ou timeout indicam ausência de resposta, sem validar a autenticação. Se o cliente abrir um prompt de SQL, saia com `exit` e interrompa o uso da instância para revisar a conta.

O teste demonstra a rejeição dessa tentativa local sem senha. A proteção também depende das interfaces de escuta, das permissões NTFS, dos privilégios das contas e do isolamento HTTP/HTTPS. A verificação a partir de outro dispositivo da rede permanece pendente. [Evidências do ambiente de referência](ambiente.md)
