<?php
// Configurar tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
session_start();

// Configurar logs
$log_file = __DIR__ . '/logs/login_' . date('Y-m-d') . '.log';
$log_dir = __DIR__ . '/logs';

// Criar diretório de logs se não existir
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Função para registrar logs
function registrarLog($mensagem, $tipo = 'INFO') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $msg = "[{$timestamp}] [{$tipo}] {$mensagem}" . PHP_EOL;
    file_put_contents($log_file, $msg, FILE_APPEND);
}

// Capturar erros fatais
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    registrarLog("Erro PHP #{$errno}: {$errstr} em {$errfile}:{$errline}", 'PHP_ERROR');
});

registrarLog('=== NOVA REQUISIÇÃO DE LOGIN INICIADA ===', 'START');
registrarLog('Método: ' . $_SERVER['REQUEST_METHOD']);
registrarLog('IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'));

// Configurações do banco de dados
define('DB_HOST', '45.152.46.204');
define('DB_PORT', 3306);
define('DB_USER', 'u583423626_user_ic');
define('DB_PASS', 'Infancia123456');
define('DB_NAME', 'u583423626_infancia');

registrarLog('Configurações BD definidas');

// Classe para estruturar resposta
class RespostaAPI {
    public $sucesso = false;
    public $mensagem = '';
    public $codigo = 0;
}

// Validar requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    registrarLog('Requisição não-POST rejeitada', 'ERROR');
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido'
    ]);
    exit;
}

registrarLog('Requisição POST recebida');
registrarLog('POST data: ' . json_encode($_POST, JSON_UNESCAPED_UNICODE));

try {
    // Conectar ao banco de dados
    registrarLog('Tentando conectar ao banco: ' . DB_NAME . '@' . DB_HOST . ':' . DB_PORT);
    registrarLog('Usuário: ' . DB_USER, 'DEBUG');
    
    $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Verificar conexão
    if ($conexao->connect_error) {
        $connectErr = $conexao->connect_error;
        $connectErrno = $conexao->connect_errno;
        registrarLog('❌ ERRO DE CONEXÃO AO BANCO!', 'ERROR');
        registrarLog('Erro: ' . $connectErr, 'ERROR');
        registrarLog('Código do erro: ' . $connectErrno, 'ERROR');

        // Detectar causa comum
        $sugestao = '';
        if (stripos($connectErr, 'No such file or directory') !== false || stripos($connectErr, "Can't connect to local MySQL server") !== false) {
            $sugestao = 'MySQL não está acessível. Verifique a conexão.';
        } elseif (stripos($connectErr, 'Connection refused') !== false) {
            $sugestao = 'Conexão recusada: verifique se o MySQL está rodando.';
        }

        $mensagemErro = 'Erro na conexão com o banco de dados' . ($sugestao ? ' - ' . $sugestao : '');
        throw new Exception($mensagemErro);
    }
    
    registrarLog('✓ Conectado ao banco com sucesso!', 'SUCCESS');
    
    // Definir charset UTF-8
    $conexao->set_charset('utf8mb4');
    registrarLog('✓ Charset UTF-8 definido');
    
    // Receber e sanitizar dados
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    registrarLog('Dados recebidos: email=' . $email);
    
    // Validações
    $erros = [];
    
    // Validar email
    if (empty($email)) {
        $erros[] = 'Email é obrigatório';
        registrarLog('Email vazio', 'VALIDATION');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Email inválido';
        registrarLog('Email inválido: ' . $email, 'VALIDATION');
    } elseif (strlen($email) > 100) {
        $erros[] = 'Email muito longo';
        registrarLog('Email muito longo: ' . $email, 'VALIDATION');
    }
    
    // Validar senha
    if (empty($senha)) {
        $erros[] = 'Senha é obrigatória';
        registrarLog('Senha vazia', 'VALIDATION');
    } elseif (strlen($senha) < 6) {
        $erros[] = 'Senha deve ter no mínimo 6 caracteres';
        registrarLog('Senha muito curta', 'VALIDATION');
    } elseif (strlen($senha) > 100) {
        $erros[] = 'Senha muito longa';
        registrarLog('Senha muito longa', 'VALIDATION');
    }
    
    // Se houver erros de validação, retornar
    if (!empty($erros)) {
        registrarLog('Erros de validação encontrados: ' . implode(', ', $erros), 'VALIDATION');
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => implode(', ', $erros),
            'erros' => $erros
        ]);
        $conexao->close();
        exit;
    }
    
    registrarLog('✓ Validações de formato passaram');
    
    // Buscar afiliado no banco de dados por email
    registrarLog('Buscando afiliado por email: ' . $email);
    $stmt = $conexao->prepare('SELECT id, nome, email, senha, ativo, code FROM afiliados WHERE email = ? AND ativo = 1');
    
    if (!$stmt) {
        registrarLog('❌ Erro ao preparar query de busca: ' . $conexao->error, 'ERROR');
        throw new Exception('Erro ao buscar dados: ' . $conexao->error);
    }
    
    $stmt->bind_param('s', $email);
    registrarLog('Executando query de busca...');
    
    if (!$stmt->execute()) {
        registrarLog('❌ Erro ao executar query de busca: ' . $stmt->error, 'ERROR');
        throw new Exception('Erro ao executar query: ' . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    registrarLog('Query executada - Linhas encontradas: ' . $resultado->num_rows);
    
    // Verificar se o afiliado foi encontrado
    if ($resultado->num_rows === 0) {
        registrarLog('⚠ Falha de login: email não encontrado ou inativo: ' . $email, 'WARNING');
        registrarLog('IP da tentativa: ' . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'), 'WARNING');
        
        http_response_code(401);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Email ou senha inválidos'
        ]);
        $stmt->close();
        $conexao->close();
        registrarLog('Conexão fechada - falha de autenticação');
        exit;
    }
    
    // Recuperar dados do afiliado
    $afiliado = $resultado->fetch_assoc();
    registrarLog('Afiliado encontrado: ID=' . $afiliado['id'] . ', Nome=' . $afiliado['nome']);
    
    $stmt->close();
    
    // Verificar senha usando password_verify
    registrarLog('Verificando senha...');
    if (!password_verify($senha, $afiliado['senha'])) {
        registrarLog('⚠ Falha de login: senha incorreta para email: ' . $email, 'WARNING');
        registrarLog('IP da tentativa: ' . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'), 'WARNING');
        
        http_response_code(401);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Email ou senha inválidos'
        ]);
        $conexao->close();
        registrarLog('Conexão fechada - senha incorreta');
        exit;
    }
    
    registrarLog('✓ Senha verificada com sucesso');
    
    // Login bem-sucedido - criar sessão
    registrarLog('✓ Autenticação bem-sucedida para: ' . $email);
    
    // Armazenar dados na sessão
    $_SESSION['id_afiliado'] = $afiliado['id'];
    $_SESSION['nome_afiliado'] = $afiliado['nome'];
    $_SESSION['email_afiliado'] = $afiliado['email'];
    $_SESSION['code_afiliado'] = $afiliado['code'];
    $_SESSION['login_time'] = time();
    $_SESSION['ip_login'] = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    
    registrarLog('✓ Sessão criada - ID_SESSION: ' . session_id());
    registrarLog('✓✓✓ LOGIN BEM-SUCEDIDO: Email=' . $email . ', ID=' . $afiliado['id'] . ', CODE=' . $afiliado['code'], 'SUCCESS');
    
    // Sucesso
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso!',
        'id_afiliado' => $afiliado['id'],
        'nome_afiliado' => $afiliado['nome'],
        'code_afiliado' => $afiliado['code'],
        'email_afiliado' => $afiliado['email']
    ]);
    
    $conexao->close();
    registrarLog('✓ Conexão ao banco fechada');
    registrarLog('=== REQUISIÇÃO FINALIZADA COM SUCESSO ===', 'END');
    
} catch (Exception $e) {
    // Log detalhado do erro
    registrarLog('EXCEÇÃO CAPTURADA: ' . $e->getMessage(), 'ERROR');
    registrarLog('Stack trace: ' . $e->getTraceAsString(), 'ERROR');
    registrarLog('Arquivo: ' . $e->getFile() . ':' . $e->getLine(), 'ERROR');
    
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao processar o login. Tente novamente.',
        'debug' => [
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine()
        ]
    ]);
    registrarLog('=== REQUISIÇÃO FINALIZADA COM ERRO ===', 'END');
    
} catch (Throwable $t) {
    // Capturar qualquer outro erro
    registrarLog('ERRO NÃO TRATADO: ' . $t->getMessage(), 'ERROR');
    registrarLog('Tipo: ' . get_class($t), 'ERROR');
    
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro crítico ao processar o login.',
        'debug' => [
            'erro' => $t->getMessage(),
            'tipo' => get_class($t)
        ]
    ]);
}
?>
