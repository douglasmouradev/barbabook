-- BarbaBook - Multibeneficiário: várias barbearias e nails, cada um vê só seus dados
-- Execute após o schema principal (schema.sql)

USE barbabook;

-- Tabela de estabelecimentos (barbearia ou nails)
CREATE TABLE IF NOT EXISTS estabelecimentos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  tipo VARCHAR(20) NOT NULL COMMENT 'barbeiro ou nails',
  slug VARCHAR(60) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_estabelecimento_slug (slug),
  KEY idx_estabelecimento_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vincular admin ao estabelecimento (NULL = super-admin que cadastra estabelecimentos)
ALTER TABLE usuarios_admin
  ADD COLUMN estabelecimento_id INT UNSIGNED NULL DEFAULT NULL AFTER ativo,
  ADD KEY fk_admin_estabelecimento (estabelecimento_id);

ALTER TABLE usuarios_admin
  ADD CONSTRAINT fk_admin_estabelecimento
  FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos (id) ON DELETE SET NULL;

-- Serviços por estabelecimento
ALTER TABLE servicos
  ADD COLUMN estabelecimento_id INT UNSIGNED NULL DEFAULT NULL AFTER modalidade_id,
  ADD KEY fk_servicos_estabelecimento (estabelecimento_id);

ALTER TABLE servicos
  ADD CONSTRAINT fk_servicos_estabelecimento
  FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos (id) ON DELETE CASCADE;

-- Profissionais por estabelecimento
ALTER TABLE profissionais
  ADD COLUMN estabelecimento_id INT UNSIGNED NULL DEFAULT NULL AFTER modalidade_id,
  ADD KEY fk_profissionais_estabelecimento (estabelecimento_id);

ALTER TABLE profissionais
  ADD CONSTRAINT fk_profissionais_estabelecimento
  FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos (id) ON DELETE CASCADE;

-- Agendamentos por estabelecimento
ALTER TABLE agendamentos
  ADD COLUMN estabelecimento_id INT UNSIGNED NULL DEFAULT NULL AFTER modalidade_id,
  ADD KEY fk_agend_estabelecimento (estabelecimento_id);

ALTER TABLE agendamentos
  ADD CONSTRAINT fk_agend_estabelecimento
  FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos (id) ON DELETE CASCADE;

-- Migração: criar 2 estabelecimentos padrão e associar dados existentes
INSERT IGNORE INTO estabelecimentos (nome, tipo, slug, ativo) VALUES
('Barbearia Exemplo', 'barbeiro', 'barbearia-exemplo', 1),
('Nails Exemplo', 'nails', 'nails-exemplo', 1);

SET @id_barbeiro_estab = (SELECT id FROM estabelecimentos WHERE slug = 'barbearia-exemplo' AND tipo = 'barbeiro' LIMIT 1);
SET @id_nails_estab    = (SELECT id FROM estabelecimentos WHERE slug = 'nails-exemplo' AND tipo = 'nails' LIMIT 1);
SET @id_mod_barbeiro   = (SELECT id FROM modalidades WHERE slug = 'barbeiro' LIMIT 1);
SET @id_mod_nails      = (SELECT id FROM modalidades WHERE slug = 'nails' LIMIT 1);

UPDATE servicos SET estabelecimento_id = @id_barbeiro_estab WHERE modalidade_id = @id_mod_barbeiro AND estabelecimento_id IS NULL;
UPDATE servicos SET estabelecimento_id = @id_nails_estab WHERE modalidade_id = @id_mod_nails AND estabelecimento_id IS NULL;

UPDATE profissionais SET estabelecimento_id = @id_barbeiro_estab WHERE modalidade_id = @id_mod_barbeiro AND estabelecimento_id IS NULL;
UPDATE profissionais SET estabelecimento_id = @id_nails_estab WHERE modalidade_id = @id_mod_nails AND estabelecimento_id IS NULL;

UPDATE agendamentos SET estabelecimento_id = @id_barbeiro_estab WHERE modalidade_id = @id_mod_barbeiro AND estabelecimento_id IS NULL;
UPDATE agendamentos SET estabelecimento_id = @id_nails_estab WHERE modalidade_id = @id_mod_nails AND estabelecimento_id IS NULL;

-- Vincular admins existentes ao primeiro estabelecimento (barbeiro)
UPDATE usuarios_admin SET estabelecimento_id = @id_barbeiro_estab WHERE estabelecimento_id IS NULL AND @id_barbeiro_estab IS NOT NULL LIMIT 1;
