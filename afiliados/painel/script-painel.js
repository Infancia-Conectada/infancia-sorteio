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
    totalCadastros: document.getElementById('totalCadastros'),
    listaCadastrados: document.getElementById('listaCadastrados')
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

/**
 * Renderiza a lista de cadastrados
 */
function renderizarCadastrados(cadastrados) {
    if (!cadastrados || cadastrados.length === 0) {
        elementos.listaCadastrados.innerHTML = `
            <div class="empty-state">
                <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <p class="empty-text">Nenhum cadastro ainda</p>
                <p class="empty-subtext">Compartilhe seu link para começar a receber cadastros!</p>
            </div>
        `;
        return;
    }

    const html = cadastrados.map((cadastrado, index) => `
        <div class="cadastrado-item" style="animation-delay: ${index * 0.05}s">
            <div class="cadastrado-avatar">
                ${cadastrado.avatar_url ? 
                    `<img src="${cadastrado.avatar_url}" alt="${cadastrado.nome}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23ccc%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E'">` :
                    `<div class="avatar-placeholder">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>`
                }
            </div>
            <div class="cadastrado-info">
                <p class="cadastrado-nome">${cadastrado.nome}</p>
                <a href="https://instagram.com/${cadastrado.instagram.replace('@', '')}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="cadastrado-instagram">
                    ${cadastrado.instagram}
                </a>
            </div>
            <div class="cadastrado-data">
                <span class="cadastrado-data-text">${formatarData(cadastrado.criado_em)}</span>
            </div>
        </div>
    `).join('');

    elementos.listaCadastrados.innerHTML = html;
}

/**
 * Formata data para exibição
 */
function formatarData(dataString) {
    const data = new Date(dataString);
    const hoje = new Date();
    const ontem = new Date(hoje);
    ontem.setDate(ontem.getDate() - 1);

    const dataFormatada = data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    const horaFormatada = data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    // Se for hoje
    if (data.toDateString() === hoje.toDateString()) {
        return `Hoje às ${horaFormatada}`;
    }
    
    // Se foi ontem
    if (data.toDateString() === ontem.toDateString()) {
        return `Ontem às ${horaFormatada}`;
    }

    return `${dataFormatada} às ${horaFormatada}`;
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

                        // Renderizar lista de cadastrados
                        if (data.cadastrados) {
                            renderizarCadastrados(data.cadastrados);
                        }
                    } else {
                        console.warn('Nenhuma estatística disponível, usando 0');
                        animarNumero(elementos.totalCadastros, 0);
                        renderizarCadastrados([]);
                    }
                })
                .catch(err => {
                    animarNumero(elementos.totalCadastros, 0);
                    renderizarCadastrados([]);
                });
        } catch (err) {
            animarNumero(elementos.totalCadastros, 0);
            renderizarCadastrados([]);
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