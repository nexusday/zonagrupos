<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/entorno.php';
require_once dirname(__DIR__) . '/api/conexion.php';

function escOg(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$slug = trim($_GET['slug'] ?? '');
if ($slug === '' && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('~/grupo/([^/?]+)~', (string) $_SERVER['REQUEST_URI'], $coincidencia)) {
        $slug = rawurldecode($coincidencia[1]);
    }
}

$appUrl = rtrim(envConfig('APP_URL', 'https://zonagrupos.lat'), '/');
$ogImage = $appUrl . '/img/zonagrupos.png';
$titulo = 'Grupo — ZonaGrupos';
$descripcion = 'Descubre y únete a grupos de WhatsApp, Telegram y Discord en ZonaGrupos.';
$canonical = $slug !== '' ? $appUrl . '/grupo/' . rawurlencode($slug) : $appUrl . '/';

if ($slug !== '') {
    try {
        $bd = obtenerConexion();
        $stmt = $bd->prepare(
            'SELECT nombre, descripcion, slug FROM grupos WHERE slug = :slug AND activo = 1 LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);
        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($grupo) {
            $titulo = $grupo['nombre'] . ' — ZonaGrupos';
            $texto = preg_replace('/\s+/', ' ', trim(strip_tags($grupo['descripcion'])));
            $descripcion = mb_substr($texto, 0, 160);
            $canonical = $appUrl . '/grupo/' . $grupo['slug'];
        }
    } catch (Throwable) {
        // Valores por defecto
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= escOg($titulo) ?></title>
  <link rel="icon" href="/img/zonagrupos.png" type="image/png">
  <link rel="apple-touch-icon" href="/img/zonagrupos.png">
  <meta name="description" content="<?= escOg($descripcion) ?>">
  <link rel="canonical" id="meta-canonical" href="<?= escOg($canonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="ZonaGrupos">
  <meta property="og:locale" content="es_LA">
  <meta property="og:title" id="meta-og-titulo" content="<?= escOg($titulo) ?>">
  <meta property="og:description" id="meta-og-descripcion" content="<?= escOg($descripcion) ?>">
  <meta property="og:url" content="<?= escOg($canonical) ?>">
  <meta property="og:image" id="meta-og-imagen" content="<?= escOg($ogImage) ?>">
  <meta property="og:image:alt" content="ZonaGrupos">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= escOg($titulo) ?>">
  <meta name="twitter:description" content="<?= escOg($descripcion) ?>">
  <meta name="twitter:image" content="<?= escOg($ogImage) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/estilos.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="pagina-grupo">

  <div class="fondo-animado" aria-hidden="true">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
  </div>

  <header class="cabecera">
    <div class="contenedor cabecera__interior">
      <a href="/" class="marca">
        <span class="marca__icono marca__icono--logo"><img src="/img/zonagrupos.png" alt="" width="38" height="38" decoding="async"></span>
        <span class="marca__texto">Zona<span class="marca__acento">Grupos</span></span>
      </a>
      <nav class="navegacion">
        <span class="badge-pais" id="badge-pais" hidden title="Tu país detectado por IP">
          <i data-lucide="map-pin"></i>
          <span class="badge-pais__texto" id="texto-pais">—</span>
        </span>
        <a href="/" class="btn btn--fantasma"><i data-lucide="arrow-left"></i> Volver</a>
      </nav>
    </div>
  </header>

  <main class="pagina-grupo__main">
    <div id="estado-carga" class="estado estado--carga grupo-estado-centrado">
      <div class="spinner"></div>
      <p>Cargando grupo...</p>
    </div>

    <div id="estado-error" class="estado estado--error grupo-estado-centrado" hidden>
      <i data-lucide="alert-circle"></i>
      <h3>Grupo no encontrado</h3>
      <p id="mensaje-error"></p>
      <a href="/" class="btn btn--secundario">Ir al inicio</a>
    </div>

    <div id="contenido-grupo" class="contenido-grupo" hidden></div>

    <section id="seccion-relacionados" class="grupos-relacionados contenedor" hidden>
      <div class="grupos-relacionados__cabecera">
        <h2><i data-lucide="sparkles"></i> Grupos similares</h2>
        <p>Comunidades relacionadas que te pueden interesar</p>
      </div>
      <div id="grilla-relacionados" class="grilla-relacionados" role="list"></div>
    </section>
  </main>

  <div id="barra-unirse-fija" class="barra-unirse-fija" hidden>
    <button class="btn btn--like" id="btn-like-fijo" aria-label="Dar like">
      <i data-lucide="heart"></i>
    </button>
    <button class="btn btn--unirse btn--grande" id="btn-unirse-fijo">
      <i data-lucide="external-link"></i> Unirse al grupo
    </button>
  </div>

  <footer class="pie">
    <div class="contenedor pie__interior">
      <div class="pie__marca">
        <span class="marca__icono marca__icono--pequeno marca__icono--logo"><img src="/img/zonagrupos.png" alt="" width="28" height="28" decoding="async"></span>
        <span>ZonaGrupos</span>
      </div>
      <p class="pie__texto">Directorio de grupos para la comunidad latina. Un enlace, un grupo.</p>
      <p class="pie__copy">&copy; 2026 ZonaGrupos.Lat</p>
    </div>
  </footer>

  <div class="toast-contenedor" id="toast-contenedor" aria-live="polite"></div>

  <dialog class="modal modal--reporte" id="modal-reporte">
    <div class="modal__cabecera">
      <h2>Reportar grupo</h2>
      <button type="button" class="btn btn--fantasma btn--icono" id="btn-cerrar-reporte" aria-label="Cerrar">✕</button>
    </div>
    <form id="form-reporte" class="modal__cuerpo">
      <div class="campo">
        <label for="reporte-motivo">Motivo</label>
        <select id="reporte-motivo" required>
          <option value="spam">Spam</option>
          <option value="inapropiado">Contenido inapropiado</option>
          <option value="enlace_roto">Enlace roto</option>
          <option value="estafa">Posible estafa</option>
          <option value="otro">Otro</option>
        </select>
      </div>
      <div class="campo">
        <label for="reporte-detalle">Detalle (opcional)</label>
        <textarea id="reporte-detalle" rows="3" maxlength="500" placeholder="Cuéntanos qué ocurre..."></textarea>
      </div>
      <div class="modal__pie">
        <button type="button" class="btn btn--fantasma" id="btn-cancelar-reporte">Cancelar</button>
        <button type="submit" class="btn btn--primario">Enviar reporte</button>
      </div>
    </form>
  </dialog>

  <script src="/js/api.js"></script>
  <script src="/js/geo.js"></script>
  <script src="/js/grupo.js"></script>
</body>
</html>
