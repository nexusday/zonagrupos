<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/entorno.php';
require_once dirname(__DIR__, 2) . '/api/conexion.php';
require_once dirname(__DIR__, 2) . '/api/geo.php';
require_once dirname(__DIR__, 2) . '/api/seo.php';
require_once dirname(__DIR__, 2) . '/api/og-render.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '' && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('~/og/grupo/([^/.]+)\.png~', (string) $_SERVER['REQUEST_URI'], $m)) {
        $slug = rawurldecode($m[1]);
    }
}

if ($slug === '') {
    http_response_code(404);
    exit;
}

try {
    $bd = obtenerConexion();
    $stmt = $bd->prepare(
        'SELECT nombre, plataforma, pais_nombre, pais_codigo
         FROM grupos WHERE slug = :slug AND activo = 1 LIMIT 1'
    );
    $stmt->execute([':slug' => $slug]);
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$grupo) {
        http_response_code(404);
        exit;
    }

    $pais = trim($grupo['pais_nombre'] ?? '');
    $paisNorm = normalizarPais($grupo['pais_codigo'] ?? '', $pais);
    $pais = $paisNorm['nombre'];

    ogGenerarGrupo(
        (string) $grupo['nombre'],
        nombrePlataformaSeo((string) $grupo['plataforma']),
        $pais
    );
} catch (Throwable) {
    $estatica = dirname(__DIR__) . '/img/og-portada.png';
    if (is_readable($estatica)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($estatica);
        exit;
    }
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Imagen no disponible';
}
