-- Registro de visitas por huella (evita inflar al recargar la página del grupo)
USE nzoaqaxydg_zonagrupos_db;

CREATE TABLE IF NOT EXISTS visitas_registro (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id  INT UNSIGNED NOT NULL,
  huella    VARCHAR(64)  NOT NULL,
  creado_en TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_visita_grupo_huella (grupo_id, huella, creado_en DESC),
  CONSTRAINT fk_visita_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
