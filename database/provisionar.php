<?php

declare(strict_types=1);

use DevFlow\Infra\Conexao;
use function DevFlow\Tests\exigirRecusa;
use function DevFlow\Tests\testarPersistencia;
use function DevFlow\Tests\verificarPrivilegios;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

ini_set('display_errors', '0');
ini_set('log_errors', '0');
require_once dirname(__DIR__) . '/tests/persistencia.php';

function criarBanco(PDO $admin, string $banco, string $usuario, string $senha, array $schema): void
{
    $admin->exec("CREATE DATABASE $banco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $admin->exec("USE $banco");
    foreach ($schema as $instrucao) {
        $admin->exec($instrucao);
    }

    $verificador = '*' . strtoupper(sha1(sha1($senha, true)));
    $conta = "'$usuario'@'127.0.0.1'";
    $admin->exec("CREATE USER $conta IDENTIFIED VIA mysql_native_password USING '$verificador'");
    foreach (['projetos', 'tarefas'] as $tabela) {
        $admin->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON $banco.$tabela TO $conta");
    }
}

function gravarConfiguracao(string $caminho, array $config): void
{
    $arquivo = @fopen($caminho, 'xb');
    if ($arquivo === false) {
        throw new RuntimeException('Não foi possível criar a configuração local.');
    }
    try {
        $conteudo = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        if (fwrite($arquivo, $conteudo) !== strlen($conteudo)) {
            throw new RuntimeException('Gravação incompleta da configuração local.');
        }
    } finally {
        fclose($arquivo);
    }
}

$etapa = 'entrada local e pré-condições';
try {
    $segredos = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($segredos)
        || !is_string($segredos['admin'] ?? null) || $segredos['admin'] === ''
        || !is_string($segredos['app'] ?? null) || strlen($segredos['app']) < 16
        || !is_string($segredos['testes'] ?? null) || strlen($segredos['testes']) < 16
        || count(array_unique([$segredos['admin'], $segredos['app'], $segredos['testes']])) !== 3
        || in_array('SENHA_FICTICIA_APENAS_EXEMPLO', $segredos, true)) {
        throw new RuntimeException('Entradas inválidas.');
    }

    $diretorio = dirname(__DIR__) . '/config/';
    foreach (['local.php', 'local.test.php'] as $arquivo) {
        if (file_exists($diretorio . $arquivo) || is_link($diretorio . $arquivo)) {
            throw new RuntimeException('Já existe configuração local.');
        }
    }
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('Schema indisponível.');
    }
    $schema = array_values(array_filter(array_map('trim', explode(';', $sql))));
    if (count($schema) !== 2) {
        throw new RuntimeException('Estrutura do arquivo de schema inesperada.');
    }

    $etapa = 'identidade da instância dedicada';
    $admin = new PDO(
        'mysql:host=127.0.0.1;port=3307;charset=utf8mb4',
        'root',
        $segredos['admin'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]
    );
    $identidade = $admin->query(
        'SELECT VERSION() AS versao, @@port AS porta, @@bind_address AS escuta,
                @@datadir AS dados, @@named_pipe AS pipe, CURRENT_USER() AS conta,
                @@general_log AS log_geral, @@slow_query_log AS log_lento, @@log_bin AS binlog,
                @@innodb_strict_mode AS innodb_estrito, @@check_constraint_checks AS checks,
                @@foreign_key_checks AS chaves, @@SESSION.sql_mode AS modo_sql'
    )->fetch();
    $dados = rtrim(str_replace('\\', '/', $identidade['dados']), '/');
    if (!preg_match('/^10\.4\.32-MariaDB(?:[-+]|$)/i', $identidade['versao'])
        || (int) $identidade['porta'] !== 3307
        || $identidade['escuta'] !== '127.0.0.1'
        || strcasecmp($dados, 'C:/DevFlow-MariaDB-10.4/data') !== 0
        || $identidade['conta'] !== 'root@127.0.0.1'
        || (int) $identidade['pipe'] !== 0
        || (int) $identidade['log_geral'] !== 0
        || (int) $identidade['log_lento'] !== 0
        || (int) $identidade['binlog'] !== 0
        || (int) $identidade['innodb_estrito'] !== 1
        || (int) $identidade['checks'] !== 1
        || (int) $identidade['chaves'] !== 1) {
        throw new RuntimeException('Servidor fora das condições previstas.');
    }

    $etapa = 'modo SQL estrito da sessão administrativa';
    if (!Conexao::modoSqlEstrito($identidade['modo_sql'])) {
        throw new RuntimeException('A instalação exige modo SQL estrito na sessão.');
    }

    $etapa = 'checagem de colisões';
    $bancos = $admin->prepare(
        'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE LOWER(SCHEMA_NAME) IN (?, ?)'
    );
    $bancos->execute(['devflow', 'devflow_test']);
    $contas = $admin->prepare('SELECT COUNT(*) FROM mysql.global_priv WHERE LOWER(User) IN (?, ?)');
    $contas->execute(['devflow_app', 'devflow_test_app']);
    if ((int) $bancos->fetchColumn() !== 0 || (int) $contas->fetchColumn() !== 0) {
        throw new RuntimeException('Colisão de schema ou conta.');
    }
    echo "Instância dedicada confirmada e nomes disponíveis.\n";

    $desenvolvimento = require $diretorio . 'local.example.php';
    $testes = $desenvolvimento;
    $testes['app']['environment'] = 'testing';
    $testes['database']['name'] = 'devflow_test';
    $testes['database']['user'] = 'devflow_test_app';
    $testes['database']['password'] = $segredos['testes'];
    $desenvolvimento['database']['password'] = $segredos['app'];
    unset($segredos);

    $etapa = 'instalação e testes em devflow_test';
    criarBanco($admin, 'devflow_test', 'devflow_test_app', $testes['database']['password'], $schema);
    $pdoTestes = Conexao::abrir($testes['database'], true);
    testarPersistencia($pdoTestes, false);

    $etapa = 'instalação vazia em devflow';
    criarBanco($admin, 'devflow', 'devflow_app', $desenvolvimento['database']['password'], $schema);
    $pdoApp = Conexao::abrir($desenvolvimento['database']);
    verificarPrivilegios($pdoApp, 'devflow', 'devflow_app');
    exigirRecusa(
        static fn () => $pdoApp->query('SELECT id FROM devflow_test.projetos LIMIT 0'),
        [1044, 1142],
        'Leitura cruzada pela conta de desenvolvimento.'
    );
    exigirRecusa(
        static fn () => $pdoApp->exec('CREATE TEMPORARY TABLE devflow_prova_ddl (id INT)'),
        [1044, 1142],
        'DDL pela conta de desenvolvimento.'
    );
    $etapa = 'validação final de permissões e persistência';
    testarPersistencia($pdoTestes);

    $etapa = 'gravação das configurações ignoradas pelo Git';
    gravarConfiguracao($diretorio . 'local.php', $desenvolvimento);
    gravarConfiguracao($diretorio . 'local.test.php', $testes);
    echo "Provisionamento concluído. O banco devflow foi criado sem dados de teste.\n";
} catch (Throwable $erro) {
    $codigo = $erro instanceof PDOException ? (int) ($erro->errorInfo[1] ?? 0) : 0;
    $detalhe = $codigo ? " Código MariaDB: $codigo." : '';
    fwrite(STDERR, "Provisionamento interrompido na etapa: $etapa.$detalhe Verifique o estado antes de repetir.\n");
    exit(1);
}
