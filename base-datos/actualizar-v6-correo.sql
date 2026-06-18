-- v6: correo del publicador (notificación y límite anti-spam)
USE nzoaqaxydg_zonagrupos_db;

ALTER TABLE grupos
  ADD COLUMN correo_publicador VARCHAR(254) NULL DEFAULT NULL AFTER clasificacion,
  ADD INDEX idx_correo_creado (correo_publicador, creado_en DESC);
