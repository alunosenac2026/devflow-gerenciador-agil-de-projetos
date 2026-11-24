<?php

declare(strict_types=1);

namespace DevFlow\Projetos;

use JsonException;
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
    if ($rota !== '/api/projetos') {
        return erroProjetos(404, 'rota_inexistente', 'Rota não encontrada.');
    }
    if ($metodo === 'GET') {
        return [200, (new Projetos($abrirConexao()))->listar()];
    }
    if ($metodo !== 'POST') {
        return erroProjetos(405, 'metodo_invalido', 'Método não permitido.');
    }
    if ($origem !== 'http://127.0.0.1:8082') {
        return erroProjetos(403, 'origem_invalida', 'Origem da requisição não permitida.');
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

    $nome = $dados->nome ?? null;
    if (!is_string($nome)) {
        return erroProjetos(422, 'dados_invalidos', 'Informe o nome do projeto.', 'nome');
    }
    $nome = preg_replace('/\A[\s\p{Z}]+|[\s\p{Z}]+\z/u', '', $nome);
    if ($nome === '' || mb_strlen($nome, 'UTF-8') > 120) {
        return erroProjetos(422, 'dados_invalidos', 'O nome deve ter de 1 a 120 caracteres.', 'nome');
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

    return [201, (new Projetos($abrirConexao()))->criar($nome, $descricao)];
}
