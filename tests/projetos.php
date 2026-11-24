<?php

declare(strict_types=1);

use DevFlow\Infra\Conexao;
use function DevFlow\Projetos\atenderProjetos;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require_once dirname(__DIR__) . '/app/Infra/Conexao.php';
require_once dirname(__DIR__) . '/app/Projetos/ApiProjetos.php';

function exigirProjeto(bool $condicao, string $rotulo): void
{
    if (!$condicao) {
        throw new RuntimeException($rotulo);
    }
}

function verificarContratoProjetos(): void
{
    $abrir = static function (): PDO {
        throw new RuntimeException('Uma requisição inválida tentou acessar o banco.');
    };
    $origem = 'http://127.0.0.1:8082';
    $json = 'application/json';
    $rota = '/api/projetos';
    $casos = [
        ['GET', '/outra', null, '', '', 404, null],
        ['DELETE', $rota, $origem, '', '', 405, null],
        ['POST', $rota, null, $json, '{}', 403, null],
        ['POST', $rota, 'null', $json, '{}', 403, null],
        ['POST', $rota, 'http://localhost:8082', $json, '{}', 403, null],
        ['POST', $rota, $origem, '', '{}', 415, null],
        ['POST', $rota, $origem, 'text/plain', '{}', 415, null],
        ['POST', $rota, $origem, 'application/json; charset=ISO-8859-1', '{}', 415, null],
        ['POST', $rota, $origem, 'application/json; charset=UTF-8', '{', 400, null],
        ['POST', $rota, $origem, 'Application/JSON; Charset="utf-8"', '{}', 422, 'nome'],
        ['POST', $rota, $origem, $json, '[]', 422, null],
        ['POST', $rota, $origem, $json, 'null', 422, null],
        ['POST', $rota, $origem, $json, '{"nome":null}', 422, 'nome'],
        ['POST', $rota, $origem, $json, '{"nome":12}', 422, 'nome'],
        ['POST', $rota, $origem, $json, '{"nome":" \u00a0 "}', 422, 'nome'],
        ['POST', $rota, $origem, $json, '{"nome":"Exemplo","id":1}', 422, null],
        ['POST', $rota, $origem, $json, '{"nome":"Exemplo","descricao":false}', 422, 'descricao'],
        ['POST', $rota, $origem, $json, json_encode(['nome' => str_repeat('🚀', 121)]), 422, 'nome'],
        [
            'POST', $rota, $origem, $json,
            json_encode(['nome' => 'Exemplo', 'descricao' => str_repeat('á', 2001)]), 422, 'descricao',
        ],
    ];
    foreach ($casos as $indice => [$metodo, $caminho, $origin, $tipo, $corpo, $status, $campo]) {
        [$retorno, $dados] = atenderProjetos($metodo, $caminho, $origin, $tipo, $corpo, $abrir);
        exigirProjeto(
            $retorno === $status && isset($dados['erro']['codigo'], $dados['erro']['mensagem'])
                && ($dados['erro']['campo'] ?? null) === $campo,
            'Contrato inválido no caso ' . ($indice + 1) . '.'
        );
    }
    echo count($casos) . " casos de contrato sem banco aprovados.\n";
}

function verificarIntegracaoProjetos(): void
{
    $pdo = Conexao::local(true);
    $prefixo = 'Projeto sintético ' . bin2hex(random_bytes(12)) . ' ';
    $nome = $prefixo . str_repeat('🚀', 120 - mb_strlen($prefixo, 'UTF-8'));
    $inicioDescricao = '<b>Texto literal</b>';
    $descricao = $inicioDescricao . str_repeat('á', 2000 - mb_strlen($inicioDescricao, 'UTF-8'));
    $abrir = static fn (): PDO => $pdo;

    try {
        [$status, $criado] = atenderProjetos(
            'POST',
            '/api/projetos',
            'http://127.0.0.1:8082',
            'application/json; charset=UTF-8',
            json_encode(['nome' => " $nome ", 'descricao' => " $descricao "], JSON_THROW_ON_ERROR),
            $abrir
        );
        exigirProjeto($status === 201, 'POST deveria retornar 201.');
        exigirProjeto(
            $criado['nome'] === $nome && $criado['descricao'] === $descricao && (int) $criado['id'] > 0,
            'O retorno da criação diverge dos dados persistidos.'
        );

        // Outra conexão comprova que a gravação está visível após recarregar.
        $ler = static fn (): PDO => Conexao::local(true);
        [$status, $lista] = atenderProjetos('GET', '/api/projetos', null, '', '', $ler);
        exigirProjeto($status === 200 && in_array($criado, $lista, true), 'Projeto ausente da listagem.');
    } finally {
        $limpar = $pdo->prepare('DELETE FROM projetos WHERE BINARY nome = BINARY ?');
        $limpar->execute([$nome]);
    }
    echo "Criação e listagem confirmadas em devflow_test.\n";
}

ini_set('display_errors', '0');
try {
    verificarContratoProjetos();
    if (in_array('--integracao', $argv, true)) {
        verificarIntegracaoProjetos();
    }
} catch (Throwable $erro) {
    $mensagem = $erro::class === RuntimeException::class ? $erro->getMessage() : 'Teste de projetos reprovado.';
    fwrite(STDERR, $mensagem . PHP_EOL);
    exit(1);
}
