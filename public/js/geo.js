/**
 * ZonaGrupos — Detección de país del visitante (caché en sesión)
 */
const VisitanteGeo = (() => {
  const CLAVE_CACHE = 'zg_pais_visitante';
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

  async function detectar() {
    const cache = leerCache();
    if (cache) {
      paisActual = cache;
      return cache;
    }

    const datos = await ApiGrupos.obtenerMiPais();
    paisActual = datos;
    guardarCache(datos);
    return datos;
  }

  function obtener() {
    return paisActual || leerCache();
  }

  function mostrarEnElemento(badge, nombrePais) {
    if (!badge) return;
    badge.hidden = false;
    const texto = badge.querySelector('#texto-pais, .badge-pais__texto');
    if (texto) texto.textContent = nombrePais;
  }

  async function iniciarBadge(idBadge = 'badge-pais') {
    const badge = document.getElementById(idBadge);
    if (!badge) return null;

    try {
      const datos = await detectar();
      const nombre = datos.pais?.nombre || 'Desconocido';
      mostrarEnElemento(badge, nombre);
      badge.title = datos.es_local
        ? 'Desarrollo local — país simulado'
        : `Detectado por IP (${datos.fuente || 'ip-api.com'})`;
      if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [badge] });
      return datos;
    } catch (e) {
      console.warn('[ZonaGrupos:geo] No se detectó país:', e.message);
      return null;
    }
  }

  return { detectar, obtener, iniciarBadge, mostrarEnElemento };
})();
