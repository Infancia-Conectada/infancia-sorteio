document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cadastroForm');
    const btnParticipar = document.getElementById('btnParticipar');
    const feedback = document.getElementById('feedback');

    // Gerenciamento de CSRF
    let csrfTokenCarregado = false;

    async function carregarCsrfToken() {
        try {
            const response = await fetch('cadastro/gerar_csrf.php', {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Falha ao buscar token CSRF');
            }

            const data = await response.json();
            let tokenInput = form.querySelector('input[name="csrf_token"]');
            if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'csrf_token';
                form.appendChild(tokenInput);
            }

            tokenInput.value = data.token;
            csrfTokenCarregado = true;
        } catch (error) {
            console.error('Erro ao carregar token CSRF:', error);
            mostrarErro('Erro ao inicializar o formulário. Recarregue a página.');
        }
    }

    carregarCsrfToken();

    // Validar e limpar campos ao sair do foco
    const emailInput = document.getElementById('email');
    const senhaInput = document.getElementById('senha');

    emailInput.addEventListener('blur', function() {
        validarEmail();
    });

    senhaInput.addEventListener('blur', function() {
        validarSenha();
    });

    // Função para validar email
    function validarEmail() {
        const email = emailInput.value.trim();
        const emailError = document.getElementById('email-error');

        if (email === '') {
            emailError.textContent = 'Email é obrigatório';
            emailInput.classList.add('error');
            return false;
        }

        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regexEmail.test(email)) {
            emailError.textContent = 'Email inválido';
            emailInput.classList.add('error');
            return false;
        }

        emailError.textContent = '';
        emailInput.classList.remove('error');
        return true;
    }

    // Função para validar senha
    function validarSenha() {
        const senha = senhaInput.value;
        const senhaError = document.getElementById('senha-error');

        if (senha === '') {
            senhaError.textContent = 'Senha é obrigatória';
            senhaInput.classList.add('error');
            return false;
        }

        if (senha.length < 6) {
            senhaError.textContent = 'Senha deve ter no mínimo 6 caracteres';
            senhaInput.classList.add('error');
            return false;
        }

        senhaError.textContent = '';
        senhaInput.classList.remove('error');
        return true;
    }

    // Função principal de validação
    function validarFormulario() {
        const emailValido = validarEmail();
        const senhaValida = validarSenha();

        return emailValido && senhaValida;
    }

    // Função para mostrar erro
    function mostrarErro(mensagem) {
        feedback.className = 'feedback error';
        feedback.textContent = mensagem;
    }

    // Função para mostrar sucesso
    function mostrarSucesso(mensagem) {
        feedback.className = 'feedback success';
        feedback.textContent = mensagem;
    }

    // Função para mostrar info
    function mostrarInfo(mensagem) {
        feedback.className = 'feedback info';
        feedback.textContent = mensagem;
    }

    // Função para limpar feedback
    function limparFeedback() {
        feedback.classList.add('hidden');
        feedback.textContent = '';
    }

    // Listener do formulário
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!csrfTokenCarregado) {
            mostrarErro('Aguarde o carregamento do formulário antes de enviar.');
            return;
        }

        // Validação básica no cliente
        if (!validarFormulario()) {
            mostrarErro('Por favor, corrija os campos destacados.');
            return;
        }

        // Desabilitar botão e mostrar carregamento
        btnParticipar.disabled = true;
        btnParticipar.classList.add('loading');
        limparFeedback();

        try {
            // Coletar dados do formulário
            const formData = new FormData(form);

            // Enviar dados ao servidor
            const response = await fetch('validar_login.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const contentType = response.headers.get('content-type');

            if (!contentType || !contentType.includes('application/json')) {
                const texto = await response.text();
                throw new Error('Resposta inválida do servidor');
            }

            const dados = await response.json();

            // Verificar resposta
            if (dados.sucesso) {
                // Marcar campos como válidos (borda verde)
                emailInput.classList.remove('invalid', 'error');
                emailInput.classList.add('valid');
                senhaInput.classList.remove('invalid', 'error');
                senhaInput.classList.add('valid');

                mostrarSucesso(dados.mensagem);

                // Armazenar informações na sessão (opcional, já está no servidor)
                localStorage.setItem('id_afiliado', dados.id_afiliado);
                localStorage.setItem('nome_afiliado', dados.nome_afiliado);
                localStorage.setItem('code_afiliado', dados.code_afiliado);

                // Redirecionar para o painel de afiliado após 1 segundos
                setTimeout(() => {
                    window.location.href = 'painel/';
                }, 1000);

            } else {
                // Marcar campos como inválidos (borda vermelha) e dar focus
                emailInput.classList.remove('valid');
                emailInput.classList.add('invalid', 'error');
                senhaInput.classList.remove('valid');
                senhaInput.classList.add('invalid', 'error');
                
                // Limpar mensagens de erro individuais
                document.getElementById('email-error').textContent = '';
                document.getElementById('senha-error').textContent = '';
                
                // Dar focus no primeiro campo (email)
                setTimeout(() => {
                    emailInput.focus();
                }, 100);

                mostrarErro(dados.mensagem);

                // Mostrar erros detalhados se existirem
                if (dados.erros && Array.isArray(dados.erros)) {
                    // Erros já tratados no mostrarErro
                }
                await carregarCsrfToken();
            }

        } catch (erro) {
            console.error('Erro na requisição:', erro);
            mostrarErro('Erro ao conectar ao servidor. Tente novamente.');
            await carregarCsrfToken();
        } finally {
            // Re-habilitar botão
            btnParticipar.disabled = false;
            btnParticipar.classList.remove('loading');
        }
    });
});
