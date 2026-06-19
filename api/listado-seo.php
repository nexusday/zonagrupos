<?php
declare(strict_types=1);

require_once __DIR__ . '/seo.php';

function escHtmlSeo(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderizarTarjetaGrupoSeo(array $grupo): string
{
    $slug = (string) ($grupo['slug'] ?? '');
    if ($slug === '') {
        return '';
    }

    $url = '/grupo/' . rawurlencode($slug);
    $nombre = escHtmlSeo($grupo['nombre'] ?? 'Grupo');
    $plataforma = escHtmlSeo(nombrePlataformaSeo($grupo['plataforma'] ?? 'whatsapp'));
    $plataformaClase = escHtmlSeo(preg_replace('/[^a-z]/', '', $grupo['plataforma'] ?? 'whatsapp') ?? 'whatsapp');
    $pais = trim($grupo['pais_nombre'] ?? '');
    $paisHtml = $pais !== '' && codigoPaisReal($grupo['pais_codigo'] ?? '')
        ? '<span class="tarjeta-lista__pais">' . escHtmlSeo($pais) . '</span>'
        : '';
    $adulto = ($grupo['clasificacion'] ?? 'normal') === 'adulto'
        ? '<span class="tarjeta-lista__badge-adulto">+18</span>'
        : '';

    return '<a href="' . $url . '" class="tarjeta-lista tarjeta-lista--ssr" role="listitem">'
        . '<span class="tarjeta-lista__plataforma tarjeta-lista__plataforma--' . $plataformaClase . '" title="' . $plataforma . '">'
        . $plataforma . '</span>'
        . '<span class="tarjeta-lista__nombre">' . $nombre . '</span>'
        . $adulto . $paisHtml
        . '</a>';
}

function resolverEtiquetaDesdeSlug(PDO $bd, string $slug): string
{
    $slug = mb_strtolower(trim($slug));
    if ($slug === '') {
        return '';
    }

    $stmt = $bd->query(
        'SELECT DISTINCT e.nombre
         FROM etiquetas e
         INNER JOIN grupo_etiquetas ge ON ge.etiqueta_id = e.id
         INNER JOIN grupos g ON g.id = ge.grupo_id AND g.activo = 1'
    );
    $filas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($filas as $nombre) {
        if (slugEtiqueta((string) $nombre) === $slug) {
            return (string) $nombre;
        }
    }

    return str_replace('-', ' ', $slug);
}

function renderizarEnlaceEtiquetaSeo(array $etiqueta): string
{
    $nombre = escHtmlSeo($etiqueta['nombre'] ?? '');
    $usos = (int) ($etiqueta['usos'] ?? 0);
    $href = urlEtiqueta($etiqueta['nombre'] ?? '');

    return '<a href="' . $href . '" class="etiqueta etiqueta--enlace" role="listitem">'
        . '<span class="etiqueta__nombre">' . $nombre . '</span>'
        . '<span class="etiqueta__usos">' . $usos . '</span>'
        . '</a>';
}

function renderizarEnlaceGrupoSeo(array $grupo): string
{
    $slug = (string) ($grupo['slug'] ?? '');
    if ($slug === '') {
        return '';
    }

    $url = '/grupo/' . rawurlencode($slug);
    $nombre = escHtmlSeo($grupo['nombre'] ?? 'Grupo');
    $plataforma = escHtmlSeo(nombrePlataformaSeo($grupo['plataforma'] ?? 'whatsapp'));

    return '<li><a href="' . $url . '">' . $nombre . '</a> <span class="mapa-enlaces__meta">(' . $plataforma . ')</span></li>';
}

function renderizarListaEnlacesGrupos(array $grupos): string
{
    $html = '';
    foreach ($grupos as $grupo) {
        $enlace = renderizarEnlaceGrupoSeo($grupo);
        if ($enlace !== '') {
            $html .= $enlace . "\n";
        }
    }

    return $html;
}

function datosSeoInicio(PDO $bd, int $limiteGrupos = 36, int $limiteEtiquetas = 24): array
{
    $total = (int) $bd->query('SELECT COUNT(*) FROM grupos WHERE activo = 1')->fetchColumn();

    $stmtGrupos = $bd->prepare(
        'SELECT id, nombre, slug, plataforma, pais_codigo, pais_nombre, clasificacion, descripcion, creado_en
         FROM grupos
         WHERE activo = 1 AND slug IS NOT NULL AND slug != \'\'
         ORDER BY creado_en DESC
         LIMIT :limite'
    );
    $stmtGrupos->bindValue(':limite', max(1, min($limiteGrupos, 100)), PDO::PARAM_INT);
    $stmtGrupos->execute();
    $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtEtiquetas = $bd->prepare(
        'SELECT e.nombre, COUNT(ge.grupo_id) AS usos
         FROM etiquetas e
         INNER JOIN grupo_etiquetas ge ON ge.etiqueta_id = e.id
         INNER JOIN grupos g ON g.id = ge.grupo_id AND g.activo = 1
         GROUP BY e.id, e.nombre
         HAVING usos > 0
         ORDER BY usos DESC, e.nombre ASC
         LIMIT :limite'
    );
    $stmtEtiquetas->bindValue(':limite', max(1, min($limiteEtiquetas, 60)), PDO::PARAM_INT);
    $stmtEtiquetas->execute();
    $etiquetas = $stmtEtiquetas->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $porPlataforma = ['whatsapp' => [], 'telegram' => [], 'discord' => []];
    foreach ($grupos as $grupo) {
        $plat = $grupo['plataforma'] ?? 'whatsapp';
        if (isset($porPlataforma[$plat]) && count($porPlataforma[$plat]) < 10) {
            $porPlataforma[$plat][] = $grupo;
        }
    }

    return [
        'total'     => $total,
        'grupos'    => $grupos,
        'etiquetas' => $etiquetas,
        'whatsapp'  => $porPlataforma['whatsapp'],
        'telegram'  => $porPlataforma['telegram'],
        'discord'   => $porPlataforma['discord'],
    ];
}
