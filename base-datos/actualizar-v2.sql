-- Actualización v2: páginas por grupo y restricción por país
USE nzoaqaxydg_zonagrupos_db;

ALTER TABLE grupos
  ADD COLUMN IF NOT EXISTS slug VARCHAR(150) NULL AFTER nombre,
  ADD COLUMN IF NOT EXISTS pais_codigo VARCHAR(5) NOT NULL DEFAULT 'LAT' AFTER plataforma,
  ADD COLUMN IF NOT EXISTS pais_nombre VARCHAR(80) NOT NULL DEFAULT 'Latinoamérica' AFTER pais_codigo,
  ADD COLUMN IF NOT EXISTS restriccion_pais ENUM('todos','solo_pais') NOT NULL DEFAULT 'todos' AFTER pais_nombre;

-- MySQL 8.0 no tiene IF NOT EXISTS en ADD COLUMN — usar procedimiento alternativo en script Node
