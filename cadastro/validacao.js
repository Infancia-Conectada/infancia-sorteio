document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cadastroForm');
    const btnParticipar = document.getElementById('btnParticipar');
    const feedback = document.getElementById('feedback');

    console.log('✓ DOM carregado e validacao.js ativo');

    // Gerar CSRF token se não existir
    if (!document.querySelector('input[name="csrf_token"]')) {
        console.log('Gerando token CSRF...');
        fetch('gerar_csrf.php')
            .then(response => {
                console.log('Resposta CSRF:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Token CSRF recebido:', data.token);
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = data.token;
                form.appendChild(input);
                console.log('✓ Token CSRF adicionado ao formulário');
            })
            .catch(erro => {
                console.error('Erro ao gerar CSRF:', erro);
                mostrarErro('Erro ao gerar token de segurança');
            });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        console.log('Formulário submetido');

        // Validação básica no cliente
        if (!validarFormulario()) {
            console.log('Validação do formulário falhou');
            return;
        }

        console.log('✓ Validação passada, enviando dados...');

        // Desabilitar botão e mostrar carregamento
        btnParticipar.disabled = true;
        btnParticipar.classList.add('loading');

        try {
            // Coletar dados do formulário
            const formData = new FormData(form);
            
            console.log('Dados enviados:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ':', pair[1].substring ? pair[1].substring(0, 20) + '...' : pair[1]);
            }

            // Enviar dados ao servidor
            console.log('Enviando requisição para processar_cadastro.php...');
            const response = await fetch('processar_cadastro.php', {
                method: 'POST',
                body: formData
            });

            console.log('Resposta recebida com status:', response.status);
            console.log('Headers da resposta:', response.headers.get('content-type'));

            const dados = await response.json();
            console.log('Dados JSON recebidos:', dados);

            // Mostrar feedback
            if (response.ok) {
                console.log('✓ Cadastro realizado com sucesso!');
                mostrarSucesso(dados.mensagem);
                form.reset();
                // Redirecionar após 2 segundos (opcional)
                setTimeout(() => {
                    window.location.href = '../index.html';
                }, 2000);
            } else {
                console.error('✗ Erro na resposta:', dados);
                mostrarErro(dados.mensagem || 'Erro ao processar cadastro (Status: ' + response.status + ')');
            }

        } catch (erro) {
            console.error('✗ Erro de conexão:', erro);
            mostrarErro('Erro de conexão. Tente novamente. Detalhes: ' + erro.message);
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
