/**
 * Migra la BD al esquema con hashtags (borra todos los datos).
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const raiz = path.join(__dirname, '..');
const esquema = path.join(raiz, 'base-datos', 'esquema.sql');
const host = process.env.BD_HOST || '127.0.0.1';
const usuario = process.env.BD_USUARIO || 'root';
const clave = process.env.BD_CLAVE || '';

const RUTAS_MYSQL = [
  'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe',
  'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
];

function buscarMysql() {
  for (const bin of ['mysql', 'mariadb']) {
    try { execSync(`"${bin}" --version`, { stdio: 'pipe', shell: true }); return bin; } catch { /* */ }
  }
  for (const ruta of RUTAS_MYSQL) {
    if (fs.existsSync(ruta)) return `"${ruta}"`;
  }
  return null;
}

const cliente = buscarMysql();
if (!cliente) {
  console.error('MySQL no detectado.');
  process.exit(1);
}

const args = clave ? `-h${host} -u${usuario} -p${clave}` : `-h${host} -u${usuario}`;

console.log('Recreando base de datos zona_grupos (se borran todos los datos)...\n');

try {
  execSync(`${cliente} ${args} -e "DROP DATABASE IF EXISTS zona_grupos;"`, { stdio: 'inherit', shell: true });
  execSync(`${cliente} ${args} < "${esquema}"`, { stdio: 'inherit', shell: true });
  console.log('\n✓ Base de datos recreada. Reinicia: npm start');
} catch {
  console.error('\n✗ Error. Verifica que MySQL esté corriendo: net start MySQL84');
  process.exit(1);
}
