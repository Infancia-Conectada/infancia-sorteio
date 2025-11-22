// ========================================
// PAINEL DO AFILIADO - script de frontend
// ========================================
// Este script mostra as estatísticas do afiliado, constrói o link de
// indicação e provê ações (copiar link, visitar, logout).
//
// Importante:
// - Os dados básicos do afiliado (id, nome, code) são mantidos no
//   localStorage no momento do login para inicializar a UI rapidamente.
// - Estatísticas reais são obtidas via fetch('stats.php') que depende de
//   uma sessão PHP válida (credentials: 'same-origin').
// - Se o servidor retornar dados, o script atualiza o número e o link.
// ========================================

// Elementos do DOM
const elementos = {
    loadingScreen: document.getElementById('loadingScreen'),
    nomeAfiliado: document.getElementById('nomeAfiliado'),
    linkAfiliado: document.getElementById('linkAfiliado'),
    btnCopiar: document.getElementById('btnCopiar'),
    btnVisitar: document.getElementById('btnVisitar'),
    btnLogout: document.getElementById('btnLogout'),
    totalCadastros: document.getElementById('totalCadastros')
};

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

/**
 * Obtém dados da sessão PHP
 */
function obterDadosSessao() {
    // Os dados já estão disponíveis no PHP via session, mas aqui usamos
    // localStorage para inicializar a interface sem depender da chamada ao servidor.
    return {
        nome: localStorage.getItem('nome_afiliado') || 'Afiliado',
        code: localStorage.getItem('code_afiliado') || '',
        id: localStorage.getItem('id_afiliado') || ''
    };
}

/**
 * Constrói o link de afiliado
 */
function construirLinkAfiliado(code) {
    const baseUrl = window.location.origin;
    return `${baseUrl}/?ref=${code}`;
}

/**
 * Anima a contagem de um número
 */
function animarNumero(elemento, valorFinal) {
    const duracao = 1500;
    const incremento = valorFinal / (duracao / 16);
    let valorAtual = 0;

    const intervalo = setInterval(() => {
        valorAtual += incremento;
        if (valorAtual >= valorFinal) {
            valorAtual = valorFinal;
            clearInterval(intervalo);
        }
        elemento.textContent = Math.floor(valorAtual);
    }, 16);
}

// ========================================
// FUNÇÕES DE AÇÃO
// ========================================

/**
 * Copia o link de afiliado
 */
async function copiarLink() {
    try {
        const link = elementos.linkAfiliado.textContent;
        await navigator.clipboard.writeText(link);
        
        const btnTexto = elementos.btnCopiar.querySelector('.btn-text');
        const textoOriginal = btnTexto.textContent;
        
        btnTexto.textContent = 'Copiado!';
        elementos.btnCopiar.classList.add('copiado');
        
        setTimeout(() => {
            btnTexto.textContent = textoOriginal;
            elementos.btnCopiar.classList.remove('copiado');
        }, 2000);
        
    } catch (error) {
        console.error('Erro ao copiar:', error);
        alert('Erro ao copiar o link');
    }
}

/**
 * Realiza logout
 */
function realizarLogout() {
    if (confirm('Deseja realmente sair do painel?')) {
        // Limpar localStorage
        localStorage.removeItem('id_afiliado');
        localStorage.removeItem('nome_afiliado');
        localStorage.removeItem('code_afiliado');
        
        // Redirecionar para página que destroi a sessão
        // De /afiliados/painel/ para /afiliados/logout.php
        window.location.href = '../logout.php';
    }
}

// ========================================
// INICIALIZAÇÃO
// ========================================

/**
 * Inicializa o painel
 */
function inicializarPainel() {
    try {
        // Obter dados da sessão
        const dados = obterDadosSessao();
        
        // Atualizar nome do afiliado
        elementos.nomeAfiliado.textContent = dados.nome;
        
        // Construir e exibir link
        const linkAfiliado = construirLinkAfiliado(dados.code);
        elementos.linkAfiliado.textContent = linkAfiliado;
        elementos.btnVisitar.href = linkAfiliado;
        
        // Configurar event listeners
        elementos.btnCopiar.addEventListener('click', copiarLink);
        elementos.btnLogout.addEventListener('click', realizarLogout);
        
        // Buscar estatísticas reais do servidor
        // - Repare que usamos { credentials: 'same-origin' } para garantir que
        //   o cookie de sessão (definido pelo PHP) seja enviado na requisição.
        // - A resposta deve ser um JSON {sucesso:true, total_cadastros: N, ...}
        try {
            fetch('stats.php', { credentials: 'same-origin' })
                .then(res => {
                    if (!res.ok) throw new Error('Erro ao buscar estatísticas');
                    return res.json();
                })
                .then(data => {
                    if (data && data.sucesso) {
                        const total = parseInt(data.total_cadastros || 0, 10);
                        animarNumero(elementos.totalCadastros, total);

                        // Atualizar nome/link com dados do servidor quando disponíveis
                        if (data.nome_afiliado) {
                            elementos.nomeAfiliado.textContent = data.nome_afiliado;
                        }
                        if (data.code_afiliado) {
                            const link = construirLinkAfiliado(data.code_afiliado);
                            elementos.linkAfiliado.textContent = link;
                            elementos.btnVisitar.href = link;
                        }
                    } else {
                        console.warn('Nenhuma estatística disponível, usando 0');
                        animarNumero(elementos.totalCadastros, 0);
                    }
                })
                .catch(err => {
                    animarNumero(elementos.totalCadastros, 0);
                });
        } catch (err) {
            animarNumero(elementos.totalCadastros, 0);
        }
        
    } catch (error) {
        // Erro ao inicializar painel
    } finally {
        // Esconder loading
        elementos.loadingScreen.classList.add('hidden');
    }
}

// Iniciar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarPainel);
} else {
    inicializarPainel();
}
