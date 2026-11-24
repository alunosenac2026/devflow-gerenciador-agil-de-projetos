-- Execute no banco vazio selecionado explicitamente pelo cliente.

CREATE TABLE projetos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(2000) DEFAULT NULL,
    criado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    atualizado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT chk_projetos_nome CHECK (CHAR_LENGTH(TRIM(nome)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tarefas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    projeto_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    descricao VARCHAR(2000) DEFAULT NULL,
    -- Um caractere extra impede que o truncamento de espaços torne um valor inválido válido.
    prioridade VARCHAR(6) NOT NULL DEFAULT 'baixa',
    status VARCHAR(13) NOT NULL DEFAULT 'a_fazer',
    posicao INT NOT NULL DEFAULT 0,
    criado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    atualizado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_tarefas_projeto_status_posicao_id (projeto_id, status, posicao, id),
    CONSTRAINT fk_tarefas_projeto FOREIGN KEY (projeto_id) REFERENCES projetos (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_tarefas_titulo CHECK (CHAR_LENGTH(TRIM(titulo)) > 0),
    CONSTRAINT chk_tarefas_prioridade CHECK (
        BINARY prioridade IN (BINARY 'baixa', BINARY 'media', BINARY 'alta')
    ),
    CONSTRAINT chk_tarefas_status CHECK (
        BINARY status IN (BINARY 'a_fazer', BINARY 'em_andamento', BINARY 'concluido')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
