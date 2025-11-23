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
