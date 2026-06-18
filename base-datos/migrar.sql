-- Migración: categorías → hashtags (ejecutar si ya tenías la BD antigua)
-- npm run migrar-db

USE zona_grupos;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS grupo_etiquetas;
DROP TABLE IF EXISTS etiquetas;
DROP TABLE IF EXISTS likes_registro;
DROP TABLE IF EXISTS grupos;
DROP TABLE IF EXISTS categorias;

SET FOREIGN_KEY_CHECKS = 1;

SOURCE esquema.sql;
