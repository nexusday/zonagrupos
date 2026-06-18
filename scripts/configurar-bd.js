/**
 * Script para crear la base de datos MySQL de ZonaGrupos
 * Uso: node scripts/configurar-bd.js
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const esquema = path.join(__dirname, '..', 'base-datos', 'esquema.sql');
const host = process.env.BD_HOST || '127.0.0.1';
const usuario = process.env.BD_USUARIO || 'root';
const clave = process.env.BD_CLAVE || '';

const RUTAS_MYSQL_WINDOWS = [
  'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe',
  'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
  'C:\\Program Files\\MariaDB 11.4\\bin\\mysql.exe',
  'C:\\xampp\\mysql\\bin\\mysql.exe',
  'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysql.exe',
];

function buscarClienteMysql() {
  const candidatos = ['mysql', 'mariadb'];

  for (const bin of candidatos) {
    try {
      execSync(`"${bin}" --version`, { stdio: 'pipe', shell: true });
      return bin;
    } catch { /* siguiente */ }
  }

  for (const ruta of RUTAS_MYSQL_WINDOWS) {
    if (fs.existsSync(ruta)) return ruta;
  }

  return null;
}

function comillas(ruta) {
  return ruta.includes(' ') ? `"${ruta}"` : ruta;
}

console.log('Configurando base de datos ZonaGrupos...\n');

if (!fs.existsSync(esquema)) {
  console.error('No se encontró base-datos/esquema.sql');
  process.exit(1);
}

const cliente = buscarClienteMysql();

if (!cliente) {
  console.error('MySQL/MariaDB no detectado.\n');
  console.error('Opciones:');
  console.error('  1. Instalar MySQL: winget install Oracle.MySQL');
  console.error('  2. O XAMPP: https://www.apachefriends.org/');
  console.error('  3. Luego ejecuta: npm run setup-db');
  console.error('\nManual (PowerShell como administrador):');
  console.error('  & "C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe" -u root -p < base-datos\\esquema.sql');
  process.exit(1);
}

console.log(`Cliente detectado: ${cliente}\n`);

try {
  const argsConexion = clave
    ? `-h${host} -u${usuario} -p${clave}`
    : `-h${host} -u${usuario}`;

  const comando = `${comillas(cliente)} ${argsConexion} < "${esquema}"`;

  execSync(comando, { stdio: 'inherit', shell: true });

  console.log('\n✓ Base de datos configurada correctamente.');
  console.log('  Reinicia el servidor: npm start');
} catch {
  console.error('\n✗ Error al configurar la base de datos.\n');
  console.error('Verifica que el servicio MySQL esté corriendo:');
  console.error('  net start MySQL84');
  console.error('\nSi no existe el servicio, ejecuta como administrador:');
  console.error('  npm run iniciar-mysql');
  console.error('\nCredenciales (variables de entorno opcionales): BD_HOST, BD_USUARIO, BD_CLAVE');
  process.exit(1);
}
