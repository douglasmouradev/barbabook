-- Adiciona coluna forma_pagamento na tabela agendamentos (se ainda não existir)
-- Execute: mysql -u root -p barbabook < database/alter_agendamentos_forma_pagamento.sql

USE barbabook;

-- MySQL não tem IF NOT EXISTS para coluna; rode uma vez. Se der "Duplicate column", ignore.
ALTER TABLE agendamentos
  ADD COLUMN forma_pagamento VARCHAR(30) NULL AFTER hora_fim;
