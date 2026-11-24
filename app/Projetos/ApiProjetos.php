<?php

declare(strict_types=1);

namespace DevFlow\Projetos;

use JsonException;
use PDOException;
use stdClass;

require_once __DIR__ . '/Projetos.php';

function erroProjetos(int $status, string $codigo, string $mensagem, ?string $campo = null): array
{
    $erro = ['codigo' => $codigo, 'mensagem' => $mensagem];
    if ($campo !== null) {
        $erro['campo'] = $campo;
    }
    return [$status, ['erro' => $erro]];
}

function atenderProjetos(
    string $metodo,
    string $rota,
    ?string $origem,
    string $tipo,
    string $corpo,
    callable $abrirConexao
): array {
    $colecao = $rota === '/api/projetos';
    $id = null;
    if (!$colecao) {
        if (!preg_match('~\A/api/projetos/([0-9]+)\z~', $rota, $partes)) {
            return erroProjetos(404, 'rota_inexistente', 'Rota não encontrada.');
        }
        $decimal = ltrim($partes[1], '0');
        if ($decimal === '' || strlen($decimal) > 10
            || (strlen($decimal) === 10 && strcmp($decimal, '4294967295') > 0)) {
            return erroProjetos(404, 'projeto_inexistente', 'Projeto não encontrado.');
        }
        $id = (int) $decimal;
    }
    $permitidos = $colecao ? ['GET', 'POST'] : ['GET', 'PATCH', 'DELETE'];
    if (!in_array($metodo, $permitidos, true)) {
        return [...erroProjetos(405, 'metodo_invalido', 'Método não permitido.'), implode(', ', $permitidos)];
    }
    if ($metodo === 'GET') {
        $projetos = new Projetos($abrirConexao());
        $resultado = $id === null ? $projetos->listar() : $projetos->consultar($id);
        return $resultado === null
            ? erroProjetos(404, 'projeto_inexistente', 'Projeto não encontrado.')
            : [200, $resultado];
    }
    if ($origem !== 'http://127.0.0.1:8082') {
        return erroProjetos(403, 'origem_invalida', 'Origem da requisição não permitida.');
    }
    if ($metodo === 'DELETE') {
        if ($corpo !== '') {
            return erroProjetos(400, 'corpo_inesperado', 'Envie a exclusão sem corpo.');
        }
        $projetos = new Projetos($abrirConexao());
        try {
            $excluido = $projetos->excluir($id);
        } catch (PDOException $erro) {
            if (($erro->errorInfo[0] ?? null) === '23000' && (int) ($erro->errorInfo[1] ?? 0) === 1451) {
                return erroProjetos(409, 'projeto_com_tarefas', 'O projeto possui tarefas vinculadas.');
            }
            throw $erro;
        }
        return $excluido ? [204, null] : erroProjetos(404, 'projeto_inexistente', 'Projeto não encontrado.');
    }
    if (!preg_match('/\Aapplication\/json(?:\s*;\s*charset\s*=\s*(?:"utf-8"|utf-8))?\z/i', trim($tipo))) {
        return erroProjetos(415, 'tipo_invalido', 'Envie um objeto JSON em UTF-8.');
    }
    try {
        $dados = json_decode($corpo, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return erroProjetos(400, 'json_malformado', 'O corpo da requisição contém JSON malformado.');
    }
    if (!$dados instanceof stdClass) {
        return erroProjetos(422, 'dados_invalidos', 'Envie um objeto JSON.');
    }
    if (array_diff(array_keys(get_object_vars($dados)), ['nome', 'descricao'])) {
        return erroProjetos(422, 'campo_desconhecido', 'Somente nome e descrição são aceitos.');
    }

    $campos = [];
    if ($metodo === 'POST' || property_exists($dados, 'nome')) {
        $nome = $dados->nome ?? null;
        if (!is_string($nome)) {
            return erroProjetos(422, 'dados_invalidos', 'Informe o nome do projeto.', 'nome');
        }
        $nome = preg_replace('/\A[\s\p{Z}]+|[\s\p{Z}]+\z/u', '', $nome);
        if ($nome === '' || mb_strlen($nome, 'UTF-8') > 120) {
            return erroProjetos(422, 'dados_invalidos', 'O nome deve ter de 1 a 120 caracteres.', 'nome');
        }
        $campos['nome'] = $nome;
    }

    $descricao = $dados->descricao ?? null;
    if ($descricao !== null) {
        if (!is_string($descricao)) {
            return erroProjetos(422, 'dados_invalidos', 'A descrição deve ser um texto ou nula.', 'descricao');
        }
        $descricao = preg_replace('/\A[\s\p{Z}]+|[\s\p{Z}]+\z/u', '', $descricao);
        if (mb_strlen($descricao, 'UTF-8') > 2000) {
            return erroProjetos(422, 'dados_invalidos', 'A descrição deve ter até 2.000 caracteres.', 'descricao');
        }
        $descricao = $descricao === '' ? null : $descricao;
    }

    if (property_exists($dados, 'descricao')) {
        $campos['descricao'] = $descricao;
    }
    if ($campos === []) {
        return erroProjetos(422, 'dados_invalidos', 'Informe pelo menos um campo para atualizar.');
    }

    $projetos = new Projetos($abrirConexao());
    if ($metodo === 'POST') {
        return [201, $projetos->criar($campos['nome'], $descricao)];
    }
    $projeto = $projetos->editar($id, $campos);
    return $projeto === null
        ? erroProjetos(404, 'projeto_inexistente', 'Projeto não encontrado.')
        : [200, $projeto];
}
