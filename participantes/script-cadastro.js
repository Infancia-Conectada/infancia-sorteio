// ========================================
// Função de Máscara de Telefone
// ========================================
function aplicarMascaraTelefone(valor) {
    valor = valor.replace(/\D/g, ""); // Remove tudo que não é número

    if (valor.length <= 10) {
        // Telefone fixo: (11) 3456-7890
        valor = valor.replace(/^(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
    } else {
        // Celular: (11) 98765-4321
        valor = valor.replace(/^(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3");
    }

    return valor;
}

// ========================================
// Validação de Campos
// ========================================

// Verifica se o nome tem pelo menos 3 caracteres (ignora espaços extras)
function validarNome(nome) {
    return nome.trim().length >= 3;
}

// Verifica se o telefone está no formato válido (com DDD e traço)
function validarTelefone(telefone) {
    // Aceita tanto (00) 0000-0000 quanto (00) 00000-0000
    const regex = /^\(\d{2}\)\s?\d{4,5}-\d{4}$/;
    return regex.test(telefone);
}

// ========================================
// Captura parâmetro único da URL
// ========================================
function obterParametroUnico() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('ref') || urlParams.get('utm_source') || urlParams.get('source') || null;
}

// ========================================
// Eventos e Lógica do Formulário
// ========================================
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("cadastroForm");
    const nomeInput = document.getElementById("nome");
    const telefoneInput = document.getElementById("telefone");
    const feedback = document.getElementById("feedback");
    const btn = document.getElementById("btnParticipar");

    // Captura o parâmetro único da URL ao carregar a página
    const parametroUnico = obterParametroUnico();

    // Aplica máscara enquanto o usuário digita
    telefoneInput.addEventListener("input", (e) => {
        e.target.value = aplicarMascaraTelefone(e.target.value);
    });

    // Quando o formulário é enviado
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        feedback.className = "feedback hidden";
        nomeInput.classList.remove("invalid", "valid");
        telefoneInput.classList.remove("invalid", "valid");

        const nome = nomeInput.value.trim();
        const telefone = telefoneInput.value.trim();

        let valido = true;

        // Validação do nome
        if (!validarNome(nome)) {
            const msg = document.getElementById("nome-error");
            msg.textContent = "Digite um nome válido (mínimo 3 caracteres).";
            msg.classList.add("show");
            nomeInput.classList.add("invalid");
            valido = false;
        } else {
            document.getElementById("nome-error").textContent = "";
            nomeInput.classList.add("valid");
        }

        // Validação do telefone
        if (!validarTelefone(telefone)) {
            const msg = document.getElementById("telefone-error");
            msg.textContent = "Digite um telefone válido (ex: (11) 98765-4321).";
            msg.classList.add("show");
            telefoneInput.classList.add("invalid");
            valido = false;
        } else {
            document.getElementById("telefone-error").textContent = "";
            telefoneInput.classList.add("valid");
        }

        // Caso esteja tudo certo
        if (valido) {
            btn.classList.add("loading");
            btn.disabled = true;

            try {
                // Envia dados para criar sessão no servidor
                const response = await fetch("https://infanciaconectada.com.br/sorteio/registrar.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ 
                        acao: 'criar_sessao',
                        nome, 
                        telefone,
                        parametro_unico: parametroUnico // Adiciona o parâmetro único
                    })
                });

                // Verifica se a resposta é OK
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }

                const result = await response.json();
                console.log("Resposta do servidor:", result);

                if (result.status === 'ok' && result.sessao_id) {
                    feedback.textContent = "Redirecionando...";
                    feedback.className = "feedback success";

                    // Aguarda um pouco antes de redirecionar
                    await new Promise(resolve => setTimeout(resolve, 1000));

                    // Redireciona com ID da sessão
                    window.location.href = `https://infanciaconectada.com.br/sorteio?s=${result.sessao_id}`;
                } else {
                    throw new Error(result.mensagem || "Erro ao criar sessão");
                }
            } catch (error) {
                console.error("Erro completo:", error);
                feedback.textContent = "Erro ao processar cadastro: " + (error.message || "Tente novamente.");
                feedback.className = "feedback error";
                btn.classList.remove("loading");
                btn.disabled = false;
            }
        } else {
            feedback.textContent = "Por favor, corrija os campos destacados.";
            feedback.className = "feedback error";
        }
    });
});