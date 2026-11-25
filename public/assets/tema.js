(() => {
    const chave = 'devflow-tema';
    const raiz = document.documentElement;
    const sistema = window.matchMedia('(prefers-color-scheme: dark)');
    let preferencia = null;
    let alternador;

    try {
        const salvo = window.localStorage.getItem(chave);
        if (salvo === 'dark' || salvo === 'light') {
            preferencia = salvo;
        }
    } catch {
        // A preferência do sistema continua disponível.
    }

    function aplicar() {
        const tema = preferencia ?? (sistema.matches ? 'dark' : 'light');
        raiz.dataset.bsTheme = tema;
        alternador?.setAttribute('aria-pressed', String(tema === 'dark'));
    }

    aplicar();
    sistema.addEventListener('change', aplicar);

    document.addEventListener('DOMContentLoaded', () => {
        alternador = document.getElementById('alternar-tema');
        aplicar();
        alternador.hidden = false;
        alternador.addEventListener('click', () => {
            preferencia = raiz.dataset.bsTheme === 'dark' ? 'light' : 'dark';
            aplicar();
            try {
                window.localStorage.setItem(chave, preferencia);
            } catch {
                // O tema escolhido permanece aplicado nesta página.
            }
        });
    }, { once: true });
})();
