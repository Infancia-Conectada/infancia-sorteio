<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/session.php';
start_secure_session();

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'token' => $_SESSION['csrf_token']
]);
?>
