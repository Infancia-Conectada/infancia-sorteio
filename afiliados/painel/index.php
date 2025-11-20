<?php 
// Verificar autenticação ANTES de qualquer HTML
require_once 'verificar_autenticacao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta name="theme-color" content="#000">
    <link rel="manifest" href="/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Painel de Afiliado - Sorteio Infância Conectada">
    
    <!-- Prevenir cache após logout -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title>Painel de Afiliado | Sorteio Infância Conectada</title>
    <link rel="stylesheet" href="styles-painel.css">
</head>

<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Carregando painel...</p>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <div class="header-info">
                    <h1 class="header-title">Olá, <span id="nomeAfiliado">Carregando...</span>!</h1>
                    <p class="header-subtitle">Bem-vindo ao Painel de Afiliado - Infância Conectada</p>
                </div>
            </div>
            <button id="btnLogout" class="btn-logout">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span class="btn-logout-text">Sair</span>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Seção do Link de Afiliado -->
        <section class="section-link">
            <div class="card card-link">
                <div class="card-header">
                    <svg class="icon-title" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="7"></circle>
                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                    </svg>
                    <h2 class="card-title">Seu Link de Afiliado</h2>
                </div>

                <div class="link-container">
                    <p id="linkAfiliado" class="link-text">Carregando...</p>
                </div>

                <div class="link-actions">
                    <button id="btnCopiar" class="btn-primary">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span class="btn-text">Copiar Link</span>
                    </button>

                    <a id="btnVisitar" href="#" target="_blank" rel="noopener noreferrer" class="btn-secondary">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span>Visitar Link</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Estatísticas -->
        <section class="section-stats">
            <h2 class="section-title">
                <svg class="icon-title" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Estatísticas
            </h2>

            <!-- Card de Cadastros -->
            <div class="card stat-card-main">
                <div class="stat-content">
                    <div class="stat-icon-main">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-label-main">Pessoas Cadastradas</h3>
                        <p id="totalCadastros" class="stat-value-main">0</p>
                        <p class="stat-description-main">Total de pessoas que se cadastraram pelo seu link</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dicas -->
        <section class="section-tips">
            <div class="card tips-card">
                <h3 class="tips-title">
                    <span class="tips-icon">💡</span>
                    Dicas para Aumentar suas Conversões
                </h3>
                <ul class="tips-list">
                    <li class="tip-item">
                        <span class="tip-bullet">•</span>
                        <span>Compartilhe seu link em suas redes sociais</span>
                    </li>
                    <li class="tip-item">
                        <span class="tip-bullet">•</span>
                        <span>Crie conteúdo sobre educação infantil e tecnologia</span>
                    </li>
                    <li class="tip-item">
                        <span class="tip-bullet">•</span>
                        <span>Engaje com sua audiência e responda dúvidas</span>
                    </li>
                    <li class="tip-item">
                        <span class="tip-bullet">•</span>
                        <span>Compartilhe os benefícios do sorteio Infância Conectada</span>
                    </li>
                </ul>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p class="footer-text">
                <span class="footer-icon">🔒</span>
                <span>Painel seguro - Infância Conectada © 2025</span>
            </p>
        </div>
    </footer>

    <script src="script-painel.js"></script>
</body>

</html>