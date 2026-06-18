-- v6: correo del publicador (notificación y límite anti-spam)
-- Ejecutar: npm run actualizar-correo

USE nzoaqaxydg_zonagrupos_db;

SET @col = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grupos' AND COLUMN_NAME = 'correo_publicador'
);
SET @sql = IF(@col = 0,
  'ALTER TABLE grupos ADD COLUMN correo_publicador VARCHAR(254) NULL DEFAULT NULL AFTER clasificacion',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grupos' AND INDEX_NAME = 'idx_correo_creado'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE grupos ADD INDEX idx_correo_creado (correo_publicador, creado_en DESC)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
