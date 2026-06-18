/**
 * ZonaGrupos — Página individual del grupo (SEO + experiencia completa)
 */
(() => {
  'use strict';

  let grupoActual = null;

  const iconosPlataforma = {
    whatsapp: 'message-circle', telegram: 'send', discord: 'headphones', otro: 'link',
  };

  const nombresPlataforma = {
    whatsapp: 'WhatsApp', telegram: 'Telegram', discord: 'Discord', otro: 'Otro',
  };

  const coloresPlataforma = {
    whatsapp: '#25d366', telegram: '#29a8e0', discord: '#5865f2', otro: '#7c3aed',
  };

  function obtenerSlugDeUrl() {
    const partes = window.location.pathname.split('/').filter(Boolean);
    if (partes[0] === 'grupo' && partes[1]) return decodeURIComponent(partes[1]);
    return new URLSearchParams(window.location.search).get('slug') || '';
  }

  function escaparHtml(texto) {
    const d = document.createElement('div');
    d.textContent = texto;
    return d.innerHTML;
  }

  function formatearNumero(n) {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return n.toString();
  }

  function formatearFecha(iso) {
    if (!iso) return '—';
    try {
      return new Intl.DateTimeFormat('es', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(iso));
    } catch {
      return iso;
    }
  }

  function mostrarToast(mensaje, tipo = 'exito') {
    const c = document.getElementById('toast-contenedor');
    const t = document.createElement('div');
    t.className = `toast toast--${tipo}`;
    t.textContent = mensaje;
    c.appendChild(t);
    setTimeout(() => t.remove(), 4000);
  }

  function actualizarSeo(grupo) {
    document.title = `${grupo.nombre} — ZonaGrupos`;
    const desc = grupo.descripcion.slice(0, 160);
    document.querySelector('meta[name="description"]').content = desc;
    document.getElementById('meta-og-titulo').content = grupo.nombre;
    document.getElementById('meta-og-descripcion').content = desc;
    document.getElementById('meta-canonical').href = `${window.location.origin}${grupo.url}`;
  }

  function crearParticulas() {
    const cont = document.getElementById('particulas-grupo');
    if (!cont || cont.childElementCount) return;
    for (let i = 0; i < 18; i++) {
      const p = document.createElement('span');
      p.className = 'particula';
      p.style.left = `${Math.random() * 100}%`;
      p.style.animationDelay = `${Math.random() * 8}s`;
      p.style.animationDuration = `${6 + Math.random() * 8}s`;
      cont.appendChild(p);
    }
  }

  function animarContador(elemento, valorFinal, duracion = 900) {
    if (!elemento) return;
    const inicio = performance.now();
    const desde = 0;
    const tick = (ahora) => {
      const progreso = Math.min((ahora - inicio) / duracion, 1);
      const ease = 1 - Math.pow(1 - progreso, 3);
      const actual = Math.round(desde + (valorFinal - desde) * ease);
      elemento.textContent = formatearNumero(actual);
      if (progreso < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  }

  function renderizarEtiquetas(etiquetas) {
    if (!etiquetas?.length) {
      return '<p class="grupo-sin-etiquetas">Sin etiquetas</p>';
    }
    return etiquetas.map((e) =>
      `<a href="/?busqueda=${encodeURIComponent(e.nombre)}" class="etiqueta etiqueta--solo">${escaparHtml(e.nombre)}</a>`
    ).join('');
  }

  function renderizarPagina(grupo) {
    const restriccionTexto = grupo.restriccion_pais === 'solo_pais'
      ? `Solo ${grupo.pais.nombre}`
      : 'Abierto para todos';

    const restriccionIcono = grupo.restriccion_pais === 'solo_pais' ? 'shield' : 'globe-2';

    const avisoPais = !grupo.puede_unirse
      ? `<div class="aviso-pais aviso-pais--bloqueado">
           <i data-lucide="shield-alert"></i>
           <div>
             <strong>Acceso restringido por país</strong>
             <p>Este grupo es solo para <strong>${escaparHtml(grupo.pais.nombre)}</strong>.
             Tu ubicación: <strong>${escaparHtml(grupo.pais_visitante?.nombre || 'Desconocida')}</strong>.</p>
           </div>
         </div>`
      : `<div class="aviso-pais aviso-pais--ok">
           <i data-lucide="check-circle"></i>
           <div>
             <strong>Puedes unirte</strong>
             <p>Este grupo está disponible para tu ubicación (${escaparHtml(grupo.pais_visitante?.nombre || 'tu país')}).</p>
           </div>
         </div>`;

    const color = coloresPlataforma[grupo.plataforma] || '#7c3aed';

    return `
      <section class="grupo-hero grupo-hero--${grupo.plataforma}" style="--color-plataforma:${color}">
        <div class="grupo-hero__inner contenedor">
          <div class="grupo-hero__icono" aria-hidden="true">
            <i data-lucide="${iconosPlataforma[grupo.plataforma]}"></i>
          </div>
          <span class="badge-plataforma badge-plataforma--${grupo.plataforma}">
            <i data-lucide="${iconosPlataforma[grupo.plataforma]}"></i>
            ${nombresPlataforma[grupo.plataforma]}
          </span>
          <h1 class="grupo-hero__titulo">${escaparHtml(grupo.nombre)}</h1>
          <p class="grupo-hero__meta">
            <span><i data-lucide="calendar"></i> Publicado ${formatearFecha(grupo.creado_en)}</span>
            <span><i data-lucide="map-pin"></i> ${escaparHtml(grupo.pais.nombre)}</span>
          </p>
        </div>
      </section>

      <div class="contenedor grupo-layout">
        <div class="grupo-layout__principal">
          <article class="detalle-grupo detalle-grupo--completo">
            <section class="detalle-bloque">
              <h2 class="detalle-bloque__titulo"><i data-lucide="align-left"></i> Descripción</h2>
              <p class="detalle-grupo__descripcion">${escaparHtml(grupo.descripcion)}</p>
            </section>

            <section class="detalle-bloque">
              <h2 class="detalle-bloque__titulo"><i data-lucide="tags"></i> Etiquetas</h2>
              <div class="detalle-grupo__etiquetas">${renderizarEtiquetas(grupo.etiquetas)}</div>
            </section>

            <section class="detalle-bloque detalle-bloque--stats">
              <h2 class="detalle-bloque__titulo"><i data-lucide="bar-chart-3"></i> Estadísticas</h2>
              <div class="detalle-grupo__stats">
                <div class="detalle-stat detalle-stat--animado">
                  <i data-lucide="heart"></i>
                  <span data-contador="${grupo.likes}">0</span>
                  <small>Likes</small>
                </div>
                <div class="detalle-stat detalle-stat--animado">
                  <i data-lucide="eye"></i>
                  <span data-contador="${grupo.visitas}">0</span>
                  <small>Visitas</small>
                </div>
                <div class="detalle-stat">
                  <i data-lucide="map-pin"></i>
                  <span>${escaparHtml(grupo.pais.nombre)}</span>
                  <small>País</small>
                </div>
                <div class="detalle-stat">
                  <i data-lucide="${restriccionIcono}"></i>
                  <span>${restriccionTexto}</span>
                  <small>Acceso</small>
                </div>
              </div>
            </section>

            ${avisoPais}

            <div class="detalle-grupo__acciones detalle-grupo__acciones--desktop">
              <button class="btn btn--like ${grupo.ya_dio_like ? 'activo' : ''}" id="btn-like">
                <i data-lucide="heart"></i> <span>${formatearNumero(grupo.likes)}</span> Me gusta
              </button>
              <button class="btn btn--unirse ${grupo.plataforma} btn--grande" id="btn-unirse"
                      ${grupo.puede_unirse ? '' : 'disabled'}>
                <i data-lucide="external-link"></i> Unirse al grupo
              </button>
              <button class="btn btn--fantasma" id="btn-compartir" type="button">
                <i data-lucide="share-2"></i> Compartir
              </button>
              <button class="btn btn--fantasma btn--reportar" id="btn-reportar" type="button">
                <i data-lucide="flag"></i> Reportar
              </button>
            </div>
          </article>
        </div>

        <aside class="grupo-sidebar">
          <div class="grupo-card grupo-card--cta">
            <div class="grupo-card__icono grupo-card__icono--${grupo.plataforma}">
              <i data-lucide="${iconosPlataforma[grupo.plataforma]}"></i>
            </div>
            <h3>¿Listo para entrar?</h3>
            <p>Únete a la comunidad en ${nombresPlataforma[grupo.plataforma]} con un solo clic.</p>
            <button class="btn btn--unirse ${grupo.plataforma} btn--grande btn--ancho" id="btn-unirse-sidebar"
                    ${grupo.puede_unirse ? '' : 'disabled'}>
              <i data-lucide="external-link"></i> Unirse ahora
            </button>
          </div>

          <div class="grupo-card">
            <h3><i data-lucide="info"></i> Información</h3>
            <ul class="grupo-info-lista">
              <li><span>Plataforma</span><strong>${nombresPlataforma[grupo.plataforma]}</strong></li>
              <li><span>País del grupo</span><strong>${escaparHtml(grupo.pais.nombre)}</strong></li>
              <li><span>Acceso</span><strong>${restriccionTexto}</strong></li>
              <li><span>Publicado</span><strong>${formatearFecha(grupo.creado_en)}</strong></li>
            </ul>
          </div>

          <div class="grupo-card grupo-card--tip">
            <i data-lucide="lightbulb"></i>
            <p><strong>Tip:</strong> Dale like si te gustó el grupo y ayúdalo a subir en el directorio.</p>
          </div>

          <a href="/" class="btn btn--secundario btn--ancho">
            <i data-lucide="compass"></i> Explorar más grupos
          </a>
        </aside>
      </div>
    `;
  }

  function emojiBandera(codigo) {
    const c = (codigo || '').toUpperCase();
    if (!/^[A-Z]{2}$/.test(c) || c === 'LA') return null;
    return String.fromCodePoint(...[...c].map((l) => 0x1F1E6 - 65 + l.charCodeAt(0)));
  }

  function crearTarjetaRelacionada(g) {
    const paisHtml = g.restriccion_pais === 'solo_pais'
      ? `<span class="tarjeta-lista__bandera">${emojiBandera(g.pais?.codigo) || '📍'}</span>`
      : '<i data-lucide="globe" class="tarjeta-lista__flecha"></i>';
    return `
      <a href="${escaparHtml(g.url)}" class="tarjeta-lista tarjeta-lista--compacta" role="listitem">
        <span class="tarjeta-lista__plataforma tarjeta-lista__plataforma--${g.plataforma}">
          <i data-lucide="${iconosPlataforma[g.plataforma]}"></i>
        </span>
        <span class="tarjeta-lista__nombre">${escaparHtml(g.nombre)}</span>
        ${paisHtml}
      </a>
    `;
  }

  async function cargarRelacionados(grupo) {
    const etiqueta = grupo.etiquetas?.[0]?.nombre;
    if (!etiqueta) return;

    try {
      const { grupos } = await ApiGrupos.obtenerGrupos({
        etiqueta,
        por_pagina: 4,
        orden: 'populares',
      });
      const otros = grupos.filter((g) => g.id !== grupo.id).slice(0, 3);
      if (!otros.length) return;

      const seccion = document.getElementById('seccion-relacionados');
      const grilla = document.getElementById('grilla-relacionados');
      grilla.innerHTML = otros.map(crearTarjetaRelacionada).join('');
      seccion.hidden = false;
      if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [seccion] });
    } catch { /* opcional */ }
  }

  async function ejecutarUnirse() {
    const r = await ApiGrupos.unirseGrupo({ grupo_id: grupoActual.id, slug: grupoActual.slug });
    window.open(r.enlace, '_blank', 'noopener,noreferrer');
  }

  async function ejecutarLike(btn) {
    if (btn.classList.contains('activo')) return;
    const r = await ApiGrupos.darLike(grupoActual.id);
    document.querySelectorAll('#btn-like, #btn-like-fijo').forEach((b) => {
      b.classList.add('activo');
      const span = b.querySelector('span');
      if (span) span.textContent = formatearNumero(r.likes);
    });
    mostrarToast('¡Gracias por tu like!');
  }

  function copiarTexto(texto) {
    if (navigator.clipboard?.writeText) {
      return navigator.clipboard.writeText(texto);
    }
    return new Promise((resolve, reject) => {
      const ta = document.createElement('textarea');
      ta.value = texto;
      ta.style.cssText = 'position:fixed;left:-9999px;top:0';
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      try {
        document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
      } catch (err) {
        reject(err);
      } finally {
        ta.remove();
      }
    });
  }

  async function compartirGrupo() {
    const url = `${window.location.origin}${grupoActual.url}`;
    try {
      await copiarTexto(url);
      mostrarToast('Enlace copiado al portapapeles');
    } catch {
      mostrarToast('No se pudo copiar el enlace', 'error');
    }
  }

  function enlazarAcciones() {
    const unirseBtns = ['btn-unirse', 'btn-unirse-sidebar', 'btn-unirse-fijo'];
    unirseBtns.forEach((id) => {
      document.getElementById(id)?.addEventListener('click', async () => {
        try { await ejecutarUnirse(); }
        catch (e) { mostrarToast(e.message, 'error'); }
      });
    });

    document.getElementById('btn-like')?.addEventListener('click', async (e) => {
      try { await ejecutarLike(e.currentTarget); }
      catch (err) { mostrarToast(err.message, 'error'); }
    });

    document.getElementById('btn-like-fijo')?.addEventListener('click', async (e) => {
      try { await ejecutarLike(e.currentTarget); }
      catch (err) { mostrarToast(err.message, 'error'); }
    });

    document.getElementById('btn-compartir')?.addEventListener('click', () => {
      compartirGrupo();
    });

    const modalReporte = document.getElementById('modal-reporte');
    document.getElementById('btn-reportar')?.addEventListener('click', () => modalReporte?.showModal());
    document.getElementById('btn-cerrar-reporte')?.addEventListener('click', () => modalReporte?.close());
    document.getElementById('btn-cancelar-reporte')?.addEventListener('click', () => modalReporte?.close());
    document.getElementById('form-reporte')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        await ApiGrupos.reportarGrupo({
          grupo_id: grupoActual.id,
          motivo: document.getElementById('reporte-motivo').value,
          detalle: document.getElementById('reporte-detalle').value.trim(),
        });
        modalReporte.close();
        mostrarToast('Reporte enviado. Gracias.');
      } catch (err) {
        mostrarToast(err.message, 'error');
      }
    });
  }

  function configurarBarraFija(grupo) {
    const barra = document.getElementById('barra-unirse-fija');
    const btnUnirse = document.getElementById('btn-unirse-fijo');
    if (!barra || !btnUnirse) return;

    btnUnirse.className = `btn btn--unirse ${grupo.plataforma} btn--grande`;
    btnUnirse.disabled = !grupo.puede_unirse;
    barra.hidden = false;

    const likeFijo = document.getElementById('btn-like-fijo');
    if (grupo.ya_dio_like && likeFijo) likeFijo.classList.add('activo');
  }

  function mostrarVista(vista) {
    const vistas = {
      carga: document.getElementById('estado-carga'),
      error: document.getElementById('estado-error'),
      contenido: document.getElementById('contenido-grupo'),
    };
    Object.entries(vistas).forEach(([nombre, el]) => {
      if (el) el.hidden = nombre !== vista;
    });
  }

  async function iniciar() {
    crearParticulas();
    VisitanteGeo.iniciarBadge();
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const slug = obtenerSlugDeUrl();
    if (!slug) {
      mostrarVista('error');
      document.getElementById('mensaje-error').textContent = 'URL de grupo no válida.';
      return;
    }

    try {
      const { grupo } = await ApiGrupos.obtenerDetalle(slug);
      grupoActual = grupo;
      actualizarSeo(grupo);

      const contenedor = document.getElementById('contenido-grupo');
      contenedor.innerHTML = renderizarPagina(grupo);
      mostrarVista('contenido');

      if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [contenedor] });

      contenedor.querySelectorAll('[data-contador]').forEach((el) => {
        animarContador(el, parseInt(el.dataset.contador, 10) || 0);
      });

      enlazarAcciones();
      configurarBarraFija(grupo);
      await cargarRelacionados(grupo);
    } catch (e) {
      mostrarVista('error');
      document.getElementById('mensaje-error').textContent = e.message;
    }
  }

  document.addEventListener('DOMContentLoaded', iniciar);
})();
