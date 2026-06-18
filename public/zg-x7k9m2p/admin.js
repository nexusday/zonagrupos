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
      if (tab.dataset.tab === 'reportes') cargarReportes();
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
      tbody.innerHTML = '<tr><td colspan="8">Sin grupos</td></tr>';
      return;
    }

    tbody.innerHTML = grupos.map((g) => `
      <tr class="${g.activo ? '' : 'inactivo'}">
        <td>
          <a href="${esc(g.url)}" target="_blank" rel="noopener">${esc(g.nombre)}</a>
          <br><small style="color:#9494a8">${esc(g.slug)}</small>
        </td>
        <td>${esc(g.plataforma)}</td>
        <td>${esc(g.pais?.nombre || '—')}</td>
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

  verificarSesion();
})();
