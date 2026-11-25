<?php

declare(strict_types=1);

namespace DevFlow\Infra;

use JsonException;
use stdClass;

function erroApi(int $status, string $codigo, string $mensagem, ?string $campo = null): array
{
    $erro = ['codigo' => $codigo, 'mensagem' => $mensagem];
    if ($campo !== null) {
        $erro['campo'] = $campo;
    }
    return [$status, ['erro' => $erro]];
}

function identificarRecurso(string $valor): ?int
{
    if (!ctype_digit($valor)) {
        return null;
    }
    $decimal = ltrim($valor, '0');
    if ($decimal === '' || strlen($decimal) > 10
        || (strlen($decimal) === 10 && strcmp($decimal, '4294967295') > 0)) {
        return null;
    }
    return (int) $decimal;
}

function origemPermitida(?string $origem, callable $lerOrigem): bool
{
    return $origem !== null && $origem !== 'null' && $origem === $lerOrigem();
}

function lerObjetoJson(string $tipo, string $corpo): stdClass|array
{
    if (!preg_match('/\Aapplication\/json(?:\s*;\s*charset\s*=\s*(?:"utf-8"|utf-8))?\z/i', trim($tipo))) {
        return erroApi(415, 'tipo_invalido', 'Envie um objeto JSON em UTF-8.');
    }
    try {
        $dados = json_decode($corpo, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return erroApi(400, 'json_malformado', 'O corpo da requisição contém JSON malformado.');
    }
    return $dados instanceof stdClass
        ? $dados
        : erroApi(422, 'dados_invalidos', 'Envie um objeto JSON.');
}

function apararTexto(string $texto): string
{
    return preg_replace('/\A[\s\p{Z}]+|[\s\p{Z}]+\z/u', '', $texto);
}
