<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/entorno.php';
require_once dirname(__DIR__) . '/api/conexion.php';
require_once dirname(__DIR__) . '/api/seo.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$base = urlBaseApp();
$ahora = date('c');

$urls = [
    [
        'loc'        => $base . '/',
        'changefreq' => 'daily',
        'priority'   => '1.0',
        'lastmod'    => $ahora,
    ],
    [
        'loc'        => $base . '/terminos',
        'changefreq' => 'monthly',
        'priority'   => '0.5',
        'lastmod'    => $ahora,
    ],
];

try {
    $bd = obtenerConexion();
    $stmt = $bd->query(
        "SELECT slug, actualizado_en, creado_en
         FROM grupos
         WHERE activo = 1 AND slug IS NOT NULL AND slug != ''
         ORDER BY actualizado_en DESC"
    );

    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = fechaIso($fila['actualizado_en'] ?? $fila['creado_en'] ?? null) ?? $ahora;
        $urls[] = [
            'loc'        => urlGrupo((string) $fila['slug'], $base),
            'changefreq' => 'weekly',
            'priority'   => '0.8',
            'lastmod'    => $lastmod,
        ];
    }
} catch (Throwable) {
    // Sitemap mínimo con la home
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $entrada) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($entrada['loc'], ENT_XML1) . "</loc>\n";
    echo '    <lastmod>' . htmlspecialchars($entrada['lastmod'], ENT_XML1) . "</lastmod>\n";
    echo '    <changefreq>' . htmlspecialchars($entrada['changefreq'], ENT_XML1) . "</changefreq>\n";
    echo '    <priority>' . htmlspecialchars($entrada['priority'], ENT_XML1) . "</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
