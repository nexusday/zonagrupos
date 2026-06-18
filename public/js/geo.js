/**
 * ZonaGrupos — Detección de país del visitante (caché en sesión)
 */
const VisitanteGeo = (() => {
  const CLAVE_CACHE = 'zg_pais_visitante_v2';
  const TTL_MS = 30 * 60 * 1000; // 30 minutos

  let paisActual = null;

  function leerCache() {
    try {
      const raw = sessionStorage.getItem(CLAVE_CACHE);
      if (!raw) return null;
      const datos = JSON.parse(raw);
      if (!datos?.pais || Date.now() - (datos.ts || 0) > TTL_MS) return null;
      return datos;
    } catch {
      return null;
    }
  }

  function guardarCache(datos) {
    try {
      sessionStorage.setItem(CLAVE_CACHE, JSON.stringify({ ...datos, ts: Date.now() }));
    } catch { /* ignorar */ }
  }

  /** En local el servidor ve 127.0.0.1; el navegador sí respeta VPN/extensiones. */
  async function detectarDesdeNavegador() {
    const proveedores = [
      async () => {
        const res = await fetch('https://ipapi.co/json/', { signal: AbortSignal.timeout(5000) });
        const datos = await res.json();
        if (!datos?.country_code) return null;
        return {
          codigo: String(datos.country_code).toUpperCase(),
          nombre: datos.country_name || datos.country_code,
        };
      },
      async () => {
        const res = await fetch('https://www.cloudflare.com/cdn-cgi/trace', { signal: AbortSignal.timeout(5000) });
        const texto = await res.text();
        const loc = texto.match(/^loc=(\w+)/m)?.[1];
        if (!loc) return null;
        return { codigo: loc.toUpperCase(), nombre: loc.toUpperCase() };
      },
    ];

    for (const proveedor of proveedores) {
      try {
        const pais = await proveedor();
        if (pais) {
          return {
            exito: true,
            ip: null,
            es_local: true,
            pais,
            fuente: 'navegador (VPN)',
          };
        }
      } catch { /* siguiente proveedor */ }
    }
    return null;
  }

  async function detectar() {
    const cache = leerCache();
    if (cache) {
      paisActual = cache;
      return cache;
    }

    const datos = await ApiGrupos.obtenerMiPais();

    if (datos.es_local) {
      const desdeNav = await detectarDesdeNavegador();
      if (desdeNav) {
        paisActual = desdeNav;
        guardarCache(desdeNav);
        return desdeNav;
      }
    }

    paisActual = datos;
    guardarCache(datos);
    return datos;
  }

  function obtener() {
    return paisActual || leerCache();
  }

  function cabecerasApi() {
    const datos = obtener();
    if (!datos?.es_local || !datos?.pais?.codigo) return {};
    return {
      'X-Geo-Pais': datos.pais.codigo,
      'X-Geo-Pais-Nombre': datos.pais.nombre,
    };
  }

  function mostrarEnElemento(badge, nombrePais, codigoPais) {
    if (!badge) return;
    badge.hidden = false;
    const texto = badge.querySelector('#texto-pais, .badge-pais__texto');
    if (texto) texto.textContent = nombrePais;
    const slot = badge.querySelector('.badge-pais__bandera');
    if (slot && typeof Banderas !== 'undefined') {
      slot.innerHTML = Banderas.html(codigoPais, 'bandera-icono--badge')
        || '<i data-lucide="map-pin" class="bandera-icono--fallback" aria-hidden="true"></i>';
    }
  }

  async function iniciarBadge(idBadge = 'badge-pais') {
    const badge = document.getElementById(idBadge);
    if (!badge) return null;

    try {
      const datos = await detectar();
      const nombre = datos.pais?.nombre || 'Desconocido';
      const codigo = datos.pais?.codigo || '';
      mostrarEnElemento(badge, nombre, codigo);
      badge.title = datos.fuente
        ? `Detectado por IP (${datos.fuente})`
        : 'País detectado por IP';
      if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [badge] });
      return datos;
    } catch (e) {
      console.warn('[ZonaGrupos:geo] No se detectó país:', e.message);
      return null;
    }
  }

  return { detectar, obtener, cabecerasApi, iniciarBadge, mostrarEnElemento };
})();
