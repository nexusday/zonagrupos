-- ZonaGrupos — Esquema limpio (sin datos por defecto)
-- Hosting: importar en nzoaqaxydg_zonagrupos_db (sin CREATE DATABASE)

USE nzoaqaxydg_zonagrupos_db;

CREATE TABLE IF NOT EXISTS grupos (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(120) NOT NULL,
  slug            VARCHAR(150) NULL,
  descripcion     VARCHAR(500) NOT NULL DEFAULT '',
  enlace          VARCHAR(500) NOT NULL,
  plataforma      ENUM('whatsapp','telegram','discord') NOT NULL DEFAULT 'whatsapp',
  pais_codigo     VARCHAR(5)   NOT NULL DEFAULT 'LAT',
  pais_nombre     VARCHAR(80)  NOT NULL DEFAULT 'Latinoamérica',
  restriccion_pais ENUM('todos','solo_pais') NOT NULL DEFAULT 'todos',
  likes           INT UNSIGNED NOT NULL DEFAULT 0,
  visitas         INT UNSIGNED NOT NULL DEFAULT 0,
  activo          TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enlace (enlace(255)),
  UNIQUE KEY uq_slug (slug),
  INDEX idx_plataforma (plataforma),
  INDEX idx_likes (likes DESC),
  INDEX idx_creado (creado_en DESC),
  FULLTEXT INDEX ft_busqueda (nombre, descripcion)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS etiquetas (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(30)  NOT NULL,
  usos      INT UNSIGNED NOT NULL DEFAULT 0,
  creado_en TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_etiqueta (nombre),
  INDEX idx_usos (usos DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grupo_etiquetas (
  grupo_id    INT UNSIGNED NOT NULL,
  etiqueta_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (grupo_id, etiqueta_id),
  INDEX idx_etiqueta (etiqueta_id),
  CONSTRAINT fk_ge_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ge_etiqueta FOREIGN KEY (etiqueta_id) REFERENCES etiquetas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS likes_registro (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id    INT UNSIGNED NOT NULL,
  huella      VARCHAR(64)  NOT NULL,
  creado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_like (grupo_id, huella),
  CONSTRAINT fk_like_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visitas_registro (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id  INT UNSIGNED NOT NULL,
  huella    VARCHAR(64)  NOT NULL,
  creado_en TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_visita_grupo_huella (grupo_id, huella, creado_en DESC),
  CONSTRAINT fk_visita_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
