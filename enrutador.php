<?php

declare(strict_types=1);



$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';



if (str_starts_with($uri, '/api/')) {

    $archivo = __DIR__ . '/api' . substr($uri, 4);

    if (is_file($archivo)) {

        require $archivo;

        return true;

    }

    http_response_code(404);

    header('Content-Type: application/json');

    echo json_encode(['exito' => false, 'mensaje' => 'API no encontrada']);

    return true;

}



// Página de grupo: /grupo/slug (meta OG para WhatsApp)
if (preg_match('#^/grupo/([^/]+)/?$#', $uri, $coincidencia)) {
    $_GET['slug'] = rawurldecode($coincidencia[1]);
    $grupoPhp = __DIR__ . '/public/grupo.php';
    if (is_file($grupoPhp)) {
        require $grupoPhp;
        return true;
    }
}



$publico = __DIR__ . '/public' . ($uri === '/' ? '/index.html' : $uri);

if (is_file($publico)) {

    return false;

}



$index = __DIR__ . '/public/index.html';

if (is_file($index)) {

    header('Content-Type: text/html; charset=utf-8');

    readfile($index);

    return true;

}



http_response_code(404);

echo 'Página no encontrada';

return true;

