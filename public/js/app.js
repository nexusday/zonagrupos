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
    modal: document.getElementById('modal-publicar'),
    formulario: document.getElementById('formulario-grupo'),
    contadorDescripcion: document.getElementById('contador-descripcion'),
    vistaEtiquetasPrevia: document.getElementById('vista-etiquetas-previa'),
    sugerenciasBusqueda: document.getElementById('sugerencias-busqueda'),
    filtroActivo: document.getElementById('filtro-activo'),
    filtroActivoTexto: document.getElementById('filtro-activo-texto'),
    tituloListado: document.getElementById('titulo-listado'),
    explorarTemas: document.getElementById('explorar-temas'),
    gridEtiquetas: document.getElementById('grid-etiquetas'),
    inputExplorarEtiquetas: document.getElementById('input-explorar-etiquetas'),
    infoExplorarEtiquetas: document.getElementById('info-explorar-etiquetas'),
    btnMasEtiquetas: document.getElementById('btn-mas-etiquetas'),
    toastContenedor: document.getElementById('toast-contenedor'),
  };

  const explorarEtiquetasEstado = { pagina: 1, q: '', total: 0, cargado: false };

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
    whatsapp: 'https://chat.whatsapp.com/... o https://wa.me/...',
    telegram: 'https://t.me/...',
    discord: 'https://discord.gg/...',
  };

  const patronesEnlace = {
    whatsapp: /(chat\.whatsapp\.com|wa\.me)/i,
    telegram: /(t\.me|telegram\.me)/i,
    discord: /(discord\.gg|discord\.com\/invite)/i,
  };

  const mensajesEnlace = {
    whatsapp: 'El enlace debe ser de WhatsApp (chat.whatsapp.com o wa.me).',
    telegram: 'El enlace debe ser de Telegram (t.me).',
    discord: 'El enlace debe ser de Discord (discord.gg o discord.com/invite).',
  };

  function validarEnlacePlataforma(enlace, plataforma) {
    if (!patronesEnlace[plataforma]) return false;
    try {
      const url = new URL(enlace);
      if (!['http:', 'https:'].includes(url.protocol)) return false;
    } catch {
      return false;
    }
    return patronesEnlace[plataforma].test(enlace);
  }

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

  function renderizarEtiquetas(etiquetas, clickeable = true, mostrarUsos = false) {
    if (!etiquetas?.length) return '';
    return etiquetas.map((et) => {
      const attrs = clickeable
        ? `class="etiqueta" data-etiqueta="${escaparHtml(et.nombre)}" role="button" tabindex="0"`
        : `class="etiqueta etiqueta--solo"`;
      const usos = mostrarUsos && et.usos ? `<span class="etiqueta__usos">${formatearNumero(et.usos)}</span>` : '';
      return `<span ${attrs}>${escaparHtml(et.nombre)}${usos}</span>`;
    }).join('');
  }

  function renderizarEtiquetaEnlace(et) {
    return `<a href="/?busqueda=${encodeURIComponent(et.nombre)}" class="etiqueta etiqueta--enlace" role="listitem">
      <span class="etiqueta__nombre">${escaparHtml(et.nombre)}</span>
      <span class="etiqueta__usos">${formatearNumero(et.usos || 0)}</span>
    </a>`;
  }

  function actualizarFiltroActivo() {
    const partes = [];
    if (estado.etiqueta) partes.push(`tema: ${estado.etiqueta}`);
    if (estado.busqueda) partes.push(`"${estado.busqueda}"`);
    if (estado.plataforma) {
      partes.push(nombresPlataforma[estado.plataforma] || estado.plataforma);
    }

    if (partes.length) {
      elementos.filtroActivoTexto.textContent = partes.join(' · ');
      elementos.filtroActivo.hidden = false;
    } else {
      elementos.filtroActivo.hidden = true;
    }

    const titulos = {
      recientes: 'Grupos recientes',
      populares: 'Grupos populares',
      visitas: 'Más visitados',
    };
    let titulo = titulos[estado.orden] || 'Grupos';
    if (estado.etiqueta) titulo = `Tema: ${estado.etiqueta}`;
    else if (estado.busqueda) titulo = `Resultados: ${estado.busqueda}`;
    elementos.tituloListado.textContent = titulo;
  }

  function limpiarFiltros() {
    estado.busqueda = '';
    estado.etiqueta = '';
    estado.pagina = 1;
    elementos.inputBusqueda.value = '';
    ocultarSugerencias();
    document.querySelectorAll('.chip[data-plataforma]').forEach((c) => {
      c.classList.toggle('chip--activo', c.dataset.plataforma === '');
    });
    estado.plataforma = '';
    actualizarFiltroActivo();
    cargarGrupos();
  }

  function ocultarSugerencias() {
    elementos.sugerenciasBusqueda.hidden = true;
    elementos.inputBusqueda.setAttribute('aria-expanded', 'false');
    elementos.sugerenciasBusqueda.innerHTML = '';
  }

  async function mostrarSugerenciasEtiquetas(termino) {
    if (!termino || termino.length < 2 || termino.includes(' ')) {
      ocultarSugerencias();
      return;
    }
    try {
      const { etiquetas } = await ApiGrupos.buscarEtiquetas(termino.replace(/^#/, ''), 6);
      if (!etiquetas.length) {
        ocultarSugerencias();
        return;
      }
      elementos.sugerenciasBusqueda.innerHTML = etiquetas.map((et) => `
        <button type="button" class="sugerencias-busqueda__item" data-etiqueta="${escaparHtml(et.nombre)}" role="option">
          <span>${escaparHtml(et.nombre)}</span>
          <small>${formatearNumero(et.usos)} grupos</small>
        </button>
      `).join('');
      elementos.sugerenciasBusqueda.hidden = false;
      elementos.inputBusqueda.setAttribute('aria-expanded', 'true');
    } catch {
      ocultarSugerencias();
    }
  }

  function emojiBandera(codigo) {
    const c = (codigo || '').toUpperCase();
    if (!/^[A-Z]{2}$/.test(c) || c === 'LA') return null;
    return String.fromCodePoint(...[...c].map((l) => 0x1F1E6 - 65 + l.charCodeAt(0)));
  }

  function renderizarPaisListado(grupo) {
    if (grupo.restriccion_pais === 'solo_pais') {
      const flag = emojiBandera(grupo.pais?.codigo) || '📍';
      const nombre = grupo.pais?.nombre || 'País';
      return `<span class="tarjeta-lista__pais" title="Solo ${escaparHtml(nombre)}">
        <span class="tarjeta-lista__bandera" aria-hidden="true">${flag}</span>
        <span class="tarjeta-lista__pais-texto">${escaparHtml(nombre)}</span>
      </span>`;
    }
    return `<span class="tarjeta-lista__pais" title="Abierto para todos los países">
      <i data-lucide="globe" aria-hidden="true"></i>
      <span class="tarjeta-lista__pais-texto">Global</span>
    </span>`;
  }

  function crearTarjetaGrupo(grupo, indice) {
    const url = escaparHtml(grupo.url || `/grupo/grupo-${grupo.id}`);
    const tarjeta = document.createElement('a');
    tarjeta.href = url;
    tarjeta.className = 'tarjeta-lista';
    tarjeta.role = 'listitem';
    tarjeta.style.animationDelay = `${indice * 0.03}s`;

    tarjeta.innerHTML = `
      <span class="tarjeta-lista__plataforma tarjeta-lista__plataforma--${grupo.plataforma}" title="${nombresPlataforma[grupo.plataforma]}">
        <i data-lucide="${iconosPlataforma[grupo.plataforma]}"></i>
      </span>
      <span class="tarjeta-lista__nombre">${escaparHtml(grupo.nombre)}</span>
      ${renderizarPaisListado(grupo)}
      <i data-lucide="chevron-right" class="tarjeta-lista__flecha" aria-hidden="true"></i>
    `;

    return tarjeta;
  }

  function filtrarPorEtiqueta(nombre) {
    estado.etiqueta = nombre;
    estado.busqueda = '';
    estado.pagina = 1;
    elementos.inputBusqueda.value = nombre;
    ocultarSugerencias();
    actualizarFiltroActivo();
    cargarGrupos();
  }

  async function cargarGrupos() {
    if (estado.cargando) return;
    estado.cargando = true;
    alternarEstados({ carga: true });
    actualizarFiltroActivo();

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
      elementos.statGrupos.textContent = `Grupos totales: ${formatearNumero(estadisticas.total_grupos)}`;
    } catch {
      elementos.statGrupos.textContent = 'Grupos totales: —';
    }
  }

  async function cargarExplorarEtiquetas(reset = false) {
    if (reset) {
      explorarEtiquetasEstado.pagina = 1;
      elementos.gridEtiquetas.innerHTML = '';
    }

    const { etiquetas, paginacion } = await ApiGrupos.explorarEtiquetas({
      q: explorarEtiquetasEstado.q,
      pagina: explorarEtiquetasEstado.pagina,
      porPagina: 24,
    });

    explorarEtiquetasEstado.total = paginacion.total;
    explorarEtiquetasEstado.cargado = true;

    if (reset && !etiquetas.length) {
      elementos.gridEtiquetas.innerHTML = '<p class="explorar-temas__vacio">Sin temas con ese nombre.</p>';
    } else {
      elementos.gridEtiquetas.insertAdjacentHTML(
        'beforeend',
        etiquetas.map((et) => renderizarEtiquetaEnlace(et)).join('')
      );
    }

    const { pagina, total_paginas, total } = paginacion;
    elementos.infoExplorarEtiquetas.textContent = total
      ? `${total.toLocaleString('es')} temas · página ${pagina} de ${total_paginas}`
      : '';
    elementos.btnMasEtiquetas.hidden = pagina >= total_paginas;
  }

  function actualizarPreviaEtiquetas() {
    const tags = parsearEtiquetasInput(document.getElementById('campo-etiquetas').value);
    elementos.vistaEtiquetasPrevia.innerHTML = tags.length
      ? renderizarEtiquetas(tags.map((n) => ({ nombre: n })), false)
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
    document.body.classList.add('modal-abierto');
    const cuerpo = elementos.modal.querySelector('.modal__cuerpo');
    if (cuerpo) cuerpo.scrollTop = 0;
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
    document.body.classList.remove('modal-abierto');
  }

  function actualizarSelectorPlataforma() {
    document.querySelectorAll('.plataforma-opcion').forEach((opcion) => {
      const input = opcion.querySelector('input');
      opcion.classList.toggle('plataforma-opcion--activa', input.checked);
    });
    const plataforma = document.querySelector('input[name="plataforma"]:checked')?.value || 'whatsapp';
    const campoEnlace = document.getElementById('campo-enlace');
    if (campoEnlace) campoEnlace.placeholder = placeholdersEnlace[plataforma] || placeholdersEnlace.whatsapp;
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
    });

    document.getElementById('btn-limpiar-filtro').addEventListener('click', limpiarFiltros);

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
      if (e.key === 'Enter') {
        ocultarSugerencias();
        ejecutarBusqueda();
      }
      if (e.key === 'Escape') ocultarSugerencias();
    });

    let temporizadorBusqueda;
    let temporizadorSugerencias;
    elementos.inputBusqueda.addEventListener('input', () => {
      clearTimeout(temporizadorBusqueda);
      clearTimeout(temporizadorSugerencias);
      const valor = elementos.inputBusqueda.value.trim();
      temporizadorSugerencias = setTimeout(() => mostrarSugerenciasEtiquetas(valor), 200);
      temporizadorBusqueda = setTimeout(ejecutarBusqueda, 450);
    });

    elementos.sugerenciasBusqueda.addEventListener('click', (e) => {
      const item = e.target.closest('[data-etiqueta]');
      if (!item) return;
      filtrarPorEtiqueta(item.dataset.etiqueta);
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.busqueda-wrap')) ocultarSugerencias();
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
        actualizarFiltroActivo();
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

    elementos.modal.addEventListener('close', () => {
      document.body.classList.remove('modal-abierto');
    });

    elementos.modal.addEventListener('cancel', (e) => {
      e.preventDefault();
      cerrarModal();
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
        plataforma: document.querySelector('input[name="plataforma"]:checked')?.value || 'whatsapp',
        restriccion_pais: document.querySelector('input[name="restriccion_pais"]:checked')?.value || 'todos',
      };

      if (!['whatsapp', 'telegram', 'discord'].includes(datos.plataforma)) {
        mostrarToast('Solo puedes publicar grupos de WhatsApp, Telegram o Discord.', 'error');
        btnEnviar.disabled = false;
        return;
      }

      if (!validarEnlacePlataforma(datos.enlace, datos.plataforma)) {
        mostrarToast(mensajesEnlace[datos.plataforma] || 'Enlace no válido.', 'error');
        btnEnviar.disabled = false;
        return;
      }

      log('info', 'Publicando grupo', { nombre: datos.nombre, plataforma: datos.plataforma });

      try {
        const respuesta = await ApiGrupos.crearGrupo(datos);
        log('info', 'Grupo publicado', respuesta.grupo);
        mostrarToast(respuesta.mensaje, 'exito');
        cerrarModal();
        estado.pagina = 1;
        await Promise.all([cargarGrupos(), cargarEstadisticas()]);
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

    elementos.explorarTemas?.addEventListener('toggle', () => {
      if (elementos.explorarTemas.open && !explorarEtiquetasEstado.cargado) {
        cargarExplorarEtiquetas(true).catch(() => {});
      }
    });

    let timerExplorar;
    elementos.inputExplorarEtiquetas?.addEventListener('input', (e) => {
      clearTimeout(timerExplorar);
      timerExplorar = setTimeout(() => {
        explorarEtiquetasEstado.q = e.target.value.trim();
        cargarExplorarEtiquetas(true).catch(() => {});
      }, 350);
    });

    elementos.btnMasEtiquetas?.addEventListener('click', () => {
      explorarEtiquetasEstado.pagina += 1;
      cargarExplorarEtiquetas(false).catch(() => {});
    });

    document.addEventListener('click', (e) => {
      const etiqueta = e.target.closest('[data-etiqueta]');
      if (etiqueta && !etiqueta.closest('.sugerencias-busqueda')) {
        e.preventDefault();
        filtrarPorEtiqueta(etiqueta.dataset.etiqueta);
      }
    });
  }

  async function iniciar() {
    refrescarIconos();
    enlazarEventos();
    leerParametrosUrl();
    VisitanteGeo.iniciarBadge().then(() => actualizarAyudaPais());
    await cargarEstadisticas();
    actualizarFiltroActivo();
    await cargarGrupos();
  }

  document.addEventListener('DOMContentLoaded', iniciar);
})();
