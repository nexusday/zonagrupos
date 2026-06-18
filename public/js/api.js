/**
 * ZonaGrupos — Capa de comunicación con la API
 */
const ApiGrupos = (() => {
  const BASE = '/api';

  async function solicitar(ruta, opciones = {}) {
    const respuesta = await fetch(`${BASE}${ruta}`, {
      headers: { 'Content-Type': 'application/json', ...opciones.headers },
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

    obtenerMiPais() {
      return solicitar('/visitante.php');
    },
  };
})();
