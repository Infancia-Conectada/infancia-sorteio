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

    // Sanitizar entradas para prevenir XSS
    $nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $instagram = htmlspecialchars($instagram, ENT_QUOTES, 'UTF-8');

    // Valida formato do telefone
    if (!preg_match('/^\(\d{2}\)\s?\d{4,5}-\d{4}$/', $telefone)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Formato de telefone inválido']);
        exit;
    }

    // Limpar telefone - salvar apenas dígitos no banco
    $telefone_limpo = preg_replace('/\D/', '', $telefone);

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

    // SEGURANÇA: Verificar se Instagram já foi usado em participação anterior
    $stmt = $conn->prepare("SELECT telefone FROM participantes WHERE instagram = ?");
    $stmt->bind_param("s", $instagram_limpo);
    $stmt->execute();
    $resultInstagram = $stmt->get_result();

    if ($resultInstagram->num_rows > 0) {
        $telefoneRegistrado = $resultInstagram->fetch_assoc()['telefone'];
                
        // Se o Instagram já está registrado com OUTRO telefone, bloqueia
        if ($telefoneRegistrado !== $telefone_limpo) {
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Este Instagram já foi usado para participar. Cada @ pode participar apenas uma vez.'
            ]);
            exit;
        }
        // Se for o MESMO telefone, permite continuar (usuário retornando)
    }

    // Extrair avatar_url da resposta do Instagram
    $avatar_url = isset($resultadoInstagram['data']['avatarUrl']) ? $resultadoInstagram['data']['avatarUrl'] : null;

    // ID seguro
    $sessao_id = bin2hex(random_bytes(16));
    $csrf_token = bin2hex(random_bytes(32)); // Token CSRF único para esta sessão
    $expiracao = date('Y-m-d H:i:s', strtotime('+2 hours'));

    // Cria tabela sessoes_temp caso não exista
    $conn->query("CREATE TABLE IF NOT EXISTS sessoes_temp (
        id VARCHAR(50) PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        telefone VARCHAR(20) NOT NULL,
        instagram VARCHAR(31) NOT NULL,
        avatar_url VARCHAR(600) DEFAULT NULL,
        csrf_token VARCHAR(64) NOT NULL,
        parametro_unico VARCHAR(255) DEFAULT NULL,
        expiracao DATETIME NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_expiracao (expiracao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Remove sessões expiradas
    $conn->query("DELETE FROM sessoes_temp WHERE expiracao < NOW()");

    // Verifica se já existe sessão ativa para este telefone
    $stmt = $conn->prepare("SELECT id, instagram FROM sessoes_temp WHERE telefone = ? AND expiracao > NOW()");
    $stmt->bind_param("s", $telefone_limpo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sessao_existente = $result->fetch_assoc();
        $sessao_existente_id = $sessao_existente['id'];
        
        // Verificar se o Instagram também é o mesmo
        if ($sessao_existente['instagram'] === $instagram_limpo) {
            // Mesmo telefone E mesmo Instagram: apenas renova sessão (auto-login)
            $stmt = $conn->prepare("UPDATE sessoes_temp 
                SET nome = ?, avatar_url = ?, csrf_token = ?, parametro_unico = ?, expiracao = ? 
                WHERE id = ?");
            $stmt->bind_param("ssssss", $nome, $avatar_url, $csrf_token, $parametro_unico, $expiracao, $sessao_existente_id);
            $stmt->execute();

            echo json_encode([
                'status' => 'ok', 
                'sessao_id' => $sessao_existente_id, 
                'csrf_token' => $csrf_token,
                'redirect' => true  // Auto-login
            ]);
            exit;
        } else {
            // Mesmo telefone mas Instagram DIFERENTE: bloqueia
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Este telefone já está cadastrado com outro Instagram (@' . $sessao_existente['instagram'] . '). Use o mesmo @ para continuar.'
            ]);
            exit;
        }
    }

    // Cria nova sessão
    $stmt = $conn->prepare("INSERT INTO sessoes_temp (id, nome, telefone, instagram, avatar_url, csrf_token, parametro_unico, expiracao)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $sessao_id, $nome, $telefone_limpo, $instagram_limpo, $avatar_url, $csrf_token, $parametro_unico, $expiracao);
    $stmt->execute();

    // RESERVAR INSTAGRAM: Criar registro em participantes (sem empresa ainda)
    // Isso impede que outro telefone use este @ mesmo se a sessão expirar
    $stmt = $conn->prepare("SELECT id FROM participantes WHERE telefone = ?");
    $stmt->bind_param("s", $telefone_limpo);
    $stmt->execute();
    $participanteExiste = $stmt->get_result();

    if ($participanteExiste->num_rows === 0) {
        // Cria participante com todas empresas = 0 (ainda não participou)
        $stmt = $conn->prepare("
            INSERT INTO participantes 
            (nome, telefone, e1, e2, e3, e4, parametro_unico, instagram, avatar_url)
            VALUES (?, ?, 0, 0, 0, 0, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $nome, $telefone_limpo, $parametro_unico, $instagram_limpo, $avatar_url);
        $stmt->execute();

        // Enviar mensagem de boas-vindas via WhatsApp
        enviarMensagemBoasVindas($nome, $telefone_limpo, $parametro_unico);
    }

    echo json_encode(['status' => 'ok', 'sessao_id' => $sessao_id, 'csrf_token' => $csrf_token]);
    exit;


// ========================================
// AÇÃO 2: VALIDAR SESSÃO
// ========================================
} elseif ($acao === 'validar_sessao') {

    if (!isset($data['sessao_id'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID de sessão não fornecido']);
        exit;
    }

    // Validação CSRF opcional para validar_sessao (apenas verifica se enviado)
    $sessao_id = $data['sessao_id'];

    $stmt = $conn->prepare("SELECT nome, telefone, instagram, avatar_url, csrf_token, parametro_unico 
        FROM sessoes_temp WHERE id = ? AND expiracao > NOW()");
    $stmt->bind_param("s", $sessao_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sessão inválida ou expirada']);
        exit;
    }

    $sessao = $result->fetch_assoc();

    // Mascarar telefone - mostrar apenas últimos 4 dígitos
    $telefone = $sessao['telefone'];
    $telefone_limpo = preg_replace('/\D/', '', $telefone);
    $ultimos4 = substr($telefone_limpo, -4);
    $telefone_mascarado = '(**) *****-' . $ultimos4;

    // Sanitizar saídas para prevenir XSS
    echo json_encode([
        'status' => 'ok',
        'dados' => [
            'nome' => htmlspecialchars($sessao['nome'], ENT_QUOTES, 'UTF-8'),
            'telefone' => $telefone_mascarado,
            'instagram' => htmlspecialchars($sessao['instagram'], ENT_QUOTES, 'UTF-8'),
            'avatar_url' => $sessao['avatar_url'] ? htmlspecialchars($sessao['avatar_url'], ENT_QUOTES, 'UTF-8') : null,
            'csrf_token' => $sessao['csrf_token'],
            'parametro_unico' => $sessao['parametro_unico'] ? htmlspecialchars($sessao['parametro_unico'], ENT_QUOTES, 'UTF-8') : null
        ]
    ]);

    exit;
}



// ========================================
// AÇÃO 3: OBTER PROGRESSO DO USUÁRIO
// ========================================
elseif ($acao === 'obter_progresso') {

    if (!isset($data['sessao_id'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID de sessão não fornecido']);
        exit;
    }

    $sessao_id = $data['sessao_id'];

    // Buscar dados da sessão para obter telefone
    $stmt = $conn->prepare("SELECT telefone FROM sessoes_temp WHERE id = ? AND expiracao > NOW()");
    $stmt->bind_param("s", $sessao_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sessão inválida ou expirada']);
        exit;
    }

    $sessao = $result->fetch_assoc();
    $telefone = $sessao['telefone'];

    // Buscar progresso do participante
    $stmt = $conn->prepare("SELECT e1, e2, e3, e4 FROM participantes WHERE telefone = ?");
    $stmt->bind_param("s", $telefone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Participante ainda não tem registros
        echo json_encode([
            'status' => 'ok',
            'progresso' => [
                'e1' => 0,
                'e2' => 0,
                'e3' => 0,
                'e4' => 0,
                'total' => 0,
                'completado' => false
            ]
        ]);
        exit;
    }

    $participante = $result->fetch_assoc();
    $e1 = (int)$participante['e1'];
    $e2 = (int)$participante['e2'];
    $e3 = (int)$participante['e3'];
    $e4 = (int)$participante['e4'];
    $total = $e1 + $e2 + $e3 + $e4;
    $completado = ($total === 4);

    echo json_encode([
        'status' => 'ok',
        'progresso' => [
            'e1' => $e1,
            'e2' => $e2,
            'e3' => $e3,
            'e4' => $e4,
            'total' => $total,
            'completado' => $completado
        ]
    ]);

    exit;
}



// ========================================
// AÇÃO 4: REGISTRAR PARTICIPAÇÃO
// ========================================
elseif ($acao === 'registrar_participacao') {

    if (!isset($data['sessao_id']) || !isset($data['empresa'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados incompletos']);
        exit;
    }

    // Validação CSRF obrigatória
    if (!isset($data['csrf_token']) || empty($data['csrf_token'])) {
        error_log('CSRF: Token não fornecido');
        echo json_encode(['status' => 'erro', 'mensagem' => 'Token de segurança não fornecido']);
        exit;
    }

    $sessao_id = $data['sessao_id'];
    $csrf_token_fornecido = $data['csrf_token'];
    $empresa = intval($data['empresa']);

    if ($empresa < 1 || $empresa > 4) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Empresa inválida']);
        exit;
    }

    // Busca dados da sessão incluindo csrf_token
    $stmt = $conn->prepare("SELECT nome, telefone, instagram, csrf_token, parametro_unico 
        FROM sessoes_temp WHERE id = ? AND expiracao > NOW()");
    $stmt->bind_param("s", $sessao_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Sessão expirada']);
        exit;
    }

    $sessao = $result->fetch_assoc();

    // Validar CSRF token
    if (!hash_equals($sessao['csrf_token'], $csrf_token_fornecido)) {
        error_log('CSRF: Token inválido - Esperado: ' . substr($sessao['csrf_token'], 0, 10) . '... | Recebido: ' . substr($csrf_token_fornecido, 0, 10) . '...');
        echo json_encode(['status' => 'erro', 'mensagem' => 'Token de segurança inválido']);
        exit;
    }

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

        // Atualiza apenas a participação usando CASE WHEN (sem interpolação de variável)
        // Isso previne completamente SQL injection ao evitar concatenação de strings
        $sql = "UPDATE participantes SET 
            e1 = CASE WHEN ? = 1 THEN 1 ELSE e1 END,
            e2 = CASE WHEN ? = 2 THEN 1 ELSE e2 END,
            e3 = CASE WHEN ? = 3 THEN 1 ELSE e3 END,
            e4 = CASE WHEN ? = 4 THEN 1 ELSE e4 END
        WHERE telefone = ? AND instagram = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiss", $empresa, $empresa, $empresa, $empresa, $telefone, $instagram);
        $stmt->execute();

        // Verificar se completou todas as 4 empresas (buscar por Instagram, que é único)
        $stmt = $conn->prepare("SELECT e1, e2, e3, e4, parametro_unico FROM participantes WHERE instagram = ?");
        $stmt->bind_param("s", $instagram);
        $stmt->execute();
        $result = $stmt->get_result();
        $participante = $result->fetch_assoc();

        if ($participante['e1'] == 1 && $participante['e2'] == 1 && $participante['e3'] == 1 && $participante['e4'] == 1) {
            // Completou todas! Enviar mensagens
            enviarMensagemCurtidas($nome, $telefone);
            
            // Se veio por afiliado, notificar o afiliado
            if (!empty($participante['parametro_unico'])) {
                notificarAfiliado($participante['parametro_unico'], $nome);
            }
        }

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
        (nome, telefone, e1, e2, e3, e4, parametro_unico, instagram, avatar_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // TIPOS CORRIGIDOS
    $stmt->bind_param("ssiiiisss", 
        $nome, 
        $telefone, 
        $e1, 
        $e2, 
        $e3, 
        $e4, 
        $parametro_unico, 
        $instagram,
        $sessao['avatar_url']
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

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

/**
 * Envia mensagem de boas-vindas para participante
 */
function enviarMensagemBoasVindas($nome, $telefone, $parametroUnico) {
    $apiBaseUrl = env('API_BASE_URL', 'https://api-express-production-c152.up.railway.app');
    $endpoint = $apiBaseUrl . '/api/notifications/participant/welcome';
    
    // Limpar telefone e adicionar código do Brasil
    $telefone_limpo = preg_replace('/\D/', '', $telefone);
    if (!str_starts_with($telefone_limpo, '55')) {
        $telefone_limpo = '55' . $telefone_limpo;
    }
    
    // Preparar payload
    $payload = [
        'phoneNumber' => $telefone_limpo,
        'name' => $nome
    ];
    
    // Adicionar referredBy apenas se tiver parametro_unico
    if (!empty($parametroUnico)) {
        // Buscar nome do afiliado pelo code
        global $conn;
        $stmt = $conn->prepare("SELECT nome FROM afiliados WHERE code = ?");
        $stmt->bind_param("s", $parametroUnico);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $afiliado = $result->fetch_assoc();
            $payload['referredBy'] = $afiliado['nome'];
        }
    }
    
    // Enviar requisição
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . env('API_KEY', 'a9F3kP7q1Xv2bL6tR8mC4yN0wZ5hJ2Q')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log de debug (apenas em desenvolvimento)
    if (env('APP_ENV') === 'development') {
        error_log("WhatsApp Boas-vindas - HTTP: $httpCode, Response: $response");
    }
}

/**
 * Envia mensagem quando completa todas as curtidas
 */
function enviarMensagemCurtidas($nome, $telefone) {
    $apiBaseUrl = env('API_BASE_URL', 'https://api-express-production-c152.up.railway.app');
    $endpoint = $apiBaseUrl . '/api/notifications/participant/likes-completed';
    
    // Limpar telefone e adicionar código do Brasil
    $telefone_limpo = preg_replace('/\D/', '', $telefone);
    if (!str_starts_with($telefone_limpo, '55')) {
        $telefone_limpo = '55' . $telefone_limpo;
    }
    
    // Preparar payload
    $payload = [
        'phoneNumber' => $telefone_limpo,
        'name' => $nome,
        'totalLikes' => 4,
        'companies' => ['Montreal', 'Del Match', 'ST Motors', 'Infância Conectada'],
        'drawDate' => '28 de novembro de 2025'
    ];
    
    // Enviar requisição
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . env('API_KEY', 'a9F3kP7q1Xv2bL6tR8mC4yN0wZ5hJ2Q')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log de debug (apenas em desenvolvimento)
    if (env('APP_ENV') === 'development') {
        error_log("WhatsApp Curtidas Completas - HTTP: $httpCode, Response: $response");
    }
}

/**
 * Notifica afiliado sobre novo indicado
 */
function notificarAfiliado($codigoAfiliado, $nomeParticipante) {
    global $conn;
    
    // Buscar dados do afiliado pelo code
    $stmt = $conn->prepare("SELECT nome, telefone FROM afiliados WHERE code = ?");
    $stmt->bind_param("s", $codigoAfiliado);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return; // Afiliado não encontrado
    }
    
    $afiliado = $result->fetch_assoc();
    $nomeAfiliado = $afiliado['nome'];
    $telefoneAfiliado = $afiliado['telefone'];
    
    // Verificar se afiliado tem telefone cadastrado
    if (empty($telefoneAfiliado)) {
        if (env('APP_ENV') === 'development') {
            error_log("Afiliado $codigoAfiliado não tem telefone cadastrado. Notificação não enviada.");
        }
        return;
    }
    
    // Contar total de indicações ativas deste afiliado
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM participantes WHERE parametro_unico = ?");
    $stmt->bind_param("s", $codigoAfiliado);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalIndicacoes = $result->fetch_assoc()['total'];
    
    $apiBaseUrl = env('API_BASE_URL', 'https://api-express-production-c152.up.railway.app');
    $endpoint = $apiBaseUrl . '/api/notifications/affiliate/new-referral';
    
    // Limpar telefone e adicionar código do Brasil
    $telefone_limpo = preg_replace('/\D/', '', $telefoneAfiliado);
    if (!str_starts_with($telefone_limpo, '55')) {
        $telefone_limpo = '55' . $telefone_limpo;
    }
    
    // Preparar payload
    $payload = [
        'phoneNumber' => $telefone_limpo,
        'affiliateName' => $nomeAfiliado,
        'newUserName' => $nomeParticipante,
        'totalActiveReferrals' => $totalIndicacoes
    ];
    
    // Enviar requisição
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . env('API_KEY', 'a9F3kP7q1Xv2bL6tR8mC4yN0wZ5hJ2Q')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log de debug (apenas em desenvolvimento)
    if (env('APP_ENV') === 'development') {
        error_log("WhatsApp Afiliado - HTTP: $httpCode, Response: $response");
    }
}
?>
