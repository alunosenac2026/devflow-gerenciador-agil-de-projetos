<?php

declare(strict_types=1);

namespace DevFlow\Projetos;

use PDOException;
use function DevFlow\Infra\apararTexto;
use function DevFlow\Infra\erroApi as erroProjetos;
use function DevFlow\Infra\identificarRecurso;
use function DevFlow\Infra\lerObjetoJson;
use function DevFlow\Infra\origemPermitida;

require_once dirname(__DIR__) . '/Infra/Api.php';
require_once __DIR__ . '/Projetos.php';

function atenderProjetos(
    string $metodo,
    string $rota,
    ?string $origem,
    string $tipo,
    string $corpo,
    callable $abrirConexao,
    callable $lerOrigem
): array {
    $colecao = $rota === '/api/projetos';
    $id = null;
    if (!$colecao) {
        if (!preg_match('~\A/api/projetos/([0-9]+)\z~', $rota, $partes)) {
            return erroProjetos(404, 'rota_inexistente', 'Rota não encontrada.');
        }
        $id = identificarRecurso($partes[1]);
        if ($id === null) {
            return erroProjetos(404, 'projeto_inexistente', 'Projeto não encontrado.');
        }
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
    if (!origemPermitida($origem, $lerOrigem)) {
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
    $dados = lerObjetoJson($tipo, $corpo);
    if (is_array($dados)) {
        return $dados;
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
        $nome = apararTexto($nome);
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
        $descricao = apararTexto($descricao);
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
