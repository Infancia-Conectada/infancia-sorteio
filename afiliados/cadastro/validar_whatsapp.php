<?php
/**
 * Endpoint para validar número de WhatsApp via AJAX
 */

header('Content-Type: application/json');

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido',
        'hasWhatsApp' => false
    ]);
    exit;
}

// Carregar função de validação
require_once __DIR__ . '/../../config/whatsapp.php';

// Obter JSON do corpo da requisição
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

// Validar se número foi enviado
if (!isset($dados['number']) || empty($dados['number'])) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Número não informado',
        'hasWhatsApp' => false
    ]);
    exit;
}

// Formatar número para padrão brasileiro (XX) XXXXX-XXXX
$numero = $dados['number'];

// Remover código do país se presente
if (str_starts_with($numero, '55')) {
    $numero = substr($numero, 2);
}

// Formatar para validação
if (strlen($numero) === 11) {
    // Celular: (XX) XXXXX-XXXX
    $telefoneFormatado = '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 5) . '-' . substr($numero, 7);
} elseif (strlen($numero) === 10) {
    // Fixo: (XX) XXXX-XXXX
    $telefoneFormatado = '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 4) . '-' . substr($numero, 6);
} else {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Número inválido',
        'hasWhatsApp' => false
    ]);
    exit;
}

// Validar WhatsApp
$resultado = validarWhatsApp($telefoneFormatado);

// Retornar resposta
http_response_code(200);
echo json_encode($resultado);
