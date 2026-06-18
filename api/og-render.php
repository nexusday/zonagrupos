<?php
declare(strict_types=1);

const OG_ANCHO = 1200;
const OG_ALTO = 630;

function ogFuenteDisponible(): ?string
{
    static $fuente = null;
    if ($fuente !== null) {
        return $fuente !== '' ? $fuente : null;
    }

    $candidatos = [
        'C:\\Windows\\Fonts\\segoeuib.ttf',
        'C:\\Windows\\Fonts\\arialbd.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
    ];

    foreach ($candidatos as $ruta) {
        if (is_readable($ruta)) {
            $fuente = $ruta;
            return $ruta;
        }
    }

    $fuente = '';
    return null;
}

function ogCrearLienzo(): GdImage
{
    if (!extension_loaded('gd')) {
        throw new RuntimeException('GD no disponible');
    }

    $img = imagecreatetruecolor(OG_ANCHO, OG_ALTO);
    if ($img === false) {
        throw new RuntimeException('No se pudo crear la imagen OG');
    }

    imagealphablending($img, true);
    imagesavealpha($img, true);

    return $img;
}

function ogFondoDegradado(GdImage $img): void
{
    $colores = [
        [10, 10, 15],
        [18, 12, 36],
        [22, 28, 48],
        [12, 18, 32],
    ];

    for ($y = 0; $y < OG_ALTO; $y++) {
        $t = $y / OG_ALTO;
        $r = (int) ($colores[0][0] * (1 - $t) + $colores[2][0] * $t);
        $g = (int) ($colores[0][1] * (1 - $t) + $colores[2][1] * $t);
        $b = (int) ($colores[0][2] * (1 - $t) + $colores[2][2] * $t);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, OG_ANCHO, $y, $color);
    }

    $acento = imagecolorallocatealpha($img, 124, 58, 237, 90);
    imagefilledellipse($img, 180, 120, 420, 420, $acento);
    $verde = imagecolorallocatealpha($img, 37, 211, 102, 70);
    imagefilledellipse($img, 1020, 520, 360, 360, $verde);
}

function ogTextoMultilinea(GdImage $img, string $texto, int $x, int $y, int $maxAncho, int $tamano, int $color): int
{
    $fuente = ogFuenteDisponible();
    $lineas = [];
    $palabras = preg_split('/\s+/', trim($texto)) ?: [];
    $lineaActual = '';

    foreach ($palabras as $palabra) {
        $prueba = $lineaActual === '' ? $palabra : $lineaActual . ' ' . $palabra;
        $ancho = $fuente
            ? (imagettfbbox($tamano, 0, $fuente, $prueba)[2] - imagettfbbox($tamano, 0, $fuente, $prueba)[0])
            : (strlen($prueba) * imagefontwidth(5));

        if ($ancho > $maxAncho && $lineaActual !== '') {
            $lineas[] = $lineaActual;
            $lineaActual = $palabra;
        } else {
            $lineaActual = $prueba;
        }
    }
    if ($lineaActual !== '') {
        $lineas[] = $lineaActual;
    }

    $lineas = array_slice($lineas, 0, 3);
    $alturaLinea = (int) ($tamano * 1.35);
    $cursorY = $y;

    foreach ($lineas as $linea) {
        if ($fuente) {
            imagettftext($img, $tamano, 0, $x, $cursorY + $tamano, $color, $fuente, $linea);
        } else {
            imagestring($img, 5, $x, $cursorY, $linea, $color);
        }
        $cursorY += $alturaLinea;
    }

    return $cursorY;
}

function ogDibujarMarca(GdImage $img, string $subtitulo = ''): void
{
    $blanco = imagecolorallocate($img, 245, 245, 250);
    $suave = imagecolorallocate($img, 148, 148, 168);
    $verde = imagecolorallocate($img, 37, 211, 102);
    $morado = imagecolorallocate($img, 167, 139, 250);

    $fuente = ogFuenteDisponible();
    if ($fuente) {
        imagettftext($img, 28, 0, 72, 78, $morado, $fuente, 'Zona');
        $anchoZona = imagettfbbox(28, 0, $fuente, 'Zona')[2];
        imagettftext($img, 28, 0, 72 + $anchoZona, 78, $verde, $fuente, 'Grupos');
    } else {
        imagestring($img, 5, 72, 52, 'ZonaGrupos', $morado);
    }

    $titulo = 'Grupos de WhatsApp, Telegram y Discord';
    ogTextoMultilinea($img, $titulo, 72, 150, 920, 46, $blanco);

    if ($subtitulo !== '') {
        ogTextoMultilinea($img, $subtitulo, 72, 340, 920, 30, $suave);
    } else {
        ogTextoMultilinea($img, 'Encuentra comunidades en Latinoamérica · Publica tu enlace gratis', 72, 340, 920, 28, $suave);
    }

    $pie = 'zonagrupos.lat';
    if ($fuente) {
        imagettftext($img, 22, 0, 72, OG_ALTO - 56, $suave, $fuente, $pie);
    } else {
        imagestring($img, 4, 72, OG_ALTO - 56, $pie, $suave);
    }
}

function ogResponderPng(GdImage $img): void
{
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    imagepng($img, null, 6);
    imagedestroy($img);
}

function ogGenerarPortada(): void
{
    $img = ogCrearLienzo();
    ogFondoDegradado($img);
    ogDibujarMarca($img);
    ogResponderPng($img);
}

function ogGenerarGrupo(string $nombre, string $plataforma, string $pais = ''): void
{
    $img = ogCrearLienzo();
    ogFondoDegradado($img);

    $blanco = imagecolorallocate($img, 245, 245, 250);
    $suave = imagecolorallocate($img, 148, 148, 168);
    $verde = imagecolorallocate($img, 37, 211, 102);
    $fuente = ogFuenteDisponible();

    if ($fuente) {
        imagettftext($img, 24, 0, 72, 72, $suave, $fuente, 'Grupo en ZonaGrupos');
    }

    ogTextoMultilinea($img, $nombre, 72, 130, 1000, 52, $blanco);

    $meta = 'Grupo de ' . $plataforma;
    if ($pais !== '') {
        $meta .= ' · ' . $pais;
    }
    ogTextoMultilinea($img, $meta, 72, 360, 1000, 30, $suave);

    if ($fuente) {
        imagettftext($img, 22, 0, 72, OG_ALTO - 56, $verde, $fuente, 'Unirse en zonagrupos.lat');
    }

    ogResponderPng($img);
}
