<?php
// Arquivo para visualizar logs de cadastro

$log_dir = __DIR__ . '/logs';

// Verificar se diretório de logs existe
if (!is_dir($log_dir)) {
    die('Nenhum log encontrado ainda.');
}

// Listar arquivos de log
$arquivos_log = array_reverse(glob($log_dir . '/cadastro_*.log'));

if (empty($arquivos_log)) {
    die('Nenhum arquivo de log encontrado.');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizador de Logs - Cadastro Afiliados</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            margin-bottom: 20px;
            color: #4ec9b0;
        }
        
        .log-selector {
            margin-bottom: 20px;
        }
        
        select {
            padding: 10px;
            background: #252526;
            color: #d4d4d4;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .log-content {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            padding: 15px;
            max-height: 600px;
            overflow-y: auto;
            line-height: 1.6;
        }
        
        .log-line {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid transparent;
        }
        
        .log-line.START {
            border-left-color: #4ec9b0;
            color: #4ec9b0;
        }
        
        .log-line.SUCCESS {
            border-left-color: #6a9955;
            color: #6a9955;
        }
        
        .log-line.ERROR {
            border-left-color: #f48771;
            color: #f48771;
        }
        
        .log-line.WARNING {
            border-left-color: #dcdcaa;
            color: #dcdcaa;
        }
        
        .log-line.VALIDATION {
            border-left-color: #9cdcfe;
            color: #9cdcfe;
        }
        
        .log-line.DEBUG {
            border-left-color: #ce9178;
            color: #ce9178;
        }
        
        .log-line.INFO {
            border-left-color: #d4d4d4;
            color: #d4d4d4;
        }
        
        .controls {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        button {
            padding: 10px 20px;
            background: #4ec9b0;
            color: #1e1e1e;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        button:hover {
            background: #5edfc7;
        }
        
        .info {
            background: #252526;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            color: #9cdcfe;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Visualizador de Logs - Cadastro Afiliados</h1>
        
        <div class="info">
            Total de arquivos de log: <strong><?php echo count($arquivos_log); ?></strong>
        </div>
        
        <div class="log-selector">
            <label for="arquivo-log">Selecione um arquivo de log:</label><br><br>
            <select id="arquivo-log" onchange="carregarLog(this.value)">
                <option value="">-- Selecione --</option>
                <?php foreach ($arquivos_log as $arquivo): ?>
                    <option value="<?php echo basename($arquivo); ?>" <?php echo basename($arquivo) === basename($arquivos_log[0]) ? 'selected' : ''; ?>>
                        <?php echo basename($arquivo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="log-content" class="log-content">
            Carregando...
        </div>
        
        <div class="controls">
            <button onclick="atualizarLog()">🔄 Atualizar</button>
            <button onclick="limparLogs()">🗑️ Limpar Logs</button>
        </div>
    </div>
    
    <script>
        function carregarLog(arquivo) {
            if (!arquivo) return;
            
            fetch('visualizar_logs.php?arquivo=' + encodeURIComponent(arquivo))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('log-content').innerHTML = data;
                });
        }
        
        function atualizarLog() {
            const arquivo = document.getElementById('arquivo-log').value;
            carregarLog(arquivo);
        }
        
        function limparLogs() {
            if (confirm('Tem certeza que deseja limpar todos os logs?')) {
                fetch('visualizar_logs.php?limpar=1')
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    });
            }
        }
        
        // Carregar log ao abrir a página
        window.addEventListener('load', function() {
            const arquivo = document.getElementById('arquivo-log').value;
            if (arquivo) carregarLog(arquivo);
        });
    </script>
</body>
</html>

<?php
// Se solicitado, retornar conteúdo do log
if (isset($_GET['arquivo'])) {
    $arquivo = $_GET['arquivo'];
    $caminho = $log_dir . '/' . basename($arquivo);
    
    if (file_exists($caminho)) {
        $conteudo = file_get_contents($caminho);
        $linhas = array_reverse(explode(PHP_EOL, $conteudo));
        
        foreach ($linhas as $linha) {
            if (empty($linha)) continue;
            
            // Detectar tipo de log
            $tipo = 'INFO';
            if (preg_match('/\[(\w+)\]/', $linha, $matches)) {
                $tipo = $matches[1];
            }
            
            echo '<div class="log-line ' . $tipo . '">' . htmlspecialchars($linha) . '</div>';
        }
    }
    exit;
}

// Se solicitado, limpar logs
if (isset($_GET['limpar'])) {
    array_map('unlink', glob($log_dir . '/cadastro_*.log'));
    echo 'Logs limpos com sucesso!';
    exit;
}
?>
