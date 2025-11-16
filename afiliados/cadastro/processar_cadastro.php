<?php
// Configurar tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
session_start();

// Configurar logs
$log_file = __DIR__ . '/logs/cadastro_' . date('Y-m-d') . '.log';
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

registrarLog('=== NOVA REQUISIÇÃO INICIADA ===', 'START');
registrarLog('Método: ' . $_SERVER['REQUEST_METHOD']);
registrarLog('IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'));

// Configurações do banco de dados

define('DB_HOST', '45.152.46.204');
define('DB_PORT', 3306);
define('DB_USER', 'u583423626_user_ic');
define('DB_PASS', 'Infancia123456');
define('DB_NAME', 'u583423626_infancia');

registrarLog('Configurações BD definidas');

// Classe para tratar erros
class RespuestaAPI {
    public $sucesso = false;
    public $mensagem = '';
    public $codigo = 0;
}

// Função para gerar código único e aleatório
function gerarCodigoAfiliado() {
    registrarLog('Iniciando geração de código único');
    
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $tamanho_codigo = 8; // Código de 8 caracteres
    
    $codigo = '';
    for ($i = 0; $i < $tamanho_codigo; $i++) {
        $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    
    registrarLog('Código gerado: ' . $codigo);
    return $codigo;
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

// Validar CSRF (opcional, mas recomendado)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    registrarLog('Token CSRF inválido ou não encontrado', 'ERROR');
    registrarLog('Token esperado: ' . ($_SESSION['csrf_token'] ?? 'NENHUM'), 'DEBUG');
    registrarLog('Token recebido: ' . ($_POST['csrf_token'] ?? 'NENHUM'), 'DEBUG');
    http_response_code(403);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Token de segurança inválido'
    ]);
    exit;
}

registrarLog('Token CSRF válido');

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

        // Detectar causa comum: tentativa de conexão por socket quando MySQL não está local
        $sugestao = '';
        if (stripos($connectErr, 'No such file or directory') !== false || stripos($connectErr, "Can't connect to local MySQL server") !== false) {
            $sugestao = 'Possíveis causas: MySQL não está rodando, DB_HOST está apontando para um socket local ("localhost") sem servidor, ou container PHP não alcança o MySQL.\n';
            $sugestao .= 'Se estiver usando Docker sem serviço MySQL, inicie um container MySQL ou aponte DB_HOST para o host correto (ex: host.docker.internal) ou para o nome do serviço no docker-compose (ex: \"mysql\").';
        } elseif (stripos($connectErr, 'Connection refused') !== false) {
            $sugestao = 'Conexão recusada: verifique se o MySQL está rodando e aceitando conexões TCP na porta correta (3306).';
        }

        $mensagemErro = 'Erro na conexão: ' . $connectErr . ($sugestao ? ' - ' . $sugestao : '');
        throw new Exception($mensagemErro);
    }
    
    registrarLog('✓ Conectado ao banco com sucesso!', 'SUCCESS');
    
    // Definir charset UTF-8
    $conexao->set_charset('utf8mb4');
    registrarLog('✓ Charset UTF-8 definido');
    
    // Verificar se a tabela afiliados existe
    registrarLog('Verificando se tabela afiliados existe...');
    $resultado = $conexao->query("SHOW TABLES LIKE 'afiliados'");
    
    if ($resultado && $resultado->num_rows > 0) {
        registrarLog('✓ Tabela afiliados existe');
    } else {
        registrarLog('⚠ Tabela afiliados NÃO encontrada', 'WARNING');
    }
    
    // Receber e sanitizar dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmarsenha'] ?? '';
    
    registrarLog('Dados recebidos: nome=' . $nome . ', email=' . $email . ', telefone=' . $telefone);
    
    // Validações
    $erros = [];
    
    // Validar nome
    if (empty($nome)) {
        $erros[] = 'Nome é obrigatório';
    } elseif (strlen($nome) < 3 || strlen($nome) > 100) {
        $erros[] = 'Nome deve ter entre 3 e 100 caracteres';
    } elseif (!preg_match('/^[a-záéíóúâêôãõçñ\s]*$/i', $nome)) {
        $erros[] = 'Nome contém caracteres inválidos';
    }
    
    // Validar email
    if (empty($email)) {
        $erros[] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Email inválido';
    } elseif (strlen($email) > 100) {
        $erros[] = 'Email muito longo';
    }
    
    // Validar telefone
    if (empty($telefone)) {
        $erros[] = 'Telefone é obrigatório';
    } elseif (!preg_match('/^\(\d{2}\)\s?\d{4,5}-\d{4}$/', $telefone)) {
        $erros[] = 'Telefone inválido. Use o formato (XX) XXXXX-XXXX';
    }
    
    // Validar senha
    if (empty($senha)) {
        $erros[] = 'Senha é obrigatória';
    } elseif (strlen($senha) < 6) {
        $erros[] = 'Senha deve ter no mínimo 6 caracteres';
    } elseif (strlen($senha) > 100) {
        $erros[] = 'Senha muito longa';
    }
    
    // Validar confirmação de senha
    if ($senha !== $confirmar_senha) {
        $erros[] = 'As senhas não conferem';
    }
    
    // Se houver erros, retornar
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
    
    registrarLog('Todas as validações passaram');
    
    // Verificar se email já existe
    registrarLog('Verificando se email já existe: ' . $email);
    $stmt = $conexao->prepare('SELECT id FROM afiliados WHERE email = ?');
    
    if (!$stmt) {
        registrarLog('❌ Erro ao preparar query de verificação de email: ' . $conexao->error, 'ERROR');
        throw new Exception('Erro ao verificar email: ' . $conexao->error);
    }
    
    $stmt->bind_param('s', $email);
    registrarLog('Executando query de verificação de email...');
    
    if (!$stmt->execute()) {
        registrarLog('❌ Erro ao executar query de verificação: ' . $stmt->error, 'ERROR');
        throw new Exception('Erro ao executar query: ' . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    registrarLog('Query executada - Linhas encontradas: ' . $resultado->num_rows);
    
    if ($resultado->num_rows > 0) {
        registrarLog('⚠ Email já cadastrado: ' . $email, 'WARNING');
        http_response_code(409);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Este email já está cadastrado'
        ]);
        $stmt->close();
        $conexao->close();
        registrarLog('Conexão fechada devido email duplicado');
        exit;
    }
    
    registrarLog('✓ Email disponível: ' . $email);
    $stmt->close();
    
    // Hash da senha usando bcrypt
    registrarLog('Gerando hash de senha com bcrypt');
    $senha_hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
    registrarLog('✓ Hash de senha gerado com sucesso');
    
    // Gerar código único para o afiliado
    registrarLog('Gerando código único para o afiliado');
    $codigo_afiliado = gerarCodigoAfiliado();
    registrarLog('✓ Código gerado: ' . $codigo_afiliado);
    
    // Preparar e executar inserção
    registrarLog('Preparando statement de inserção');
    $stmt = $conexao->prepare('INSERT INTO afiliados (nome, email, telefone, senha, code) VALUES (?, ?, ?, ?, ?)');
    
    if (!$stmt) {
        registrarLog('❌ Erro ao preparar statement de inserção: ' . $conexao->error, 'ERROR');
        throw new Exception('Erro ao preparar statement: ' . $conexao->error);
    }
    
    registrarLog('Bindando parâmetros do INSERT');
    $stmt->bind_param('sssss', $nome, $email, $telefone, $senha_hash, $codigo_afiliado);
    
    registrarLog('Executando INSERT no banco...');
    if (!$stmt->execute()) {
        registrarLog('❌ Erro ao inserir dados: ' . $stmt->error, 'ERROR');
        throw new Exception('Erro ao inserir dados: ' . $stmt->error);
    }
    
    registrarLog('✓ Inserção realizada com sucesso');
    
    $id_afiliado = $conexao->insert_id;
    registrarLog('ID do novo afiliado: ' . $id_afiliado);
    $stmt->close();
    
    registrarLog('✓✓✓ Afiliado cadastrado com sucesso: ID=' . $id_afiliado . ', CODE=' . $codigo_afiliado . ', EMAIL=' . $email, 'SUCCESS');
    
    // Sucesso
    http_response_code(201);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cadastro realizado com sucesso!',
        'id_afiliado' => $id_afiliado,
        'code' => $codigo_afiliado
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
        'mensagem' => 'Erro ao processar o cadastro. Tente novamente.',
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
        'mensagem' => 'Erro crítico ao processar o cadastro.',
        'debug' => [
            'erro' => $t->getMessage(),
            'tipo' => get_class($t)
        ]
    ]);
}
?>
