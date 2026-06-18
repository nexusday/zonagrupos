(() => {
  'use strict';

  const API = '/api/admin.php';

  const TOKEN_KEY = 'zg_admin_token';

  function obtenerToken() {
    return sessionStorage.getItem(TOKEN_KEY) || '';
  }

  function guardarToken(token) {
    sessionStorage.setItem(TOKEN_KEY, token);
  }

  function borrarToken() {
    sessionStorage.removeItem(TOKEN_KEY);
  }

  async function api(accion, opciones = {}) {
    let url = `${API}?accion=${encodeURIComponent(accion)}`;
    if (opciones.query) {
      Object.entries(opciones.query).forEach(([k, v]) => {
        url += `&${encodeURIComponent(k)}=${encodeURIComponent(v)}`;
      });
    }
    const token = obtenerToken();
    const headers = {
      'Content-Type': 'application/json',
      ...(token ? { 'X-Admin-Token': token } : {}),
      ...opciones.headers,
    };
    const resp = await fetch(url, { ...opciones, headers });
    const datos = await resp.json();
    if (!resp.ok || datos.exito === false) {
      throw new Error(datos.mensaje || 'Error');
    }
    return datos;
  }

  function esc(texto) {
    const d = document.createElement('div');
    d.textContent = texto ?? '';
    return d.innerHTML;
  }

  function fmtFecha(iso) {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('es', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));
  }

  const motivos = {
    spam: 'Spam',
    inapropiado: 'Inapropiado',
    enlace_roto: 'Enlace roto',
    estafa: 'Estafa',
    otro: 'Otro',
  };

  const vistas = {
    login: document.getElementById('vista-login'),
    panel: document.getElementById('vista-panel'),
  };

  async function verificarSesion() {
    if (!obtenerToken()) return;
    try {
      const { autenticado } = await api('sesion', { method: 'GET' });
      if (autenticado) mostrarPanel();
      else borrarToken();
    } catch { borrarToken(); }
  }

  function mostrarPanel() {
    vistas.login.hidden = true;
    vistas.panel.hidden = false;
    cargarTodo();
  }

  document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();
    const err = document.getElementById('login-error');
    err.hidden = true;
    try {
      const datos = await api('login', {
        method: 'POST',
        body: JSON.stringify({
          usuario: document.getElementById('login-usuario').value.trim(),
          clave: document.getElementById('login-clave').value,
        }),
      });
      guardarToken(datos.token);
      mostrarPanel();
    } catch (ex) {
      err.textContent = ex.message;
      err.hidden = false;
    }
  });

  document.getElementById('btn-logout').addEventListener('click', async () => {
    try { await api('logout', { method: 'POST', body: '{}' }); } catch { /* */ }
    borrarToken();
    vistas.panel.hidden = true;
    vistas.login.hidden = false;
  });

  document.querySelectorAll('.admin-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.admin-tab').forEach((t) => t.classList.remove('admin-tab--activo'));
      tab.classList.add('admin-tab--activo');
      document.getElementById('tab-grupos').hidden = tab.dataset.tab !== 'grupos';
      document.getElementById('tab-reportes').hidden = tab.dataset.tab !== 'reportes';
      document.getElementById('tab-correo').hidden = tab.dataset.tab !== 'correo';
      if (tab.dataset.tab === 'reportes') cargarReportes();
      if (tab.dataset.tab === 'correo') cargarCorreoPanel();
    });
  });

  document.getElementById('select-orden-grupos').addEventListener('change', cargarGrupos);
  document.getElementById('check-inactivos').addEventListener('change', cargarGrupos);
  document.getElementById('select-estado-reportes').addEventListener('change', cargarReportes);

  async function cargarEstadisticas() {
    const { estadisticas: s } = await api('estadisticas', { method: 'GET' });
    document.getElementById('admin-stats').innerHTML = `
      <div class="admin-stat"><strong>${s.grupos_activos}</strong><span>Grupos activos</span></div>
      <div class="admin-stat"><strong>${s.reportes_pendientes}</strong><span>Reportes pend.</span></div>
      <div class="admin-stat"><strong>${s.grupos_eliminados}</strong><span>Eliminados</span></div>
      <div class="admin-stat"><strong>${s.reportes_total}</strong><span>Reportes total</span></div>
    `;
    const badge = document.getElementById('badge-reportes');
    if (s.reportes_pendientes > 0) {
      badge.textContent = s.reportes_pendientes;
      badge.hidden = false;
    } else {
      badge.hidden = true;
    }
  }

  async function cargarGrupos() {
    const orden = document.getElementById('select-orden-grupos').value;
    const todos = document.getElementById('check-inactivos').checked ? '1' : '0';
    const { grupos } = await api('grupos', { method: 'GET', query: { orden, todos } });

    const tbody = document.getElementById('cuerpo-grupos');
    if (!grupos.length) {
      tbody.innerHTML = '<tr><td colspan="10">Sin grupos</td></tr>';
      return;
    }

    tbody.innerHTML = grupos.map((g) => `
      <tr class="${g.activo ? '' : 'inactivo'}">
        <td>
          <a href="${esc(g.url)}" target="_blank" rel="noopener">${esc(g.nombre)}</a>
          <br><small style="color:#9494a8">${esc(g.slug)}</small>
        </td>
        <td><small>${esc(g.correo_publicador || '—')}</small></td>
        <td>${esc(g.plataforma)}</td>
        <td>${esc(g.pais?.nombre || '—')}</td>
        <td>
          <select class="admin-select-clasificacion ${g.clasificacion === 'adulto' ? 'admin-select-clasificacion--adulto' : ''}"
                  data-clasificacion="${g.id}" aria-label="Tipo de contenido de ${esc(g.nombre)}">
            <option value="normal" ${g.clasificacion !== 'adulto' ? 'selected' : ''}>General</option>
            <option value="adulto" ${g.clasificacion === 'adulto' ? 'selected' : ''}>+18</option>
          </select>
        </td>
        <td>${g.likes}</td>
        <td>${g.visitas}</td>
        <td>${g.reportes_pendientes > 0 ? `<span class="badge-reporte">${g.reportes_pendientes}</span>` : '0'}</td>
        <td>${fmtFecha(g.creado_en)}</td>
        <td class="admin-acciones-celda">
          ${g.activo
            ? `<button class="btn btn--peligro btn--mini" data-eliminar="${g.id}">Eliminar</button>`
            : `<button class="btn btn--ok btn--mini" data-restaurar="${g.id}">Restaurar</button>`}
        </td>
      </tr>
    `).join('');

    tbody.querySelectorAll('[data-eliminar]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este grupo?')) return;
        await api('eliminar', { method: 'POST', body: JSON.stringify({ grupo_id: +btn.dataset.eliminar }) });
        cargarTodo();
      });
    });

    tbody.querySelectorAll('[data-restaurar]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        await api('restaurar', { method: 'POST', body: JSON.stringify({ grupo_id: +btn.dataset.restaurar }) });
        cargarTodo();
      });
    });

    tbody.querySelectorAll('[data-clasificacion]').forEach((select) => {
      select.addEventListener('change', async () => {
        const grupoId = +select.dataset.clasificacion;
        const valor = select.value;
        const anterior = valor === 'adulto' ? 'normal' : 'adulto';
        select.disabled = true;
        try {
          await api('clasificacion', {
            method: 'POST',
            body: JSON.stringify({ grupo_id: grupoId, clasificacion: valor }),
          });
          select.classList.toggle('admin-select-clasificacion--adulto', valor === 'adulto');
        } catch (ex) {
          select.value = anterior;
          alert(ex.message || 'No se pudo guardar');
        } finally {
          select.disabled = false;
        }
      });
    });
  }

  async function cargarReportes() {
    const estado = document.getElementById('select-estado-reportes').value;
    const { reportes } = await api('reportes', { method: 'GET', query: { estado } });
    const tbody = document.getElementById('cuerpo-reportes');

    if (!reportes.length) {
      tbody.innerHTML = '<tr><td colspan="6">Sin reportes</td></tr>';
      return;
    }

    tbody.innerHTML = reportes.map((r) => `
      <tr>
        <td><a href="/grupo/${esc(r.grupo_slug)}" target="_blank">${esc(r.grupo_nombre)}</a></td>
        <td>${esc(motivos[r.motivo] || r.motivo)}</td>
        <td>${esc(r.detalle || '—')}</td>
        <td>${fmtFecha(r.creado_en)}</td>
        <td>${esc(r.estado)}</td>
        <td class="admin-acciones-celda">
          ${r.estado === 'pendiente' ? `
            <button class="btn btn--ok btn--mini" data-rev="${r.id}">Revisado</button>
            <button class="btn btn--fantasma btn--mini" data-desc="${r.id}">Descartar</button>
            <button class="btn btn--peligro btn--mini" data-delg="${r.grupo_id}">Eliminar grupo</button>
          ` : ''}
        </td>
      </tr>
    `).join('');

    tbody.querySelectorAll('[data-rev]').forEach((b) => {
      b.addEventListener('click', () => actualizarReporte(+b.dataset.rev, 'revisado'));
    });
    tbody.querySelectorAll('[data-desc]').forEach((b) => {
      b.addEventListener('click', () => actualizarReporte(+b.dataset.desc, 'descartado'));
    });
    tbody.querySelectorAll('[data-delg]').forEach((b) => {
      b.addEventListener('click', async () => {
        if (!confirm('¿Eliminar el grupo reportado?')) return;
        await api('eliminar', { method: 'POST', body: JSON.stringify({ grupo_id: +b.dataset.delg }) });
        cargarTodo();
      });
    });
  }

  async function actualizarReporte(id, estado) {
    await api('reporte_estado', { method: 'POST', body: JSON.stringify({ id, estado }) });
    cargarTodo();
  }

  async function cargarTodo() {
    await Promise.all([cargarEstadisticas(), cargarGrupos()]);
    if (!document.getElementById('tab-reportes').hidden) cargarReportes();
  }

  async function cargarCorreoPanel() {
    await Promise.all([cargarCorreoConfig(), cargarListaCorreos()]);
  }

  async function cargarCorreoConfig() {
    const info = document.getElementById('correo-desde-info');
    const btn = document.getElementById('btn-enviar-correo');
    try {
      const datos = await api('correo_config', { method: 'GET' });
      if (datos.configurado) {
        const total = datos.total_correos ?? 0;
        info.textContent = `Desde: ${datos.nombre_remitente} <${datos.desde}> · ${total} correo${total === 1 ? '' : 's'} guardados`;
        btn.disabled = false;
      } else {
        info.textContent = 'El envío de correo no está configurado en el servidor.';
        btn.disabled = true;
      }
    } catch {
      info.textContent = 'No se pudo cargar la configuración del correo.';
      btn.disabled = true;
    }
  }

  function obtenerCorreosSeleccionados() {
    return [...document.querySelectorAll('.correo-check:checked')].map((c) => c.value);
  }

  function actualizarContadorCorreos() {
    const total = document.querySelectorAll('.correo-check').length;
    const marcados = document.querySelectorAll('.correo-check:checked').length;
    const contador = document.getElementById('correo-contador');
    const todos = document.getElementById('correo-enviar-todos').checked;
    if (todos) {
      contador.textContent = `Enviarás a los ${total} correos`;
    } else if (marcados > 0) {
      contador.textContent = `${marcados} de ${total} seleccionados`;
    } else {
      contador.textContent = `${total} correo${total === 1 ? '' : 's'}`;
    }

    const selTodos = document.getElementById('correo-seleccionar-todos');
    if (total === 0) {
      selTodos.checked = false;
      selTodos.indeterminate = false;
      return;
    }
    selTodos.checked = marcados === total;
    selTodos.indeterminate = marcados > 0 && marcados < total;
  }

  async function cargarListaCorreos() {
    const tbody = document.getElementById('cuerpo-correos');
    try {
      const { correos } = await api('correos', { method: 'GET' });
      if (!correos.length) {
        tbody.innerHTML = '<tr><td colspan="5">Aún no hay correos. Se guardan al publicar grupos.</td></tr>';
        actualizarContadorCorreos();
        return;
      }

      tbody.innerHTML = correos.map((c) => `
        <tr>
          <td><input type="checkbox" class="correo-check" value="${esc(c.correo)}"></td>
          <td>${esc(c.correo)}</td>
          <td>${c.grupos_publicados}</td>
          <td>${esc(c.ultimo_grupo_nombre || '—')}</td>
          <td>${fmtFecha(c.actualizado_en)}</td>
        </tr>
      `).join('');

      tbody.querySelectorAll('.correo-check').forEach((check) => {
        check.addEventListener('change', () => {
          if (document.getElementById('correo-enviar-todos').checked) {
            document.getElementById('correo-enviar-todos').checked = false;
            document.querySelectorAll('.correo-check').forEach((c) => { c.disabled = false; });
          }
          actualizarContadorCorreos();
        });
      });
      actualizarContadorCorreos();
    } catch (ex) {
      tbody.innerHTML = `<tr><td colspan="5">${esc(ex.message)}</td></tr>`;
    }
  }

  document.getElementById('correo-seleccionar-todos').addEventListener('change', (e) => {
    if (document.getElementById('correo-enviar-todos').checked) return;
    const marcar = e.target.checked;
    document.querySelectorAll('.correo-check').forEach((c) => { c.checked = marcar; });
    actualizarContadorCorreos();
  });

  document.getElementById('correo-enviar-todos').addEventListener('change', (e) => {
    const activo = e.target.checked;
    document.getElementById('correo-seleccionar-todos').disabled = activo;
    document.querySelectorAll('.correo-check').forEach((c) => {
      c.disabled = activo;
      if (activo) c.checked = false;
    });
    actualizarContadorCorreos();
  });

  document.getElementById('form-correo').addEventListener('submit', async (e) => {
    e.preventDefault();
    const estado = document.getElementById('correo-estado');
    const btn = document.getElementById('btn-enviar-correo');
    const enviarTodos = document.getElementById('correo-enviar-todos').checked;
    const seleccionados = obtenerCorreosSeleccionados();
    const manual = document.getElementById('correo-para').value.trim();

    if (!enviarTodos && seleccionados.length === 0 && !manual) {
      alert('Selecciona correos de la lista, marca «todos» o escribe uno adicional.');
      return;
    }

    if (enviarTodos) {
      const total = document.querySelectorAll('.correo-check').length;
      if (total === 0 && !manual) {
        alert('No hay correos en la lista.');
        return;
      }
      if (!confirm(`¿Enviar este mensaje a los ${total} correos de la lista?`)) return;
    } else if (seleccionados.length > 1) {
      if (!confirm(`¿Enviar a ${seleccionados.length} correos seleccionados?`)) return;
    }

    estado.hidden = true;
    estado.classList.remove('admin-correo__estado--error');
    btn.disabled = true;

    const cuerpo = {
      para: manual,
      asunto: document.getElementById('correo-asunto').value.trim(),
      mensaje: document.getElementById('correo-mensaje').value.trim(),
      todos: enviarTodos,
      correos: seleccionados,
    };

    try {
      const datos = await api('enviar_correo', {
        method: 'POST',
        body: JSON.stringify(cuerpo),
      });
      estado.textContent = datos.mensaje || 'Correos enviados.';
      estado.hidden = false;
      document.getElementById('correo-mensaje').value = '';
    } catch (ex) {
      estado.textContent = ex.message || 'No se pudo enviar.';
      estado.classList.add('admin-correo__estado--error');
      estado.hidden = false;
    } finally {
      btn.disabled = false;
    }
  });

  verificarSesion();
})();
