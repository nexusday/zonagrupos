<?php
declare(strict_types=1);

/**
 * GET /api/visitante.php
 * Detecta el país del visitante por su IP (ip-api.com, gratis).
 */
require_once __DIR__ . '/entorno.php';
require_once __DIR__ . '/respuestas.php';
require_once __DIR__ . '/geo.php';
require_once __DIR__ . '/logger.php';

enviarCabecerasCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderError('Método no permitido.', 405);
}

try {
    $ip = obtenerIpCliente();
    $esLocal = esIpLocal($ip);
    $pais = obtenerPaisVisitante();
    $desdeNavegador = $esLocal && isset($_SERVER['HTTP_X_GEO_PAIS']);

    registrarLog('info', 'País visitante detectado', [
        'ip'    => $esLocal ? 'local' : substr($ip, 0, 8) . '…',
        'pais'  => $pais['codigo'],
        'local' => $esLocal,
    ]);

    responderJson([
        'exito'    => true,
        'ip'       => $esLocal ? null : $ip,
        'es_local' => $esLocal,
        'pais'     => $pais,
        'fuente'   => $desdeNavegador
            ? 'navegador (VPN)'
            : ($esLocal ? 'ip-api.com (IP pública)' : 'ip-api.com'),
    ]);
} catch (Throwable $e) {
    registrarLog('error', 'Error geo visitante: ' . $e->getMessage());
    responderError('No se pudo detectar tu ubicación.', 500);
}
