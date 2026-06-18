<?php
declare(strict_types=1);

/**
 * Verifica conexión MySQL (usado por npm start).
 * Usa la misma config que la API: config.env + config.env.local
 */
require_once dirname(__DIR__) . '/api/conexion.php';

try {
    $bd = obtenerConexion();
    $bd->query('SELECT 1 FROM grupos LIMIT 1');
    echo 'OK';
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage());
    exit(1);
}
