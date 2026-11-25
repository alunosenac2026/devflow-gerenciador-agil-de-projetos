<?php

header("Content-Security-Policy: default-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');

?>
<!doctype html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Projetos | DevFlow</title>
    <link rel="icon" type="image/svg+xml" sizes="any" href="/assets/favicon.svg">
    <script src="/assets/tema.js"></script>
    <link rel="stylesheet" href="/assets/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/projetos.css">
    <script src="/assets/projetos.js" type="module"></script>
</head>
<body>
    <header class="marca">
        <div class="container marca-conteudo">
            <a href="/" class="marca-link">
                <svg class="marca-sinal" viewBox="0 0 160 140" aria-hidden="true" focusable="false">
                    <use href="/assets/favicon.svg#panda"></use>
                </svg>
                <span>Dev<span class="marca-flow">Flow</span></span>
            </a>
            <nav class="marca-nav d-none d-sm-flex" aria-label="Seções">
                <a href="#projetos">Projetos</a>
                <a href="#titulo-tarefas">Tarefas</a>
            </nav>
            <button type="button" class="btn alternar-tema" id="alternar-tema" aria-pressed="false" hidden>
                <span class="alternar-tema-sinal" aria-hidden="true"></span>
                Modo escuro
            </button>
        </div>
    </header>
    <main class="container area-trabalho">
        <h1>Área de trabalho</h1>
        <div class="area-grade">
            <div class="area-projetos" id="projetos">
                <section class="painel" aria-labelledby="titulo-cadastro">
                    <h2 id="titulo-cadastro">Novo projeto</h2>
                    <form id="form-projeto" method="post">
                        <div class="mb-3">
                            <label class="form-label" for="nome">Nome</label>
                            <input class="form-control" type="text" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
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
                </section>
                <section class="painel" aria-labelledby="titulo-lista">
                    <h2 id="titulo-lista">Projetos cadastrados</h2>
                    <p id="estado-lista" class="estado-vazio mb-0" role="status">Carregando projetos…</p>
                    <button type="button" id="recarregar-projetos" class="btn btn-outline-primary mt-3" hidden>
                        Tentar novamente
                    </button>
                    <ul id="lista-projetos" class="lista-projetos list-unstyled mb-0"></ul>
                </section>
            </div>
            <section class="painel painel-tarefas" aria-labelledby="titulo-tarefas">
                <div class="tarefas-cabecalho">
                    <h2 id="titulo-tarefas">Tarefas do projeto</h2>
                    <div class="seletor-projeto">
                        <label class="form-label" for="projeto-tarefas">Projeto</label>
                        <select class="form-select" id="projeto-tarefas" disabled>
                            <option value="">Selecione um projeto</option>
                        </select>
                    </div>
                </div>
                <div id="mensagem-tarefas" role="status" aria-atomic="true"></div>
                <div id="conteudo-tarefas" hidden>
                    <?php $estadosTarefa = ['a_fazer' => 'A fazer', 'em_andamento' => 'Em andamento', 'concluido' => 'Concluído']; ?>
                    <section class="painel-progresso mb-4" aria-labelledby="titulo-progresso">
                        <h3 id="titulo-progresso" class="h5 mb-3">Progresso do projeto</h3>
                        <dl class="indicadores mb-3">
                            <?php foreach (['total' => 'Total de tarefas'] + $estadosTarefa as $indicador => $rotulo): ?>
                                <div class="indicador">
                                    <dt class="small"><?= $rotulo ?></dt>
                                    <dd id="progresso-<?= $indicador ?>" class="mb-0">0</dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                        <div class="d-flex justify-content-between gap-2 mb-1">
                            <label for="barra-progresso" class="form-label small mb-0">Conclusão do projeto</label>
                            <span id="percentual-progresso" class="small fw-semibold">0%</span>
                        </div>
                        <progress id="barra-progresso" class="barra-progresso d-block w-100" value="0" max="100"></progress>
                    </section>
                    <div class="editor-tarefa mb-4">
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
                                <textarea class="form-control" id="descricao-tarefa" name="descricao" rows="2"></textarea>
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
                    <div id="quadro-tarefas" class="row row-cols-1 row-cols-xl-3 g-3">
                        <?php foreach ($estadosTarefa as $estado => $rotulo): ?>
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
                <p class="estado-vazio projeto-vazio mb-0">Selecione um projeto para ver o quadro Kanban e o progresso.</p>
            </section>
        </div>
    </main>
    <dialog id="confirmar-exclusao" class="painel dialogo-exclusao" aria-labelledby="titulo-confirmacao" aria-describedby="texto-confirmacao">
        <h2 id="titulo-confirmacao">Confirmar exclusão</h2>
        <p id="texto-confirmacao"></p>
        <form method="dialog" class="d-flex flex-wrap justify-content-end gap-2">
            <button type="submit" class="btn btn-outline-secondary" value="cancelar" autofocus>Cancelar</button>
            <button type="submit" class="btn btn-danger" value="excluir">Excluir</button>
        </form>
    </dialog>
</body>
</html>
