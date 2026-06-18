<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-config.php';

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

function directorioCacheGeo(): string
{
    return dirname(__DIR__) . '/logs/geo-cache';
}

function leerCacheGeo(string $clave): ?array
{
    $archivo = directorioCacheGeo() . '/' . md5($clave) . '.json';
    if (!is_file($archivo) || (time() - filemtime($archivo)) >= 86400) {
        return null;
    }
    $cacheado = json_decode((string) file_get_contents($archivo), true);
    if (!is_array($cacheado) || empty($cacheado['codigo'])) {
        return null;
    }
    return $cacheado;
}

function guardarCacheGeo(string $clave, array $pais): void
{
    $dir = directorioCacheGeo();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents(
        $dir . '/' . md5($clave) . '.json',
        json_encode($pais, JSON_UNESCAPED_UNICODE)
    );
}

/**
 * Consulta ip-api.com. Sin $ip usa la IP pública de salida (útil en localhost + VPN).
 */
function consultarIpApi(?string $ip = null): array
{
    $claveCache = $ip ?? '__ip_publica__';
    $cacheado = leerCacheGeo($claveCache);
    if ($cacheado !== null) {
        return $cacheado;
    }

    if ($ip === null || $ip === '') {
        $url = 'http://ip-api.com/json/?fields=status,country,countryCode,query';
    } else {
        $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,countryCode,query';
    }

    $contexto = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
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

    $ipReal = $datos['query'] ?? $ip ?? $claveCache;
    guardarCacheGeo((string) $ipReal, $pais);
    if ($claveCache !== (string) $ipReal) {
        guardarCacheGeo($claveCache, $pais);
    }

    return $pais;
}

function obtenerPaisDesdeIp(string $ip): array
{
    $overrideCodigo = envConfig('GEO_PAIS_CODIGO', '');
    $overrideNombre = envConfig('GEO_PAIS_NOMBRE', '');
    if ($overrideCodigo !== '' && $overrideNombre !== '' && esIpLocal($ip)) {
        return [
            'codigo' => strtoupper($overrideCodigo),
            'nombre' => $overrideNombre,
        ];
    }

    if (esIpLocal($ip)) {
        return consultarIpApi(null);
    }

    return consultarIpApi($ip);
}

/**
 * País del visitante actual. En local acepta cabeceras del navegador (VPN/extensiones).
 */
function obtenerPaisVisitante(): array
{
    $ip = obtenerIpCliente();

    if (esIpLocal($ip)) {
        $codigo = strtoupper(trim($_SERVER['HTTP_X_GEO_PAIS'] ?? ''));
        $nombre = trim($_SERVER['HTTP_X_GEO_PAIS_NOMBRE'] ?? '');
        if ($codigo !== '' && $nombre !== '' && preg_match('/^[A-Z]{2,3}$/', $codigo)) {
            return ['codigo' => $codigo, 'nombre' => $nombre];
        }
    }

    return obtenerPaisDesdeIp($ip);
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
