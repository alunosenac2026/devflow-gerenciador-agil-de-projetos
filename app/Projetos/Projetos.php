<?php

declare(strict_types=1);

namespace DevFlow\Projetos;

use PDO;
use RuntimeException;

final class Projetos
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        return $this->pdo->query(
            'SELECT id, nome, descricao, criado_em, atualizado_em FROM projetos ORDER BY id DESC'
        )->fetchAll();
    }

    public function consultar(int $id): ?array
    {
        $consultar = $this->pdo->prepare(
            'SELECT id, nome, descricao, criado_em, atualizado_em FROM projetos WHERE id = ?'
        );
        $consultar->execute([$id]);
        $projeto = $consultar->fetch();

        return $projeto === false ? null : $projeto;
    }

    public function criar(string $nome, ?string $descricao): array
    {
        $this->pdo->beginTransaction();

        try {
            $inserir = $this->pdo->prepare('INSERT INTO projetos (nome, descricao) VALUES (?, ?)');
            $inserir->execute([$nome, $descricao]);
            $projeto = $this->consultar((int) $this->pdo->lastInsertId());
            if ($projeto === null) {
                throw new RuntimeException('Projeto não encontrado após a gravação.');
            }
            $this->pdo->commit();

            return $projeto;
        } catch (\Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $erro;
        }
    }

    public function editar(int $id, array $campos): ?array
    {
        $atribuicoes = [];
        $valores = [];
        foreach (['nome', 'descricao'] as $campo) {
            if (array_key_exists($campo, $campos)) {
                $atribuicoes[] = "$campo = ?";
                $valores[] = $campos[$campo];
            }
        }
        $valores[] = $id;
        $sql = 'UPDATE projetos SET ' . implode(', ', $atribuicoes) . ' WHERE id = ?';

        $this->pdo->beginTransaction();
        try {
            $atualizar = $this->pdo->prepare($sql);
            $atualizar->execute($valores);
            $projeto = $this->consultar($id);
            $this->pdo->commit();

            return $projeto;
        } catch (\Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $erro;
        }
    }

    public function excluir(int $id): bool
    {
        $excluir = $this->pdo->prepare('DELETE FROM projetos WHERE id = ?');
        $excluir->execute([$id]);

        return $excluir->rowCount() === 1;
    }
}
