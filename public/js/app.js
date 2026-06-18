/**
 * ZonaGrupos — Lógica principal de la interfaz
 */
(() => {
  'use strict';

  const log = (nivel, msg, datos) => {
    console[nivel === 'error' ? 'error' : 'log'](`[ZonaGrupos] ${msg}`, datos || '');
  };

  const estado = {
    busqueda: '',
    plataforma: '',
    etiqueta: '',
    orden: 'recientes',
    pagina: 1,
    cargando: false,
  };

  const elementos = {
    grilla: document.getElementById('grilla-grupos'),
    carga: document.getElementById('estado-carga'),
    vacio: document.getElementById('estado-vacio'),
    error: document.getElementById('estado-error'),
    mensajeError: document.getElementById('mensaje-error'),
    paginacion: document.getElementById('paginacion'),
    infoPaginacion: document.getElementById('info-paginacion'),
    btnPagAnterior: document.getElementById('btn-pag-anterior'),
    btnPagSiguiente: document.getElementById('btn-pag-siguiente'),
    inputBusqueda: document.getElementById('input-busqueda'),
    selectOrden: document.getElementById('select-orden'),
    statGrupos: document.getElementById('stat-grupos'),
    statLikes: document.getElementById('stat-likes'),
    statVisitas: document.getElementById('stat-visitas'),
    modal: document.getElementById('modal-publicar'),
    formulario: document.getElementById('formulario-grupo'),
    contadorDescripcion: document.getElementById('contador-descripcion'),
    vistaEtiquetasPrevia: document.getElementById('vista-etiquetas-previa'),
    tendencias: document.getElementById('tendencias'),
    listaTendencias: document.getElementById('lista-tendencias'),
    toastContenedor: document.getElementById('toast-contenedor'),
  };

  const iconosPlataforma = {
    whatsapp: 'message-circle',
    telegram: 'send',
    discord: 'headphones',
    otro: 'link',
  };

  const nombresPlataforma = {
    whatsapp: 'WhatsApp',
    telegram: 'Telegram',
    discord: 'Discord',
    otro: 'Otro',
  };

  const placeholdersEnlace = {
    whatsapp: 'https://chat.whatsapp.com/...',
    telegram: 'https://t.me/...',
    discord: 'https://discord.gg/...',
    otro: 'https://...',
  };

  function parsearEtiquetasInput(texto) {
    if (!texto?.trim()) return [];
    const unicas = new Set();
    texto.split(/[\s,;]+/).forEach((t) => {
      const nombre = t.trim().replace(/^#+/, '').toLowerCase();
      if (nombre.length >= 2 && nombre.length <= 30 && /^[\w\u00C0-\u024F_]+$/u.test(nombre)) {
        unicas.add(nombre);
      }
    });
    return [...unicas];
  }

  function formatearNumero(numero) {
    if (numero >= 1000000) return (numero / 1000000).toFixed(1) + 'M';
    if (numero >= 1000) return (numero / 1000).toFixed(1) + 'K';
    return numero.toString();
  }

  function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  }

  function mostrarToast(mensaje, tipo = 'exito') {
    const toast = document.createElement('div');
    toast.className = `toast toast--${tipo}`;
    toast.textContent = mensaje;
    elementos.toastContenedor.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  function refrescarIconos(contenedor = document) {
    if (typeof lucide !== 'undefined') {
      lucide.createIcons({ nodes: [contenedor] });
    }
  }

  function alternarEstados({ carga = false, vacio = false, error = false }) {
    elementos.carga.hidden = !carga;
    elementos.vacio.hidden = !vacio;
    elementos.error.hidden = !error;
    elementos.grilla.hidden = vacio || error;
    elementos.paginacion.hidden = vacio || error;
  }

  function renderizarEtiquetas(etiquetas, clickeable = true) {
    if (!etiquetas?.length) return '';
    return etiquetas.map((et) => {
      const color = et.color || '#7c3aed';
      const attrs = clickeable
        ? `class="etiqueta-hash" data-etiqueta="${escaparHtml(et.nombre)}" role="button" tabindex="0"`
        : `class="etiqueta-hash etiqueta-hash--solo"`;
      return `<span ${attrs} style="--color-etiqueta:${color}">${escaparHtml(et.nombre)}</span>`;
    }).join('');
  }

  function crearTarjetaGrupo(grupo, indice) {
    const tarjeta = document.createElement('article');
    tarjeta.className = 'tarjeta-grupo';
    tarjeta.role = 'listitem';
    tarjeta.style.animationDelay = `${indice * 0.05}s`;

    const etiquetasHtml = grupo.etiquetas?.length
      ? `<div class="tarjeta-grupo__etiquetas">${renderizarEtiquetas(grupo.etiquetas)}</div>`
      : '';

    tarjeta.innerHTML = `
      <div class="tarjeta-grupo__cabecera">
        <div class="tarjeta-grupo__info">
          <h3 class="tarjeta-grupo__nombre" title="${escaparHtml(grupo.nombre)}">${escaparHtml(grupo.nombre)}</h3>
          <p class="tarjeta-grupo__descripcion">${escaparHtml(grupo.descripcion || '')}</p>
        </div>
        <div class="tarjeta-grupo__meta">
          <span class="badge-plataforma badge-plataforma--${grupo.plataforma}">
            <i data-lucide="${iconosPlataforma[grupo.plataforma]}"></i>
            ${nombresPlataforma[grupo.plataforma]}
          </span>
          <span class="tarjeta-grupo__visitas" title="Visitas">
            <i data-lucide="eye"></i> ${formatearNumero(grupo.visitas)}
          </span>
        </div>
      </div>
      ${etiquetasHtml}
      <div class="tarjeta-grupo__acciones">
        <button class="btn btn--like ${grupo.ya_dio_like ? 'activo' : ''}"
                data-like="${grupo.id}" aria-label="Dar like">
          <i data-lucide="heart"></i>
          <span>${formatearNumero(grupo.likes)}</span>
        </button>
        <a href="${escaparHtml(grupo.url || '/grupo/grupo-' + grupo.id)}"
           class="btn btn--unirse ${grupo.plataforma}">
          <i data-lucide="external-link"></i>
          Ver grupo
        </a>
      </div>
    `;

    return tarjeta;
  }

  function filtrarPorEtiqueta(nombre) {
    estado.etiqueta = nombre;
    estado.busqueda = '';
    estado.pagina = 1;
    elementos.inputBusqueda.value = nombre;
    cargarGrupos();
    document.querySelectorAll('.etiqueta-hash--activa').forEach((el) =>
      el.classList.remove('etiqueta-hash--activa')
    );
    document.querySelectorAll(`[data-etiqueta="${nombre}"]`).forEach((el) =>
      el.classList.add('etiqueta-hash--activa')
    );
  }

  async function cargarGrupos() {
    if (estado.cargando) return;
    estado.cargando = true;
    alternarEstados({ carga: true });

    try {
      const respuesta = await ApiGrupos.obtenerGrupos({
        busqueda: estado.busqueda,
        plataforma: estado.plataforma,
        etiqueta: estado.etiqueta,
        orden: estado.orden,
        pagina: estado.pagina,
      });

      elementos.grilla.innerHTML = '';

      if (respuesta.grupos.length === 0) {
        alternarEstados({ vacio: true });
      } else {
        alternarEstados({});
        respuesta.grupos.forEach((grupo, i) => {
          elementos.grilla.appendChild(crearTarjetaGrupo(grupo, i));
        });
        refrescarIconos(elementos.grilla);
        actualizarPaginacion(respuesta.paginacion);
      }
    } catch (err) {
      elementos.mensajeError.textContent = err.message;
      alternarEstados({ error: true });
    } finally {
      estado.cargando = false;
    }
  }

  function actualizarPaginacion(pag) {
    const { pagina, total_paginas, total } = pag;
    elementos.paginacion.hidden = total_paginas <= 1;
    elementos.infoPaginacion.textContent = `Página ${pagina} de ${total_paginas} (${total} grupos)`;
    elementos.btnPagAnterior.disabled = pagina <= 1;
    elementos.btnPagSiguiente.disabled = pagina >= total_paginas;
  }

  async function cargarEstadisticas() {
    try {
      const { estadisticas } = await ApiGrupos.obtenerEstadisticas();
      elementos.statGrupos.textContent = formatearNumero(estadisticas.total_grupos);
      elementos.statLikes.textContent = formatearNumero(estadisticas.total_likes);
      elementos.statVisitas.textContent = formatearNumero(estadisticas.total_visitas);
    } catch {
      ['statGrupos', 'statLikes', 'statVisitas'].forEach((id) => {
        elementos[id].textContent = '0';
      });
    }
  }

  async function cargarTendencias() {
    try {
      const { etiquetas } = await ApiGrupos.obtenerEtiquetasTendencia();
      if (!etiquetas.length) {
        elementos.tendencias.hidden = true;
        return;
      }
      elementos.listaTendencias.innerHTML = renderizarEtiquetas(etiquetas);
      elementos.tendencias.hidden = false;
      refrescarIconos(elementos.tendencias);
    } catch {
      elementos.tendencias.hidden = true;
    }
  }

  function actualizarPreviaEtiquetas() {
    const tags = parsearEtiquetasInput(document.getElementById('campo-etiquetas').value);
    elementos.vistaEtiquetasPrevia.innerHTML = tags.length
      ? renderizarEtiquetas(tags.map((n) => ({ nombre: n, color: '#7c3aed' })), false)
      : '';
  }

  function abrirModal() {
    elementos.formulario.reset();
    elementos.contadorDescripcion.textContent = '0';
    elementos.vistaEtiquetasPrevia.innerHTML = '';
    actualizarSelectorPlataforma();
    actualizarSelectorRestriccion();
    actualizarAyudaPais();
    elementos.modal.showModal();
    refrescarIconos(elementos.modal);
  }

  function actualizarAyudaPais() {
    const ayuda = document.getElementById('ayuda-pais-detectado');
    if (!ayuda) return;
    const datos = typeof VisitanteGeo !== 'undefined' ? VisitanteGeo.obtener() : null;
    const nombre = datos?.pais?.nombre;
    if (nombre) {
      ayuda.textContent = `Tu país detectado: ${nombre}. Con "Solo mi país" solo entran personas de ${nombre}.`;
    } else {
      ayuda.textContent = 'Detectamos tu país por IP al publicar.';
    }
  }

  function cerrarModal() {
    elementos.modal.close();
  }

  function actualizarSelectorPlataforma() {
    document.querySelectorAll('.plataforma-opcion').forEach((opcion) => {
      const input = opcion.querySelector('input');
      opcion.classList.toggle('plataforma-opcion--activa', input.checked);
    });
    const plataforma = document.querySelector('input[name="plataforma"]:checked')?.value || 'whatsapp';
    const campoEnlace = document.getElementById('campo-enlace');
    if (campoEnlace) campoEnlace.placeholder = placeholdersEnlace[plataforma] || 'https://...';
  }

  function actualizarSelectorRestriccion() {
    document.querySelectorAll('.restriccion-opcion').forEach((opcion) => {
      const input = opcion.querySelector('input');
      opcion.classList.toggle('restriccion-opcion--activa', input.checked);
    });
  }

  function leerParametrosUrl() {
    const params = new URLSearchParams(window.location.search);
    const busqueda = params.get('busqueda')?.trim() || '';
    if (!busqueda) return;
    estado.busqueda = busqueda;
    elementos.inputBusqueda.value = busqueda;
    if (busqueda.startsWith('#')) {
      estado.etiqueta = busqueda.slice(1).split(/\s/)[0].toLowerCase();
      estado.busqueda = '';
    } else if (busqueda && !busqueda.includes(' ')) {
      estado.etiqueta = busqueda.toLowerCase();
      estado.busqueda = '';
    }
    estado.pagina = 1;
  }

  function enlazarEventos() {
    document.getElementById('btn-publicar').addEventListener('click', abrirModal);
    document.getElementById('btn-publicar-vacio')?.addEventListener('click', abrirModal);
    document.getElementById('btn-cerrar-modal').addEventListener('click', cerrarModal);
    document.getElementById('btn-cancelar').addEventListener('click', cerrarModal);
    document.getElementById('btn-reintentar').addEventListener('click', () => {
      cargarGrupos();
      cargarEstadisticas();
      cargarTendencias();
    });

    const ejecutarBusqueda = () => {
      const valor = elementos.inputBusqueda.value.trim();
      estado.pagina = 1;

      if (valor.startsWith('#')) {
        estado.etiqueta = valor.slice(1).split(/\s/)[0].toLowerCase();
        estado.busqueda = '';
      } else if (valor && !valor.includes(' ')) {
        estado.etiqueta = valor.toLowerCase();
        estado.busqueda = '';
      } else {
        estado.busqueda = valor;
        estado.etiqueta = '';
      }

      cargarGrupos();
    };

    document.getElementById('btn-buscar').addEventListener('click', ejecutarBusqueda);
    elementos.inputBusqueda.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') ejecutarBusqueda();
    });

    let temporizadorBusqueda;
    elementos.inputBusqueda.addEventListener('input', () => {
      clearTimeout(temporizadorBusqueda);
      temporizadorBusqueda = setTimeout(ejecutarBusqueda, 400);
    });

    document.getElementById('btn-buscar-movil').addEventListener('click', () => {
      document.getElementById('busqueda-principal').scrollIntoView({ behavior: 'smooth' });
      elementos.inputBusqueda.focus();
    });

    document.querySelectorAll('.chip[data-plataforma]').forEach((chip) => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.chip[data-plataforma]').forEach((c) => c.classList.remove('chip--activo'));
        chip.classList.add('chip--activo');
        estado.plataforma = chip.dataset.plataforma;
        estado.pagina = 1;
        cargarGrupos();
      });
    });

    elementos.selectOrden.addEventListener('change', () => {
      estado.orden = elementos.selectOrden.value;
      estado.pagina = 1;
      cargarGrupos();
    });

    elementos.btnPagAnterior.addEventListener('click', () => {
      if (estado.pagina > 1) {
        estado.pagina--;
        cargarGrupos();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });

    elementos.btnPagSiguiente.addEventListener('click', () => {
      estado.pagina++;
      cargarGrupos();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.querySelectorAll('input[name="plataforma"]').forEach((input) => {
      input.addEventListener('change', actualizarSelectorPlataforma);
    });

    document.querySelectorAll('input[name="restriccion_pais"]').forEach((input) => {
      input.addEventListener('change', actualizarSelectorRestriccion);
    });

    const campoDescripcion = document.getElementById('campo-descripcion');
    const campoEtiquetas = document.getElementById('campo-etiquetas');
    campoDescripcion.addEventListener('input', (e) => {
      elementos.contadorDescripcion.textContent = e.target.value.length;
    });
    campoEtiquetas.addEventListener('input', actualizarPreviaEtiquetas);

    elementos.formulario.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btnEnviar = document.getElementById('btn-enviar');
      btnEnviar.disabled = true;

      const descripcion = campoDescripcion.value.trim();
      const etiquetas = parsearEtiquetasInput(campoEtiquetas.value);

      if (!descripcion) {
        mostrarToast('Agrega una descripción del grupo', 'error');
        btnEnviar.disabled = false;
        return;
      }
      if (etiquetas.length === 0) {
        mostrarToast('Agrega al menos una etiqueta, ej: gaming, amigos', 'error');
        btnEnviar.disabled = false;
        return;
      }
      if (etiquetas.length > 10) {
        mostrarToast('Máximo 10 etiquetas', 'error');
        btnEnviar.disabled = false;
        return;
      }

      const datos = {
        nombre: document.getElementById('campo-nombre').value.trim(),
        descripcion,
        etiquetas,
        enlace: document.getElementById('campo-enlace').value.trim(),
        plataforma: document.querySelector('input[name="plataforma"]:checked').value,
        restriccion_pais: document.querySelector('input[name="restriccion_pais"]:checked')?.value || 'todos',
      };

      log('info', 'Publicando grupo', { nombre: datos.nombre, plataforma: datos.plataforma });

      try {
        const respuesta = await ApiGrupos.crearGrupo(datos);
        log('info', 'Grupo publicado', respuesta.grupo);
        mostrarToast(respuesta.mensaje, 'exito');
        cerrarModal();
        estado.pagina = 1;
        await Promise.all([cargarGrupos(), cargarEstadisticas(), cargarTendencias()]);
        if (respuesta.grupo?.url) {
          setTimeout(() => { window.location.href = respuesta.grupo.url; }, 800);
        }
      } catch (err) {
        log('error', 'Error al publicar', err.message);
        mostrarToast(err.message, 'error');
      } finally {
        btnEnviar.disabled = false;
      }
    });

    document.addEventListener('click', (e) => {
      const etiqueta = e.target.closest('[data-etiqueta]');
      if (etiqueta) {
        e.preventDefault();
        filtrarPorEtiqueta(etiqueta.dataset.etiqueta);
      }
    });

    elementos.grilla.addEventListener('click', async (e) => {
      const btnLike = e.target.closest('[data-like]');
      if (btnLike) {
        e.preventDefault();
        const grupoId = parseInt(btnLike.dataset.like, 10);
        if (btnLike.classList.contains('activo')) return;

        try {
          const respuesta = await ApiGrupos.darLike(grupoId);
          btnLike.classList.add('activo');
          btnLike.querySelector('span').textContent = formatearNumero(respuesta.likes);
          cargarEstadisticas();
        } catch (err) {
          mostrarToast(err.message, 'error');
        }
        return;
      }
    });
  }

  async function iniciar() {
    refrescarIconos();
    enlazarEventos();
    leerParametrosUrl();
    VisitanteGeo.iniciarBadge().then(() => actualizarAyudaPais());
    await Promise.all([cargarEstadisticas(), cargarTendencias()]);
    await cargarGrupos();
  }

  document.addEventListener('DOMContentLoaded', iniciar);
})();
