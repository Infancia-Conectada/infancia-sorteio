#!/bin/bash

# Script de Build para Hostinger
# Cria pasta dist com apenas os arquivos necessários para produção

echo "🚀 Iniciando build para produção..."

# Remove dist antiga se existir
if [ -d "dist" ]; then
    echo "🗑  Removendo pasta dist antiga..."
    rm -rf dist
fi

# Cria pasta dist
echo "📁 Criando estrutura de pastas..."
mkdir -p dist

# Copia arquivos raiz (HTML, CSS, JS, PHP)
echo "📄 Copiando arquivos raiz..."
[ -f "index.html" ] && cp index.html dist/
[ -f "script-cadastro.js" ] && cp script-cadastro.js dist/
[ -f "styles-cadastro.css" ] && cp styles-cadastro.css dist/
[ -f "avatar-instagram.php" ] && cp avatar-instagram.php dist/
[ -f "proxy-image.php" ] && cp proxy-image.php dist/
[ -f "validar_instagram.php" ] && cp validar_instagram.php dist/

# Copia pasta adm completa
echo "🎯  Copiando pasta adm..."
if [ -d "adm" ]; then
    mkdir -p dist/adm
    cp -r adm/* dist/adm/
fi

# Copia pasta afiliados completa (exceto logs)
echo "👥 Copiando pasta afiliados..."
if [ -d "afiliados" ]; then
    mkdir -p dist/afiliados
    cp -r afiliados/* dist/afiliados/ 2>/dev/null
    # Remove pastas de logs
    rm -rf dist/afiliados/logs dist/afiliados/cadastro/logs dist/afiliados/painel/logs 2>/dev/null
fi

# Copia pasta config completa
echo "⚙  Copiando arquivos de configuração..."
if [ -d "config" ]; then
    mkdir -p dist/config
    cp -r config/* dist/config/
fi

# Copia pasta images completa
echo "🖼  Copiando imagens..."
if [ -d "images" ]; then
    mkdir -p dist/images
    cp -r images/* dist/images/
fi

# Copia pasta política de privacidade completa
echo "📜 Copiando política de privacidade..."
if [ -d "politica-privacidade" ]; then
    mkdir -p dist/politica-privacidade
    cp -r politica-privacidade/* dist/politica-privacidade/
fi

# Copia pasta sorteio completa
echo "🎯 Copiando arquivos do sorteio..."
if [ -d "sorteio" ]; then
    mkdir -p dist/sorteio
    cp -r sorteio/* dist/sorteio/
fi

# Copia arquivos opcionais da raiz
echo "📋 Copiando arquivos opcionais..."
[ -f "manifest.json" ] && cp manifest.json dist/
[ -f "robots.txt" ] && cp robots.txt dist/
[ -f "favicon.ico" ] && cp favicon.ico dist/
[ -f ".env.example" ] && cp .env.example dist/

# Copia ou cria .htaccess
if [ -f ".htaccess" ]; then
    cp .htaccess dist/
elif [ ! -f "dist/.htaccess" ]; then
    echo "🔧 Criando .htaccess para produção..."
    cat > dist/.htaccess << 'EOF'
# Força HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Headers de segurança
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Cache para assets estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

# Compressão Gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Proteção de arquivos sensíveis
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Bloqueia acesso a arquivos de log
<FilesMatch "\.(log|sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>
EOF
fi

# Cria README para deploy
cat > dist/README.md << 'EOF'
# Deploy na Hostinger

## 📦 Conteúdo do Build

Esta pasta contém apenas os arquivos necessários para produção.

## 🚀 Como fazer deploy

1. Acesse o painel da Hostinger
2. Vá em "Gerenciador de Arquivos"
3. Navegue até public_html
4. Faça upload de todo o conteúdo desta pasta dist
5. Configure as variáveis de ambiente em config/env.php

## ⚙ Configuração necessária

### 1. Banco de Dados
- Crie o banco MySQL na Hostinger
- Importe o arquivo script.sql (não incluído no dist)
- Atualize as credenciais em config/env.php

### 2. Variáveis de Ambiente
Edite config/env.php e configure:
- DB_HOST
- DB_NAME
- DB_USER
- DB_PASS
- WHATSAPP_API_URL
- WHATSAPP_API_KEY
- INSTAGRAM_API_URL

### 3. Permissões
Execute via SSH ou File Manager:
bash
chmod 755 afiliados/cadastro
chmod 755 afiliados/painel
chmod 755 sorteio
chmod 644 *.php


## 📋 Checklist de Deploy

- [ ] Upload dos arquivos
- [ ] Banco de dados criado e importado
- [ ] config/env.php configurado
- [ ] Permissões ajustadas
- [ ] Testar página inicial
- [ ] Testar cadastro de participante
- [ ] Testar cadastro de afiliado
- [ ] Testar login de afiliado
- [ ] Verificar integração WhatsApp
- [ ] Verificar integração Instagram

## 🔒 Segurança

- HTTPS já configurado no .htaccess
- Headers de segurança habilitados
- Arquivos sensíveis protegidos
- Display errors desabilitado em produção

## 📞 Suporte

Em caso de problemas, verifique:
1. Logs de erro do PHP (painel Hostinger)
2. Console do navegador (F12)
3. Configurações do banco de dados
4. Permissões de arquivos
EOF

echo ""
echo "✅ Build concluído com sucesso!"
echo ""
echo "📊 Estatísticas:"
echo "   Total de arquivos: $(find dist -type f | wc -l)"
echo "   Tamanho total: $(du -sh dist | cut -f1)"
echo ""
echo "📦 Próximos passos:"
echo "   1. Revise o arquivo dist/config/env.php"
echo "   2. Faça upload do CONTEÚDO de dist/ para public_html na Hostinger"
echo "   3. Configure o banco de dados MySQL"
echo "   4. Importe o script.sql"
echo "   5. Leia o arquivo dist/README.md para mais instruções"
echo ""