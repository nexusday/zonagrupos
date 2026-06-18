<?php
declare(strict_types=1);

/**
 * Credenciales del panel admin — NO subir sun.env a git.
 */
function cargarSunEnv(): array
{
    static $vars = null;
    if ($vars !== null) {
        return $vars;
    }

    $vars = [];
    $raiz = dirname(__DIR__);
    foreach ([$raiz . '/sun.env', $raiz . '/config.sun.env'] as $archivo) {
        if (!is_readable($archivo)) {
            continue;
        }
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $linea, 2);
            $vars[trim($k)] = trim($v, " \t\"'");
        }
        break;
    }

    return $vars;
}

function sunConfig(string $clave, string $defecto = ''): string
{
    $vars = cargarSunEnv();
    return $vars[$clave] ?? $defecto;
}
