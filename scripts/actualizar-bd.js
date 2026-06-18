/**
 * Aplica todas las migraciones de la BD en orden (v2 → v6).
 * Usa config.env + config.env.local (misma BD que npm start).
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

function cargarEnv(archivo) {
  const p = path.join(__dirname, '..', archivo);
  if (!fs.existsSync(p)) return;
  fs.readFileSync(p, 'utf8').split('\n').forEach((linea) => {
    const t = linea.trim();
    if (!t || t.startsWith('#') || !t.includes('=')) return;
    const [k, ...v] = t.split('=');
    process.env[k.trim()] = v.join('=').trim();
  });
}

cargarEnv('config.env');
cargarEnv('config.env.local');

const host = process.env.BD_HOST || '127.0.0.1';
const usuario = process.env.BD_USUARIO || 'root';
const clave = process.env.BD_CLAVE || '';
const nombre = process.env.BD_NOMBRE || 'zona_grupos';

const RUTAS = ['C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe'];

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

function ejecutarSql(contenido, etiqueta) {
  const tmp = path.join(__dirname, '..', 'logs', `_tmp_${etiqueta}.sql`);
  fs.mkdirSync(path.dirname(tmp), { recursive: true });
  fs.writeFileSync(tmp, contenido, 'utf8');
  try {
    execSync(`${cliente} ${args} < "${tmp}"`, { stdio: 'inherit', shell: true });
    console.log(`✓ ${etiqueta}`);
  } finally {
    try { fs.unlinkSync(tmp); } catch { /* */ }
  }
}

const sqlV2 = `
USE ${nombre};

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA='${nombre}' AND TABLE_NAME='grupos' AND COLUMN_NAME='slug');
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

console.log(`Actualizando BD "${nombre}" en ${host}...\n`);

try {
  ejecutarSql(sqlV2, 'v2 slug y país');

  const pasos = [
    ['actualizar-reportes.js', 'v3 reportes'],
    ['actualizar-visitas.js', 'v4 visitas'],
    ['actualizar-clasificacion.js', 'v5 clasificación'],
    ['actualizar-correo.js', 'v6 correo publicador'],
    ['actualizar-correos-contacto.js', 'v7 lista de correos'],
  ];

  for (const [script, etiqueta] of pasos) {
    console.log(`\n→ ${etiqueta}...`);
    execSync(`node "${path.join(__dirname, script)}"`, { stdio: 'inherit', shell: true });
  }

  console.log('\n✓ Base de datos actualizada (todas las migraciones).');
} catch {
  console.error('\nError al actualizar BD');
  process.exit(1);
}
