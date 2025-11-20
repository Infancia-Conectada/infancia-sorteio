document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cadastroForm');
    const btnParticipar = document.getElementById('btnParticipar');
    const feedback = document.getElementById('feedback');

    console.log('✓ DOM carregado e validacao.js ativo');

    // Gerar CSRF token ANTES de qualquer submit
    let csrfTokenCarregado = false;
    
    fetch('gerar_csrf.php')
        .then(response => {
            console.log('Resposta CSRF:', response.status);
            if (!response.ok) {
                throw new Error('Erro ao buscar token CSRF');
            }
            return response.json();
        })
        .then(data => {
            console.log('Token CSRF recebido');
            
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
            console.log('✓ Token CSRF adicionado ao formulário');
        })
        .catch(erro => {
            console.error('Erro ao gerar CSRF:', erro);
            mostrarErro('Erro ao carregar página. Recarregue e tente novamente.');
        });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        console.log('Formulário submetido');

        // Verificar se CSRF foi carregado
        if (!csrfTokenCarregado) {
            mostrarErro('Aguarde o carregamento completo da página');
            return;
        }

        // Validação básica no cliente
        if (!validarFormulario()) {
            console.log('Validação do formulário falhou');
            return;
        }

        console.log('✓ Validação básica passada');

        // Desabilitar botão e mostrar carregamento
        btnParticipar.disabled = true;
        btnParticipar.classList.add('loading');
        limparFeedback();

        // Validar WhatsApp
        const telefone = document.getElementById('telefone').value;
        console.log('Iniciando validação de WhatsApp...');
        
        const resultadoWhatsApp = await validarWhatsApp(telefone);
        
        if (!resultadoWhatsApp.sucesso || !resultadoWhatsApp.hasWhatsApp) {
            console.log('✗ Telefone não possui WhatsApp');
            mostrarErro(resultadoWhatsApp.mensagem || 'O telefone informado não possui WhatsApp ativo');
            btnParticipar.disabled = false;
            btnParticipar.classList.remove('loading');
            return;
        }
        
        console.log('✓ WhatsApp validado com sucesso');
        console.log('✓ Validação passada, enviando dados...');

        try {
            // Coletar dados do formulário
            const formData = new FormData(form);
            
            console.log('Dados sendo enviados (campos):');
            for (let pair of formData.entries()) {
                // NÃO logar senhas por segurança
                if (pair[0] === 'senha' || pair[0] === 'confirmarsenha') {
                    console.log(pair[0] + ': [OCULTADO]');
                } else if (pair[0] === 'csrf_token') {
                    console.log(pair[0] + ': [TOKEN]');
                } else {
                    console.log(pair[0] + ':', pair[1]);
                }
            }

            // Enviar dados ao servidor
            console.log('Enviando requisição para processar_cadastro.php...');
            const response = await fetch('processar_cadastro.php', {
                method: 'POST',
                body: formData
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
            console.log('Dados JSON recebidos:', dados);

            // Mostrar feedback
            if (response.ok && dados.sucesso) {
                console.log('✓ Cadastro realizado com sucesso!');
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
        }

        // Validar email
        if (!isEmailValido(email.value)) {
            mostrarErrosCampo('email', 'Email inválido');
            valido = false;
        } else {
            limparErrosCampo('email');
        }

        // Validar telefone
        if (!isTelefoneValido(telefone.value)) {
            mostrarErrosCampo('telefone', 'Telefone inválido. Use (XX) XXXXX-XXXX');
            valido = false;
        } else {
            limparErrosCampo('telefone');
        }

        // Validar senha
        if (senha.value.length < 6) {
            mostrarErrosCampo('senha', 'Senha deve ter no mínimo 6 caracteres');
            valido = false;
        } else {
            limparErrosCampo('senha');
        }

        // Validar confirmação de senha
        if (senha.value !== confirmarsenha.value) {
            mostrarErrosCampo('confirmarsenha', 'As senhas não conferem');
            valido = false;
        } else {
            limparErrosCampo('confirmarsenha');
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
            
            console.log('Validando WhatsApp para:', numeroComCodigo);
            
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
            console.log('Resposta validação WhatsApp:', dados);
            
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
        if (elemento) {
            elemento.textContent = mensagem;
        }
    }

    function limparErrosCampo(nomeId) {
        const elemento = document.getElementById(`${nomeId}-error`);
        if (elemento) {
            elemento.textContent = '';
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