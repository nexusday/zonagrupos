/**
 * ZonaGrupos — Capa de comunicación con la API
 */
const ApiGrupos = (() => {
  const BASE = '/api';

  async function solicitar(ruta, opciones = {}) {
    const cabecerasGeo = typeof VisitanteGeo !== 'undefined' ? VisitanteGeo.cabecerasApi() : {};
    const respuesta = await fetch(`${BASE}${ruta}`, {
      headers: { 'Content-Type': 'application/json', ...cabecerasGeo, ...opciones.headers },
      ...opciones,
    });

    let datos;
    try {
      datos = await respuesta.json();
    } catch {
      throw new Error('Respuesta inválida del servidor');
    }

    if (!respuesta.ok || datos.exito === false) {
      throw new Error(datos.mensaje || 'Error en la solicitud');
    }

    return datos;
  }

  function armarConsulta(parametros) {
    const params = new URLSearchParams();
    Object.entries(parametros).forEach(([clave, valor]) => {
      if (valor !== '' && valor !== null && valor !== undefined) {
        params.set(clave, valor);
      }
    });
    const cadena = params.toString();
    return cadena ? `?${cadena}` : '';
  }

  return {
    obtenerGrupos(filtros = {}) {
      return solicitar(`/grupos.php${armarConsulta(filtros)}`);
    },

    obtenerDetalle(slug) {
      return solicitar(`/grupos.php?accion=detalle&slug=${encodeURIComponent(slug)}`);
    },

    obtenerEstadisticas() {
      return solicitar('/grupos.php?accion=estadisticas');
    },

    obtenerEtiquetasTendencia(limite = 15) {
      return solicitar(`/etiquetas.php?accion=tendencia&limite=${limite}`);
    },

    buscarEtiquetas(q, limite = 8) {
      return solicitar(`/etiquetas.php?accion=buscar&q=${encodeURIComponent(q)}&limite=${limite}`);
    },

    explorarEtiquetas({ q = '', pagina = 1, porPagina = 24 } = {}) {
      const params = new URLSearchParams({ accion: 'explorar', pagina, por_pagina: porPagina });
      if (q) params.set('q', q);
      return solicitar(`/etiquetas.php?${params}`);
    },

    crearGrupo(datos) {
      return solicitar('/grupos.php?accion=crear', {
        method: 'POST',
        body: JSON.stringify(datos),
      });
    },

    unirseGrupo(datos) {
      return solicitar('/grupos.php?accion=unirse', {
        method: 'POST',
        body: JSON.stringify(datos),
      });
    },

    darLike(grupoId) {
      return solicitar('/grupos.php?accion=like', {
        method: 'POST',
        body: JSON.stringify({ grupo_id: grupoId }),
      });
    },

    reportarGrupo(datos) {
      return solicitar('/reportes.php?accion=crear', {
        method: 'POST',
        body: JSON.stringify(datos),
      });
    },

    obtenerMiPais() {
      return solicitar('/visitante.php');
    },
  };
})();
