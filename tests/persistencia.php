<?php

declare(strict_types=1);

namespace DevFlow\Tests;

use DevFlow\Infra\Conexao;
use PDO;
use PDOException;
use RuntimeException;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require_once dirname(__DIR__) . '/app/Infra/Conexao.php';

function exigir(bool $condicao, string $verificacao): void
{
    if (!$condicao) {
        throw new RuntimeException($verificacao);
    }
}

function exigirRecusa(callable $operacao, array $codigos, string $verificacao): void
{
    try {
        $operacao();
    } catch (PDOException $erro) {
        exigir(in_array((int) ($erro->errorInfo[1] ?? 0), $codigos, true), $verificacao);
        return;
    }

    throw new RuntimeException($verificacao);
}

function verificarPrivilegios(PDO $pdo, string $banco, string $usuario): void
{
    $grantee = "'$usuario'@'127.0.0.1'";
    $tabelas = $pdo->prepare(
        "SELECT CONCAT(TABLE_SCHEMA, '.', TABLE_NAME, ':', PRIVILEGE_TYPE, ':', IS_GRANTABLE)
         FROM information_schema.TABLE_PRIVILEGES WHERE GRANTEE = ?"
    );
    $tabelas->execute([$grantee]);
    $obtidos = $tabelas->fetchAll(PDO::FETCH_COLUMN);

    $esperados = [];
    foreach (['projetos', 'tarefas'] as $tabela) {
        foreach (['SELECT', 'INSERT', 'UPDATE', 'DELETE'] as $privilegio) {
            $esperados[] = "$banco.$tabela:$privilegio:NO";
        }
    }
    sort($obtidos);
    sort($esperados);
    exigir($obtidos === $esperados, 'Privilégios por tabela divergentes.');

    $extras = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM information_schema.USER_PRIVILEGES
             WHERE GRANTEE = ? AND (PRIVILEGE_TYPE <> 'USAGE' OR IS_GRANTABLE <> 'NO'))
          + (SELECT COUNT(*) FROM information_schema.SCHEMA_PRIVILEGES WHERE GRANTEE = ?)
          + (SELECT COUNT(*) FROM information_schema.COLUMN_PRIVILEGES WHERE GRANTEE = ?)
          + (SELECT COUNT(*) FROM information_schema.APPLICABLE_ROLES)"
    );
    $extras->execute([$grantee, $grantee, $grantee]);
    exigir((int) $extras->fetchColumn() === 0, 'Privilégios adicionais identificados.');
}

function testarPersistencia(PDO $pdo, bool $verificarAcessoCruzado = true): void
{
    $identidade = $pdo->query(
        'SELECT DATABASE() AS banco, @@port AS porta, CURRENT_USER() AS conta,
                @@character_set_connection AS charset'
    )->fetch();
    exigir(
        $identidade['banco'] === 'devflow_test'
            && (int) $identidade['porta'] === 3307
            && $identidade['conta'] === 'devflow_test_app@127.0.0.1'
            && $identidade['charset'] === 'utf8mb4',
        'Executor bloqueado: destino de testes inválido.'
    );
    verificarPrivilegios($pdo, 'devflow_test', 'devflow_test_app');
    exigirRecusa(
        static fn () => $pdo->exec('CREATE TEMPORARY TABLE devflow_prova_ddl (id INT)'),
        [1044, 1142],
        'DDL deveria ser recusado.'
    );
    if ($verificarAcessoCruzado) {
        exigirRecusa(
            static fn () => $pdo->query('SELECT id FROM devflow.projetos LIMIT 0'),
            [1044, 1142],
            'Leitura no outro banco deveria ser recusada.'
        );
    }

    $pdo->beginTransaction();
    try {
        $nome = "Projeto sintético — ação 🚀 'Unicode'";
        $nova = $pdo->prepare('INSERT INTO projetos (nome) VALUES (?)');
        $nova->execute([$nome]);
        $projeto = (int) $pdo->lastInsertId();
        exigir($projeto > 0, 'ID do projeto não gerado.');
        exigirRecusa(
            static fn () => $nova->execute(['   ']),
            [4025],
            'Nome em branco deveria ser recusado.'
        );

        $lerProjeto = $pdo->prepare(
            'SELECT nome, descricao, criado_em, atualizado_em FROM projetos WHERE id = ?'
        );
        $lerProjeto->execute([$projeto]);
        $original = $lerProjeto->fetch();
        exigir(
            $original && $original['nome'] === $nome && $original['descricao'] === null
                && $original['criado_em'] !== null
                && $original['criado_em'] === $original['atualizado_em'],
            'Unicode ou valores iniciais do projeto divergentes.'
        );
        usleep(2000);
        $editarProjeto = $pdo->prepare('UPDATE projetos SET descricao = ? WHERE id = ?');
        $editarProjeto->execute(['Descrição sintética', $projeto]);
        $lerProjeto->execute([$projeto]);
        $editado = $lerProjeto->fetch();
        exigir(
            $editado && $editado['descricao'] === 'Descrição sintética'
                && $editado['criado_em'] === $original['criado_em']
                && $editado['atualizado_em'] > $original['atualizado_em'],
            'Timestamp de atualização do projeto divergente.'
        );

        $titulo = 'Tarefa sintética 🧪';
        $inserirPadrao = $pdo->prepare('INSERT INTO tarefas (projeto_id, titulo) VALUES (?, ?)');
        $inserirPadrao->execute([$projeto, $titulo]);
        $tarefa = (int) $pdo->lastInsertId();
        $lerTarefa = $pdo->prepare(
            'SELECT titulo, descricao, prioridade, status, posicao, criado_em, atualizado_em
             FROM tarefas WHERE id = ?'
        );
        $lerTarefa->execute([$tarefa]);
        $padrao = $lerTarefa->fetch();
        exigir(
            $padrao && $padrao['titulo'] === $titulo && $padrao['descricao'] === null
                && $padrao['prioridade'] === 'baixa' && $padrao['status'] === 'a_fazer'
                && (int) $padrao['posicao'] === 0 && $padrao['criado_em'] !== null
                && $padrao['criado_em'] === $padrao['atualizado_em'],
            'Padrões ou Unicode da tarefa divergentes.'
        );
        exigirRecusa(
            static fn () => $inserirPadrao->execute([0, 'Órfã']),
            [1452],
            'Tarefa órfã deveria ser recusada.'
        );
        exigirRecusa(
            static fn () => $inserirPadrao->execute([$projeto, '   ']),
            [4025],
            'Título em branco deveria ser recusado.'
        );
        $excluirProjeto = $pdo->prepare('DELETE FROM projetos WHERE id = ?');
        exigirRecusa(
            static fn () => $excluirProjeto->execute([$projeto]),
            [1451],
            'Exclusão de projeto vinculado deveria ser recusada.'
        );
        $alterarId = $pdo->prepare('UPDATE projetos SET id = 0 WHERE id = ?');
        exigirRecusa(
            static fn () => $alterarId->execute([$projeto]),
            [1451],
            'Alteração da chave de projeto vinculado deveria ser recusada.'
        );

        $alterarTarefa = $pdo->prepare('UPDATE tarefas SET prioridade = ?, status = ? WHERE id = ?');
        usleep(2000);
        foreach ([['media', 'em_andamento'], ['alta', 'concluido'], ['baixa', 'a_fazer']] as $par) {
            $alterarTarefa->execute([...$par, $tarefa]);
            $lerTarefa->execute([$tarefa]);
            $atual = $lerTarefa->fetch();
            exigir(
                $atual && [$atual['prioridade'], $atual['status']] === $par
                    && $atual['criado_em'] === $padrao['criado_em']
                    && $atual['atualizado_em'] > $padrao['atualizado_em'],
                'Valores válidos ou timestamps da tarefa divergentes.'
            );
        }
        foreach ([
            ['outra', 'a_fazer'], ['Baixa', 'a_fazer'], ['BAIXA', 'a_fazer'],
            ['baixa ', 'a_fazer'], ['baixa    ', 'a_fazer'], [' alta', 'a_fazer'],
            ['baixa', 'outro'], ['baixa', 'A_FAZER'], ['baixa', 'Em_andamento'],
            ['baixa', 'em_andamento '], ['baixa', 'em_andamento    '], ['baixa', ' concluido'],
        ] as $par) {
            exigirRecusa(
                static fn () => $alterarTarefa->execute([...$par, $tarefa]),
                [4025, 1406],
                'Prioridade ou estado inválido deveria ser recusado.'
            );
        }

        $inserirOrdenada = $pdo->prepare('INSERT INTO tarefas (projeto_id, titulo, posicao) VALUES (?, ?, ?)');
        $inserirOrdenada->execute([$projeto, 'Empate de posição', 0]);
        $empate = (int) $pdo->lastInsertId();
        $inserirOrdenada->execute([$projeto, 'Primeira posição', -1]);
        $primeira = (int) $pdo->lastInsertId();
        $listar = $pdo->prepare(
            'SELECT id FROM tarefas WHERE projeto_id = ? AND status = ? ORDER BY posicao, id'
        );
        $listar->execute([$projeto, 'a_fazer']);
        exigir(
            array_map('intval', $listar->fetchAll(PDO::FETCH_COLUMN)) === [$primeira, $tarefa, $empate],
            'Ordenação por posição e ID divergente.'
        );
        $excluirTarefas = $pdo->prepare('DELETE FROM tarefas WHERE projeto_id = ?');
        $excluirTarefas->execute([$projeto]);
        $excluirProjeto->execute([$projeto]);
        exigir($excluirProjeto->rowCount() === 1, 'Projeto sem tarefas deveria ser excluído.');
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
    echo "Persistência de testes validada.\n";
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    ini_set('display_errors', '0');
    try {
        testarPersistencia(Conexao::local(true));
    } catch (\Throwable $erro) {
        $saida = $erro::class === RuntimeException::class
            ? $erro->getMessage()
            : 'Falha nos testes de persistência.';
        fwrite(STDERR, $saida . PHP_EOL);
        exit(1);
    }
}
