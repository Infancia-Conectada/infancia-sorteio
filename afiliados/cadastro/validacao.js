document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cadastroForm');
    const btnParticipar = document.getElementById('btnParticipar');
    const feedback = document.getElementById('feedback');

    // Gerar CSRF token ANTES de qualquer submit
    let csrfTokenCarregado = false;
    
    fetch('gerar_csrf.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao buscar token CSRF');
            }
            return response.json();
        })
        .then(data => {
            // Remover token antigo se existir
            const tokenAntigo = form.querySelector('input[name="csrf_token"]');
            if (tokenAntigo) {
                tokenAntigo.remove();
            }
            
            // Adicionar novo token
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = data.token;
            form.appendChild(input);
            
            csrfTokenCarregado = true;
        })
        .catch(erro => {
            console.error('Erro ao gerar CSRF:', erro);
            mostrarErro('Erro ao carregar página. Recarregue e tente novamente.');
        });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Limpar feedback anterior
        limparFeedback();

        // Verificar se CSRF foi carregado
        if (!csrfTokenCarregado) {
            mostrarErro('Aguarde o carregamento completo da página');
            return;
        }

        // Validação básica no cliente (aplica classes valid/invalid)
        if (!validarFormulario()) {
            mostrarErro('Por favor, corrija os campos destacados.');
            return;
        }

        // Desabilitar botão e mostrar carregamento
        btnParticipar.disabled = true;
        btnParticipar.classList.add('loading');
        feedback.className = 'feedback info';
        feedback.textContent = '⏳ Aguarde, estamos verificando as informações...';
        feedback.classList.remove('hidden');

        // Validar WhatsApp
        const telefone = document.getElementById('telefone').value;
        const inputTelefone = document.getElementById('telefone');
        
        const resultadoWhatsApp = await validarWhatsApp(telefone);
        
        if (!resultadoWhatsApp.sucesso || !resultadoWhatsApp.hasWhatsApp) {
            inputTelefone.classList.remove('valid');
            inputTelefone.classList.add('invalid');
            inputTelefone.focus();
            mostrarErro(resultadoWhatsApp.mensagem || 'O telefone informado não possui WhatsApp ativo');
            btnParticipar.disabled = false;
            btnParticipar.classList.remove('loading');
            return;
        }

        try {
            // Coletar dados do formulário
            const formData = new FormData(form);

            // Enviar dados ao servidor
            const response = await fetch('processar_cadastro.php', {
                method: 'POST',
                body: formData
            });

            const contentType = response.headers.get('content-type');

            if (!contentType || !contentType.includes('application/json')) {
                const texto = await response.text();
                console.error('Resposta não é JSON:', texto.substring(0, 200));
                throw new Error('Resposta inválida do servidor');
            }

            const dados = await response.json();

            // Mostrar feedback
            if (response.ok && dados.sucesso) {
                mostrarSucesso(dados.mensagem || 'Cadastro realizado com sucesso!');
                form.reset();
                
                // Redirecionar após 2 segundos
                setTimeout(() => {
                    window.location.href = '../index.html';
                }, 2000);
            } else {
                console.error('✗ Erro na resposta:', dados);
                mostrarErro(dados.mensagem || 'Erro ao processar cadastro');
            }

        } catch (erro) {
            console.error('✗ Erro de conexão:', erro);
            mostrarErro('Erro de conexão com o servidor. Tente novamente.');
        } finally {
            // Reabilitar botão
            btnParticipar.disabled = false;
            btnParticipar.classList.remove('loading');
        }
    });

    function validarFormulario() {
        const nome = document.getElementById('nome');
        const email = document.getElementById('email');
        const telefone = document.getElementById('telefone');
        const senha = document.getElementById('senha');
        const confirmarsenha = document.getElementById('confirmarsenha');

        let valido = true;

        // Validar nome
        if (nome.value.trim().length < 3) {
            mostrarErrosCampo('nome', 'Nome deve ter no mínimo 3 caracteres');
            valido = false;
        } else {
            limparErrosCampo('nome');
            nome.classList.add('valid');
        }

        // Validar email
        if (!isEmailValido(email.value)) {
            mostrarErrosCampo('email', 'Email inválido');
            valido = false;
        } else {
            limparErrosCampo('email');
            email.classList.add('valid');
        }

        // Validar telefone
        if (!isTelefoneValido(telefone.value)) {
            mostrarErrosCampo('telefone', 'Telefone inválido. Use (XX) XXXXX-XXXX');
            valido = false;
        } else {
            limparErrosCampo('telefone');
            telefone.classList.add('valid');
        }

        // Validar senha
        if (senha.value.length < 6) {
            mostrarErrosCampo('senha', 'Senha deve ter no mínimo 6 caracteres');
            valido = false;
        } else {
            limparErrosCampo('senha');
            senha.classList.add('valid');
        }

        // Validar confirmação de senha
        if (senha.value !== confirmarsenha.value) {
            mostrarErrosCampo('confirmarsenha', 'As senhas não conferem');
            valido = false;
        } else {
            limparErrosCampo('confirmarsenha');
            confirmarsenha.classList.add('valid');
        }

        return valido;
    }

    function isEmailValido(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    function isTelefoneValido(telefone) {
        const regex = /^\(\d{2}\)\s?\d{4,5}-\d{4}$/;
        return regex.test(telefone);
    }

    async function validarWhatsApp(telefone) {
        try {
            // Remover formatação e adicionar código do Brasil
            const numeroLimpo = telefone.replace(/\D/g, '');
            const numeroComCodigo = '55' + numeroLimpo;
            
            const response = await fetch('validar_whatsapp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    number: numeroComCodigo
                })
            });
            
            if (!response.ok) {
                console.error('Erro na validação de WhatsApp:', response.status);
                return {
                    sucesso: false,
                    mensagem: 'Erro ao validar WhatsApp',
                    hasWhatsApp: false
                };
            }
            
            const dados = await response.json();
            
            return dados;
        } catch (erro) {
            console.error('Erro ao validar WhatsApp:', erro);
            return {
                sucesso: false,
                mensagem: 'Erro ao validar WhatsApp',
                hasWhatsApp: false
            };
        }
    }

    function mostrarErrosCampo(nomeId, mensagem) {
        const elemento = document.getElementById(`${nomeId}-error`);
        const input = document.getElementById(nomeId);
        if (elemento) {
            elemento.textContent = mensagem;
        }
        if (input) {
            input.classList.add('invalid');
            input.classList.remove('valid');
        }
    }

    function limparErrosCampo(nomeId) {
        const elemento = document.getElementById(`${nomeId}-error`);
        const input = document.getElementById(nomeId);
        if (elemento) {
            elemento.textContent = '';
        }
        if (input) {
            input.classList.remove('invalid');
        }
    }

    function mostrarSucesso(mensagem) {
        feedback.className = 'feedback success';
        feedback.textContent = '✓ ' + mensagem;
        feedback.classList.remove('hidden');
    }

    function mostrarErro(mensagem) {
        feedback.className = 'feedback error';
        feedback.textContent = '✗ ' + mensagem;
        feedback.classList.remove('hidden');
    }

    function limparFeedback() {
        feedback.classList.add('hidden');
        feedback.textContent = '';
    }

    // Mascarar telefone em tempo real
    const telefoneInput = document.getElementById('telefone');
    telefoneInput.addEventListener('input', function(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length > 0) {
            if (valor.length <= 2) {
                valor = `(${valor}`;
            } else if (valor.length <= 6) {
                valor = `(${valor.slice(0, 2)}) ${valor.slice(2)}`;
            } else {
                valor = `(${valor.slice(0, 2)}) ${valor.slice(2, 7)}-${valor.slice(7, 11)}`;
            }
        }
        e.target.value = valor;
    });
});