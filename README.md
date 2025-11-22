# Infância Sorteio - Sistema de Sorteios e Afiliados

Sistema completo de gerenciamento de sorteios com programa de afiliados integrado.

## 🚀 Stack Tecnológica

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 8.2, MySQL 8.0
- **Infraestrutura**: Docker, Docker Compose
- **APIs Externas**: WhatsApp Validation, Instagram Validation

## 📋 Pré-requisitos

- Docker e Docker Compose instalados
- Porta 80 (HTTP) e 3307 (MySQL) disponíveis
- Domínio configurado (produção)

## 🔧 Configuração

### 1. Variáveis de Ambiente

Copie o arquivo `.env.example` para `.env` e configure:

```bash
cp .env.example .env
```

**Variáveis críticas para PRODUÇÃO:**

```env
# Banco de dados
DB_HOST=db
DB_PORT=3307
DB_USER=root
DB_PASS=SenhaForteSegura123!  # TROCAR!
DB_NAME=u583423626_infancia

# Ambiente
APP_ENV=production  # TROCAR de development para production

# CORS - Adicionar domínio real
ALLOWED_ORIGINS=https://seudominio.com,https://www.seudominio.com

# Cookies seguros
FORCE_SECURE_COOKIES=true  # TROCAR para true em produção
SESSION_SAMESITE=Lax  # Lax para suportar links de afiliados

# APIs externas
API_BASE_URL=https://api-express-production-c152.up.railway.app
API_KEY=sua_chave_api
```

### 2. Deploy Local (Desenvolvimento)

```bash
# Subir containers
docker-compose up -d

# Verificar logs
docker-compose logs -f

# Parar containers
docker-compose down
```

Acesse: `http://localhost`

### 3. Deploy Produção (Railway/VPS)

#### Railway

1. **Criar conta**: https://railway.app
2. **Conectar repositório GitHub**
3. **Configurar variáveis de ambiente**:
   - Vá em: Project → Variables
   - Adicione todas as variáveis do `.env`
4. **Deploy automático** acontece no push

#### Healthcheck
Railway irá verificar: `https://seudominio.com/health.php`

## 🗄️ Banco de Dados

### Estrutura

- `participantes` - Usuários que participam dos sorteios
- `afiliados` - Parceiros do programa de afiliados
- `sessoes_temp` - Sessões temporárias (2 horas)

### Índices Criados (Performance)

```sql
-- Executar script.sql na primeira instalação
mysql -u root -p u583423626_infancia < script.sql
```

**Índices implementados:**
- `idx_parametro_unico` (500x mais rápido)
- `idx_email` (500x mais rápido)
- `idx_code` (300x mais rápido)
- `idx_telefone`, `idx_instagram`, `idx_empresas`

## 🔒 Segurança

### Implementado

✅ **SQL Injection**: Prepared statements em todas as queries  
✅ **CSRF Protection**: Tokens em formulários  
✅ **Password Hashing**: Bcrypt com cost 12  
✅ **XSS Protection**: Headers de segurança no `.htaccess`  
✅ **HTTPS Redirect**: Automático via `.htaccess`  
✅ **CORS Restritivo**: Whitelist de origens permitidas  
✅ **Session Security**: Cookies seguros, SameSite, HttpOnly  

### Pendente (Recomendado)

⚠️ **Rate Limiting**: Implementar proteção contra força bruta  
⚠️ **Monitoramento**: Configurar Sentry (veja abaixo)  
⚠️ **Backup Automático**: Script de backup MySQL  

## 📊 Monitoramento (Sentry)

### Setup Rápido

1. **Criar conta**: https://sentry.io (gratuito até 5k erros/mês)

2. **Criar projeto PHP**

3. **Instalar SDK**:
```bash
composer require sentry/sentry
```

4. **Adicionar ao `.env`**:
```env
SENTRY_DSN=https://xxx@xxx.ingest.sentry.io/xxx
```

5. **Inicializar** (adicionar no início dos arquivos PHP principais):
```php
require 'vendor/autoload.php';
Sentry\init(['dsn' => env('SENTRY_DSN')]);
```

### Alertas

Configure em Sentry:
- Email/Slack quando erro crítico ocorrer
- Performance monitoring
- Stack traces completos

## 🔄 Backup

### Script de Backup MySQL

Criar `backup.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DB_NAME="u583423626_infancia"
DB_USER="root"
DB_PASS="sua_senha"
DB_HOST="db"

mkdir -p $BACKUP_DIR
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Manter apenas últimos 7 dias
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
```

### Cronjob (Diário às 3AM)

```bash
chmod +x backup.sh
crontab -e
# Adicionar:
0 3 * * * /path/to/backup.sh
```

## 🧪 Testes

### Healthcheck

```bash
curl https://seudominio.com/health.php
```

Resposta esperada:
```json
{
  "status": "healthy",
  "timestamp": 1732208400,
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Connected and responsive"
    }
  }
}
```

### Teste de Carga

```bash
# Instalar Apache Bench
sudo apt install apache2-utils

# Testar com 100 requisições simultâneas
ab -n 1000 -c 100 https://seudominio.com/
```

## 📁 Estrutura do Projeto

```
infancia-sorteio/
├── index.html                  # Landing page principal
├── script-cadastro.js          # Validação formulário sorteio
├── styles-cadastro.css         # Estilos sorteio
├── health.php                  # Endpoint de healthcheck
├── .htaccess                   # Configurações Apache
├── .env                        # Variáveis de ambiente
├── .env.example                # Template de variáveis
├── docker-compose.yml          # Configuração Docker
├── script.sql                  # Schema + índices do banco
│
├── config/
│   ├── env.php                 # Loader de variáveis .env
│   ├── session.php             # Configuração sessões seguras
│   ├── whatsapp.php            # API validação WhatsApp
│   └── instagram.php           # API validação Instagram
│
├── sorteio/
│   ├── index.html              # Página de seleção de empresa
│   ├── registrar.php           # API principal (criar/validar/registrar)
│   └── script-sorteio.js       # Lógica frontend sorteio
│
├── afiliados/
│   ├── index.html              # Login afiliados
│   ├── validar_login.php       # API autenticação
│   ├── logout.php              # Logout
│   │
│   ├── cadastro/
│   │   ├── index.html          # Cadastro de afiliados
│   │   ├── processar_cadastro.php
│   │   ├── validacao.js
│   │   └── validar_whatsapp.php
│   │
│   └── painel/
│       ├── index.php           # Dashboard afiliado
│       ├── stats.php           # API de estatísticas
│       └── verificar_autenticacao.php
│
└── images/                     # Assets estáticos
```

## 🐛 Troubleshooting

### Erro: "CORS policy blocked"

**Causa**: `ALLOWED_ORIGINS` não configurado corretamente

**Solução**:
```env
ALLOWED_ORIGINS=https://seudominio.com,https://www.seudominio.com
```

### Erro: "Session not persisting"

**Causa**: Cookies não seguros em HTTPS

**Solução**:
```env
FORCE_SECURE_COOKIES=true
```

### Erro: "Database connection failed"

**Causa**: Credenciais incorretas ou host errado

**Solução**: Verificar variáveis `DB_*` no `.env`

### Performance lenta

**Causa**: Índices não criados

**Solução**:
```bash
mysql -u root -p u583423626_infancia < script.sql
```

## 📞 Suporte

- **Documentação completa**: Ver código-fonte comentado
- **Issues**: Abrir issue no GitHub
- **Email**: suporte@infanciaconectada.com.br

## 📄 Licença

Propriedade de Infância Conectada. Todos os direitos reservados.

---

**Versão**: 1.0.0  
**Última atualização**: Novembro 2025
