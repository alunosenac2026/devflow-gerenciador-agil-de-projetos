<?php

declare(strict_types=1);

namespace DevFlow\Tarefas;

use PDO;
use RuntimeException;
use Throwable;

final class Tarefas
{
    private const EDITAVEIS = ['titulo', 'descricao', 'prioridade', 'status'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listarDoProjeto(int $projetoId): ?array
    {
        $listar = $this->pdo->prepare(
            'SELECT t.id, t.projeto_id, t.titulo, t.descricao, t.prioridade, t.status,
                    t.posicao, t.criado_em, t.atualizado_em
             FROM projetos p LEFT JOIN tarefas t ON t.projeto_id = p.id
             WHERE p.id = ? ORDER BY t.posicao ASC, t.id ASC'
        );
        $listar->execute([$projetoId]);
        $tarefas = $listar->fetchAll();
        if ($tarefas === []) {
            return null;
        }
        return $tarefas[0]['id'] === null ? [] : $tarefas;
    }

    public function consultar(int $id): ?array
    {
        $consultar = $this->pdo->prepare(
            'SELECT id, projeto_id, titulo, descricao, prioridade, status, posicao, criado_em, atualizado_em
             FROM tarefas WHERE id = ?'
        );
        $consultar->execute([$id]);
        $tarefa = $consultar->fetch();
        return $tarefa === false ? null : $tarefa;
    }

    public function criar(int $projetoId, array $campos): array
    {
        $dados = ['projeto_id' => $projetoId] + array_intersect_key($campos, array_flip(self::EDITAVEIS));
        $colunas = implode(', ', array_keys($dados));
        $parametros = implode(', ', array_fill(0, count($dados), '?'));
        $inserir = $this->pdo->prepare("INSERT INTO tarefas ($colunas) VALUES ($parametros)");

        $this->pdo->beginTransaction();
        try {
            $inserir->execute(array_values($dados));
            $tarefa = $this->consultar((int) $this->pdo->lastInsertId());
            if ($tarefa === null) {
                throw new RuntimeException('Tarefa não encontrada após a gravação.');
            }
            $this->pdo->commit();
            return $tarefa;
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $erro;
        }
    }

    public function editar(int $id, array $campos): ?array
    {
        $dados = array_intersect_key($campos, array_flip(self::EDITAVEIS));
        if ($dados === []) {
            return $this->consultar($id);
        }
        $atribuicoes = implode(' = ?, ', array_keys($dados)) . ' = ?';
        $atualizar = $this->pdo->prepare("UPDATE tarefas SET $atribuicoes WHERE id = ?");

        $this->pdo->beginTransaction();
        try {
            $atualizar->execute([...array_values($dados), $id]);
            $tarefa = $this->consultar($id);
            $this->pdo->commit();
            return $tarefa;
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $erro;
        }
    }

    public function excluir(int $id): bool
    {
        $excluir = $this->pdo->prepare('DELETE FROM tarefas WHERE id = ?');
        $excluir->execute([$id]);
        return $excluir->rowCount() === 1;
    }
}
