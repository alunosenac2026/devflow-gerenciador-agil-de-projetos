<?php

declare(strict_types=1);

use DevFlow\Infra\Conexao;
use DevFlow\Projetos\Projetos;
use function DevFlow\Projetos\atenderProjetos;
use function DevFlow\Tarefas\atenderTarefas;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require_once dirname(__DIR__) . '/app/Infra/Conexao.php';
require_once dirname(__DIR__) . '/app/Projetos/ApiProjetos.php';
require_once dirname(__DIR__) . '/app/Tarefas/ApiTarefas.php';

function exigirTarefa(bool $condicao, string $rotulo): void
{
    if (!$condicao) {
        throw new RuntimeException($rotulo);
    }
}

function verificarContratoTarefas(): void
{
    $abrir = static function (): PDO {
        throw new RuntimeException('Uma solicitação inválida tentou acessar o banco.');
    };
    $origem = 'http://127.0.0.1:18082';
    $lerOrigem = static fn (): string => $origem;
    $json = 'application/json';
    $colecao = '/api/projetos/1/tarefas';
    $individual = '/api/tarefas/1';
    $casos = [
        ['GET', '/api/tarefas', null, '', '', 404, null],
        ['GET', "$colecao/2", null, '', '', 404, null],
        ['GET', '/api/projetos/0/tarefas', null, '', '', 404, null],
        ['GET', '/api/projetos/4294967296/tarefas', null, '', '', 404, null],
        ['GET', '/api/tarefas/1.5', null, '', '', 404, null],
        ['GET', '/api/tarefas/-1', null, '', '', 404, null],
        ['GET', '/api/tarefas/18446744073709551615', null, '', '', 404, null],
        ['PATCH', $colecao, $origem, $json, '{}', 405, null],
        ['POST', $individual, $origem, $json, '{}', 405, null],
        ['POST', $colecao, null, $json, '{}', 403, null],
        ['PATCH', $individual, 'null', $json, '{}', 403, null],
        ['DELETE', $individual, 'http://127.0.0.1:8082', '', '', 403, null],
        ['DELETE', $individual, "$origem/", '', '', 403, null],
        ['POST', $colecao, $origem, '', '{}', 415, null],
        ['PATCH', $individual, $origem, 'text/plain', '{}', 415, null],
        ['POST', $colecao, $origem, 'application/json; charset=ISO-8859-1', '{}', 415, null],
        ['POST', $colecao, $origem, 'Application/JSON; Charset="utf-8"', '{', 400, null],
        ['PATCH', $individual, $origem, $json, '{', 400, null],
        ['POST', $colecao, $origem, $json, '[]', 422, null],
        ['POST', $colecao, $origem, $json, '{}', 422, 'titulo'],
        ['POST', $colecao, $origem, $json, '{"titulo":null}', 422, 'titulo'],
        ['POST', $colecao, $origem, $json, '{"titulo":1}', 422, 'titulo'],
        ['POST', $colecao, $origem, $json, '{"titulo":" \u00a0 "}', 422, 'titulo'],
        ['POST', $colecao, $origem, $json, json_encode(['titulo' => str_repeat('🚀', 121)]), 422, 'titulo'],
        ['POST', $colecao, $origem, $json, '{"titulo":"A","descricao":false}', 422, 'descricao'],
        ['PATCH', $individual, $origem, $json, json_encode(['descricao' => str_repeat('á', 2001)]), 422, 'descricao'],
        ['POST', $colecao, $origem, $json, '{"titulo":"A","prioridade":null}', 422, 'prioridade'],
        ['PATCH', $individual, $origem, $json, '{"prioridade":"Alta"}', 422, 'prioridade'],
        ['PATCH', $individual, $origem, $json, '{"prioridade":"media "}', 422, 'prioridade'],
        ['PATCH', $individual, $origem, $json, '{"prioridade":"média"}', 422, 'prioridade'],
        ['PATCH', $individual, $origem, $json, '{"prioridade":1}', 422, 'prioridade'],
        ['POST', $colecao, $origem, $json, '{"titulo":"A","status":"pendente"}', 422, 'status'],
        ['PATCH', $individual, $origem, $json, '{"status":null}', 422, 'status'],
        ['PATCH', $individual, $origem, $json, '{"status":"CONCLUIDO"}', 422, 'status'],
        ['PATCH', $individual, $origem, $json, '{"status":"em_andamento "}', 422, 'status'],
        ['PATCH', $individual, $origem, $json, '{"titulo":""}', 422, 'titulo'],
        ['PATCH', $individual, $origem, $json, '{}', 422, null],
        ['DELETE', $individual, $origem, $json, '{}', 400, null],
    ];
    foreach (['id', 'projeto_id', 'posicao', 'criado_em', 'atualizado_em', 'extra'] as $campoInterno) {
        $casos[] = ['PATCH', $individual, $origem, $json, json_encode([$campoInterno => 1]), 422, null];
        $casos[] = ['POST', $colecao, $origem, $json, json_encode(['titulo' => 'A', $campoInterno => 1]), 422, null];
    }

    foreach ($casos as $indice => [$metodo, $rota, $origin, $tipo, $corpo, $status, $campo]) {
        $resposta = atenderTarefas($metodo, $rota, $origin, $tipo, $corpo, $abrir, $lerOrigem);
        exigirTarefa(
            $resposta[0] === $status && isset($resposta[1]['erro']['codigo'], $resposta[1]['erro']['mensagem'])
                && ($resposta[1]['erro']['campo'] ?? null) === $campo,
            'Contrato de tarefas divergente no caso ' . ($indice + 1) . '.'
        );
        if ($status === 405) {
            exigirTarefa(
                $resposta[2] === ($rota === $colecao ? 'GET, POST' : 'GET, PATCH, DELETE'),
                'Cabeçalho Allow divergente.'
            );
        }
    }
    echo count($casos) . " casos de contrato de tarefas sem banco aprovados.\n";
}

function verificarIntegracaoTarefas(): void
{
    $pdo = Conexao::local(true);
    $projetos = new Projetos($pdo);
    $prefixo = 'Tarefas sintéticas ' . bin2hex(random_bytes(8));
    $origem = Conexao::origemLocal(true);
    $lerOrigem = static fn (): string => $origem;
    $abrir = static fn (): PDO => $pdo;
    $chamar = static fn (string $metodo, string $rota, array $campos = []): array =>
        atenderTarefas(
            $metodo, $rota, $origem, 'application/json',
            in_array($metodo, ['POST', 'PATCH'], true) ? json_encode($campos, JSON_THROW_ON_ERROR) : '',
            $abrir, $lerOrigem
        );
    $criar = static function (string $rota, array $campos) use ($chamar): array {
        [$status, $tarefa] = $chamar('POST', $rota, $campos);
        exigirTarefa($status === 201, 'POST de tarefa deveria retornar 201.');
        return $tarefa;
    };
    $primeiro = $segundo = null;
    try {
        $primeiro = $projetos->criar("$prefixo A", null)['id'];
        $segundo = $projetos->criar("$prefixo B", null)['id'];
        $rotaA = "/api/projetos/$primeiro/tarefas";
        $rotaB = "/api/projetos/$segundo/tarefas";
        exigirTarefa($chamar('GET', $rotaA) === [200, []], 'Projeto vazio deveria retornar lista vazia.');

        $titulo = str_repeat('🚀', 120);
        $inicioDescricao = '<b>Texto literal</b>';
        $descricao = $inicioDescricao . str_repeat('á', 2000 - mb_strlen($inicioDescricao, 'UTF-8'));
        $padrao = $criar($rotaA, ['titulo' => " $titulo ", 'descricao' => " $descricao "]);
        exigirTarefa(
            (int) $padrao['projeto_id'] === (int) $primeiro && $padrao['titulo'] === $titulo
                && $padrao['descricao'] === $descricao && $padrao['prioridade'] === 'baixa'
                && $padrao['status'] === 'a_fazer' && (int) $padrao['posicao'] === 0,
            'Vínculo, Unicode ou valores padrão divergentes.'
        );
        $media = $criar($rotaA, ['titulo' => 'Média', 'prioridade' => 'media', 'status' => 'em_andamento']);
        $alta = $criar($rotaA, ['titulo' => 'Alta', 'prioridade' => 'alta', 'status' => 'concluido']);
        $outra = $criar($rotaB, ['titulo' => 'Outro projeto']);
        exigirTarefa($chamar('GET', $rotaB) === [200, [$outra]], 'Listagem cruzou projetos.');
        exigirTarefa(
            $media['prioridade'] === 'media' && $media['status'] === 'em_andamento'
                && $alta['prioridade'] === 'alta' && $alta['status'] === 'concluido',
            'Prioridades ou estados explícitos divergentes.'
        );

        $posicionar = $pdo->prepare('UPDATE tarefas SET posicao = ? WHERE id = ? AND projeto_id = ?');
        $posicionar->execute([5, $padrao['id'], $primeiro]);
        [$status, $listadas] = $chamar('GET', $rotaA);
        exigirTarefa(
            $status === 200 && array_column($listadas, 'id') === [$media['id'], $alta['id'], $padrao['id']],
            'Listagem deveria ordenar por posição e desempatar pelo ID.'
        );

        $rotaTarefa = '/api/tarefas/' . $padrao['id'];
        [$status, $editada] = $chamar('PATCH', $rotaTarefa, ['prioridade' => 'alta', 'status' => 'concluido']);
        exigirTarefa(
            $status === 200 && $editada['titulo'] === $titulo && $editada['descricao'] === $descricao
                && $editada['prioridade'] === 'alta' && $editada['status'] === 'concluido'
                && (int) $editada['posicao'] === 5 && (int) $editada['projeto_id'] === (int) $primeiro,
            'PATCH deveria preservar os campos omitidos, a posição e o vínculo.'
        );
        [$status, $editada] = $chamar('PATCH', $rotaTarefa, ['titulo' => '  Título revisado  ', 'descricao' => null]);
        exigirTarefa(
            $status === 200 && $editada['titulo'] === 'Título revisado' && $editada['descricao'] === null,
            'PATCH deveria aparar o título e limpar a descrição.'
        );
        [$status] = $chamar('PATCH', $rotaTarefa, ['titulo' => 'Não persistir', 'status' => 'CONCLUIDO']);
        exigirTarefa($status === 422, 'PATCH com campo inválido deveria retornar 422.');
        $novaConexao = static fn (): PDO => Conexao::local(true);
        exigirTarefa(
            atenderTarefas('GET', $rotaTarefa, null, '', '', $novaConexao, $lerOrigem) === [200, $editada],
            'Nova conexão deveria recuperar somente as alterações válidas.'
        );

        $transicoes = [
            ['concluido', 'a_fazer'],
            ['a_fazer', 'em_andamento'],
            ['em_andamento', 'a_fazer'],
            ['a_fazer', 'concluido'],
            ['concluido', 'em_andamento'],
            ['em_andamento', 'concluido'],
        ];
        $camposPreservados = array_flip(['id', 'projeto_id', 'titulo', 'descricao', 'prioridade', 'posicao', 'criado_em']);
        $dadosOriginais = array_intersect_key($editada, $camposPreservados);
        foreach ($transicoes as [$origemEstado, $destinoEstado]) {
            exigirTarefa($editada['status'] === $origemEstado, 'Estado de origem inesperado na transição.');
            [$status, $editada] = $chamar('PATCH', $rotaTarefa, ['status' => $destinoEstado]);
            exigirTarefa(
                $status === 200 && $editada['status'] === $destinoEstado
                    && array_intersect_key($editada, $camposPreservados) === $dadosOriginais,
                "Transição $origemEstado → $destinoEstado deveria alterar somente o estado da tarefa."
            );
            [$status, $aposRecarga] = atenderTarefas('GET', $rotaA, null, '', '', $novaConexao, $lerOrigem);
            exigirTarefa(
                $status === 200 && in_array($editada, $aposRecarga, true)
                    && array_column($aposRecarga, 'id') === [$media['id'], $alta['id'], $padrao['id']],
                "Transição $origemEstado → $destinoEstado deveria persistir e manter a ordenação."
            );
        }

        [$status] = atenderProjetos('DELETE', "/api/projetos/$primeiro", $origem, '', '', $abrir, $lerOrigem);
        exigirTarefa($status === 409, 'Projeto com tarefas deveria permanecer protegido.');
        exigirTarefa($chamar('DELETE', $rotaTarefa) === [204, null], 'Exclusão deveria retornar 204 sem corpo.');
        foreach (['GET', 'PATCH', 'DELETE'] as $metodo) {
            $campos = $metodo === 'PATCH' ? ['titulo' => 'Ausente'] : [];
            exigirTarefa($chamar($metodo, $rotaTarefa, $campos)[0] === 404, 'Tarefa ausente deveria retornar 404.');
        }

        exigirTarefa($chamar('DELETE', '/api/tarefas/' . $outra['id']) === [204, null], 'Exclusão da tarefa isolada falhou.');
        exigirTarefa($projetos->excluir((int) $segundo), 'Exclusão do projeto vazio falhou.');
        exigirTarefa($chamar('GET', $rotaB)[0] === 404, 'GET de tarefas com projeto inexistente deveria retornar 404.');
        exigirTarefa($chamar('POST', $rotaB, ['titulo' => 'Órfã'])[0] === 404, 'POST em projeto inexistente deveria retornar 404.');
    } finally {
        $limparTarefas = $pdo->prepare('DELETE FROM tarefas WHERE projeto_id = ?');
        foreach ([$primeiro, $segundo] as $id) {
            if ($id !== null) {
                $limparTarefas->execute([$id]);
                $projetos->excluir((int) $id);
            }
        }
    }
    echo "CRUD de tarefas, prioridades, vínculo e ordenação confirmados em devflow_test.\n";
    echo "Seis transições do Kanban confirmadas em devflow_test, com nova leitura a cada movimento.\n";
}

ini_set('display_errors', '0');
try {
    verificarContratoTarefas();
    if (in_array('--integracao', $argv, true)) {
        verificarIntegracaoTarefas();
    }
} catch (Throwable $erro) {
    $mensagem = $erro::class === RuntimeException::class ? $erro->getMessage() : 'Teste de tarefas reprovado.';
    fwrite(STDERR, $mensagem . PHP_EOL);
    exit(1);
}
