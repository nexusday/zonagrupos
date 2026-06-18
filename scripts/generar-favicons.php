<?php
declare(strict_types=1);

/**
 * Genera favicon.ico y PNGs en tamaños que Google acepta (múltiplos de 48).
 */
$origen = dirname(__DIR__) . '/public/img/zonagrupos.png';
$destinoDir = dirname(__DIR__) . '/public';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD no disponible. Habilita extension=gd en php.ini\n");
    exit(1);
}

$fuente = @imagecreatefrompng($origen);
if ($fuente === false) {
    fwrite(STDERR, "No se pudo leer {$origen}\n");
    exit(1);
}

function guardarPngCuadrado(GdImage $origen, int $tamano, string $ruta): void
{
    $destino = imagecreatetruecolor($tamano, $tamano);
    imagealphablending($destino, false);
    imagesavealpha($destino, true);
    $transparente = imagecolorallocatealpha($destino, 0, 0, 0, 127);
    imagefilledrectangle($destino, 0, 0, $tamano, $tamano, $transparente);

    $ancho = imagesx($origen);
    $alto = imagesy($origen);
    imagecopyresampled($destino, $origen, 0, 0, 0, 0, $tamano, $tamano, $ancho, $alto);
    imagepng($destino, $ruta, 6);
    imagedestroy($destino);
}

guardarPngCuadrado($fuente, 48, $destinoDir . '/img/favicon-48.png');
guardarPngCuadrado($fuente, 192, $destinoDir . '/img/favicon-192.png');
guardarPngCuadrado($fuente, 512, $destinoDir . '/img/favicon-512.png');

// favicon.ico (48x48 embebido)
$icono = imagecreatetruecolor(48, 48);
imagealphablending($icono, false);
imagesavealpha($icono, true);
$transparente = imagecolorallocatealpha($icono, 0, 0, 0, 127);
imagefilledrectangle($icono, 0, 0, 48, 48, $transparente);
imagecopyresampled($icono, $fuente, 0, 0, 0, 0, 48, 48, imagesx($fuente), imagesy($fuente));
imagepng($icono, $destinoDir . '/favicon.ico');
imagedestroy($icono);
imagedestroy($fuente);

echo "OK: favicon.ico, favicon-48.png, favicon-192.png, favicon-512.png\n";
