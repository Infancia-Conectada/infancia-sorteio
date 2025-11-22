<?php
/**
 * Endpoint para validar username de Instagram via AJAX
 */

header('Content-Type: application/json');

// Permitir apenas GET ou POST
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => 'Método não permitido'
    ]);
    exit;
}

// Carregar função de validação
require_once __DIR__ . '/config/instagram.php';

// Obter username
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = $_GET['username'] ?? '';
} else {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    $username = $dados['username'] ?? '';
}

// Validar se username foi enviado
if (empty($username)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => 'Username não informado'
    ]);
    exit;
}

// Remover @ se existir
$username = ltrim($username, '@');

// Validar formato básico do username
if (!preg_match('/^[a-zA-Z0-9._]{1,30}$/', $username)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => 'Username inválido'
    ]);
    exit;
}

// Validar Instagram
$resultado = validarInstagram($username);

// Retornar resposta
http_response_code(200);
echo json_encode($resultado);
