-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS `infancia-conectada` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar o banco de dados
USE `infancia-conectada`;

-- Criar tabela afiliados com todos os campos
CREATE TABLE IF NOT EXISTS `afiliados` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT 'ID único do afiliado',
    `code` VARCHAR(8) NOT NULL UNIQUE COMMENT 'Código de referência único do afiliado',
    `nome` VARCHAR(100) NOT NULL COMMENT 'Nome completo do afiliado',
    `email` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email do afiliado',
    `telefone` VARCHAR(15) NOT NULL UNIQUE COMMENT 'Telefone do afiliado',
    `senha` VARCHAR(255) NOT NULL COMMENT 'Senha criptografada com bcrypt',
    `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora do cadastro',
    `ativo` BOOLEAN DEFAULT TRUE COMMENT 'Status do afiliado',
    INDEX `idx_code` (`code`) COMMENT 'Índice para busca rápida por código',
    INDEX `idx_email` (`email`) COMMENT 'Índice para busca rápida por email'
);