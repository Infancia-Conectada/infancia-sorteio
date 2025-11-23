<?php
/**
 * Endpoint de estatísticas do painel de afiliados
 *
 * Retorna um JSON com o total de cadastros associados ao `code_afiliado` salvo
 * na sessão (campo `parametro_unico` na tabela `participantes`).
 *
 * Segurança/uso:
 * - Requer sessão PHP válida e que $_SESSION['code_afiliado'] esteja definido.
 * - É protegido por `start_secure_session()` que aplica políticas de cookie.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/session.php';
start_secure_session();

// Verificar sessão e garantir que o afiliado está autenticado
if (empty($_SESSION['id_afiliado']) || empty($_SESSION['code_afiliado'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autenticado']);
    exit;
}

require_once __DIR__ . '/../../config/env.php';

$code = $_SESSION['code_afiliado'];

$dbHost = env('DB_HOST');
$dbPort = (int) env('DB_PORT', 3306);
$dbUser = env('DB_USER');
$dbPass = env('DB_PASS');
$dbName = env('DB_NAME');

// Conectar ao banco
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de conexão ao banco']);
    exit;
}

$mysqli->set_charset('utf8mb4');

// Contar cadastros por parametro_unico igual ao code do afiliado
$stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM participantes WHERE parametro_unico = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao preparar consulta']);
    exit;
}

$stmt->bind_param('s', $code);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total = (int) ($row['total'] ?? 0);
$stmt->close();

// Buscar lista de cadastrados
$stmt = $mysqli->prepare('
    SELECT nome, instagram, avatar_url, criado_em 
    FROM participantes 
    WHERE parametro_unico = ? 
    ORDER BY criado_em DESC 
    LIMIT 100
');

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao buscar cadastrados']);
    exit;
}

$stmt->bind_param('s', $code);
$stmt->execute();
$res = $stmt->get_result();

$cadastrados = [];
while ($row = $res->fetch_assoc()) {
    $cadastrados[] = [
        'nome' => $row['nome'],
        'instagram' => $row['instagram'],
        'avatar_url' => $row['avatar_url'],
        'criado_em' => $row['criado_em']
    ];
}

$stmt->close();
$mysqli->close();

echo json_encode([
    'sucesso' => true,
    'total_cadastros' => $total,
    'nome_afiliado' => $_SESSION['nome_afiliado'] ?? null,
    'code_afiliado' => $code,
    'cadastrados' => $cadastrados
]);

?>