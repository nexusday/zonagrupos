<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/og-render.php';

$salida = dirname(__DIR__) . '/public/img/og-portada.png';

$img = ogCrearLienzo();
ogFondoDegradado($img);
ogDibujarMarca($img);

if (!imagepng($img, $salida, 6)) {
    fwrite(STDERR, "No se pudo guardar {$salida}\n");
    exit(1);
}

imagedestroy($img);
echo "OK: {$salida}\n";
