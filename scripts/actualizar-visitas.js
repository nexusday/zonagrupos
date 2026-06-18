/**
 * Ejecuta base-datos/actualizar-v4-visitas.sql (tabla visitas_registro)
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

let sql = fs.readFileSync(path.join(__dirname, '..', 'base-datos', 'actualizar-v4-visitas.sql'), 'utf8');
sql = sql.replace(/USE nzoaqaxydg_zonagrupos_db;/, `USE ${nombre};`);

const tmp = path.join(__dirname, '..', 'logs', '_tmp_visitas.sql');
fs.mkdirSync(path.dirname(tmp), { recursive: true });
fs.writeFileSync(tmp, sql);

const args = clave ? `-h${host} -u${usuario} -p${clave}` : `-h${host} -u${usuario}`;

try {
  execSync(`${cliente} ${args} < "${tmp}"`, { stdio: 'inherit', shell: true });
  console.log('\n✓ Tabla visitas_registro creada (v4).');
} catch {
  console.error('Error al actualizar BD');
  process.exit(1);
} finally {
  try { fs.unlinkSync(tmp); } catch { /* */ }
}
