'use strict';

const rotaProjetos = '/api/projetos';
const formulario = document.querySelector('#form-projeto');
const botaoCriar = document.querySelector('#criar-projeto');
const mensagem = document.querySelector('#mensagem-cadastro');
const estadoLista = document.querySelector('#estado-lista');
const lista = document.querySelector('#lista-projetos');
const recarregar = document.querySelector('#recarregar-projetos');
const cancelar = document.querySelector('#cancelar-edicao');
const tituloCadastro = document.querySelector('#titulo-cadastro');
let editando = null;
let ocupado = false;

async function requisitar(rota = rotaProjetos, opcoes) {
    const resposta = await fetch(rota, opcoes);
    if (resposta.status === 204) {
        return null;
    }
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

function bloquearAcoes(bloquear) {
    ocupado = bloquear;
    botaoCriar.disabled = bloquear;
    cancelar.disabled = bloquear;
    recarregar.disabled = bloquear;
    for (const botao of lista.querySelectorAll('button')) {
        botao.disabled = bloquear;
    }
}

function mostrarMensagem(texto, tipo) {
    mensagem.className = 'alert alert-' + tipo + ' mb-0 mt-3';
    mensagem.textContent = texto;
    mensagem.hidden = false;
}

function erroOperacao(erro, alternativa) {
    mostrarMensagem(erro instanceof Error && erro.name === 'Error' ? erro.message : alternativa, 'danger');
}

function atualizarEstadoLista() {
    if (recarregar.hidden) {
        estadoLista.textContent = 'Nenhum projeto cadastrado.';
        estadoLista.hidden = lista.childElementCount > 0;
    }
}

function limparEdicao() {
    editando = null;
    formulario.reset();
    for (const campo of formulario.querySelectorAll('[aria-invalid]')) {
        campo.removeAttribute('aria-invalid');
    }
    tituloCadastro.textContent = 'Novo projeto';
    botaoCriar.textContent = 'Criar projeto';
    cancelar.hidden = true;
}

function itemProjeto(projeto) {
    const item = document.createElement('li');
    item.id = 'projeto-' + projeto.id;
    const titulo = document.createElement('h3');
    titulo.className = 'h6 mb-0';
    titulo.textContent = projeto.nome;
    item.append(titulo);
    if (projeto.descricao) {
        const descricao = document.createElement('p');
        descricao.textContent = projeto.descricao;
        item.append(descricao);
    }
    const acoes = document.createElement('div');
    acoes.className = 'd-flex flex-wrap gap-2 mt-3';
    const editar = document.createElement('button');
    editar.type = 'button';
    editar.className = 'btn btn-outline-primary btn-sm';
    editar.textContent = 'Editar';
    editar.setAttribute('aria-label', 'Editar ' + projeto.nome);
    editar.addEventListener('click', () => iniciarEdicao(projeto.id));
    const excluir = document.createElement('button');
    excluir.type = 'button';
    excluir.className = 'btn btn-outline-danger btn-sm';
    excluir.textContent = 'Excluir';
    excluir.setAttribute('aria-label', 'Excluir ' + projeto.nome);
    excluir.addEventListener('click', () => excluirProjeto(projeto, item));
    acoes.append(editar, excluir);
    item.append(acoes);
    return item;
}

async function iniciarEdicao(id) {
    if (ocupado) {
        return;
    }
    bloquearAcoes(true);
    try {
        const projeto = await requisitar(rotaProjetos + '/' + id);
        limparEdicao();
        editando = projeto.id;
        tituloCadastro.textContent = 'Editar projeto';
        botaoCriar.textContent = 'Salvar alterações';
        cancelar.hidden = false;
        formulario.elements.namedItem('nome').value = projeto.nome;
        formulario.elements.namedItem('descricao').value = projeto.descricao ?? '';
        formulario.elements.namedItem('nome').focus();
        mensagem.hidden = true;
    } catch (erro) {
        erroOperacao(erro, 'Não foi possível carregar o projeto para edição.');
    } finally {
        bloquearAcoes(false);
    }
}

async function excluirProjeto(projeto, item) {
    if (ocupado || !window.confirm('Excluir o projeto "' + projeto.nome + '"?')) {
        return;
    }
    bloquearAcoes(true);
    try {
        await requisitar(rotaProjetos + '/' + projeto.id, { method: 'DELETE' });
        item.remove();
        if (editando === projeto.id) {
            limparEdicao();
        }
        atualizarEstadoLista();
        mostrarMensagem('Projeto excluído.', 'success');
        formulario.elements.namedItem('nome').focus();
    } catch (erro) {
        erroOperacao(erro, 'Não foi possível excluir o projeto.');
    } finally {
        bloquearAcoes(false);
    }
}

async function carregarProjetos() {
    bloquearAcoes(true);
    recarregar.hidden = true;
    estadoLista.hidden = false;
    estadoLista.textContent = 'Carregando projetos…';
    try {
        const projetos = await requisitar();
        lista.replaceChildren(...projetos.map(itemProjeto));
        atualizarEstadoLista();
    } catch {
        estadoLista.textContent = 'Não foi possível carregar os projetos.';
        recarregar.hidden = false;
    } finally {
        bloquearAcoes(false);
    }
}

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    if (ocupado) {
        return;
    }

    const campos = new FormData(formulario);
    for (const campo of formulario.querySelectorAll('[aria-invalid]')) {
        campo.removeAttribute('aria-invalid');
    }
    bloquearAcoes(true);
    mensagem.hidden = true;

    try {
        const alterando = editando !== null;
        const rota = alterando ? rotaProjetos + '/' + editando : rotaProjetos;
        const projeto = await requisitar(rota, {
            method: alterando ? 'PATCH' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome: campos.get('nome'), descricao: campos.get('descricao') }),
        });
        const item = itemProjeto(projeto);
        const anterior = alterando ? document.getElementById('projeto-' + projeto.id) : null;
        if (anterior) {
            anterior.replaceWith(item);
        } else {
            lista.prepend(item);
        }
        atualizarEstadoLista();
        limparEdicao();
        mostrarMensagem(alterando ? 'Projeto atualizado.' : 'Projeto criado.', 'success');
    } catch (erro) {
        erroOperacao(erro, 'Não foi possível salvar o projeto. Tente novamente.');
        if (erro.campo === 'nome' || erro.campo === 'descricao') {
            const campo = formulario.elements.namedItem(erro.campo);
            campo.setAttribute('aria-invalid', 'true');
            campo.focus();
        }
    } finally {
        bloquearAcoes(false);
    }
});

recarregar.addEventListener('click', carregarProjetos);
cancelar.addEventListener('click', () => {
    limparEdicao();
    mensagem.hidden = true;
    formulario.elements.namedItem('nome').focus();
});
carregarProjetos();
