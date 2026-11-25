<?php

header("Content-Security-Policy: default-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Projetos | DevFlow</title>
    <link rel="stylesheet" href="/assets/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/projetos.css">
    <script src="/assets/projetos.js" type="module"></script>
</head>
<body>
    <header class="marca">
        <div class="container">
            <a href="/" class="marca-link">DevFlow</a>
        </div>
    </header>
    <main class="container py-4 py-md-5">
        <h1 class="mb-4 mb-md-5">Projetos</h1>
        <div class="row g-4 align-items-start">
            <section class="col-lg-5" aria-labelledby="titulo-cadastro">
                <div class="painel">
                    <h2 id="titulo-cadastro">Novo projeto</h2>
                    <form id="form-projeto" method="post">
                        <div class="mb-3">
                            <label class="form-label" for="nome">Nome</label>
                            <input class="form-control" type="text" id="nome" name="nome" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="descricao">
                                Descrição <span class="text-body-secondary">(opcional)</span>
                            </label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="4"></textarea>
                        </div>
                        <button type="submit" id="criar-projeto" class="btn btn-primary" disabled>Criar projeto</button>
                        <button type="button" id="cancelar-edicao" class="btn btn-outline-secondary ms-2" hidden>
                            Cancelar edição
                        </button>
                        <p id="mensagem-cadastro" class="mb-0 mt-3" role="status" hidden></p>
                    </form>
                    <noscript><p class="mt-3 mb-0">Ative o JavaScript para cadastrar e listar projetos.</p></noscript>
                </div>
            </section>
            <section class="col-lg-7" aria-labelledby="titulo-lista">
                <div class="painel">
                    <h2 id="titulo-lista">Projetos cadastrados</h2>
                    <p id="estado-lista" class="text-body-secondary mb-0" role="status">Carregando projetos…</p>
                    <button type="button" id="recarregar-projetos" class="btn btn-outline-primary mt-3" hidden>
                        Tentar novamente
                    </button>
                    <ul id="lista-projetos" class="lista-projetos list-unstyled mb-0"></ul>
                </div>
            </section>
        </div>
        <section class="painel mt-4" aria-labelledby="titulo-tarefas">
            <h2 id="titulo-tarefas">Tarefas do projeto</h2>
            <label class="form-label" for="projeto-tarefas">Projeto</label>
            <select class="form-select mb-3" id="projeto-tarefas" disabled>
                <option value="">Selecione um projeto</option>
            </select>
            <div id="mensagem-tarefas" role="status" aria-atomic="true"></div>
            <div id="conteudo-tarefas" hidden>
                <div class="mb-4">
                    <h3 id="titulo-form-tarefa" class="h6">Nova tarefa</h3>
                    <form id="form-tarefa" method="post" class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="titulo-tarefa">Título</label>
                            <input class="form-control" type="text" id="titulo-tarefa" name="titulo" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="prioridade-tarefa">Prioridade</label>
                            <select class="form-select" id="prioridade-tarefa" name="prioridade">
                                <option value="baixa">Baixa</option>
                                <option value="media">Média</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="status-tarefa">Estado</label>
                            <select class="form-select" id="status-tarefa" name="status">
                                <option value="a_fazer">A fazer</option>
                                <option value="em_andamento">Em andamento</option>
                                <option value="concluido">Concluído</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="descricao-tarefa">Descrição (opcional)</label>
                            <textarea class="form-control" id="descricao-tarefa" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary" id="salvar-tarefa" disabled>Criar tarefa</button>
                            <button type="button" class="btn btn-outline-secondary" id="cancelar-tarefa" hidden>
                                Cancelar edição
                            </button>
                        </div>
                    </form>
                </div>
                <h3 id="titulo-lista-tarefas" class="h5 mb-3">Quadro de tarefas</h3>
                <div id="quadro-tarefas" class="row row-cols-1 row-cols-lg-3 g-3">
                    <?php foreach (['a_fazer' => 'A fazer', 'em_andamento' => 'Em andamento', 'concluido' => 'Concluído'] as $estado => $rotulo): ?>
                        <section class="col" data-estado="<?= $estado ?>" aria-labelledby="coluna-<?= $estado ?>">
                            <div class="kanban-coluna h-100">
                                <h4 id="coluna-<?= $estado ?>" class="h6 mb-3"><?= $rotulo ?></h4>
                                <p class="kanban-vazia text-body-secondary mb-0">Nenhuma tarefa.</p>
                                <ul class="list-unstyled mb-0"></ul>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
