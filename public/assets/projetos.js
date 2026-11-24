'use strict';

const rotaProjetos = '/api/projetos';
const formulario = document.querySelector('#form-projeto');
const botaoCriar = document.querySelector('#criar-projeto');
const mensagem = document.querySelector('#mensagem-cadastro');
const estadoLista = document.querySelector('#estado-lista');
const lista = document.querySelector('#lista-projetos');
const recarregar = document.querySelector('#recarregar-projetos');

async function requisitar(opcoes) {
    const resposta = await fetch(rotaProjetos, opcoes);
    const tipo = resposta.headers.get('content-type') ?? '';
    if (!tipo.toLowerCase().startsWith('application/json')) {
        throw new Error('A API de projetos está indisponível.');
    }
    const dados = await resposta.json();
    if (!resposta.ok) {
        const erro = new Error(dados.erro?.mensagem ?? 'Não foi possível concluir a operação.');
        erro.campo = dados.erro?.campo;
        throw erro;
    }
    return dados;
}

function itemProjeto(projeto) {
    const item = document.createElement('li');
    const titulo = document.createElement('h3');
    titulo.className = 'h6 mb-0';
    titulo.textContent = projeto.nome;
    item.append(titulo);
    if (projeto.descricao) {
        const descricao = document.createElement('p');
        descricao.textContent = projeto.descricao;
        item.append(descricao);
    }
    return item;
}

async function carregarProjetos() {
    botaoCriar.disabled = true;
    recarregar.hidden = true;
    estadoLista.hidden = false;
    estadoLista.textContent = 'Carregando projetos…';
    try {
        const projetos = await requisitar();
        lista.replaceChildren(...projetos.map(itemProjeto));
        estadoLista.textContent = 'Nenhum projeto cadastrado.';
        estadoLista.hidden = projetos.length > 0;
    } catch {
        estadoLista.textContent = 'Não foi possível carregar os projetos.';
        recarregar.hidden = false;
    } finally {
        botaoCriar.disabled = false;
    }
}

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    if (botaoCriar.disabled) {
        return;
    }

    const campos = new FormData(formulario);
    for (const campo of formulario.querySelectorAll('[aria-invalid]')) {
        campo.removeAttribute('aria-invalid');
    }
    botaoCriar.disabled = true;
    recarregar.disabled = true;
    mensagem.hidden = true;

    try {
        const projeto = await requisitar({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome: campos.get('nome'), descricao: campos.get('descricao') }),
        });
        lista.prepend(itemProjeto(projeto));
        if (recarregar.hidden) {
            estadoLista.hidden = true;
        }
        formulario.reset();
        mensagem.className = 'alert alert-success mb-0 mt-3';
        mensagem.textContent = 'Projeto criado.';
    } catch (erro) {
        mensagem.className = 'alert alert-danger mb-0 mt-3';
        mensagem.textContent = erro instanceof Error && erro.name === 'Error'
            ? erro.message
            : 'Não foi possível salvar o projeto. Tente novamente.';
        if (erro.campo === 'nome' || erro.campo === 'descricao') {
            const campo = formulario.elements.namedItem(erro.campo);
            campo.setAttribute('aria-invalid', 'true');
            campo.focus();
        }
    } finally {
        mensagem.hidden = false;
        recarregar.disabled = false;
        botaoCriar.disabled = false;
    }
});

recarregar.addEventListener('click', carregarProjetos);
carregarProjetos();
