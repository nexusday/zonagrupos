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
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/estilos.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="pagina-legal">

  <div class="fondo-animado fondo-animado--sutil" aria-hidden="true">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
  </div>

  <header class="cabecera cabecera--sutil">
    <div class="contenedor cabecera__interior">
      <a href="/" class="marca">
        <span class="marca__icono marca__icono--logo">
          <img src="/img/zonagrupos.png" alt="" width="38" height="38" decoding="async">
        </span>
        <span class="marca__texto">Zona<span class="marca__acento">Grupos</span></span>
      </a>
      <nav class="navegacion">
        <a href="/" class="legal-volver"><i data-lucide="arrow-left"></i> Inicio</a>
      </nav>
    </div>
  </header>

  <main class="legal-main">
    <article class="legal-documento contenedor">

      <header class="legal-cabecera">
        <p class="legal-cabecera__etiqueta">Transparencia</p>
        <h1>Términos y condiciones</h1>
        <p class="legal-cabecera__intro">
          Qué datos usamos, por qué y qué no guardamos.
        </p>
        <time class="legal-cabecera__fecha" datetime="2026-06">Actualizado · junio 2026</time>
      </header>

      <nav class="legal-indice" aria-label="Secciones">
        <a href="#ip">IP</a>
        <a href="#cookies">Cookies</a>
        <a href="#enlaces">Enlaces</a>
        <a href="#correos">Correo</a>
        <a href="#otros">Más datos</a>
      </nav>

      <div class="legal-cuerpo">

        <section class="legal-bloque" id="ip">
          <h2><i data-lucide="globe"></i> Dirección IP</h2>
          <p>
            Al publicar un grupo, el sistema puede consultar tu <strong>IP pública</strong> en ese momento
            —por ejemplo, para detectar tu país y aplicar restricciones de acceso.
          </p>
          <ul class="legal-lista">
            <li>No guardamos tu IP en la base de datos.</li>
            <li>No la usamos con fines maliciosos.</li>
            <li>Solo el sistema la consulta cuando hace falta.</li>
          </ul>
        </section>

        <section class="legal-bloque" id="cookies">
          <h2><i data-lucide="cookie"></i> Cookies</h2>
          <p>
            Usamos cookies para que la web cargue rápido y la experiencia sea fluida al navegar y publicar.
          </p>
          <ul class="legal-lista">
            <li>No las guardamos en ninguna base de datos.</li>
            <li>Permiten recordar preferencias básicas en tu navegador.</li>
          </ul>
        </section>

        <section class="legal-bloque" id="enlaces">
          <h2><i data-lucide="link"></i> Enlaces que publicas</h2>
          <p>
            Los enlaces de invitación (WhatsApp, Telegram, Discord, etc.)
            <strong>sí se guardan</strong> en nuestra base de datos.
          </p>
          <ul class="legal-lista">
            <li>Es necesario para mostrar tu grupo en el directorio.</li>
            <li>Publica solo enlaces de grupos que puedas compartir.</li>
          </ul>
        </section>

        <section class="legal-bloque" id="correos">
          <h2><i data-lucide="mail"></i> Correo electrónico</h2>
          <p>
            Si publicas un grupo, pedimos tu <strong>correo</strong> para enviarte confirmaciones,
            avisos y novedades sobre tus publicaciones.
          </p>
          <ul class="legal-lista">
            <li>Tu correo sí se guarda en nuestra base de datos.</li>
            <li>Nunca pediremos contraseñas ni datos bancarios por correo.</li>
            <li>Ante dudas, verifica que el mensaje venga de nuestro correo oficial.</li>
          </ul>
          <p class="legal-nota">
            Correo oficial:
            <a href="mailto:soporte-oficial@zonagrupos.lat">soporte-oficial@zonagrupos.lat</a>
          </p>
        </section>

        <section class="legal-bloque" id="otros">
          <h2><i data-lucide="shield-check"></i> ¿Algo más?</h2>
          <p>
            Aparte de lo anterior, <strong>no recopilamos ni guardamos otra información personal</strong>.
            Nombre, descripción y etiquetas de cada grupo son lo que tú decides publicar.
          </p>
        </section>

      </div>

      <footer class="legal-pie">
        <p>¿Preguntas? <a href="mailto:soporte-oficial@zonagrupos.lat">soporte-oficial@zonagrupos.lat</a></p>
      </footer>

    </article>
  </main>

  <footer class="pie pie--minimo">
    <div class="contenedor pie__interior">
      <nav class="pie__enlaces" aria-label="Legal">
        <a href="/terminos" aria-current="page">Términos y condiciones</a>
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
