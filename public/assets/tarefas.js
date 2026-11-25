const prioridades = { baixa: 'Baixa', media: 'Média', alta: 'Alta' };
const estados = { a_fazer: 'A fazer', em_andamento: 'Em andamento', concluido: 'Concluído' };

export function iniciarTarefas(requisitar) {
    const seletor = document.querySelector('#projeto-tarefas');
    const conteudo = document.querySelector('#conteudo-tarefas');
    const formulario = document.querySelector('#form-tarefa');
    const tituloForm = document.querySelector('#titulo-form-tarefa');
    const tituloLista = document.querySelector('#titulo-lista-tarefas');
    const salvar = document.querySelector('#salvar-tarefa');
    const cancelar = document.querySelector('#cancelar-tarefa');
    const lista = document.querySelector('#lista-tarefas');
    const estadoLista = document.querySelector('#estado-lista-tarefas');
    const mensagem = document.querySelector('#mensagem-tarefas');
    const titulo = formulario.elements.namedItem('titulo');
    const projetos = new Map();
    let projetoId = '';
    let editando = null;
    let tarefas = [];
    let ocupado = false;
    let restaurarSelecao = true;

    function bloquearAcoes(bloquear) {
        ocupado = bloquear;
        seletor.disabled = bloquear || projetos.size === 0;
        salvar.disabled = bloquear || projetoId === '';
        cancelar.disabled = bloquear;
        for (const botao of lista.querySelectorAll('button')) {
            botao.disabled = bloquear;
        }
    }

    function mostrarMensagem(texto, tipo) {
        mensagem.className = 'alert alert-' + tipo + ' mb-3';
        mensagem.textContent = texto;
        mensagem.hidden = false;
    }

    function informarErro(erro, alternativa) {
        mostrarMensagem(erro instanceof Error && erro.name === 'Error' ? erro.message : alternativa, 'danger');
    }

    function limparEdicao() {
        editando = null;
        formulario.reset();
        tituloForm.textContent = 'Nova tarefa';
        salvar.textContent = 'Criar tarefa';
        cancelar.hidden = true;
        for (const campo of formulario.querySelectorAll('[aria-invalid]')) {
            campo.removeAttribute('aria-invalid');
        }
    }

    function guardarSelecao() {
        const url = new URL(window.location.href);
        if (projetoId) {
            url.searchParams.set('projeto', projetoId);
        } else {
            url.searchParams.delete('projeto');
        }
        window.history.replaceState(null, '', url);
    }

    function fecharProjeto() {
        projetoId = '';
        tarefas = [];
        conteudo.hidden = true;
        lista.replaceChildren();
        limparEdicao();
        mensagem.hidden = true;
        guardarSelecao();
    }

    function atualizarProjetos() {
        const escolhido = seletor.value;
        const inicial = document.createElement('option');
        inicial.value = '';
        inicial.textContent = 'Selecione um projeto';
        const opcoes = [inicial];
        for (const [id, projeto] of projetos) {
            const opcao = document.createElement('option');
            opcao.value = id;
            opcao.textContent = projeto.nome;
            opcoes.push(opcao);
        }
        seletor.replaceChildren(...opcoes);
        seletor.value = projetos.has(escolhido) ? escolhido : projetoId;
        if (projetos.has(projetoId)) {
            tituloLista.textContent = 'Tarefas de ' + projetos.get(projetoId).nome;
        }
        bloquearAcoes(ocupado);
    }

    function itemTarefa(tarefa) {
        const item = document.createElement('li');
        const nome = document.createElement('h4');
        nome.className = 'h6 mb-1';
        nome.textContent = tarefa.titulo;
        const meta = document.createElement('small');
        meta.className = 'text-body-secondary';
        meta.textContent = prioridades[tarefa.prioridade] + ' · ' + estados[tarefa.status];
        item.append(nome, meta);
        if (tarefa.descricao) {
            const descricao = document.createElement('p');
            descricao.textContent = tarefa.descricao;
            item.append(descricao);
        }

        const acoes = document.createElement('div');
        acoes.className = 'd-flex flex-wrap gap-2 mt-3';
        const editar = document.createElement('button');
        editar.type = 'button';
        editar.className = 'btn btn-outline-primary btn-sm';
        editar.textContent = 'Editar';
        editar.setAttribute('aria-label', 'Editar tarefa ' + tarefa.titulo);
        editar.addEventListener('click', () => iniciarEdicao(tarefa.id));
        const excluir = document.createElement('button');
        excluir.type = 'button';
        excluir.className = 'btn btn-outline-danger btn-sm';
        excluir.textContent = 'Excluir';
        excluir.setAttribute('aria-label', 'Excluir tarefa ' + tarefa.titulo);
        excluir.addEventListener('click', () => excluirTarefa(tarefa));
        acoes.append(editar, excluir);
        item.append(acoes);
        return item;
    }

    function renderizarTarefas() {
        tarefas.sort((a, b) => a.posicao - b.posicao || a.id - b.id);
        lista.replaceChildren(...tarefas.map(itemTarefa));
        estadoLista.hidden = tarefas.length > 0;
    }

    async function selecionarProjeto(id) {
        if (ocupado) {
            return;
        }
        if (!id) {
            fecharProjeto();
            bloquearAcoes(false);
            return;
        }
        bloquearAcoes(true);
        try {
            const resultado = await requisitar('/api/projetos/' + id + '/tarefas');
            if (!projetos.has(id)) {
                return;
            }
            projetoId = id;
            seletor.value = id;
            tarefas = resultado;
            limparEdicao();
            renderizarTarefas();
            tituloLista.textContent = 'Tarefas de ' + projetos.get(id).nome;
            conteudo.hidden = false;
            mensagem.hidden = true;
            guardarSelecao();
        } catch (erro) {
            seletor.value = projetoId;
            informarErro(erro, 'Não foi possível carregar as tarefas.');
        } finally {
            bloquearAcoes(false);
        }
    }

    async function iniciarEdicao(id) {
        if (ocupado) {
            return;
        }
        const selecionado = projetoId;
        bloquearAcoes(true);
        try {
            const tarefa = await requisitar('/api/tarefas/' + id);
            if (projetoId !== selecionado) {
                return;
            }
            limparEdicao();
            editando = tarefa.id;
            tituloForm.textContent = 'Editar tarefa';
            salvar.textContent = 'Salvar alterações';
            cancelar.hidden = false;
            titulo.value = tarefa.titulo;
            formulario.elements.namedItem('descricao').value = tarefa.descricao ?? '';
            formulario.elements.namedItem('prioridade').value = tarefa.prioridade;
            formulario.elements.namedItem('status').value = tarefa.status;
            mensagem.hidden = true;
            titulo.focus();
        } catch (erro) {
            informarErro(erro, 'Não foi possível carregar a tarefa para edição.');
        } finally {
            bloquearAcoes(false);
        }
    }

    async function excluirTarefa(tarefa) {
        if (ocupado || !window.confirm('Excluir a tarefa "' + tarefa.titulo + '"?')) {
            return;
        }
        const selecionado = projetoId;
        bloquearAcoes(true);
        try {
            await requisitar('/api/tarefas/' + tarefa.id, { method: 'DELETE' });
            if (projetoId !== selecionado) {
                return;
            }
            tarefas = tarefas.filter(item => item.id !== tarefa.id);
            if (editando === tarefa.id) {
                limparEdicao();
            }
            renderizarTarefas();
            mostrarMensagem('Tarefa excluída.', 'success');
            titulo.focus();
        } catch (erro) {
            informarErro(erro, 'Não foi possível excluir a tarefa.');
        } finally {
            bloquearAcoes(false);
        }
    }

    formulario.addEventListener('submit', async evento => {
        evento.preventDefault();
        if (ocupado || !projetoId) {
            return;
        }
        const selecionado = projetoId;
        const alterando = editando !== null;
        const rota = alterando ? '/api/tarefas/' + editando : '/api/projetos/' + projetoId + '/tarefas';
        const campos = Object.fromEntries(new FormData(formulario));
        for (const campo of formulario.querySelectorAll('[aria-invalid]')) {
            campo.removeAttribute('aria-invalid');
        }
        bloquearAcoes(true);
        try {
            const tarefa = await requisitar(rota, {
                method: alterando ? 'PATCH' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(campos),
            });
            if (projetoId !== selecionado) {
                return;
            }
            tarefas = tarefas.filter(item => item.id !== tarefa.id);
            tarefas.push(tarefa);
            renderizarTarefas();
            limparEdicao();
            mostrarMensagem(alterando ? 'Tarefa atualizada.' : 'Tarefa criada.', 'success');
            titulo.focus();
        } catch (erro) {
            informarErro(erro, 'Não foi possível salvar a tarefa.');
            const campo = formulario.elements.namedItem(erro?.campo ?? '');
            if (campo) {
                campo.setAttribute('aria-invalid', 'true');
                campo.focus();
            }
        } finally {
            bloquearAcoes(false);
        }
    });

    seletor.addEventListener('change', () => selecionarProjeto(seletor.value));
    cancelar.addEventListener('click', () => {
        limparEdicao();
        mensagem.hidden = true;
        titulo.focus();
    });

    return {
        carregarProjetos(listaProjetos) {
            projetos.clear();
            for (const projeto of listaProjetos) {
                projetos.set(String(projeto.id), projeto);
            }
            atualizarProjetos();
            if (restaurarSelecao) {
                restaurarSelecao = false;
                const id = new URL(window.location.href).searchParams.get('projeto');
                if (projetos.has(id)) {
                    selecionarProjeto(id);
                }
            }
        },
        salvarProjeto(projeto) {
            projetos.set(String(projeto.id), projeto);
            atualizarProjetos();
        },
        removerProjeto(id) {
            projetos.delete(String(id));
            if (projetoId === String(id)) {
                fecharProjeto();
            }
            atualizarProjetos();
        },
    };
}
