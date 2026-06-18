<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-config.php';

/**
 * Adapta variables cuando PHP corre desde el servidor Node (CLI).
 */
if (PHP_SAPI === 'cli') {
    $_SERVER['REQUEST_METHOD']  = getenv('REQUEST_METHOD') ?: 'GET';
    $_SERVER['HTTP_ORIGIN']     = getenv('HTTP_ORIGIN') ?: '';
    $_SERVER['HTTP_USER_AGENT'] = getenv('HTTP_USER_AGENT') ?: '';
    $_SERVER['REMOTE_ADDR']     = getenv('REMOTE_ADDR') ?: '127.0.0.1';
    $_SERVER['HTTP_X_FORWARDED_FOR'] = getenv('HTTP_X_FORWARDED_FOR') ?: '';

    $consulta = getenv('QUERY_STRING') ?: '';
    if ($consulta !== '') {
        parse_str($consulta, $parametrosGet);
        $_GET = array_merge($_GET, $parametrosGet);
    }
}

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $texto, ?string $codificacion = null): int
    {
        return strlen($texto);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $texto, ?string $codificacion = null): string
    {
        return strtolower($texto);
    }
}

function leerCuerpoJson(): array
{
    $crudo = getenv('JSON_CUERPO');
    if ($crudo === false || $crudo === '') {
        $crudo = file_get_contents('php://input');
    }
    if ($crudo === false || $crudo === '') {
        return [];
    }

    $datos = json_decode($crudo, true);
    return is_array($datos) ? $datos : [];
}
