<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/og-render.php';

function ogServirEstatica(string $archivo): bool
{
    if (!is_readable($archivo)) {
        return false;
    }

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    readfile($archivo);
    return true;
}

try {
    ogGenerarPortada();
} catch (Throwable) {
    if (ogServirEstatica(dirname(__DIR__) . '/img/og-portada.png')) {
        exit;
    }
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Imagen no disponible';
}
