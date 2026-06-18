<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-config.php';

$origenes = [];
$origenEnv = getenv('CORS_ORIGEN') ?: getenv('APP_URL') ?: '';

if ($origenEnv !== '') {
    $origenes[] = rtrim($origenEnv, '/');
}

foreach (['http://localhost:4600', 'http://127.0.0.1:4600', 'http://localhost:4500', 'http://127.0.0.1:4500', 'http://localhost:3333', 'http://127.0.0.1:3333'] as $local) {
    if (!in_array($local, $origenes, true)) {
        $origenes[] = $local;
    }
}

$localConfig = __DIR__ . '/config.local.php';
if (is_readable($localConfig)) {
    $extra = require $localConfig;
    if (isset($extra['origenes']) && is_array($extra['origenes'])) {
        $origenes = array_values(array_unique([...$origenes, ...$extra['origenes']]));
    }
}

return [
    'bd_host'     => getenv('BD_HOST') ?: '127.0.0.1',
    'bd_nombre'   => getenv('BD_NOMBRE') ?: 'zona_grupos',
    'bd_usuario'  => getenv('BD_USUARIO') ?: 'root',
    'bd_clave'    => getenv('BD_CLAVE') ?: '',
    'bd_charset'  => 'utf8mb4',
    'origenes'    => $origenes,
];
