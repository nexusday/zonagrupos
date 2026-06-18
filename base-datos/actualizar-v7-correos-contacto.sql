-- v7: lista de correos de publicadores
USE nzoaqaxydg_zonagrupos_db;

CREATE TABLE IF NOT EXISTS correos_contacto (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  correo              VARCHAR(254) NOT NULL,
  grupos_publicados   INT UNSIGNED NOT NULL DEFAULT 1,
  ultimo_grupo_nombre VARCHAR(120) NULL DEFAULT NULL,
  creado_en           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  actualizado_en      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_correo (correo),
  INDEX idx_actualizado (actualizado_en DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO correos_contacto (correo, grupos_publicados, ultimo_grupo_nombre, creado_en, actualizado_en)
SELECT
  g.correo_publicador,
  COUNT(*) AS total,
  (
    SELECT g2.nombre FROM grupos g2
    WHERE g2.correo_publicador = g.correo_publicador
    ORDER BY g2.creado_en DESC
    LIMIT 1
  ) AS ultimo_nombre,
  MIN(g.creado_en),
  MAX(g.creado_en)
FROM grupos g
WHERE g.correo_publicador IS NOT NULL
  AND g.correo_publicador != ''
GROUP BY g.correo_publicador
ON DUPLICATE KEY UPDATE
  grupos_publicados = VALUES(grupos_publicados),
  ultimo_grupo_nombre = VALUES(ultimo_grupo_nombre),
  actualizado_en = VALUES(actualizado_en);
