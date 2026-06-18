-- Quita el pseudo-país LAT / Latinoamérica (grupos son globales, país = IP real al publicar)
UPDATE grupos
SET pais_codigo = '', pais_nombre = ''
WHERE pais_codigo = 'LAT'
   OR pais_nombre IN ('Latinoamérica', 'Latinoamerica');

ALTER TABLE grupos
  MODIFY pais_codigo VARCHAR(5) NOT NULL DEFAULT '',
  MODIFY pais_nombre VARCHAR(80) NOT NULL DEFAULT '';
