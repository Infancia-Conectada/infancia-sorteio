document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cadastroForm');
    const btnParticipar = document.getElementById('btnParticipar');
    const feedback = document.getElementById('feedback');

    console.log('✓ DOM carregado e script-login.js ativo');

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
            console.log('✓ Token CSRF carregado para o formulário de login');
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
        console.log('Campo email perdeu o foco, validando...');
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
        feedback.classList.remove('hidden');
        feedback.classList.remove('sucesso');
        feedback.classList.add('erro');
        feedback.textContent = mensagem;
        console.error('Erro:', mensagem);
    }

    // Função para mostrar sucesso
    function mostrarSucesso(mensagem) {
        feedback.classList.remove('hidden');
        feedback.classList.remove('erro');
        feedback.classList.add('sucesso');
        feedback.textContent = mensagem;
        console.log('Sucesso:', mensagem);
    }

    // Função para limpar feedback
    function limparFeedback() {
        feedback.classList.add('hidden');
        feedback.textContent = '';
    }

    // Listener do formulário
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        console.log('Formulário de login submetido');

        if (!csrfTokenCarregado) {
            mostrarErro('Aguarde o carregamento do formulário antes de enviar.');
            return;
        }

        // Validação básica no cliente
        if (!validarFormulario()) {
            console.log('Validação do formulário falhou');
            mostrarErro('Por favor, preenchha todos os campos corretamente');
            return;
        }

        console.log('✓ Validação passada, enviando dados...');

        // Desabilitar botão e mostrar carregamento
        btnParticipar.disabled = true;
        btnParticipar.classList.add('loading');
        limparFeedback();

        try {
            // Coletar dados do formulário
            const formData = new FormData(form);
            
            console.log('Dados sendo enviados (campos):');
            for (let pair of formData.entries()) {
                // NÃO logar senhas por segurança
                if (pair[0] === 'senha') {
                    console.log(pair[0] + ': [OCULTADO]');
                } else if (pair[0] === 'csrf_token') {
                    console.log(pair[0] + ': [TOKEN]');
                } else {
                    console.log(pair[0] + ':', pair[1]);
                }
            }

            // Enviar dados ao servidor
            console.log('Enviando requisição para validar_login.php...');
            const response = await fetch('validar_login.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            console.log('Resposta recebida com status:', response.status);

            const contentType = response.headers.get('content-type');
            console.log('Content-Type da resposta:', contentType);

            if (!contentType || !contentType.includes('application/json')) {
                const texto = await response.text();
                console.error('Resposta não é JSON:', texto.substring(0, 200));
                throw new Error('Resposta inválida do servidor');
            }

            const dados = await response.json();
            console.log('Resposta JSON recebida:', dados);

            // Verificar resposta
            if (dados.sucesso) {
                console.log('✓✓✓ LOGIN BEM-SUCEDIDO');
                mostrarSucesso(dados.mensagem);
                
                console.log('Dados do afiliado:', {
                    id: dados.id_afiliado,
                    nome: dados.nome_afiliado,
                    code: dados.code_afiliado,
                    email: dados.email_afiliado
                });

                // Armazenar informações na sessão (opcional, já está no servidor)
                localStorage.setItem('id_afiliado', dados.id_afiliado);
                localStorage.setItem('nome_afiliado', dados.nome_afiliado);
                localStorage.setItem('code_afiliado', dados.code_afiliado);

                // Redirecionar para o painel de afiliado após 1.5 segundos
                setTimeout(() => {
                    console.log('Redirecionando para painel de afiliado...');
                    window.location.href = 'painel/';
                }, 1500);

            } else {
                console.log('Login falhou:', dados.mensagem);
                mostrarErro(dados.mensagem);

                // Mostrar erros detalhados se existirem
                if (dados.erros && Array.isArray(dados.erros)) {
                    console.log('Erros encontrados:', dados.erros);
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
