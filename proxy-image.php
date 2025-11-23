<?php
/**
 * Proxy para imagens do Instagram
 * Contorna restrições CORS
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Pega URL da imagem
$imageUrl = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($imageUrl)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'URL da imagem não fornecida';
    exit;
}

// Valida que é URL do Instagram
if (!str_contains($imageUrl, 'cdninstagram.com') && !str_contains($imageUrl, 'instagram.com')) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Apenas URLs do Instagram são permitidas';
    exit;
}

// Inicializa cURL
$ch = curl_init($imageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// Headers para simular navegador
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control: no-cache',
    'Pragma: no-cache',
    'Sec-Fetch-Dest: image',
    'Sec-Fetch-Mode: no-cors',
    'Sec-Fetch-Site: cross-site'
]);

// Executa requisição
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// Verifica sucesso
if ($httpCode !== 200 || !$imageData) {
    header('HTTP/1.1 404 Not Found');
    echo 'Imagem não encontrada';
    exit;
}

// Define tipo de conteúdo
header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
header('Cache-Control: public, max-age=3600'); // Cache de 1 hora
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Retorna imagem
echo $imageData;
