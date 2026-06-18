-- ZonaGrupos v5 — UTF-8 completo (emojis) + clasificación de contenido
-- Ejecutar: npm run actualizar-clasificacion

USE nzoaqaxydg_zonagrupos_db;

ALTER TABLE grupos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE etiquetas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @col = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grupos' AND COLUMN_NAME = 'clasificacion'
);
SET @sql = IF(@col = 0,
  "ALTER TABLE grupos ADD COLUMN clasificacion ENUM('normal','adulto') NOT NULL DEFAULT 'normal' AFTER restriccion_pais",
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grupos' AND INDEX_NAME = 'idx_clasificacion'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE grupos ADD INDEX idx_clasificacion (clasificacion)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
