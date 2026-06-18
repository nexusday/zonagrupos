/**
 * Añade columnas slug, pais y restriccion_pais sin borrar datos.
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const host = process.env.BD_HOST || '127.0.0.1';
const usuario = process.env.BD_USUARIO || 'root';
const clave = process.env.BD_CLAVE || '';

const RUTAS = [
  'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe',
];

function buscarMysql() {
  for (const b of ['mysql']) {
    try { execSync(`"${b}" --version`, { stdio: 'pipe', shell: true }); return b; }
    catch { /* */ }
  }
  for (const r of RUTAS) if (fs.existsSync(r)) return `"${r}"`;
  return null;
}

const cliente = buscarMysql();
if (!cliente) { console.error('MySQL no detectado'); process.exit(1); }

const args = clave ? `-h${host} -u${usuario} -p${clave}` : `-h${host} -u${usuario}`;

const sql = `
USE zona_grupos;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA='zona_grupos' AND TABLE_NAME='grupos' AND COLUMN_NAME='slug');
SET @sql = IF(@existe=0,
  'ALTER TABLE grupos
    ADD COLUMN slug VARCHAR(150) NULL AFTER nombre,
    ADD COLUMN pais_codigo VARCHAR(5) NOT NULL DEFAULT ''LAT'' AFTER plataforma,
    ADD COLUMN pais_nombre VARCHAR(80) NOT NULL DEFAULT ''Latinoamérica'' AFTER pais_codigo,
    ADD COLUMN restriccion_pais ENUM(''todos'',''solo_pais'') NOT NULL DEFAULT ''todos'' AFTER pais_nombre,
    ADD UNIQUE KEY uq_slug (slug)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE grupos SET slug = CONCAT('grupo-', id) WHERE slug IS NULL OR slug = '';
`;

const tmp = path.join(__dirname, '..', 'logs', '_tmp_actualizar.sql');
fs.mkdirSync(path.dirname(tmp), { recursive: true });
fs.writeFileSync(tmp, sql);

try {
  execSync(`${cliente} ${args} < "${tmp}"`, { stdio: 'inherit', shell: true });
  console.log('\n✓ Base de datos actualizada (v2).');
} catch {
  console.error('Error al actualizar BD');
  process.exit(1);
} finally {
  try { fs.unlinkSync(tmp); } catch { /* */ }
}
