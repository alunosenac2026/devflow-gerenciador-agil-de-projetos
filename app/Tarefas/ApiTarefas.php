<?php

declare(strict_types=1);

namespace DevFlow\Tarefas;

use PDOException;
use function DevFlow\Infra\apararTexto;
use function DevFlow\Infra\erroApi;
use function DevFlow\Infra\identificarRecurso;
use function DevFlow\Infra\lerObjetoJson;
use function DevFlow\Infra\origemPermitida;

require_once dirname(__DIR__) . '/Infra/Api.php';
require_once __DIR__ . '/Tarefas.php';

function atenderTarefas(
    string $metodo,
    string $rota,
    ?string $origem,
    string $tipo,
    string $corpo,
    callable $abrirConexao,
    callable $lerOrigem
): array {
    $porProjeto = preg_match('~\A/api/projetos/([^/]+)/tarefas\z~', $rota, $partes) === 1;
    if (!$porProjeto && !preg_match('~\A/api/tarefas/([^/]+)\z~', $rota, $partes)) {
        return erroApi(404, 'rota_inexistente', 'Rota não encontrada.');
    }
    $naoEncontrado = $porProjeto
        ? erroApi(404, 'projeto_inexistente', 'Projeto não encontrado.')
        : erroApi(404, 'tarefa_inexistente', 'Tarefa não encontrada.');
    $id = identificarRecurso($partes[1]);
    if ($id === null) {
        return $naoEncontrado;
    }

    $permitidos = $porProjeto ? ['GET', 'POST'] : ['GET', 'PATCH', 'DELETE'];
    if (!in_array($metodo, $permitidos, true)) {
        return [...erroApi(405, 'metodo_invalido', 'Método não permitido.'), implode(', ', $permitidos)];
    }
    if ($metodo === 'GET') {
        $tarefas = new Tarefas($abrirConexao());
        $resultado = $porProjeto ? $tarefas->listarDoProjeto($id) : $tarefas->consultar($id);
        return $resultado === null ? $naoEncontrado : [200, $resultado];
    }
    if (!origemPermitida($origem, $lerOrigem)) {
        return erroApi(403, 'origem_invalida', 'Origem da requisição não permitida.');
    }
    if ($metodo === 'DELETE') {
        if ($corpo !== '') {
            return erroApi(400, 'corpo_inesperado', 'Envie a exclusão sem corpo.');
        }
        $tarefas = new Tarefas($abrirConexao());
        return $tarefas->excluir($id) ? [204, null] : $naoEncontrado;
    }

    $dados = lerObjetoJson($tipo, $corpo);
    if (is_array($dados)) {
        return $dados;
    }
    $campos = get_object_vars($dados);
    if (array_diff(array_keys($campos), ['titulo', 'descricao', 'prioridade', 'status'])) {
        return erroApi(422, 'campo_desconhecido', 'Somente título, descrição, prioridade e estado são aceitos.');
    }
    if ($metodo === 'POST' || array_key_exists('titulo', $campos)) {
        if (!is_string($campos['titulo'] ?? null)) {
            return erroApi(422, 'dados_invalidos', 'Informe o título da tarefa.', 'titulo');
        }
        $campos['titulo'] = apararTexto($campos['titulo']);
        if ($campos['titulo'] === '' || mb_strlen($campos['titulo'], 'UTF-8') > 120) {
            return erroApi(422, 'dados_invalidos', 'O título deve ter de 1 a 120 caracteres.', 'titulo');
        }
    }
    if (isset($campos['descricao'])) {
        if (!is_string($campos['descricao'])) {
            return erroApi(422, 'dados_invalidos', 'A descrição deve ser um texto ou nula.', 'descricao');
        }
        $descricao = apararTexto($campos['descricao']);
        if (mb_strlen($descricao, 'UTF-8') > 2000) {
            return erroApi(422, 'dados_invalidos', 'A descrição deve ter até 2.000 caracteres.', 'descricao');
        }
        $campos['descricao'] = $descricao === '' ? null : $descricao;
    }
    if (array_key_exists('prioridade', $campos) && !in_array($campos['prioridade'], ['baixa', 'media', 'alta'], true)) {
        return erroApi(422, 'dados_invalidos', 'Selecione uma prioridade válida.', 'prioridade');
    }
    if (array_key_exists('status', $campos) && !in_array($campos['status'], ['a_fazer', 'em_andamento', 'concluido'], true)) {
        return erroApi(422, 'dados_invalidos', 'Selecione um estado válido.', 'status');
    }
    if ($campos === []) {
        return erroApi(422, 'dados_invalidos', 'Informe pelo menos um campo para atualizar.');
    }

    $tarefas = new Tarefas($abrirConexao());
    if ($metodo === 'POST') {
        try {
            return [201, $tarefas->criar($id, $campos)];
        } catch (PDOException $erro) {
            if (($erro->errorInfo[0] ?? null) === '23000' && (int) ($erro->errorInfo[1] ?? 0) === 1452) {
                return $naoEncontrado;
            }
            throw $erro;
        }
    }
    $tarefa = $tarefas->editar($id, $campos);
    return $tarefa === null ? $naoEncontrado : [200, $tarefa];
}
