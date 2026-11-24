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

    public function criar(string $nome, ?string $descricao): array
    {
        $this->pdo->beginTransaction();

        try {
            $inserir = $this->pdo->prepare('INSERT INTO projetos (nome, descricao) VALUES (?, ?)');
            $inserir->execute([$nome, $descricao]);
            $consultar = $this->pdo->prepare(
                'SELECT id, nome, descricao, criado_em, atualizado_em FROM projetos WHERE id = ?'
            );
            $consultar->execute([(int) $this->pdo->lastInsertId()]);
            $projeto = $consultar->fetch();
            if ($projeto === false) {
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
}
