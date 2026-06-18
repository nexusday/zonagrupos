<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/entorno.php';
require_once dirname(__DIR__) . '/api/seo.php';

$meta = metaTerminos();
?><!DOCTYPE html>
<html lang="es">
<head>
<?php emitirMetasPagina($meta); ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/estilos.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="pagina-legal">

  <div class="fondo-animado" aria-hidden="true">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
  </div>

  <header class="cabecera">
    <div class="contenedor cabecera__interior">
      <a href="/" class="marca">
        <span class="marca__icono marca__icono--logo">
          <img src="/img/zonagrupos.png" alt="" width="38" height="38" decoding="async">
        </span>
        <span class="marca__texto">Zona<span class="marca__acento">Grupos</span></span>
      </a>
      <nav class="navegacion">
        <a href="/" class="btn btn--fantasma"><i data-lucide="arrow-left"></i> Volver</a>
      </nav>
    </div>
  </header>

  <main class="legal-main contenedor">
    <header class="legal-cabecera">
      <h1>Términos y condiciones</h1>
      <p class="legal-cabecera__intro">
        Aquí te explicamos, de forma clara, qué datos usamos en ZonaGrupos y para qué.
        Sin letra pequeña ni complicaciones.
      </p>
      <p class="legal-cabecera__fecha">Última actualización: junio 2026</p>
    </header>

    <div class="legal-contenido">

      <section class="legal-seccion" id="ip">
        <div class="legal-seccion__icono" aria-hidden="true">
          <i data-lucide="globe"></i>
        </div>
        <h2>Dirección IP</h2>
        <p>
          Cuando publicas un grupo, el sistema puede usar tu <strong>IP pública</strong> en ese momento
          (por ejemplo, para saber de qué país te conectas y aplicar restricciones de acceso al grupo).
        </p>
        <ul class="legal-lista">
          <li>Nosotros <strong>no guardamos tu IP</strong> en la base de datos.</li>
          <li>No la usamos para fines maliciosos ni para rastrearte fuera de lo necesario para el sitio.</li>
          <li>Solo el sistema la consulta de forma automática cuando hace falta.</li>
        </ul>
      </section>

      <section class="legal-seccion" id="cookies">
        <div class="legal-seccion__icono" aria-hidden="true">
          <i data-lucide="cookie"></i>
        </div>
        <h2>Cookies</h2>
        <p>
          Usamos cookies para que la web funcione bien: cargas más rápidas, menos esperas y una
          experiencia fluida al navegar y publicar.
        </p>
        <ul class="legal-lista">
          <li>No guardamos esas cookies en ninguna base de datos nuestra.</li>
          <li>Sirven para que el sitio recuerde preferencias básicas en tu navegador.</li>
        </ul>
      </section>

      <section class="legal-seccion" id="enlaces">
        <div class="legal-seccion__icono" aria-hidden="true">
          <i data-lucide="link"></i>
        </div>
        <h2>Enlaces que publicas</h2>
        <p>
          Los enlaces de invitación que subes (WhatsApp, Telegram, Discord, etc.)
          <strong>sí se guardan en nuestra base de datos</strong>.
        </p>
        <ul class="legal-lista">
          <li>Es necesario para mostrar tu grupo a otros usuarios del directorio.</li>
          <li>Solo publica enlaces de grupos que tengas derecho a compartir.</li>
        </ul>
      </section>

      <section class="legal-seccion" id="correos">
        <div class="legal-seccion__icono" aria-hidden="true">
          <i data-lucide="mail"></i>
        </div>
        <h2>Correo electrónico</h2>
        <p>
          Si publicas un grupo, pedimos tu <strong>correo electrónico</strong> para enviarte
          información útil: confirmación de publicación, avisos, actualizaciones y gestión de tus grupos.
        </p>
        <ul class="legal-lista">
          <li>Tu correo <strong>sí se guarda</strong> en nuestra base de datos.</li>
          <li><strong>Nunca</strong> te pediremos contraseñas ni datos bancarios por correo.</li>
          <li>Si recibes un mensaje sospechoso, comprueba que venga de nuestro correo oficial.</li>
        </ul>
        <p class="legal-contacto">
          Correo oficial de ZonaGrupos:
          <a href="mailto:soporte-oficial@zonagrupos.lat">soporte-oficial@zonagrupos.lat</a>
        </p>
      </section>

      <section class="legal-seccion" id="otros">
        <div class="legal-seccion__icono" aria-hidden="true">
          <i data-lucide="shield-check"></i>
        </div>
        <h2>¿Recopilamos algo más?</h2>
        <p>
          Aparte de lo descrito arriba, <strong>no recopilamos ni guardamos otra información personal tuya</strong>
          en nuestros sistemas. Lo que ves en cada ficha de grupo (nombre, descripción, etiquetas) es lo que
          tú decides publicar en el directorio.
        </p>
      </section>

    </div>

    <footer class="legal-pie">
      <p>¿Dudas? Escríbenos a <a href="mailto:soporte-oficial@zonagrupos.lat">soporte-oficial@zonagrupos.lat</a></p>
      <a href="/" class="btn btn--secundario"><i data-lucide="home"></i> Volver al inicio</a>
    </footer>
  </main>

  <footer class="pie">
    <div class="contenedor pie__interior">
      <nav class="pie__enlaces" aria-label="Legal">
        <a href="/terminos">Términos y condiciones</a>
      </nav>
      <p class="pie__copy">&copy; 2026 ZonaGrupos.Lat</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof lucide !== 'undefined') lucide.createIcons();
    });
  </script>
</body>
</html>
