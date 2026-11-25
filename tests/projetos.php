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
    $origem = 'http://127.0.0.1:18082';
    $lerOrigem = static fn (): string => $origem;
    $json = 'application/json';
    $rota = '/api/projetos';
    $casos = [
        ['GET', '/outra', null, '', '', 404, null],
        ['DELETE', $rota, $origem, '', '', 405, null],
        ['POST', $rota, null, $json, '{}', 403, null],
        ['POST', $rota, 'null', $json, '{}', 403, null],
        ['POST', $rota, 'http://localhost:8082', $json, '{}', 403, null],
        ['POST', $rota, 'http://127.0.0.1:8082', $json, '{}', 403, null],
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
        ['GET', "$rota/0", null, '', '', 404, null],
        ['GET', "$rota/-1", null, '', '', 404, null],
        ['GET', "$rota/1.5", null, '', '', 404, null],
        ['GET', "$rota/1e2", null, '', '', 404, null],
        ['GET', "$rota/4294967296", null, '', '', 404, null],
        ['GET', "$rota/18446744073709551615", null, '', '', 404, null],
        ['POST', "$rota/1", $origem, $json, '{"nome":"Exemplo"}', 405, null],
        ['PATCH', "$rota/1", null, $json, '{"nome":"Exemplo"}', 403, null],
        ['PATCH', "$rota/1", $origem, 'text/plain', '{}', 415, null],
        ['PATCH', "$rota/1", $origem, $json, '{', 400, null],
        ['PATCH', "$rota/1", $origem, $json, '[]', 422, null],
        ['PATCH', "$rota/1", $origem, $json, '{}', 422, null],
        ['PATCH', "$rota/1", $origem, $json, '{"id":1}', 422, null],
        ['PATCH', "$rota/1", $origem, $json, '{"nome":null}', 422, 'nome'],
        ['PATCH', "$rota/1", $origem, $json, '{"nome":"  "}', 422, 'nome'],
        ['PATCH', "$rota/1", $origem, $json, '{"descricao":42}', 422, 'descricao'],
        ['DELETE', "$rota/1", 'null', '', '', 403, null],
        ['DELETE', "$rota/1", $origem, $json, '{}', 400, null],
        ['DELETE', "$rota/1", $origem, '', ' ', 400, null],
    ];
    foreach ($casos as $indice => [$metodo, $caminho, $origin, $tipo, $corpo, $status, $campo]) {
        $resposta = atenderProjetos($metodo, $caminho, $origin, $tipo, $corpo, $abrir, $lerOrigem);
        [$retorno, $dados] = $resposta;
        exigirProjeto(
            $retorno === $status && isset($dados['erro']['codigo'], $dados['erro']['mensagem'])
                && ($dados['erro']['campo'] ?? null) === $campo,
            'Contrato inválido no caso ' . ($indice + 1) . '.'
        );
        if ($status === 405) {
            exigirProjeto(
                ($resposta[2] ?? '') === ($caminho === $rota ? 'GET, POST' : 'GET, PATCH, DELETE'),
                'Métodos permitidos divergentes.'
            );
        }
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
    $id = null;
    $idTarefa = null;
    $origem = Conexao::origemLocal(true);
    $lerOrigem = static fn (): string => $origem;

    try {
        [$status, $criado] = atenderProjetos(
            'POST',
            '/api/projetos',
            $origem,
            'application/json; charset=UTF-8',
            json_encode(['nome' => " $nome ", 'descricao' => " $descricao "], JSON_THROW_ON_ERROR),
            $abrir,
            $lerOrigem
        );
        exigirProjeto($status === 201, 'POST deveria retornar 201.');
        $id = (int) $criado['id'];
        exigirProjeto(
            $criado['nome'] === $nome && $criado['descricao'] === $descricao && (int) $criado['id'] > 0,
            'O retorno da criação diverge dos dados persistidos.'
        );

        // Outra conexão comprova que a gravação está visível após recarregar.
        $ler = static fn (): PDO => Conexao::local(true);
        [$status, $lista] = atenderProjetos('GET', '/api/projetos', null, '', '', $ler, $lerOrigem);
        exigirProjeto($status === 200 && in_array($criado, $lista, true), 'Projeto ausente da listagem.');

        $rota = "/api/projetos/$id";
        $chamar = static fn (string $metodo, string $corpo = ''): array =>
            atenderProjetos($metodo, $rota, $origem, 'application/json', $corpo, $abrir, $lerOrigem);
        exigirProjeto($chamar('GET') === [200, $criado], 'GET individual divergente.');

        [$status, $editado] = $chamar('PATCH', '{"nome":"  Nome atualizado  "}');
        exigirProjeto(
            $status === 200 && $editado['nome'] === 'Nome atualizado' && $editado['descricao'] === $descricao,
            'PATCH deveria preservar a descrição omitida.'
        );
        [$status, $editado] = $chamar('PATCH', '{"descricao":null}');
        exigirProjeto(
            $status === 200 && $editado['nome'] === 'Nome atualizado' && $editado['descricao'] === null,
            'PATCH deveria limpar a descrição e preservar o nome omitido.'
        );
        exigirProjeto(
            $chamar('PATCH', '{"descricao":null}') === [200, $editado],
            'PATCH sem mudança em projeto existente deveria retornar 200.'
        );
        exigirProjeto(
            atenderProjetos('GET', $rota, null, '', '', $ler, $lerOrigem) === [200, $editado],
            'Alteração ausente em nova conexão.'
        );

        $vincular = $pdo->prepare('INSERT INTO tarefas (projeto_id, titulo) VALUES (?, ?)');
        $vincular->execute([$id, 'Vínculo sintético de teste']);
        $idTarefa = (int) $pdo->lastInsertId();
        [$status, $erro] = $chamar('DELETE');
        exigirProjeto(
            $status === 409 && $erro['erro']['codigo'] === 'projeto_com_tarefas',
            'DELETE com tarefa vinculada deveria retornar 409.'
        );
        exigirProjeto($chamar('GET') === [200, $editado], 'Conflito alterou o projeto.');
        $limparTarefa = $pdo->prepare('DELETE FROM tarefas WHERE id = ? AND projeto_id = ?');
        $limparTarefa->execute([$idTarefa, $id]);
        exigirProjeto($limparTarefa->rowCount() === 1, 'Conflito removeu a tarefa vinculada.');
        $idTarefa = null;
        exigirProjeto($chamar('DELETE') === [204, null], 'DELETE deveria retornar 204 sem corpo.');
        foreach (['GET', 'PATCH', 'DELETE'] as $metodo) {
            [$status] = $chamar($metodo, $metodo === 'PATCH' ? '{"nome":"Ausente"}' : '');
            exigirProjeto($status === 404, 'Operação em projeto ausente deveria retornar 404.');
        }
    } finally {
        if ($idTarefa !== null) {
            $limparTarefa = $pdo->prepare('DELETE FROM tarefas WHERE id = ? AND projeto_id = ?');
            $limparTarefa->execute([$idTarefa, $id]);
        }
        if ($id !== null) {
            $limpar = $pdo->prepare('DELETE FROM projetos WHERE id = ?');
            $limpar->execute([$id]);
        }
    }
    echo "CRUD de projetos e conflito de integridade confirmados em devflow_test.\n";
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
