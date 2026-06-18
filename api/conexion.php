<?php
declare(strict_types=1);

function obtenerConexion(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    $config = require __DIR__ . '/configuracion.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['bd_host'],
        $config['bd_puerto'] ?? 3306,
        $config['bd_nombre'],
        $config['bd_charset']
    );

    $conexion = new PDO($dsn, $config['bd_usuario'], $config['bd_clave'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]);

    return $conexion;
}
