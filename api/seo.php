<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-config.php';

function urlBaseApp(): string
{
    return rtrim(envConfig('APP_URL', 'https://zonagrupos.lat'), '/');
}

function nombrePlataformaSeo(string $plataforma): string
{
    return match ($plataforma) {
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'discord'  => 'Discord',
        default    => 'WhatsApp',
    };
}

function recortarTextoSeo(string $texto, int $maximo = 155): string
{
    $texto = preg_replace('/\s+/', ' ', trim(strip_tags($texto))) ?? '';
    if (mb_strlen($texto) <= $maximo) {
        return $texto;
    }

    $recorte = mb_substr($texto, 0, $maximo - 1);
    $ultimoEspacio = mb_strrpos($recorte, ' ');
    if ($ultimoEspacio !== false && $ultimoEspacio > (int) ($maximo * 0.6)) {
        $recorte = mb_substr($recorte, 0, $ultimoEspacio);
    }

    return rtrim($recorte, '.,;:-') . '…';
}

function construirTituloGrupo(array $grupo): string
{
    $nombre = trim($grupo['nombre'] ?? 'Grupo');
    $plataforma = nombrePlataformaSeo($grupo['plataforma'] ?? 'whatsapp');
    $pais = trim($grupo['pais_nombre'] ?? '');

    if ($pais !== '' && strtoupper($grupo['pais_codigo'] ?? '') !== 'LAT') {
        return "{$nombre} | Grupo de {$plataforma} en {$pais}";
    }

    return "{$nombre} | Grupo de {$plataforma}";
}

function construirDescripcionGrupo(array $grupo, array $etiquetas = []): string
{
    $nombre = trim($grupo['nombre'] ?? 'Grupo');
    $plataforma = nombrePlataformaSeo($grupo['plataforma'] ?? 'whatsapp');
    $pais = trim($grupo['pais_nombre'] ?? 'Latinoamérica');
    $descripcion = trim($grupo['descripcion'] ?? '');

    $intro = "\"{$nombre}\" es un grupo de {$plataforma}";
    if ($pais !== '' && strtoupper($grupo['pais_codigo'] ?? '') !== 'LAT') {
        $intro .= " de {$pais}";
    }
    $intro .= '.';

    if ($descripcion !== '') {
        $intro .= ' ' . recortarTextoSeo($descripcion, 120);
    }

    if ($etiquetas !== []) {
        $temas = implode(', ', array_slice($etiquetas, 0, 4));
        $intro .= " Temas: {$temas}.";
    }

    $intro .= ' Enlace de invitación en ZonaGrupos.';

    return recortarTextoSeo($intro, 160);
}

/** Palabras clave base del directorio + contexto del grupo */
function construirKeywordsGrupo(array $grupo, array $etiquetas = []): string
{
    $plataforma = $grupo['plataforma'] ?? 'whatsapp';
    $pais = trim($grupo['pais_nombre'] ?? '');

    $base = [
        'grupos ' . $plataforma,
        'grupo ' . $plataforma,
        'enlace grupo ' . $plataforma,
        'unirse grupo ' . $plataforma,
    ];

    if ($pais !== '' && strtoupper($grupo['pais_codigo'] ?? '') !== 'LAT') {
        $base[] = 'grupos ' . $plataforma . ' ' . mb_strtolower($pais);
    }

    $nombre = mb_strtolower(trim($grupo['nombre'] ?? ''));
    if ($nombre !== '') {
        $base[] = $nombre;
    }

    foreach ($etiquetas as $etiqueta) {
        $base[] = 'grupo ' . $etiqueta;
        $base[] = 'grupos ' . $etiqueta;
    }

    $unicas = array_values(array_unique(array_filter($base)));

    return implode(', ', array_slice($unicas, 0, 12));
}

function urlGrupo(string $slug, ?string $base = null): string
{
    $base = $base ?? urlBaseApp();

    return $base . '/grupo/' . rawurlencode($slug);
}

function fechaIso(?string $fecha): ?string
{
    if ($fecha === null || $fecha === '') {
        return null;
    }

    $timestamp = strtotime($fecha);

    return $timestamp ? date('c', $timestamp) : null;
}

function jsonLdSitio(?string $base = null): array
{
    $base = $base ?? urlBaseApp();

    return [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        '@id'      => $base . '/#sitio',
        'name'     => 'ZonaGrupos',
        'url'      => $base . '/',
        'description' => 'Directorio para encontrar y publicar grupos de WhatsApp, Telegram y Discord en Latinoamérica.',
        'inLanguage' => 'es-419',
        'publisher' => [
            '@type' => 'Organization',
            'name'  => 'ZonaGrupos',
            'url'   => $base . '/',
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => $base . '/img/zonagrupos.png',
            ],
        ],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $base . '/?busqueda={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function jsonLdGrupo(array $grupo, array $etiquetas = [], ?string $base = null): array
{
    $base = $base ?? urlBaseApp();
    $slug = $grupo['slug'] ?? '';
    $url = urlGrupo($slug, $base);
    $titulo = construirTituloGrupo($grupo);
    $descripcion = construirDescripcionGrupo($grupo, $etiquetas);
    $imagen = $base . '/img/zonagrupos.png';

    $grafo = [
        jsonLdSitio($base),
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type'    => 'ListItem',
                    'position' => 1,
                    'name'     => 'Inicio',
                    'item'     => $base . '/',
                ],
                [
                    '@type'    => 'ListItem',
                    'position' => 2,
                    'name'     => $grupo['nombre'] ?? 'Grupo',
                    'item'     => $url,
                ],
            ],
        ],
        [
            '@type'           => 'WebPage',
            '@id'             => $url . '#pagina',
            'url'             => $url,
            'name'            => $titulo,
            'description'     => $descripcion,
            'inLanguage'      => 'es-419',
            'isPartOf'        => ['@id' => $base . '/#sitio'],
            'primaryImageOfPage' => [
                '@type' => 'ImageObject',
                'url'   => $imagen,
            ],
            'about' => [
                '@type'       => 'Thing',
                'name'        => $grupo['nombre'] ?? '',
                'description' => recortarTextoSeo($grupo['descripcion'] ?? '', 300),
            ],
        ],
    ];

    $publicado = fechaIso($grupo['creado_en'] ?? null);
    $modificado = fechaIso($grupo['actualizado_en'] ?? $grupo['creado_en'] ?? null);
    if ($publicado) {
        $grafo[2]['datePublished'] = $publicado;
    }
    if ($modificado) {
        $grafo[2]['dateModified'] = $modificado;
    }

    if ($etiquetas !== []) {
        $grafo[2]['keywords'] = implode(', ', $etiquetas);
    }

    return [
        '@context' => 'https://schema.org',
        '@graph'   => $grafo,
    ];
}

function emitirJsonLd(array $datos): void
{
    echo '<script type="application/ld+json">'
        . json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>' . "\n";
}

function etiquetasDeGrupo(PDO $bd, int $grupoId): array
{
    $stmt = $bd->prepare(
        'SELECT e.nombre FROM etiquetas e
         INNER JOIN grupo_etiquetas ge ON ge.etiqueta_id = e.id
         WHERE ge.grupo_id = :grupo_id
         ORDER BY e.nombre ASC'
    );
    $stmt->execute([':grupo_id' => $grupoId]);
    $filas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return is_array($filas) ? array_map('strval', $filas) : [];
}
