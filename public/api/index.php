<?php

declare(strict_types=1);

use DevFlow\Infra\Conexao;
use function DevFlow\Projetos\atenderProjetos;
use function DevFlow\Tarefas\atenderTarefas;

ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

try {
    require_once dirname(__DIR__, 2) . '/app/Infra/Conexao.php';
    require_once dirname(__DIR__, 2) . '/app/Projetos/ApiProjetos.php';
    require_once dirname(__DIR__, 2) . '/app/Tarefas/ApiTarefas.php';

    $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
    $rota = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $atender = str_starts_with($rota, '/api/tarefas/')
        || preg_match('~\A/api/projetos/[^/]+/tarefas\z~', $rota)
        ? atenderTarefas(...)
        : atenderProjetos(...);
    $resultado = $atender(
        $metodo,
        $rota,
        $_SERVER['HTTP_ORIGIN'] ?? null,
        $_SERVER['CONTENT_TYPE'] ?? '',
        in_array($metodo, ['POST', 'PATCH', 'DELETE'], true) ? (string) file_get_contents('php://input') : '',
        Conexao::local(...),
        Conexao::origemLocal(...)
    );
    [$status, $resposta] = $resultado;
    $permitidos = $resultado[2] ?? null;
    $json = $status === 204 ? '' : json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable) {
    $status = 500;
    $json = json_encode([
        'erro' => ['codigo' => 'erro_interno', 'mensagem' => 'Não foi possível concluir a operação.'],
    ], JSON_UNESCAPED_UNICODE);
}

if ($status === 405) {
    header('Allow: ' . $permitidos);
}
if ($status !== 204) {
    header('Content-Type: application/json; charset=UTF-8');
}
http_response_code($status);
echo $json;
