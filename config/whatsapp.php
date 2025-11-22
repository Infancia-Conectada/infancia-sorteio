<?php
/**
 * Configuração da API de validação de WhatsApp
 */

// Carregar variáveis de ambiente
require_once __DIR__ . '/env.php';

/**
 * Valida se um número de telefone possui WhatsApp
 * 
 * @param string $telefone Telefone no formato (XX) XXXXX-XXXX
 * @return array Retorna array com 'sucesso', 'mensagem' e 'hasWhatsApp'
 */
function validarWhatsApp($telefone) {
    // Configurações da API
    $baseUrl = env('API_BASE_URL', 'https://api.exemplo.com');
    $apiKey = env('API_KEY', 'a9F3kP7q1Xv2bL6tR8mC4yN0wZ5hJ2Q');
    
    // Endpoint da API
    $endpoint = $baseUrl . '/api/whatsapp/validate-number';
    
    // Remover formatação do telefone e adicionar código do Brasil (55)
    $numeroLimpo = preg_replace('/\D/', '', $telefone);
    
    // Garantir que o número tenha o código do país
    if (!str_starts_with($numeroLimpo, '55')) {
        $numeroLimpo = '55' . $numeroLimpo;
    }
    
    // Preparar payload
    $payload = json_encode([
        'number' => $numeroLimpo
    ]);
    
    // Configurar requisição cURL
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Timeout de 20 segundos
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout de conexão de 5 segundos
    
    // Executar requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Verificar erros de conexão
    if ($curlError) {
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao conectar com o serviço de validação de WhatsApp',
            'hasWhatsApp' => false,
            'debug' => $curlError
        ];
    }
    
    // Verificar código HTTP
    if ($httpCode !== 200) {
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao validar número de WhatsApp (código: ' . $httpCode . ')',
            'hasWhatsApp' => false,
            'debug' => $response
        ];
    }
    
    // Decodificar resposta
    $dados = json_decode($response, true);
    
    if (!$dados) {
        return [
            'sucesso' => false,
            'mensagem' => 'Resposta inválida do serviço de validação',
            'hasWhatsApp' => false
        ];
    }
    
    // Retornar resultado
    return [
        'sucesso' => $dados['success'] ?? false,
        'mensagem' => $dados['message'] ?? 'Erro desconhecido',
        'hasWhatsApp' => $dados['hasWhatsApp'] ?? false
    ];
}
