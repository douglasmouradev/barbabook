-- BarbaBook - Schema MySQL 8
-- Domínio: tdesksolutions.com.br

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS barbabook
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE barbabook;

-- Modalidade: barbeiro | nails
CREATE TABLE modalidades (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(20) NOT NULL,
  nome VARCHAR(80) NOT NULL,
  descricao TEXT,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_modalidade_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Serviços por modalidade (barbeiro: corte, barba... | nails: unha, alongamento...)
CREATE TABLE servicos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  modalidade_id TINYINT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY fk_servicos_modalidade (modalidade_id),
  CONSTRAINT fk_servicos_modalidade FOREIGN KEY (modalidade_id) REFERENCES modalidades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profissionais (barbeiros / nail designers)
CREATE TABLE profissionais (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  modalidade_id TINYINT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY fk_profissionais_modalidade (modalidade_id),
  CONSTRAINT fk_profissionais_modalidade FOREIGN KEY (modalidade_id) REFERENCES modalidades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Horários disponíveis (slots) por profissional/dia
CREATE TABLE horarios_disponiveis (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  profissional_id INT UNSIGNED NOT NULL,
  data DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  PRIMARY KEY (id),
  KEY fk_horarios_profissional (profissional_id),
  KEY idx_data (data),
  CONSTRAINT fk_horarios_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agendamentos
CREATE TABLE agendamentos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  modalidade_id TINYINT UNSIGNED NOT NULL,
  profissional_id INT UNSIGNED NOT NULL,
  servico_id INT UNSIGNED NOT NULL,
  cliente_nome VARCHAR(120) NOT NULL,
  cliente_telefone VARCHAR(20) NOT NULL,
  cliente_email VARCHAR(120) NULL,
  data_agendamento DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  forma_pagamento VARCHAR(30) NULL,
  observacoes TEXT,
  status ENUM('pendente','confirmado','realizado','cancelado','no_show') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY fk_agend_modalidade (modalidade_id),
  KEY fk_agend_profissional (profissional_id),
  KEY fk_agend_servico (servico_id),
  KEY idx_data_agendamento (data_agendamento),
  CONSTRAINT fk_agend_modalidade FOREIGN KEY (modalidade_id) REFERENCES modalidades (id),
  CONSTRAINT fk_agend_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais (id),
  CONSTRAINT fk_agend_servico FOREIGN KEY (servico_id) REFERENCES servicos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuários administradores do sistema (login)
CREATE TABLE usuarios_admin (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Dados iniciais
INSERT INTO modalidades (slug, nome, descricao) VALUES
('barbeiro', 'Barbeiro', 'Cortes, barba e cuidados masculinos'),
('nails', 'Nails', 'Unhas, alongamento e nail design');

SET @id_barbeiro = (SELECT id FROM modalidades WHERE slug = 'barbeiro' LIMIT 1);
SET @id_nails   = (SELECT id FROM modalidades WHERE slug = 'nails' LIMIT 1);

INSERT INTO servicos (modalidade_id, nome, duracao_minutos, preco) VALUES
(@id_barbeiro, 'Corte masculino', 30, 35.00),
(@id_barbeiro, 'Barba', 20, 25.00),
(@id_barbeiro, 'Corte + Barba', 50, 55.00),
(@id_barbeiro, 'Sobrancelha', 15, 15.00),
(@id_nails, 'Manicure simples', 45, 30.00),
(@id_nails, 'Pedicure', 50, 40.00),
(@id_nails, 'Alongamento de unhas', 90, 80.00),
(@id_nails, 'Nail design / esmaltação em gel', 60, 55.00);

INSERT INTO profissionais (modalidade_id, nome) VALUES
(@id_barbeiro, 'João Barbeiro'),
(@id_barbeiro, 'Carlos Estilo'),
(@id_nails, 'Maria Nails'),
(@id_nails, 'Ana Design');
