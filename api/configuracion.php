<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-config.php';

$origenes = [];
$origenEnv = envConfig('CORS_ORIGEN') ?: envConfig('APP_URL');

if ($origenEnv !== '') {
    $origenes[] = rtrim($origenEnv, '/');
}

foreach (
    [
        'http://localhost:4600',
        'http://127.0.0.1:4600',
        'http://localhost:4500',
        'http://127.0.0.1:4500',
        'http://localhost:3333',
        'http://127.0.0.1:3333',
    ] as $local
) {
    if (!in_array($local, $origenes, true)) {
        $origenes[] = $local;
    }
}

$configLocal = [];
$archivoLocal = __DIR__ . '/config.local.php';
if (is_readable($archivoLocal)) {
    $extra = require $archivoLocal;
    if (is_array($extra)) {
        $configLocal = $extra;
        if (isset($extra['origenes']) && is_array($extra['origenes'])) {
            $origenes = array_values(array_unique([...$origenes, ...$extra['origenes']]));
        }
    }
}

$leer = static function (string $clave, string $env, string $defecto) use ($configLocal): string {
    if (isset($configLocal[$clave]) && $configLocal[$clave] !== '') {
        return (string) $configLocal[$clave];
    }
    $valor = envConfig($env, $defecto);
    return $valor !== '' ? $valor : $defecto;
};

return [
    'bd_host'    => $leer('bd_host', 'BD_HOST', 'localhost'),
    'bd_puerto'  => (int) ($configLocal['bd_puerto'] ?? envConfig('BD_PUERTO', '3306')),
    'bd_nombre'  => $leer('bd_nombre', 'BD_NOMBRE', 'zona_grupos'),
    'bd_usuario' => $leer('bd_usuario', 'BD_USUARIO', 'root'),
    'bd_clave'   => $configLocal['bd_clave'] ?? envConfig('BD_CLAVE', ''),
    'bd_charset' => 'utf8mb4',
    'origenes'   => $origenes,
];
