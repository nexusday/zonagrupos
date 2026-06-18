/**
 * Banderas para la pc
 */
const Banderas = (() => {
  const CDN = 'https://flagcdn.com';

  function codigoValido(codigo) {
    const c = (codigo || '').toUpperCase();
    if (c === 'LAT') return null;
    return /^[A-Z]{2}$/.test(c) ? c : null;
  }

  function html(codigo, claseExtra = '') {
    const c = codigoValido(codigo);
    if (!c) return '';
    const iso = c.toLowerCase();
    const clase = ['bandera-icono', claseExtra].filter(Boolean).join(' ');
    return `<img src="${CDN}/w20/${iso}.png" srcset="${CDN}/w40/${iso}.png 2x" alt="" class="${clase}" width="20" height="15" loading="lazy" decoding="async">`;
  }

  function htmlOMapa(codigo, claseExtra = '') {
    return html(codigo, claseExtra) || '<i data-lucide="map-pin" class="bandera-icono--fallback" aria-hidden="true"></i>';
  }

  return { html, htmlOMapa, codigoValido };
})();
