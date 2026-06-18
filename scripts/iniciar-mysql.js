/**
 * Inicializa e inicia el servicio MySQL en Windows (requiere admin)
 * Uso: npm run iniciar-mysql
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const MYSQL_BASE = 'C:\\Program Files\\MySQL\\MySQL Server 8.4';
const MYSQL_BIN = path.join(MYSQL_BASE, 'bin');
const DATA_DIR = 'C:\\ProgramData\\MySQL\\MySQL Server 8.4\\Data';
const INI_PATH = path.join(MYSQL_BASE, 'my.ini');
const SERVICIO = 'MySQL84';

function ejecutar(cmd, opciones = {}) {
  console.log(`> ${cmd}\n`);
  execSync(cmd, { stdio: 'inherit', shell: true, ...opciones });
}

function existe(ruta) {
  return fs.existsSync(ruta);
}

console.log('=== Configuración MySQL para ZonaGrupos ===\n');

if (!existe(path.join(MYSQL_BIN, 'mysqld.exe'))) {
  console.error('No se encontró MySQL en:', MYSQL_BASE);
  console.error('Instálalo con: winget install Oracle.MySQL');
  process.exit(1);
}

if (!existe(DATA_DIR)) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
}

if (!existe(INI_PATH)) {
  const ini = `[mysqld]\r\nbasedir=${MYSQL_BASE}\r\ndatadir=${DATA_DIR}\r\nport=3306\r\n`;
  fs.writeFileSync(INI_PATH, ini);
  console.log('Creado my.ini\n');
}

const yaInicializado = existe(path.join(DATA_DIR, 'mysql'));

if (!yaInicializado) {
  console.log('Inicializando base de datos (primera vez, sin contraseña root)...\n');
  try {
    ejecutar(`"${path.join(MYSQL_BIN, 'mysqld.exe')}" --defaults-file="${INI_PATH}" --initialize-insecure --console`);
  } catch {
    console.error('\nError al inicializar. ¿Ejecutaste PowerShell como Administrador?');
    process.exit(1);
  }
} else {
  console.log('Datos MySQL ya existen, omitiendo inicialización.\n');
}

try {
  ejecutar(`sc query ${SERVICIO}`, { stdio: 'pipe' });
} catch {
  console.log(`Instalando servicio ${SERVICIO}...\n`);
  try {
    ejecutar(`"${path.join(MYSQL_BIN, 'mysqld.exe')}" --install ${SERVICIO} --defaults-file="${INI_PATH}"`);
  } catch {
    console.error('\nNo se pudo instalar el servicio. Ejecuta como Administrador.');
    process.exit(1);
  }
}

try {
  ejecutar(`net start ${SERVICIO}`);
} catch {
  console.log('\nEl servicio ya podría estar corriendo o necesita permisos de admin.');
}

console.log('\n✓ MySQL listo. Ahora ejecuta: npm run setup-db\n');
