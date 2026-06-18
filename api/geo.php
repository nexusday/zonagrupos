<?php
declare(strict_types=1);

function obtenerIpCliente(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (str_contains($ip, ',')) {
        $ip = trim(explode(',', $ip)[0]);
    }
    if (str_starts_with($ip, '::ffff:')) {
        $ip = substr($ip, 7);
    }
    return $ip;
}

function esIpLocal(string $ip): bool
{
    return $ip === ''
        || $ip === '127.0.0.1'
        || $ip === '::1'
        || str_starts_with($ip, '192.168.')
        || str_starts_with($ip, '10.')
        || str_starts_with($ip, '172.');
}

function obtenerPaisDesdeIp(string $ip): array
{
    if (esIpLocal($ip)) {
        return ['codigo' => 'PE', 'nombre' => 'Perú'];
    }

    $cacheDir = dirname(__DIR__) . '/logs/geo-cache';
    $cacheFile = $cacheDir . '/' . md5($ip) . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
        $cacheado = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cacheado) && isset($cacheado['codigo'])) {
            return $cacheado;
        }
    }

    // ip-api.com — gratis, hasta 45 req/min sin clave
    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,countryCode';
    $contexto = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $respuesta = @file_get_contents($url, false, $contexto);

    if ($respuesta === false) {
        return ['codigo' => 'LAT', 'nombre' => 'Latinoamérica'];
    }

    $datos = json_decode($respuesta, true);
    if (!is_array($datos) || ($datos['status'] ?? '') !== 'success') {
        return ['codigo' => 'LAT', 'nombre' => 'Latinoamérica'];
    }

    $pais = [
        'codigo' => strtoupper($datos['countryCode'] ?? 'LAT'),
        'nombre' => $datos['country'] ?? 'Desconocido',
    ];

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    @file_put_contents($cacheFile, json_encode($pais, JSON_UNESCAPED_UNICODE));

    return $pais;
}

function puedeUnirseAlGrupo(array $grupo, array $paisVisitante): bool
{
    if (($grupo['restriccion_pais'] ?? 'todos') === 'todos') {
        return true;
    }

    $codigoGrupo = strtoupper($grupo['pais_codigo'] ?? '');
    $codigoVisitante = strtoupper($paisVisitante['codigo'] ?? '');

    return $codigoGrupo !== '' && $codigoGrupo === $codigoVisitante;
}
