<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/entorno.php';
require_once dirname(__DIR__) . '/api/seo.php';

$meta = metaInicio();
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
<body class="pagina-inicio">

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

      <nav class="navegacion" aria-label="Principal">
        <span class="badge-pais" id="badge-pais" hidden title="Tu país detectado por IP">
          <i data-lucide="map-pin"></i>
          <span class="badge-pais__texto" id="texto-pais">—</span>
        </span>
        <button class="btn btn--fantasma btn--icono" id="btn-buscar-movil" aria-label="Buscar">
          <i data-lucide="search"></i>
        </button>
        <button class="btn btn--primario" id="btn-publicar">
          <i data-lucide="plus"></i>
          <span class="btn-publicar-texto">Publicar grupo</span>
        </button>
      </nav>
    </div>
  </header>

  <main>
    <section class="inicio-intro">
      <div class="contenedor inicio-intro__interior">
        <div class="inicio-intro__cabecera">
          <h1 class="inicio-intro__titulo">Grupos de WhatsApp, Telegram y Discord</h1>
          <p class="inicio-intro__sub">Publica o encuentra tu comunidad.</p>
        </div>

        <div class="busqueda-wrap">
          <div class="busqueda busqueda--compacta" id="busqueda-principal">
            <div class="busqueda__fila">
              <i data-lucide="search" class="busqueda__icono"></i>
              <input
                type="search"
                id="input-busqueda"
                class="busqueda__input"
                placeholder="Buscar grupo, tema o país..."
                autocomplete="off"
                aria-label="Buscar grupos"
                aria-expanded="false"
                aria-controls="sugerencias-busqueda"
              >
            </div>
            <button class="btn btn--secundario" id="btn-buscar" type="button">Buscar</button>
          </div>
          <div class="sugerencias-busqueda" id="sugerencias-busqueda" hidden role="listbox"></div>
        </div>
      </div>
    </section>

    <section class="barra-filtros" aria-label="Filtros de grupos">
      <div class="contenedor">
        <p class="grupos-total" id="stat-grupos" aria-live="polite">Grupos totales: —</p>
        <div class="filtros">
          <div class="filtros__grupo" role="group" aria-label="Filtrar por plataforma">
            <button class="chip chip--activo" data-plataforma="">Todos</button>
            <button class="chip" data-plataforma="whatsapp">
              <i data-lucide="message-circle"></i> WhatsApp
            </button>
            <button class="chip" data-plataforma="telegram">
              <i data-lucide="send"></i> Telegram
            </button>
            <button class="chip" data-plataforma="discord">
              <i data-lucide="headphones"></i> Discord
            </button>
          </div>

          <div class="filtros__grupo filtros__grupo--contenido" role="group" aria-label="Tipo de contenido">
            <button class="chip chip--activo" data-clasificacion="">Todos</button>
            <button class="chip" data-clasificacion="normal">General</button>
            <button class="chip chip--adulto" data-clasificacion="adulto">+18</button>
          </div>

          <div class="filtros__derecha">
            <select id="select-orden" class="select" aria-label="Ordenar por">
              <option value="recientes">Más recientes</option>
              <option value="populares">Más populares</option>
              <option value="visitas">Más visitados</option>
            </select>
          </div>
        </div>
      </div>
    </section>

    <section class="grupos-seccion">
      <div class="contenedor">
        <div class="filtro-activo" id="filtro-activo" hidden>
          <span class="filtro-activo__texto" id="filtro-activo-texto"></span>
          <button type="button" class="filtro-activo__limpiar" id="btn-limpiar-filtro">Quitar filtro</button>
        </div>

        <div class="grupos-cabecera">
          <h2 class="grupos-cabecera__titulo" id="titulo-listado">Grupos recientes</h2>
        </div>

        <div id="estado-carga" class="estado estado--carga" hidden>
          <div class="spinner"></div>
          <p>Cargando grupos...</p>
        </div>

        <div id="estado-vacio" class="estado estado--vacio" hidden>
          <div class="estado-vacio__icono" aria-hidden="true">
            <i data-lucide="search-x"></i>
          </div>
          <h3>No hay resultados</h3>
          <p>Prueba con otra búsqueda o publica el primero.</p>
          <button type="button" class="estado-vacio__cta" id="btn-publicar-vacio">
            Publicar un grupo
            <i data-lucide="arrow-right"></i>
          </button>
        </div>

        <div id="estado-error" class="estado estado--error" hidden>
          <i data-lucide="wifi-off"></i>
          <h3>Sin conexión con el servidor</h3>
          <p id="mensaje-error">Verifica que el servidor esté activo y MySQL configurado.</p>
          <button class="btn btn--secundario" id="btn-reintentar">Reintentar</button>
        </div>

        <div class="grilla-grupos" id="grilla-grupos" role="list"></div>

        <nav class="paginacion" id="paginacion" aria-label="Paginación" hidden>
          <button class="btn btn--fantasma btn--icono" id="btn-pag-anterior" aria-label="Página anterior">
            <i data-lucide="chevron-left"></i>
          </button>
          <span class="paginacion__info" id="info-paginacion"></span>
          <button class="btn btn--fantasma btn--icono" id="btn-pag-siguiente" aria-label="Página siguiente">
            <i data-lucide="chevron-right"></i>
          </button>
        </nav>
      </div>
    </section>
  </main>

  <div class="barra-publicar-movil" id="barra-publicar-movil">
    <button type="button" class="barra-publicar-movil__btn" id="btn-publicar-movil">
      <i data-lucide="plus"></i>
      <span>Publicar grupo</span>
    </button>
  </div>

  <footer class="pie">
    <div class="contenedor pie__interior">
      <div class="pie__marca">
        <span class="marca__icono marca__icono--pequeno marca__icono--logo">
          <img src="/img/zonagrupos.png" alt="" width="28" height="28" decoding="async">
        </span>
        <span>ZonaGrupos</span>
      </div>
      <p class="pie__texto">ZonaGrupos - Un enlace, un grupo.</p>

      <details class="explorar-temas" id="explorar-temas">
        <summary class="explorar-temas__summary">Explorar por tema</summary>
        <div class="explorar-temas__cuerpo">
          <p class="explorar-temas__ayuda">Temas con más grupos. Busca o navega — escala a miles de categorías.</p>
          <div class="explorar-temas__buscar">
            <input type="search" id="input-explorar-etiquetas" class="explorar-temas__input" placeholder="Filtrar temas..." autocomplete="off">
          </div>
          <div class="explorar-temas__grid" id="grid-etiquetas" role="list"></div>
          <div class="explorar-temas__pie">
            <span class="explorar-temas__info" id="info-explorar-etiquetas"></span>
            <button type="button" class="btn btn--fantasma btn--mini" id="btn-mas-etiquetas" hidden>Cargar más</button>
          </div>
        </div>
      </details>

      <nav class="pie__enlaces" aria-label="Legal">
        <a href="/terminos">Términos y condiciones</a>
      </nav>
      <p class="pie__copy">&copy; 2026 ZonaGrupos.Lat</p>
    </div>
  </footer>

  <!-- Modal publicar -->
  <dialog class="modal" id="modal-publicar" aria-labelledby="modal-titulo">
    <div class="modal__cabecera">
      <h2 id="modal-titulo">Publicar un grupo</h2>
      <button class="btn btn--fantasma btn--icono" id="btn-cerrar-modal" aria-label="Cerrar">
        <i data-lucide="x"></i>
      </button>
    </div>

    <form id="formulario-grupo" class="modal__cuerpo" novalidate>
      <div class="campo">
        <label for="campo-correo">Tu correo electrónico <span class="requerido">*</span></label>
        <input type="email" id="campo-correo" name="correo" maxlength="254" required
               placeholder="tu@correo.com" autocomplete="email" inputmode="email">
        <span class="campo__ayuda">Te enviaremos el enlace de tu grupo para que lo compartas.</span>
      </div>

      <div class="campo">
        <label for="campo-nombre">Nombre del grupo <span class="requerido">*</span></label>
        <input type="text" id="campo-nombre" name="nombre" maxlength="120" required
               placeholder="Ej: Gamers LATAM 🔥" autocomplete="off">
        <span class="campo__ayuda">Letras normales y emojis. Mínimo 3 caracteres.</span>
      </div>

      <div class="campo">
        <label for="campo-descripcion">Descripción <span class="requerido">*</span></label>
        <textarea id="campo-descripcion" name="descripcion" maxlength="500" rows="3" required
                  placeholder="Describe de qué trata tu grupo, quién puede unirse, horarios..."></textarea>
        <span class="campo__ayuda">Letras normales y emojis. Sin tipografías raras.</span>
        <span class="campo__contador"><span id="contador-descripcion">0</span>/500</span>
      </div>

      <div class="campo">
        <label for="campo-etiquetas">Etiquetas <span class="requerido">*</span></label>
        <input type="text" id="campo-etiquetas" name="etiquetas" maxlength="200" required
               placeholder="gaming, amigos, latam" autocomplete="off">
        <span class="campo__ayuda">Separadas por coma o espacio. Mínimo 1, máximo 10</span>
        <div class="vista-etiquetas" id="vista-etiquetas-previa"></div>
      </div>

      <div class="campo">
        <label>Plataforma <span class="requerido">*</span></label>
        <div class="selector-plataforma" role="radiogroup">
          <label class="plataforma-opcion plataforma-opcion--activa">
            <input type="radio" name="plataforma" value="whatsapp" checked>
            <i data-lucide="message-circle"></i>
            <span>WhatsApp</span>
          </label>
          <label class="plataforma-opcion">
            <input type="radio" name="plataforma" value="telegram">
            <i data-lucide="send"></i>
            <span>Telegram</span>
          </label>
          <label class="plataforma-opcion">
            <input type="radio" name="plataforma" value="discord">
            <i data-lucide="headphones"></i>
            <span>Discord</span>
          </label>
        </div>
      </div>

      <div class="campo">
        <label for="campo-enlace">Enlace de invitación <span class="requerido">*</span></label>
        <input type="url" id="campo-enlace" name="enlace" required
               placeholder="https://chat.whatsapp.com/...">
        <span class="campo__ayuda" id="ayuda-enlace">Solo WhatsApp, Telegram o Discord. Un enlace por grupo.</span>
      </div>

      <div class="campo">
        <label>Tipo de contenido <span class="requerido">*</span></label>
        <div class="selector-restriccion selector-clasificacion" role="radiogroup" aria-label="Clasificación del grupo">
          <label class="restriccion-opcion restriccion-opcion--activa">
            <input type="radio" name="clasificacion" value="normal" checked>
            <i data-lucide="users"></i>
            <span>Grupo general</span>
          </label>
          <label class="restriccion-opcion restriccion-opcion--adulto">
            <input type="radio" name="clasificacion" value="adulto">
            <i data-lucide="shield-alert"></i>
            <span>Contenido sexual (+18)</span>
          </label>
        </div>
        <span class="campo__ayuda">Marca +18 solo si el grupo es para adultos.</span>
      </div>

      <div class="campo">
        <label>¿Quién puede unirse?</label>
        <div class="selector-restriccion" role="radiogroup" aria-label="Restricción por país">
          <label class="restriccion-opcion restriccion-opcion--activa">
            <input type="radio" name="restriccion_pais" value="todos" checked>
            <i data-lucide="globe"></i>
            <span>Todos los países</span>
          </label>
          <label class="restriccion-opcion">
            <input type="radio" name="restriccion_pais" value="solo_pais">
            <i data-lucide="map-pin"></i>
            <span>Solo mi país</span>
          </label>
        </div>
        <span class="campo__ayuda" id="ayuda-pais-detectado">Detectamos tu país por IP al publicar.</span>
      </div>

      <div class="modal__pie">
        <button type="button" class="btn btn--fantasma" id="btn-cancelar">Cancelar</button>
        <button type="submit" class="btn btn--primario" id="btn-enviar">
          <i data-lucide="rocket"></i>
          <span>Publicar</span>
        </button>
      </div>
    </form>
  </dialog>

  <div class="toast-contenedor" id="toast-contenedor" aria-live="polite"></div>

  <script src="/js/api.js"></script>
  <script src="/js/geo.js"></script>
  <script src="/js/app.js"></script>
</body>
</html>
