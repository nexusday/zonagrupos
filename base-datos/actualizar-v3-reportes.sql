-- Reportes de usuarios + soporte panel admin
-- Ejecutar: npm run actualizar-bd  o importar en phpMyAdmin

USE nzoaqaxydg_zonagrupos_db;

CREATE TABLE IF NOT EXISTS reportes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id    INT UNSIGNED NOT NULL,
  motivo      ENUM('spam','inapropiado','enlace_roto','estafa','otro') NOT NULL DEFAULT 'otro',
  detalle     VARCHAR(500) NOT NULL DEFAULT '',
  huella      VARCHAR(64)  NOT NULL,
  estado      ENUM('pendiente','revisado','descartado') NOT NULL DEFAULT 'pendiente',
  creado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_estado (estado, creado_en DESC),
  INDEX idx_grupo (grupo_id),
  CONSTRAINT fk_reporte_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
