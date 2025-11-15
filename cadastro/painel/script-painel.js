// ========================================
// CONFIGURAÇÕES E VARIÁVEIS GLOBAIS
// ========================================

// URL da API - Substitua pelo seu endpoint real
const API_BASE_URL = '/api'; // Exemplo: 'https://seusite.com.br/api'

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
// FUNÇÕES DE API
// ========================================

/**
 * Busca os dados do afiliado no banco de dados
 * @returns {Promise<Object>} Dados do afiliado
 */
async function buscarDadosAfiliado() {
    try {
        // IMPLEMENTAÇÃO REAL - Descomente e ajuste conforme sua API
        /*
        const token = localStorage.getItem('token_afiliado');
        
        if (!token) {
            window.location.href = '/login/';
            return;
        }

        const response = await fetch(`${API_BASE_URL}/afiliado/dados`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            if (response.status === 401) {
                // Token inválido ou expirado
                localStorage.removeItem('token_afiliado');
                window.location.href = '/login/';
                return;
            }
            throw new Error('Erro ao buscar dados do afiliado');
        }

        const dados = await response.json();
        return dados;
        */

        // SIMULAÇÃO PARA TESTES - Remove isso em produção
        return await simularBuscaBanco();
        
    } catch (error) {
        console.error('Erro ao buscar dados:', error);
        mostrarErro('Erro ao carregar dados. Tente novamente.');
        return null;
    }
}

/**
 * Simula busca no banco de dados (APENAS PARA TESTES)
 * Remove esta função em produção
 */
function simularBuscaBanco() {
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve({
                nome: 'João Silva',
                email: 'joao.silva@email.com',
                linkAfiliado: 'https://infanciaconectada.com.br/ref/ABC123XYZ',
                totalCadastros: 23
            });
        }, 1000);
    });
}

/**
 * Busca a contagem de cadastros do banco de dados
 * Esta função deve ser implementada para buscar os cadastros em tempo real
 * 
 * LÓGICA SUGERIDA PARA O BACKEND:
 * 
 * SELECT COUNT(*) as total 
 * FROM cadastros 
 * WHERE codigo_afiliado = ? 
 * AND status = 'ativo'
 * 
 * Onde:
 * - codigo_afiliado é o código único do afiliado (ex: ABC123XYZ)
 * - status = 'ativo' garante que só conta cadastros validados
 * 
 * @returns {Promise<number>} Número de cadastros
 */
async function buscarContagemCadastros() {
    try {
        // IMPLEMENTAÇÃO REAL - Descomente e ajuste conforme sua API
        /*
        const token = localStorage.getItem('token_afiliado');
        
        const response = await fetch(`${API_BASE_URL}/afiliado/cadastros/contagem`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Erro ao buscar contagem de cadastros');
        }

        const dados = await response.json();
        return dados.total; // Assumindo que a API retorna { total: 23 }
        */

        // SIMULAÇÃO - Remove em produção
        return 23;
        
    } catch (error) {
        console.error('Erro ao buscar cadastros:', error);
        return 0;
    }
}

// ========================================
// FUNÇÕES DE INTERFACE
// ========================================

/**
 * Atualiza a interface com os dados do afiliado
 * @param {Object} dados - Dados do afiliado
 */
function atualizarInterface(dados) {
    if (!dados) return;

    // Atualizar informações do header
    elementos.nomeAfiliado.textContent = dados.nome;

    // Atualizar link de afiliado
    elementos.linkAfiliado.textContent = dados.linkAfiliado;
    elementos.btnVisitar.href = dados.linkAfiliado;

    // Atualizar contagem de cadastros com animação
    animarNumero(elementos.totalCadastros, dados.totalCadastros);
}

/**
 * Anima a contagem de um número
 * @param {HTMLElement} elemento - Elemento a ser animado
 * @param {number} valorFinal - Valor final da contagem
 */
function animarNumero(elemento, valorFinal) {
    const duracao = 1500; // 1.5 segundos
    const incremento = valorFinal / (duracao / 16); // 60 FPS
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
 * Mostra mensagem de erro
 * @param {string} mensagem - Mensagem de erro
 */
function mostrarErro(mensagem) {
    alert(mensagem); // Você pode substituir por um toast ou modal mais elegante
}

/**
 * Esconde a tela de loading
 */
function esconderLoading() {
    elementos.loadingScreen.classList.add('hidden');
}

// ========================================
// FUNÇÕES DE AÇÃO
// ========================================

/**
 * Copia o link de afiliado para a área de transferência
 */
async function copiarLink() {
    try {
        const link = elementos.linkAfiliado.textContent;
        await navigator.clipboard.writeText(link);
        
        // Feedback visual
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
        mostrarErro('Erro ao copiar o link. Tente novamente.');
    }
}

/**
 * Realiza o logout do usuário
 */
function realizarLogout() {
    if (confirm('Deseja realmente sair do painel?')) {
        // Limpar dados de autenticação
        localStorage.removeItem('token_afiliado');
        
        // IMPLEMENTAÇÃO REAL - Descomente para invalidar token no servidor
        /*
        fetch(`${API_BASE_URL}/auth/logout`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token_afiliado')}`,
                'Content-Type': 'application/json'
            }
        }).finally(() => {
            window.location.href = '/login/';
        });
        */
        
        // Redirecionar para login
        window.location.href = '/login/';
    }
}

/**
 * Atualiza a contagem de cadastros
 * Esta função busca do banco e atualiza apenas o número de cadastros
 */
async function atualizarContagemCadastros() {
    try {
        const total = await buscarContagemCadastros();
        animarNumero(elementos.totalCadastros, total);
    } catch (error) {
        console.error('Erro ao atualizar cadastros:', error);
    }
}

/**
 * Atualiza os dados periodicamente
 * A cada 2 minutos, busca a contagem atualizada de cadastros
 */
function iniciarAtualizacaoAutomatica() {
    // Atualiza a cada 2 minutos (120000 ms)
    setInterval(async () => {
        console.log('Atualizando contagem de cadastros...');
        await atualizarContagemCadastros();
    }, 120000);
}

// ========================================
// EVENT LISTENERS
// ========================================

/**
 * Configura todos os event listeners
 */
function configurarEventListeners() {
    // Botão de copiar
    elementos.btnCopiar.addEventListener('click', copiarLink);
    
    // Botão de logout
    elementos.btnLogout.addEventListener('click', realizarLogout);
}

// ========================================
// INICIALIZAÇÃO
// ========================================

/**
 * Inicializa o painel
 */
async function inicializarPainel() {
    try {
        // Configurar event listeners
        configurarEventListeners();
        
        // Buscar dados do afiliado
        const dados = await buscarDadosAfiliado();
        
        if (dados) {
            // Atualizar interface com os dados
            atualizarInterface(dados);
            
            // Iniciar atualização automática da contagem
            iniciarAtualizacaoAutomatica();
        }
        
    } catch (error) {
        console.error('Erro ao inicializar painel:', error);
        mostrarErro('Erro ao carregar o painel. Recarregue a página.');
    } finally {
        // Esconder loading
        esconderLoading();
    }
}

// ========================================
// INICIAR QUANDO O DOM ESTIVER PRONTO
// ========================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarPainel);
} else {
    inicializarPainel();
}

// ========================================
// EXEMPLO DE ESTRUTURA DE DADOS DO BACKEND
// ========================================

/*
ESTRUTURA SUGERIDA PARA TABELA DE CADASTROS:

CREATE TABLE cadastros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo_afiliado VARCHAR(50) NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'ativo', 'inativo') DEFAULT 'ativo',
    INDEX idx_codigo_afiliado (codigo_afiliado),
    INDEX idx_status (status)
);

QUERY PARA CONTAR CADASTROS POR AFILIADO:

SELECT COUNT(*) as total 
FROM cadastros 
WHERE codigo_afiliado = 'ABC123XYZ' 
AND status = 'ativo';

ENDPOINT SUGERIDO PARA API:

GET /api/afiliado/cadastros/contagem

Response:
{
    "total": 23,
    "codigo_afiliado": "ABC123XYZ"
}
*/