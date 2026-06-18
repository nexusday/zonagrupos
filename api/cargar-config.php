<?php
declare(strict_types=1);

/**
 * Carga variables desde config.env o .env en la raíz del proyecto.
 */
function cargarVariablesEntorno(): void
{
    $raiz = dirname(__DIR__);
    $archivos = [$raiz . '/.env', $raiz . '/config.env'];

    foreach ($archivos as $archivo) {
        if (!is_readable($archivo)) {
            continue;
        }

        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            continue;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            if (!str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor, " \t\"'");

            if ($clave !== '' && getenv($clave) === false) {
                putenv("{$clave}={$valor}");
                $_ENV[$clave] = $valor;
            }
        }
        break;
    }
}

cargarVariablesEntorno();
