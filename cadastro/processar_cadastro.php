<?php
header('Content-Type: application/json');
session_start();

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'infancia-conectada');

// Classe para tratar erros
class RespuestaAPI {
    public $sucesso = false;
    public $mensagem = '';
    public $codigo = 0;
}

// Função para gerar código único e aleatório
function gerarCodigoAfiliado($conexao) {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $tamanho_codigo = 8; // Código de 8 caracteres
    
    do {
        $codigo = '';
        for ($i = 0; $i < $tamanho_codigo; $i++) {
            $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        
        // Verificar se código já existe
        $stmt = $conexao->prepare('SELECT id FROM afiliados WHERE code = ? LIMIT 1');
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $stmt->close();
        
    } while ($resultado->num_rows > 0); // Repetir se código já existe
    
    return $codigo;
}

// Validar requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido'
    ]);
    exit;
}

// Validar CSRF (opcional, mas recomendado)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Token de segurança inválido'
    ]);
    exit;
}

try {
    // Conectar ao banco de dados
    $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexão
    if ($conexao->connect_error) {
        throw new Exception('Erro na conexão: ' . $conexao->connect_error);
    }
    
    // Definir charset UTF-8
    $conexao->set_charset('utf8mb4');
    
    // Receber e sanitizar dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmarsenha'] ?? '';
    
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
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => implode(', ', $erros),
            'erros' => $erros
        ]);
        $conexao->close();
        exit;
    }
    
    // Verificar se email já existe
    $stmt = $conexao->prepare('SELECT id FROM afiliados WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Este email já está cadastrado'
        ]);
        $stmt->close();
        $conexao->close();
        exit;
    }
    $stmt->close();
    
    // Hash da senha usando bcrypt
    $senha_hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Gerar código único para o afiliado
    $codigo_afiliado = gerarCodigoAfiliado($conexao);
    
    // Preparar e executar inserção
    $stmt = $conexao->prepare('INSERT INTO afiliados (nome, email, telefone, senha, code) VALUES (?, ?, ?, ?, ?)');
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar statement: ' . $conexao->error);
    }
    
    $stmt->bind_param('sssss', $nome, $email, $telefone, $senha_hash, $codigo_afiliado);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir dados: ' . $stmt->error);
    }
    
    $id_afiliado = $conexao->insert_id;
    $stmt->close();
    
    // Sucesso
    http_response_code(201);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cadastro realizado com sucesso!',
        'id_afiliado' => $id_afiliado,
        'code' => $codigo_afiliado
    ]);
    
    $conexao->close();
    
} catch (Exception $e) {
    // Log do erro (em produção, registrar em arquivo)
    error_log('Erro no cadastro: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao processar o cadastro. Tente novamente.'
    ]);
}
?>
