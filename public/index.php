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
    <script src="/assets/projetos.js" defer></script>
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
    </main>
</body>
</html>
