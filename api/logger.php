<?php
declare(strict_types=1);

function registrarLog(string $nivel, string $mensaje, array $contexto = []): void
{
    $carpeta = dirname(__DIR__) . '/logs';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    $archivo = $carpeta . '/app-' . date('Y-m-d') . '.log';
    $linea = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($nivel),
        $mensaje,
        $contexto !== [] ? json_encode($contexto, JSON_UNESCAPED_UNICODE) : ''
    );

    file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
}
