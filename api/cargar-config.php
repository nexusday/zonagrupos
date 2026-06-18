<?php
declare(strict_types=1);

/**
 * Carga variables desde config.env / .env (raíz del proyecto o carpeta api).
 * En hosting compartido PHP no lee .env solo: este archivo lo hace explícitamente.
 */
function cargarVariablesEntorno(): array
{
    static $variables = null;

    if ($variables !== null) {
        return $variables;
    }

    $variables = [];
    $raiz = dirname(__DIR__);
    $candidatos = [
        $raiz . '/config.env',
        $raiz . '/config.env.local',
        $raiz . '/.env',
        __DIR__ . '/config.env',
        __DIR__ . '/.env',
    ];

    foreach ($candidatos as $archivo) {
        if (!is_readable($archivo)) {
            continue;
        }

        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            continue;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor, " \t\"'");

            if ($clave !== '') {
                $variables[$clave] = $valor;
                putenv("{$clave}={$valor}");
                $_ENV[$clave] = $valor;
            }
        }
    }

    return $variables;
}

function envConfig(string $clave, string $predeterminado = ''): string
{
    $variables = cargarVariablesEntorno();

    if (array_key_exists($clave, $variables)) {
        return $variables[$clave];
    }

    $delSistema = getenv($clave);
    if ($delSistema !== false) {
        return $delSistema;
    }

    return $predeterminado;
}

cargarVariablesEntorno();
