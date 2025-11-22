-- 1. Criar tabela de afiliados
CREATE TABLE IF NOT EXISTS afiliados (
	id INT AUTO_INCREMENT PRIMARY KEY,
	nome VARCHAR(100) NOT NULL,
	email VARCHAR(100) NOT NULL UNIQUE,
	telefone VARCHAR(20) DEFAULT NULL,
	senha VARCHAR(255) NOT NULL,
	code VARCHAR(32) NOT NULL UNIQUE,
	ultimo_login DATETIME DEFAULT NULL,
	criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_telefone (telefone),
	INDEX idx_email (email),
	INDEX idx_code (code),
	INDEX idx_ultimo_login (ultimo_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Criar tabela de participantes
CREATE TABLE IF NOT EXISTS participantes (
	id INT AUTO_INCREMENT PRIMARY KEY,
	nome VARCHAR(100) NOT NULL,
	telefone VARCHAR(20) NOT NULL,
  instagram VARCHAR(31) NOT NULL UNIQUE,
	avatar_url VARCHAR(255) DEFAULT NULL,
	e1 TINYINT(1) DEFAULT 0,
	e2 TINYINT(1) DEFAULT 0,
	e3 TINYINT(1) DEFAULT 0,
	e4 TINYINT(1) DEFAULT 0,
	criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	parametro_unico VARCHAR(255) DEFAULT NULL,
	INDEX idx_telefone (telefone),
	INDEX idx_parametro_unico (parametro_unico),
	INDEX idx_instagram (instagram),
	INDEX idx_empresas (e1, e2, e3, e4),
	INDEX idx_criado_em (criado_em),
	UNIQUE KEY uk_telefone_instagram (telefone, instagram)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Criar tabela de sessoes_temp (se não existir via código PHP)
CREATE TABLE IF NOT EXISTS sessoes_temp (
	id VARCHAR(50) PRIMARY KEY,
	nome VARCHAR(100) NOT NULL,
	telefone VARCHAR(20) NOT NULL,
	instagram VARCHAR(31) NOT NULL,
	parametro_unico VARCHAR(255) DEFAULT NULL,
	expiracao DATETIME NOT NULL,
	criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_telefone (telefone),
	INDEX idx_expiracao (expiracao),
	INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nota: instagram já é UNIQUE globalmente (não permite @ duplicado em nenhum telefone)
-- Nota: uk_telefone_instagram permite mesmo telefone com diferentes @ (família compartilhando telefone)