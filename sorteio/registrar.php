<?php
// Carregar configurações de ambiente primeiro
require_once __DIR__ . '/../config/env.php';

// Configurar display_errors baseado no ambiente
if (env('APP_ENV') === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

header('Content-Type: application/json');

// Carregar função de validação de WhatsApp e Instagram

// Configurar CORS restritivo
$allowed_origins = explode(',', env('ALLOWED_ORIGINS', 'http://localhost,http://localhost:80'));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} elseif (env('APP_ENV') === 'development') {
    // Em desenvolvimento, permitir localhost
    if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
        header("Access-Control-Allow-Origin: $origin");
    }
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/whatsapp.php';
require_once __DIR__ . '/../config/instagram.php';

// Configuração do banco via variáveis de ambiente
$host = env('DB_HOST', 'localhost');
$user = env('DB_USER');
$pass = env('DB_PASS');
$dbname = env('DB_NAME');

// Validar configurações obrigatórias
if (empty($user) || empty($pass) || empty($dbname)) {
    error_log('ERRO: Configurações de banco ausentes no .env');
    echo json_encode(['status' => 'erro', 'mensagem' => 'Configuração do banco de dados não encontrada']);
    exit;
}

// Conecta ao banco
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    error_log('ERRO MySQL: ' . $conn->connect_error);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro de conexão']);
    exit;
}

// Lê os dados enviados
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['acao'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ação não especificada']);
    exit;
}

$acao = $data['acao'];


// ========================================
// AÇÃO 1: CRIAR SESSÃO
// ========================================
if ($acao === 'criar_sessao') {

    if (!isset($data['nome']) || !isset($data['telefone']) || !isset($data['instagram'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados incompletos']);
        exit;
    }

    $nome = trim($data['nome']);
    $telefone = trim($data['telefone']);
    $instagram = trim($data['instagram']);
    $parametro_unico = isset($data['parametro_unico']) ? trim($data['parametro_unico']) : null;

    // Valida formato do telefone
    if (!preg_match('/^\(\d{2}\)\s?\d{4,5}-\d{4}$/', $telefone)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Formato de telefone inválido']);
        exit;
    }

    // Valida nome
    if (strlen($nome) < 3) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Nome muito curto']);
        exit;
    }

    // Valida Instagram
    $instagram_limpo = str_replace('@', '', $instagram);
    if (!preg_match('/^(?!.*\.\.)(?!\.)(?!.*\.$)[a-zA-Z0-9._]{1,30}$/', $instagram_limpo)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Instagram inválido']);
        exit;
    }

    // Validar WhatsApp
    $resultadoWhatsApp = validarWhatsApp($telefone);
    
    if (!$resultadoWhatsApp['sucesso']) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Não foi possível validar o número de WhatsApp. Tente novamente.']);
        exit;
    }
    
    if (!$resultadoWhatsApp['hasWhatsApp']) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'O telefone informado não possui WhatsApp ativo. Por favor, informe um número com WhatsApp.']);
        exit;
    }

    // Validar Instagram
    $resultadoInstagram = validarInstagram($instagram_limpo);
    
    if (!$resultadoInstagram['success']) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'O username do Instagram não existe. Verifique se digitou corretamente.']);
        exit;
    }

    // ID seguro
    $sessao_id = bin2hex(random_bytes(16));
    $expiracao = date('Y-m-d H:i:s', strtotime('+2 hours'));

    // Cria tabela sessoes_temp caso não exista
    $conn->query("CREATE TABLE IF NOT EXISTS sessoes_temp (
        id VARCHAR(50) PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        telefone VARCHAR(20) NOT NULL,
        instagram VARCHAR(31) NOT NULL,
        parametro_unico VARCHAR(255) DEFAULT NULL,
        expiracao DATETIME NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_expiracao (expiracao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Remove sessões expiradas
    $conn->query("DELETE FROM sessoes_temp WHERE expiracao < NOW()");

    // Verifica se já existe sessão ativa para este telefone
    $stmt = $conn->prepare("SELECT id FROM sessoes_temp WHERE telefone = ? AND expiracao > NOW()");
    $stmt->bind_param("s", $telefone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Atualiza sessão existente
        $stmt = $conn->prepare("UPDATE sessoes_temp 
            SET nome = ?, instagram = ?, parametro_unico = ?, expiracao = ? 
            WHERE telefone = ?");
        $stmt->bind_param("sssss", $nome, $instagram, $parametro_unico, $expiracao, $telefone);
        $stmt->execute();

        echo json_encode(['status' => 'ok', 'sessao_id' => $result->fetch_assoc()['id']]);
        exit;
    }

    // Cria nova sessão
    $stmt = $conn->prepare("INSERT INTO sessoes_temp (id, nome, telefone, instagram, parametro_unico, expiracao)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $sessao_id, $nome, $telefone, $instagram, $parametro_unico, $expiracao);
    $stmt->execute();

    echo json_encode(['status' => 'ok', 'sessao_id' => $sessao_id]);
    exit;
}



// ========================================
// AÇÃO 2: VALIDAR SESSÃO
// ========================================
elseif ($acao === 'validar_sessao') {

    if (!isset($data['sessao_id'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID de sessão não fornecido']);
        exit;
    }

    $sessao_id = $data['sessao_id'];

    $stmt = $conn->prepare("SELECT nome, telefone, instagram, parametro_unico 
        FROM sessoes_temp WHERE id = ? AND expiracao > NOW()");
    $stmt->bind_param("s", $sessao_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sessão inválida ou expirada']);
        exit;
    }

    $sessao = $result->fetch_assoc();

    echo json_encode([
        'status' => 'ok',
        'nome' => $sessao['nome'],
        'telefone' => $sessao['telefone'],
        'instagram' => $sessao['instagram'],
        'parametro_unico' => $sessao['parametro_unico']
    ]);

    exit;
}



// ========================================
// AÇÃO 3: REGISTRAR PARTICIPAÇÃO
// ========================================
elseif ($acao === 'registrar_participacao') {

    if (!isset($data['sessao_id']) || !isset($data['empresa'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados incompletos']);
        exit;
    }

    $sessao_id = $data['sessao_id'];
    $empresa = intval($data['empresa']);

    if ($empresa < 1 || $empresa > 4) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Empresa inválida']);
        exit;
    }

    // Busca dados da sessão
    $stmt = $conn->prepare("SELECT nome, telefone, instagram, parametro_unico 
        FROM sessoes_temp WHERE id = ? AND expiracao > NOW()");
    $stmt->bind_param("s", $sessao_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sessão expirada']);
        exit;
    }

    $sessao = $result->fetch_assoc();

    $nome = $sessao['nome'];
    $telefone = $sessao['telefone'];
    $instagram = $sessao['instagram'];
    $parametro_unico = $sessao['parametro_unico'];

    // Define coluna dinâmica com whitelist (previne SQL injection)
    $colunas_permitidas = ['e1', 'e2', 'e3', 'e4'];
    $coluna = $colunas_permitidas[$empresa - 1];

    // Verifica se usuário já existe
    $stmt = $conn->prepare("SELECT * FROM participantes WHERE telefone = ?");
    $stmt->bind_param("s", $telefone);
    $stmt->execute();
    $existe = $stmt->get_result();

    // Atualizar participante existente
    if ($existe->num_rows > 0) {

        $user = $existe->fetch_assoc();

        // SEGURANÇA: Verificar se Instagram pertence a este telefone
        if ($user['instagram'] !== $instagram) {
            echo json_encode([
                'status' => 'erro', 
                'mensagem' => 'Este telefone já está cadastrado com outro Instagram. Use o mesmo Instagram ou outro telefone.'
            ]);
            exit;
        }

        if ($user[$coluna] == 1) {
            echo json_encode(['status' => 'duplicado', 'mensagem' => 'Você já participou deste sorteio']);
            exit;
        }

        // Atualiza apenas a participação (não altera Instagram)
        // Validação: $coluna já foi validada pela whitelist acima
        if (!in_array($coluna, ['e1', 'e2', 'e3', 'e4'])) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Coluna inválida']);
            exit;
        }
        $sql = "UPDATE participantes SET $coluna = 1 WHERE telefone = ? AND instagram = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $telefone, $instagram);
        $stmt->execute();

        echo json_encode(['status' => 'ok', 'mensagem' => 'Participação registrada']);
        exit;
    }


    // Verificar se Instagram já existe em outro telefone
    $stmt = $conn->prepare("SELECT telefone FROM participantes WHERE instagram = ?");
    $stmt->bind_param("s", $instagram);
    $stmt->execute();
    $resultInstagram = $stmt->get_result();

    if ($resultInstagram->num_rows > 0) {
        $outroTelefone = $resultInstagram->fetch_assoc()['telefone'];
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Este Instagram já está cadastrado em outro telefone. Cada @ pode participar apenas uma vez.'
        ]);
        exit;
    }

    // NOVO PARTICIPANTE — insere na ordem correta:
    // id, nome, telefone, e1, e2, e3, e4, criado_em, parametro_unico, instagram

    $e1 = ($empresa === 1) ? 1 : 0;
    $e2 = ($empresa === 2) ? 1 : 0;
    $e3 = ($empresa === 3) ? 1 : 0;
    $e4 = ($empresa === 4) ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO participantes 
        (nome, telefone, e1, e2, e3, e4, parametro_unico, instagram)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // TIPOS CORRIGIDOS
    $stmt->bind_param("ssiiiiss", 
        $nome, 
        $telefone, 
        $e1, 
        $e2, 
        $e3, 
        $e4, 
        $parametro_unico, 
        $instagram
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'ok', 'mensagem' => 'Participação registrada']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro: ' . $stmt->error]);
    }

    exit;
}



// ========================================
// AÇÃO INVÁLIDA
// ========================================
else {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
}

$conn->close();
?>
