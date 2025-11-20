// ========================================
// CONSTANTES E CONFIGURAÇÕES
// ========================================
const CONFIG = {
    API_URL: 'https://infanciaconectada.com.br/sorteio/registrar.php',
    REDIRECT_BASE_URL: 'https://infanciaconectada.com.br/sorteio',
    REDIRECT_DELAY: 1000,
    INSTAGRAM_MAX_LENGTH: 31,
    NOME_MIN_LENGTH: 3
};

// ========================================
// MÁSCARAS DE ENTRADA
// ========================================
const Mascaras = {
    telefone: (valor) => {
        valor = valor.replace(/\D/g, "");

        if (valor.length <= 10) {
            // Telefone fixo: (11) 3456-7890
            return valor.replace(/^(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
        } else {
            // Celular: (11) 98765-4321
            return valor.replace(/^(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3");
        }
    },

    instagram: (valor) => {
        // Remove caracteres não permitidos
        valor = valor.replace(/[^a-zA-Z0-9._]/g, "");
        
        // Garante que comece com @
        if (!valor.startsWith("@")) {
            valor = "@" + valor;
        }
        
        // Limita a 31 caracteres (30 + @)
        if (valor.length > CONFIG.INSTAGRAM_MAX_LENGTH) {
            valor = valor.substring(0, CONFIG.INSTAGRAM_MAX_LENGTH);
        }
        
        return valor;
    }
};

// ========================================
// VALIDAÇÕES
// ========================================
const Validacoes = {
    nome: (nome) => {
        return nome.trim().length >= CONFIG.NOME_MIN_LENGTH;
    },

    telefone: (telefone) => {
        // Aceita (00) 0000-0000 ou (00) 00000-0000
        const regex = /^\(\d{2}\)\s?\d{4,5}-\d{4}$/;
        return regex.test(telefone);
    },

    instagram: (instagram) => {
        const username = instagram.replace('@', '');
        
        if (username.length < 1) {
            return false;
        }
        
        // Apenas letras, números, pontos e underscores
        const regex = /^[a-zA-Z0-9._]+$/;
        return regex.test(username);
    }
};

// ========================================
// UTILIDADES
// ========================================
const Utils = {
    obterParametroUnico: () => {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('ref') || 
               urlParams.get('utm_source') || 
               urlParams.get('source') || 
               null;
    },

    limparValidacoes: (inputs) => {
        inputs.forEach(input => {
            input.classList.remove("invalid", "valid");
        });
    },

    exibirErro: (inputId, mensagem) => {
        const errorSpan = document.getElementById(`${inputId}-error`);
        const input = document.getElementById(inputId);
        
        errorSpan.textContent = mensagem;
        errorSpan.classList.add("show");
        input.classList.add("invalid");
    },

    limparErro: (inputId) => {
        const errorSpan = document.getElementById(`${inputId}-error`);
        const input = document.getElementById(inputId);
        
        errorSpan.textContent = "";
        errorSpan.classList.remove("show");
        input.classList.add("valid");
    }
};

// ========================================
// GERENCIAMENTO DO FORMULÁRIO
// ========================================
class FormularioCadastro {
    constructor() {
        this.form = document.getElementById("cadastroForm");
        this.inputs = {
            nome: document.getElementById("nome"),
            telefone: document.getElementById("telefone"),
            instagram: document.getElementById("instagram")
        };
        this.feedback = document.getElementById("feedback");
        this.btnSubmit = document.getElementById("btnParticipar");
        this.parametroUnico = Utils.obterParametroUnico();

        this.inicializarEventos();
    }

    inicializarEventos() {
        // Máscara de telefone
        this.inputs.telefone.addEventListener("input", (e) => {
            e.target.value = Mascaras.telefone(e.target.value);
        });

        // Máscara de Instagram
        this.inputs.instagram.addEventListener("input", (e) => {
            e.target.value = Mascaras.instagram(e.target.value);
        });

        // Adiciona @ quando o campo Instagram recebe foco
        this.inputs.instagram.addEventListener("focus", (e) => {
            if (e.target.value === "") {
                e.target.value = "@";
            }
        });

        // Previne remoção do @
        this.inputs.instagram.addEventListener("keydown", (e) => {
            const cursorPos = e.target.selectionStart;
            if ((e.key === "Backspace" || e.key === "Delete") && cursorPos <= 1) {
                e.preventDefault();
            }
        });

        // Submit do formulário
        this.form.addEventListener("submit", (e) => this.handleSubmit(e));
    }

    validarCampos() {
        const valores = {
            nome: this.inputs.nome.value.trim(),
            telefone: this.inputs.telefone.value.trim(),
            instagram: this.inputs.instagram.value.trim()
        };

        let valido = true;

        // Validação Nome
        if (!Validacoes.nome(valores.nome)) {
            Utils.exibirErro("nome", "Digite um nome válido (mínimo 3 caracteres).");
            valido = false;
        } else {
            Utils.limparErro("nome");
        }

        // Validação Telefone
        if (!Validacoes.telefone(valores.telefone)) {
            Utils.exibirErro("telefone", "Digite um telefone válido (ex: (11) 98765-4321).");
            valido = false;
        } else {
            Utils.limparErro("telefone");
        }

        // Validação Instagram
        if (!Validacoes.instagram(valores.instagram)) {
            Utils.exibirErro("instagram", "Digite um Instagram válido (ex: @seu_usuario).");
            valido = false;
        } else {
            Utils.limparErro("instagram");
        }

        return { valido, valores };
    }

    exibirFeedback(tipo, mensagem) {
        this.feedback.textContent = mensagem;
        this.feedback.className = `feedback ${tipo}`;
    }

    setBotaoCarregando(carregando) {
        if (carregando) {
            this.btnSubmit.classList.add("loading");
            this.btnSubmit.disabled = true;
        } else {
            this.btnSubmit.classList.remove("loading");
            this.btnSubmit.disabled = false;
        }
    }

    async enviarDados(valores) {
        const payload = {
            acao: 'criar_sessao',
            nome: valores.nome,
            telefone: valores.telefone,
            instagram: valores.instagram,
            parametro_unico: this.parametroUnico
        };

        const response = await fetch(CONFIG.API_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }

        return await response.json();
    }

    async handleSubmit(e) {
        e.preventDefault();

        // Limpa feedback e validações anteriores
        this.feedback.className = "feedback hidden";
        Utils.limparValidacoes(Object.values(this.inputs));

        // Valida campos
        const { valido, valores } = this.validarCampos();

        if (!valido) {
            this.exibirFeedback("error", "Por favor, corrija os campos destacados.");
            return;
        }

        // Inicia loading
        this.setBotaoCarregando(true);

        try {
            const result = await this.enviarDados(valores);
            console.log("Resposta do servidor:", result);

            if (result.status === 'ok' && result.sessao_id) {
                this.exibirFeedback("success", "Redirecionando...");

                // Aguarda antes de redirecionar
                await new Promise(resolve => setTimeout(resolve, CONFIG.REDIRECT_DELAY));

                // Redireciona com ID da sessão
                window.location.href = `${CONFIG.REDIRECT_BASE_URL}?s=${result.sessao_id}`;
            } else {
                throw new Error(result.mensagem || "Erro ao criar sessão");
            }
        } catch (error) {
            console.error("Erro completo:", error);
            this.exibirFeedback(
                "error", 
                `Erro ao processar cadastro: ${error.message || "Tente novamente."}`
            );
            this.setBotaoCarregando(false);
        }
    }
}

// ========================================
// INICIALIZAÇÃO
// ========================================
document.addEventListener("DOMContentLoaded", () => {
    new FormularioCadastro();
});