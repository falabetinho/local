-- Script SQL para criar a tabela local_customadmin_wp_mapping
-- Tabela para armazenar mapeamentos entre itens do Moodle e WordPress
-- Execute este script no seu banco de dados Moodle

-- Nota: Substitua o prefixo 'mdl_' pelo prefixo correto do seu Moodle se for diferente

CREATE TABLE IF NOT EXISTS mdl_local_customadmin_wp_mapping (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    moodle_type VARCHAR(50) NOT NULL COMMENT 'Tipo de item do Moodle: category, course',
    moodle_id BIGINT(10) NOT NULL COMMENT 'ID do item do Moodle',
    wordpress_type VARCHAR(50) NOT NULL COMMENT 'Tipo de item do WordPress: term, post',
    wordpress_id BIGINT(10) NOT NULL COMMENT 'ID do item do WordPress',
    wordpress_taxonomy VARCHAR(100) DEFAULT NULL COMMENT 'Nome da taxonomia do WordPress (para terms)',
    wordpress_post_type VARCHAR(100) DEFAULT NULL COMMENT 'Tipo de post do WordPress (para posts)',
    sync_status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'Status da sincronização: pending, synced, error',
    last_synced BIGINT(10) DEFAULT 0 COMMENT 'Timestamp da última sincronização',
    sync_error TEXT DEFAULT NULL COMMENT 'Mensagem de erro se a sincronização falhou',
    timecreated BIGINT(10) NOT NULL DEFAULT 0,
    timemodified BIGINT(10) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY mdl_loccustowpma_mooite_uix (moodle_type, moodle_id),
    KEY mdl_loccustowpma_woritem_ix (wordpress_type, wordpress_id),
    KEY mdl_loccustowpma_synsta_ix (sync_status),
    KEY mdl_loccustowpma_mootyp_ix (moodle_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela para armazenar mapeamentos entre itens do Moodle e WordPress';

-- Verificar se a tabela foi criada
SELECT 'Tabela criada com sucesso!' AS resultado;

-- Verificar a estrutura da tabela
DESCRIBE mdl_local_customadmin_wp_mapping;
