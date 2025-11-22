<?php
/**
 * Configuração da API de validação de Instagram
 */

// Carregar variáveis de ambiente
require_once __DIR__ . '/env.php';

/**
 * Valida se um username de Instagram existe
 * 
 * @param string $username Username do Instagram (sem @)
 * @return array Retorna array com 'success', 'data' (username e avatarUrl)
 */
function validarInstagram($username) {
    // Configurações da API (usa mesma base URL do WhatsApp)
    $baseUrl = env('API_BASE_URL', 'https://api.exemplo.com');
    $apiKey = env('API_KEY', 'a9F3kP7q1Xv2bL6tR8mC4yN0wZ5hJ2Q');
    
    // Remover @ se existir
    $username = ltrim($username, '@');
    
    // Endpoint da API
    $endpoint = $baseUrl . '/api/instagram/validate/' . urlencode($username);
    
    // Configurar requisição cURL
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout de 15 segundos
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout de conexão de 5 segundos
    
    // Executar requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Verificar erros de conexão
    if ($curlError) {
        return [
            'success' => false,
            'data' => [],
            'error' => 'Erro ao conectar com o serviço de validação de Instagram',
            'debug' => $curlError
        ];
    }
    
    // Verificar código HTTP
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'data' => [],
            'error' => 'Erro ao validar username de Instagram (código: ' . $httpCode . ')',
            'debug' => $response
        ];
    }
    
    // Decodificar resposta
    $dados = json_decode($response, true);
    
    if (!$dados) {
        return [
            'success' => false,
            'data' => [],
            'error' => 'Resposta inválida do serviço de validação'
        ];
    }
    
    // Retornar resultado
    return [
        'success' => $dados['success'] ?? false,
        'data' => $dados['data'] ?? []
    ];
}
