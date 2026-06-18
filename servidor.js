/**
 * ZonaGrupos — Servidor de desarrollo
 * Sirve el frontend estático y ejecuta la API PHP con MySQL.
 */
const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn, execSync } = require('child_process');
const { URL } = require('url');

const RAIZ = __dirname;
const CARPETA_PUBLICA = path.join(RAIZ, 'public');
const CARPETA_API = path.join(RAIZ, 'api');

const RUTAS_PHP_WINDOWS = [
  'C:\\Users\\Administrator\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe',
  'C:\\php\\php.exe',
  'C:\\xampp\\php\\php.exe',
];

const RUTAS_MYSQL_WINDOWS = [
  'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe',
  'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
];

const TIPOS_MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css':  'text/css; charset=utf-8',
  '.js':   'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png':  'image/png',
  '.jpg':  'image/jpeg',
  '.svg':  'image/svg+xml',
  '.ico':  'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
};

let rutaPhp = null;
let mysqlOk = false;
let PUERTO = 4750;
const CARPETA_LOGS = path.join(RAIZ, 'logs');

function escribirLogServidor(nivel, mensaje, datos = null) {
  try {
    if (!fs.existsSync(CARPETA_LOGS)) fs.mkdirSync(CARPETA_LOGS, { recursive: true });
    const fecha = new Date().toISOString().slice(0, 10);
    const hora = new Date().toISOString();
    const extra = datos ? ` | ${JSON.stringify(datos)}` : '';
    const linea = `[${hora}] [${nivel}] ${mensaje}${extra}\n`;
    fs.appendFileSync(path.join(CARPETA_LOGS, `servidor-${fecha}.log`), linea);
  } catch { /* ignorar errores de log */ }
  if (nivel === 'error') console.error(`[servidor] ${mensaje}`, datos || '');
}

function cargarConfigEnv() {
  const archivos = [
    path.join(RAIZ, 'config.env'),
    path.join(RAIZ, 'config.env.local'),
  ];

  archivos.forEach((configEnv) => {
    if (!fs.existsSync(configEnv)) return;

    fs.readFileSync(configEnv, 'utf8').split('\n').forEach((linea) => {
      const t = linea.trim();
      if (!t || t.startsWith('#') || !t.includes('=')) return;
      const [k, ...v] = t.split('=');
      const clave = k.trim();
      const valor = v.join('=').trim();
      process.env[clave] = valor;
    });
  });

  if (process.env.PUERTO) PUERTO = parseInt(process.env.PUERTO, 10) || PUERTO;
}

function comillas(ruta) {
  return ruta.includes(' ') ? `"${ruta}"` : ruta;
}

function detectarPhp() {
  for (const binario of ['php', 'php8', 'php83']) {
    try {
      execSync(`${comillas(binario)} -v`, { stdio: 'pipe', shell: true });
      return binario;
    } catch { /* siguiente */ }
  }

  for (const ruta of RUTAS_PHP_WINDOWS) {
    if (fs.existsSync(ruta)) return ruta;
  }

  try {
    const winget = path.join(process.env.LOCALAPPDATA || '', 'Microsoft', 'WinGet', 'Packages');
    if (fs.existsSync(winget)) {
      for (const carpeta of fs.readdirSync(winget)) {
        if (!carpeta.toLowerCase().includes('php.php')) continue;
        const ejecutable = path.join(winget, carpeta, 'php.exe');
        if (fs.existsSync(ejecutable)) return ejecutable;
      }
    }
  } catch { /* ignorar */ }

  return null;
}

function detectarMysql() {
  for (const bin of ['mysql', 'mariadb']) {
    try {
      execSync(`${comillas(bin)} --version`, { stdio: 'pipe', shell: true });
      return bin;
    } catch { /* siguiente */ }
  }
  for (const ruta of RUTAS_MYSQL_WINDOWS) {
    if (fs.existsSync(ruta)) return ruta;
  }
  return null;
}

function verificarMysql() {
  if (!rutaPhp) return false;

  const script = path.join(RAIZ, 'scripts', 'verificar-bd.php');
  if (!fs.existsSync(script)) return false;

  try {
    const salida = execSync(`"${rutaPhp}" -f "${script}"`, {
      env: process.env,
      stdio: 'pipe',
      shell: true,
      windowsHide: true,
    });
    return salida.toString().trim() === 'OK';
  } catch (err) {
    const detalle = err.stderr?.toString().trim() || err.message;
    if (detalle) escribirLogServidor('error', 'MySQL', detalle.slice(0, 300));
    return false;
  }
}

function ejecutarPhp(archivoPhp, req, res) {
  return new Promise((resolve) => {
    let cuerpoEntrada = '';
    req.on('data', (chunk) => { cuerpoEntrada += chunk; });
    req.on('end', () => {
      const variables = {
        ...process.env,
        REQUEST_METHOD: req.method,
        QUERY_STRING: new URL(req.url, `http://localhost:${PUERTO}`).searchParams.toString(),
        CONTENT_TYPE: req.headers['content-type'] || '',
        HTTP_ORIGIN: req.headers.origin || '',
        HTTP_USER_AGENT: req.headers['user-agent'] || '',
        REMOTE_ADDR: req.socket.remoteAddress || '127.0.0.1',
        JSON_CUERPO: cuerpoEntrada,
      };

      const proceso = spawn(rutaPhp, ['-f', archivoPhp], {
        env: variables,
        cwd: RAIZ,
        stdio: ['ignore', 'pipe', 'pipe'],
        windowsHide: true,
      });

      let salida = '';
      let errores = '';

      proceso.stdout.on('data', (d) => { salida += d; });
      proceso.stderr.on('data', (d) => { errores += d; });

      proceso.on('close', () => {
        const salidaLimpia = salida.trim();
        const pareceJson = salidaLimpia.startsWith('{') || salidaLimpia.startsWith('[');

        if (!pareceJson) {
          escribirLogServidor('error', 'PHP sin JSON válido', {
            archivo: path.basename(archivoPhp),
            stderr: errores.trim().slice(0, 200),
            stdout: salidaLimpia.slice(0, 200),
          });
          res.writeHead(500, { 'Content-Type': 'application/json; charset=utf-8' });
          res.end(JSON.stringify({
            exito: false,
            mensaje: 'Error en la API PHP.',
            detalle: errores.trim() || salidaLimpia.slice(0, 300),
          }));
        } else {
          res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
          res.end(salidaLimpia);
        }
        resolve();
      });

      proceso.on('error', () => {
        res.writeHead(500, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ exito: false, mensaje: 'No se pudo ejecutar PHP.' }));
        resolve();
      });
    });
  });
}

function servirEstatico(rutaArchivo, res) {
  const extension = path.extname(rutaArchivo).toLowerCase();
  const tipo = TIPOS_MIME[extension] || 'application/octet-stream';

  fs.readFile(rutaArchivo, (err, contenido) => {
    if (err) {
      res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
      res.end('No encontrado');
      return;
    }
    res.writeHead(200, { 'Content-Type': tipo });
    res.end(contenido);
  });
}

function manejarSolicitud(req, res) {
  const urlParsed = new URL(req.url, `http://localhost:${PUERTO}`);
  let ruta = decodeURIComponent(urlParsed.pathname);

  escribirLogServidor('info', `${req.method} ${ruta}`);

  if (ruta.startsWith('/api/')) {
    if (!rutaPhp || !mysqlOk) {
      res.writeHead(503, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify({
        exito: false,
        mensaje: 'MySQL o PHP no disponibles. Verifica config.env y que MySQL esté activo.',
      }));
      return;
    }

    const nombreArchivo = ruta.replace('/api/', '');
    const archivoPhp = path.join(CARPETA_API, nombreArchivo);

    if (!archivoPhp.startsWith(CARPETA_API) || !fs.existsSync(archivoPhp)) {
      res.writeHead(404, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify({ exito: false, mensaje: 'Endpoint no encontrado' }));
      return;
    }

    ejecutarPhp(archivoPhp, req, res);
    return;
  }

  // Página individual de grupo (SEO): /grupo/mi-grupo-123
  if (ruta.startsWith('/grupo/') && ruta !== '/grupo/') {
    servirEstatico(path.join(CARPETA_PUBLICA, 'grupo.html'), res);
    return;
  }

  if (ruta === '/') ruta = '/index.html';

  const archivo = path.join(CARPETA_PUBLICA, ruta);
  if (!archivo.startsWith(CARPETA_PUBLICA)) {
    res.writeHead(403);
    res.end('Prohibido');
    return;
  }

  if (fs.existsSync(archivo) && fs.statSync(archivo).isFile()) {
    servirEstatico(archivo, res);
    return;
  }

  servirEstatico(path.join(CARPETA_PUBLICA, 'index.html'), res);
}

cargarConfigEnv();
rutaPhp = detectarPhp();

if (!rutaPhp) {
  console.error('\n  ✗ PHP no detectado. Instálalo con: winget install PHP.PHP.8.3\n');
  process.exit(1);
}

mysqlOk = verificarMysql();

if (!mysqlOk) {
  const host = process.env.BD_HOST || 'localhost';
  const puerto = process.env.BD_PUERTO || '3306';
  const nombre = process.env.BD_NOMBRE || 'zona_grupos';
  console.error('\n  ✗ No se pudo conectar a MySQL.');
  console.error(`    Host: ${host}:${puerto}  BD: ${nombre}`);
  console.error('');
  console.error('  En tu PC, "localhost" es tu máquina, NO el hosting.');
  console.error('  Para usar la BD de Spaceship en local:');
  console.error('    1. Copia config.env.local.example → config.env.local');
  console.error('    2. Pon el host MySQL remoto del panel (o túnel SSH)');
  console.error('    3. En Spaceship: Remote MySQL → añade tu IP pública');
  console.error('');
  console.error('  O usa MySQL local: net start MySQL84 y npm run setup-db\n');
  process.exit(1);
}

const servidor = http.createServer(manejarSolicitud);

servidor.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error(`\n  ✗ El puerto ${PUERTO} ya está en uso.`);
    console.error('    Cierra la otra instancia o ejecuta en PowerShell:');
    console.error(`    netstat -ano | findstr :${PUERTO}`);
    console.error('    taskkill /PID <número> /F\n');
    process.exit(1);
  }
  throw err;
});

servidor.listen(PUERTO, () => {
  const phpCorto = rutaPhp.length > 28 ? '...' + rutaPhp.slice(-24) : rutaPhp;
  console.log('');
  console.log('  ╔══════════════════════════════════════════╗');
  console.log('  ║         🌐  ZonaGrupos.Lat               ║');
  console.log('  ╠══════════════════════════════════════════╣');
  console.log(`  ║  Web:  http://localhost:${PUERTO}             ║`);
  console.log(`  ║  API:  http://localhost:${PUERTO}/api/        ║`);
  console.log(`  ║  PHP:  ${phpCorto.padEnd(28)}  ║`);
  console.log(`  ║  BD:   ${(process.env.BD_HOST || 'localhost').slice(0, 28).padEnd(28)}  ║`);
  console.log('  ╚══════════════════════════════════════════╝');
  console.log('');
});
