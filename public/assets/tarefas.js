const prioridades = { baixa: 'Baixa', media: 'Média', alta: 'Alta' };
const estados = { a_fazer: 'A fazer', em_andamento: 'Em andamento', concluido: 'Concluído' };

export function iniciarTarefas(requisitar, confirmarExclusao = pergunta => window.confirm(pergunta)) {
    const seletor = document.querySelector('#projeto-tarefas');
    const conteudo = document.querySelector('#conteudo-tarefas');
    const formulario = document.querySelector('#form-tarefa');
    const tituloForm = document.querySelector('#titulo-form-tarefa');
    const tituloLista = document.querySelector('#titulo-lista-tarefas');
    const salvar = document.querySelector('#salvar-tarefa');
    const cancelar = document.querySelector('#cancelar-tarefa');
    const quadro = document.querySelector('#quadro-tarefas');
    const totalProgresso = document.querySelector('#progresso-total');
    const percentualProgresso = document.querySelector('#percentual-progresso');
    const barraProgresso = document.querySelector('#barra-progresso');
    const colunas = [...quadro.querySelectorAll('[data-estado]')].map(coluna => {
        const estado = coluna.dataset.estado;
        return {
            estado,
            lista: coluna.querySelector('ul'),
            vazia: coluna.querySelector('.kanban-vazia'),
            contagem: document.getElementById('progresso-' + estado),
        };
    });
    const mensagem = document.querySelector('#mensagem-tarefas');
    const titulo = formulario.elements.namedItem('titulo');
    const campoEstado = formulario.elements.namedItem('status');
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
        for (const controle of quadro.querySelectorAll('button, select')) {
            controle.disabled = bloquear;
        }
    }

    function mostrarMensagem(texto, tipo) {
        mensagem.className = 'alert alert-' + tipo + ' mb-3';
        mensagem.textContent = texto;
    }

    function limparMensagem() {
        mensagem.className = '';
        mensagem.textContent = '';
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
        renderizarTarefas();
        limparEdicao();
        limparMensagem();
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
            tituloLista.textContent = 'Quadro de ' + projetos.get(projetoId).nome;
        }
        bloquearAcoes(ocupado);
    }

    function itemTarefa(tarefa) {
        const item = document.createElement('li');
        item.className = 'kanban-cartao';
        const nome = document.createElement('h5');
        nome.className = 'h6 mb-2';
        nome.textContent = tarefa.titulo;
        const prioridade = document.createElement('span');
        prioridade.className = 'badge kanban-prioridade-' + tarefa.prioridade;
        prioridade.textContent = prioridades[tarefa.prioridade];
        item.append(nome, prioridade);
        if (tarefa.descricao) {
            const descricao = document.createElement('p');
            descricao.className = 'mb-0 mt-2';
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

        const formMover = document.createElement('form');
        formMover.className = 'mt-3';
        const rotuloMover = document.createElement('label');
        rotuloMover.className = 'form-label small mb-1';
        rotuloMover.textContent = 'Mover para';
        const destino = document.createElement('select');
        destino.id = 'destino-tarefa-' + tarefa.id;
        destino.className = 'form-select form-select-sm';
        destino.required = true;
        destino.setAttribute('aria-label', 'Mover para outro estado: ' + tarefa.titulo);
        rotuloMover.htmlFor = destino.id;
        const inicial = document.createElement('option');
        inicial.value = '';
        inicial.textContent = 'Selecione o estado';
        destino.append(inicial);
        for (const [estado, rotulo] of Object.entries(estados)) {
            if (estado !== tarefa.status) {
                const opcao = document.createElement('option');
                opcao.value = estado;
                opcao.textContent = rotulo;
                destino.append(opcao);
            }
        }
        const mover = document.createElement('button');
        mover.type = 'submit';
        mover.className = 'btn btn-outline-primary btn-sm mt-2';
        mover.textContent = 'Mover';
        mover.setAttribute('aria-label', 'Mover tarefa ' + tarefa.titulo);
        formMover.append(rotuloMover, destino, mover);
        formMover.addEventListener('submit', evento => {
            evento.preventDefault();
            moverTarefa(tarefa, destino.value);
        });
        item.append(formMover);
        return item;
    }

    function renderizarTarefas() {
        tarefas.sort((a, b) => a.posicao - b.posicao || a.id - b.id);
        const contagens = {};
        for (const coluna of colunas) {
            const itens = tarefas.filter(tarefa => tarefa.status === coluna.estado);
            coluna.lista.replaceChildren(...itens.map(itemTarefa));
            coluna.vazia.hidden = itens.length > 0;
            contagens[coluna.estado] = itens.length;
            coluna.contagem.textContent = itens.length;
        }
        const total = tarefas.length;
        const percentual = total === 0 ? 0 : Math.round(contagens.concluido * 100 / total);
        totalProgresso.textContent = total;
        percentualProgresso.textContent = percentual + '%';
        barraProgresso.value = percentual;
    }

    async function moverTarefa(tarefa, destino) {
        if (ocupado || !destino || destino === tarefa.status) {
            return;
        }
        const selecionado = projetoId;
        bloquearAcoes(true);
        mostrarMensagem('Movendo tarefa…', 'info');
        try {
            const atualizada = await requisitar('/api/tarefas/' + tarefa.id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: destino }),
            });
            if (projetoId !== selecionado) {
                return;
            }
            tarefas = tarefas.map(item => item.id === tarefa.id ? atualizada : item);
            if (editando === tarefa.id && campoEstado.value === tarefa.status) {
                campoEstado.value = atualizada.status;
            }
            renderizarTarefas();
            mostrarMensagem('Tarefa movida para ' + estados[atualizada.status] + '.', 'success');
        } catch (erro) {
            informarErro(erro, 'Não foi possível mover a tarefa.');
        } finally {
            bloquearAcoes(false);
            if (projetoId === selecionado) {
                document.getElementById('destino-tarefa-' + tarefa.id)?.focus();
            }
        }
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
            tituloLista.textContent = 'Quadro de ' + projetos.get(id).nome;
            conteudo.hidden = false;
            limparMensagem();
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
            campoEstado.value = tarefa.status;
            limparMensagem();
            titulo.focus();
        } catch (erro) {
            informarErro(erro, 'Não foi possível carregar a tarefa para edição.');
        } finally {
            bloquearAcoes(false);
        }
    }

    async function excluirTarefa(tarefa) {
        if (ocupado) {
            return;
        }
        const confirmado = await confirmarExclusao('Excluir a tarefa "' + tarefa.titulo + '"?');
        if (!confirmado || ocupado) {
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
        limparMensagem();
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
