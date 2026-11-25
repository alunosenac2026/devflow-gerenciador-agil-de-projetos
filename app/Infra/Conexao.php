<?php

declare(strict_types=1);

namespace DevFlow\Infra;

use PDO;
use PDOException;
use RuntimeException;

final class Conexao
{
    public static function modoSqlEstrito(string $modo): bool
    {
        $modos = array_map('trim', explode(',', $modo));
        return in_array('STRICT_TRANS_TABLES', $modos, true)
            || in_array('STRICT_ALL_TABLES', $modos, true);
    }

    private static function configuracaoLocal(bool $testes): array
    {
        static $configuracoes = [];
        $arquivo = dirname(__DIR__, 2) . '/config/' . ($testes ? 'local.test.php' : 'local.php');

        if (!is_file($arquivo) || !is_readable($arquivo)) {
            throw new RuntimeException('Configuração local indisponível.');
        }
        return $configuracoes[$arquivo] ??= require $arquivo;
    }

    public static function origemLocal(bool $testes = false): string
    {
        try {
            $origem = self::configuracaoLocal($testes)['app']['origin'] ?? null;
        } catch (\Throwable) {
            throw new RuntimeException('Configuração local indisponível.');
        }
        if (!is_string($origem) || !filter_var($origem, FILTER_VALIDATE_URL)
            || !preg_match('~\Ahttps?://(?:127\.0\.0\.1|localhost|\[::1\])(?::[1-9][0-9]{0,4})?\z~', $origem)) {
            throw new RuntimeException('Origem local inválida.');
        }
        return $origem;
    }

    public static function local(bool $testes = false): PDO
    {
        try {
            return self::abrir(self::configuracaoLocal($testes)['database'] ?? [], $testes);
        } catch (\Throwable) {
            throw new RuntimeException('Não foi possível abrir a conexão local do DevFlow.');
        }
    }

    public static function abrir(array $config, bool $testes = false): PDO
    {
        $banco = $testes ? 'devflow_test' : 'devflow';
        $usuario = $testes ? 'devflow_test_app' : 'devflow_app';
        $senha = $config['password'] ?? null;

        if (($config['host'] ?? null) !== '127.0.0.1'
            || ($config['port'] ?? null) !== 3307
            || ($config['name'] ?? null) !== $banco
            || ($config['user'] ?? null) !== $usuario
            || ($config['charset'] ?? null) !== 'utf8mb4'
            || !is_string($senha) || $senha === '' || $senha === 'SENHA_FICTICIA_APENAS_EXEMPLO') {
            throw new RuntimeException('Configuração local do banco inválida.');
        }

        try {
            $pdo = new PDO(
                "mysql:host=127.0.0.1;port=3307;dbname=$banco;charset=utf8mb4",
                $usuario,
                $senha,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                ]
            );
            $identidade = $pdo->query(
                'SELECT VERSION() AS versao, @@port AS porta, @@bind_address AS escuta,
                        @@named_pipe AS pipe, DATABASE() AS banco, CURRENT_USER() AS conta,
                        @@character_set_connection AS charset,
                        @@foreign_key_checks AS chaves, @@check_constraint_checks AS checks,
                        @@SESSION.sql_mode AS modo_sql'
            )->fetch();
        } catch (PDOException) {
            throw new RuntimeException('Não foi possível abrir a conexão local do DevFlow.');
        }

        if (!preg_match('/^10\.4\.32-MariaDB(?:[-+]|$)/i', $identidade['versao'])
            || (int) $identidade['porta'] !== 3307
            || $identidade['escuta'] !== '127.0.0.1'
            || (int) $identidade['pipe'] !== 0
            || $identidade['banco'] !== $banco
            || $identidade['conta'] !== "$usuario@127.0.0.1"
            || $identidade['charset'] !== 'utf8mb4'
            || (int) $identidade['chaves'] !== 1
            || (int) $identidade['checks'] !== 1) {
            throw new RuntimeException('Identidade da conexão local inesperada.');
        }

        if (!self::modoSqlEstrito($identidade['modo_sql'])) {
            throw new RuntimeException('A conexão do DevFlow exige modo SQL estrito na sessão.');
        }

        return $pdo;
    }
}
