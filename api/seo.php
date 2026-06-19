<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-config.php';
require_once __DIR__ . '/geo.php';

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

    if ($pais !== '' && codigoPaisReal($grupo['pais_codigo'] ?? '')) {
        return "{$nombre} | Grupo de {$plataforma} en {$pais}";
    }

    return "{$nombre} | Grupo de {$plataforma}";
}

function construirDescripcionGrupo(array $grupo, array $etiquetas = []): string
{
    $nombre = trim($grupo['nombre'] ?? 'Grupo');
    $plataforma = nombrePlataformaSeo($grupo['plataforma'] ?? 'whatsapp');
    $pais = trim($grupo['pais_nombre'] ?? '');
    $descripcion = trim($grupo['descripcion'] ?? '');

    $intro = "\"{$nombre}\" es un grupo de {$plataforma}";
    if ($pais !== '' && codigoPaisReal($grupo['pais_codigo'] ?? '')) {
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

    if ($pais !== '' && codigoPaisReal($grupo['pais_codigo'] ?? '')) {
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

function slugEtiqueta(string $nombre): string
{
    $texto = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre);
    if ($texto === false || $texto === '') {
        $texto = $nombre;
    }
    $texto = mb_strtolower(trim($texto));
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? '';
    $texto = trim($texto, '-');

    return $texto !== '' ? $texto : 'tema';
}

function urlEtiqueta(string $nombre): string
{
    return '/tema/' . rawurlencode(slugEtiqueta($nombre));
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
        'description' => 'Encuentra y publica grupos de WhatsApp, Telegram y Discord en Latinoamérica.',
        'inLanguage' => 'es-419',
        'publisher' => [
            '@type' => 'Organization',
            'name'  => 'ZonaGrupos',
            'url'   => $base . '/',
            'logo'  => [
                '@type'  => 'ImageObject',
                'url'    => $base . '/img/favicon-192.png',
                'width'  => 192,
                'height' => 192,
            ],
        ],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $base . '/tema/{search_term_string}',
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
    $imagen = urlImagenOgGrupo($slug, $base);

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

function urlImagenOgPortada(?string $base = null): string
{
    $base = $base ?? urlBaseApp();

    return $base . '/og/portada.png';
}

function urlImagenOgGrupo(string $slug, ?string $base = null): string
{
    $base = $base ?? urlBaseApp();

    return $base . '/og/grupo/' . rawurlencode($slug) . '.png';
}

function jsonLdListadoGrupos(array $grupos, ?string $base = null): array
{
    $base = $base ?? urlBaseApp();
    $elementos = [];

    foreach (array_values($grupos) as $posicion => $grupo) {
        $slug = (string) ($grupo['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $elementos[] = [
            '@type'    => 'ListItem',
            'position' => $posicion + 1,
            'url'      => urlGrupo($slug, $base),
            'name'     => trim($grupo['nombre'] ?? 'Grupo'),
        ];
    }

    if ($elementos === []) {
        return [];
    }

    return [
        '@type'           => 'ItemList',
        '@id'             => $base . '/#listado-grupos',
        'name'            => 'Grupos recientes en ZonaGrupos',
        'numberOfItems'   => count($elementos),
        'itemListElement' => $elementos,
    ];
}

function jsonLdFaqInicio(?string $base = null): array
{
    $base = $base ?? urlBaseApp();

    return [
        '@type'      => 'FAQPage',
        '@id'        => $base . '/#faq',
        'mainEntity' => [
            [
                '@type'          => 'Question',
                'name'           => '¿Qué es un grupo de WhatsApp?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Es una sala de chat dentro de WhatsApp donde varias personas conversan a la vez. Si tienes el enlace de invitación, puedes unirte sin que el admin te agregue manualmente.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cómo me uno a un grupo desde ZonaGrupos?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Elige un grupo del listado, entra a su ficha y pulsa «Unirse al grupo». Te llevará al enlace oficial de WhatsApp, Telegram o Discord según corresponda.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Puedo publicar mi propio grupo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Sí. Pulsa «Publicar grupo», pega el enlace de invitación, escribe nombre y descripción, y quedará visible en el directorio en segundos.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuántas personas caben en un grupo de WhatsApp?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'WhatsApp permite hasta 1024 miembros por grupo. Telegram y Discord tienen otros límites según el tipo de comunidad.',
                ],
            ],
        ],
    ];
}

function jsonLdInicio(array $gruposRecientes = [], ?string $base = null): array
{
    $base = $base ?? urlBaseApp();

    $grafo = [
        jsonLdSitio($base),
        [
            '@type'       => 'Organization',
            '@id'         => $base . '/#organizacion',
            'name'        => 'ZonaGrupos',
            'url'         => $base . '/',
            'logo'        => [
                '@type'  => 'ImageObject',
                'url'    => $base . '/img/favicon-192.png',
                'width'  => 192,
                'height' => 192,
            ],
            'description' => 'Directorio de grupos de WhatsApp, Telegram y Discord para la comunidad latina.',
        ],
        jsonLdFaqInicio($base),
    ];

    $listado = jsonLdListadoGrupos($gruposRecientes, $base);
    if ($listado !== []) {
        $grafo[] = $listado;
    }

    return [
        '@context' => 'https://schema.org',
        '@graph'   => $grafo,
    ];
}

function metaInicio(array $gruposRecientes = [], ?string $base = null): array
{
    $base = $base ?? urlBaseApp();

    return [
        'titulo'       => 'Grupos WhatsApp, Telegram y Discord | ZonaGrupos',
        'descripcion'  => 'Directorio gratis con enlaces de invitación a grupos de WhatsApp, Telegram y Discord en Latinoamérica. Busca por país, tema o plataforma y únete en un clic.',
        'keywords'     => 'grupos whatsapp, grupos telegram, grupos discord, enlaces grupos, unirse grupos whatsapp, directorio grupos, comunidades latinoamérica',
        'canonical'    => $base . '/',
        'robots'       => 'index, follow, max-image-preview:large',
        'og_type'      => 'website',
        'og_image'     => urlImagenOgPortada($base),
        'og_image_alt' => 'ZonaGrupos — Directorio de grupos en Latinoamérica',
        'json_ld'      => jsonLdInicio($gruposRecientes, $base),
    ];
}

function metaTema(string $termino, ?string $base = null): array
{
    $base = $base ?? urlBaseApp();
    $terminoLimpio = recortarTextoSeo($termino, 60);
    $titulo = $terminoLimpio !== ''
        ? "Grupos de {$terminoLimpio} | ZonaGrupos"
        : 'Explorar temas | ZonaGrupos';

    return [
        'titulo'       => $titulo,
        'descripcion'  => "Grupos de WhatsApp, Telegram y Discord sobre «{$terminoLimpio}» en ZonaGrupos.",
        'keywords'     => 'grupos whatsapp, grupos telegram, ' . mb_strtolower($terminoLimpio),
        'canonical'    => $base . urlEtiqueta($termino),
        'robots'       => 'noindex, follow',
        'og_type'      => 'website',
        'og_image'     => urlImagenOgPortada($base),
        'og_image_alt' => 'Explorar temas — ZonaGrupos',
        'json_ld'      => null,
    ];
}

function metaBusqueda(string $termino, ?string $base = null): array
{
    return metaTema($termino, $base);
}

function metaTerminos(?string $base = null): array
{
    $base = $base ?? urlBaseApp();

    return [
        'titulo'       => 'Términos y condiciones | ZonaGrupos',
        'descripcion'  => 'Cómo ZonaGrupos trata tu IP, cookies, enlaces, correo y demás datos cuando usas el directorio.',
        'keywords'     => 'términos, condiciones, privacidad, datos, ZonaGrupos',
        'canonical'    => $base . '/terminos',
        'robots'       => 'index, follow',
        'og_type'      => 'website',
        'og_image'     => urlImagenOgPortada($base),
        'og_image_alt' => 'Términos y condiciones — ZonaGrupos',
        'json_ld'      => null,
    ];
}

function metaGrupo(array $grupo, array $etiquetas = [], ?string $base = null): array
{
    $base = $base ?? urlBaseApp();
    $slug = (string) ($grupo['slug'] ?? '');
    $esAdulto = ($grupo['clasificacion'] ?? 'normal') === 'adulto';
    $indexar = $slug !== '' && !$esAdulto;

    $robots = 'noindex, nofollow';
    if ($slug !== '') {
        $robots = $indexar ? 'index, follow, max-image-preview:large' : 'noindex, follow';
    }

    return [
        'titulo'       => construirTituloGrupo($grupo),
        'descripcion'  => construirDescripcionGrupo($grupo, $etiquetas),
        'keywords'     => construirKeywordsGrupo($grupo, $etiquetas),
        'canonical'    => urlGrupo($slug, $base),
        'robots'       => $robots,
        'og_type'      => 'article',
        'og_image'     => urlImagenOgGrupo($slug, $base),
        'og_image_alt' => trim($grupo['nombre'] ?? 'Grupo en ZonaGrupos'),
        'json_ld'      => ($slug !== '' && !$esAdulto) ? jsonLdGrupo($grupo, $etiquetas, $base) : null,
    ];
}

function escMeta(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function emitirMetasPagina(array $meta): void
{
    $base = urlBaseApp();
    $titulo = escMeta($meta['titulo'] ?? 'ZonaGrupos');
    $descripcion = escMeta($meta['descripcion'] ?? '');
    $keywords = escMeta($meta['keywords'] ?? '');
    $canonical = escMeta($meta['canonical'] ?? urlBaseApp() . '/');
    $robots = escMeta($meta['robots'] ?? 'index, follow');
    $ogType = escMeta($meta['og_type'] ?? 'website');
    $ogImage = escMeta($meta['og_image'] ?? urlImagenOgPortada());
    $ogImageAlt = escMeta($meta['og_image_alt'] ?? 'ZonaGrupos');

    echo "  <meta charset=\"UTF-8\">\n";
    echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, viewport-fit=cover\">\n";
    echo "  <meta name=\"description\" content=\"{$descripcion}\">\n";
    if ($keywords !== '') {
        echo "  <meta name=\"keywords\" content=\"{$keywords}\">\n";
    }
    echo "  <meta name=\"robots\" content=\"{$robots}\">\n";
    echo "  <meta name=\"author\" content=\"ZonaGrupos\">\n";
    echo "  <meta name=\"theme-color\" content=\"#0a0a0f\">\n";
    echo "  <meta name=\"color-scheme\" content=\"dark\">\n";
    echo "  <meta name=\"format-detection\" content=\"telephone=no\">\n";
    echo "  <meta name=\"mobile-web-app-capable\" content=\"yes\">\n";
    echo "  <meta name=\"apple-mobile-web-app-capable\" content=\"yes\">\n";
    echo "  <meta name=\"apple-mobile-web-app-title\" content=\"ZonaGrupos\">\n";
    echo "  <meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black-translucent\">\n";
    echo "  <title>{$titulo}</title>\n";
    echo "  <link rel=\"canonical\" href=\"{$canonical}\">\n";
    echo "  <link rel=\"sitemap\" type=\"application/xml\" title=\"Sitemap\" href=\"{$base}/sitemap.xml\">\n";
    echo "  <link rel=\"icon\" href=\"{$base}/favicon.ico\" sizes=\"48x48\">\n";
    echo "  <link rel=\"icon\" type=\"image/png\" sizes=\"48x48\" href=\"{$base}/img/favicon-48.png\">\n";
    echo "  <link rel=\"icon\" type=\"image/png\" sizes=\"192x192\" href=\"{$base}/img/favicon-192.png\">\n";
    echo "  <link rel=\"apple-touch-icon\" sizes=\"192x192\" href=\"{$base}/img/favicon-192.png\">\n";
    echo "  <link rel=\"manifest\" href=\"{$base}/site.webmanifest\">\n";
    echo "  <meta property=\"og:type\" content=\"{$ogType}\">\n";
    echo "  <meta property=\"og:site_name\" content=\"ZonaGrupos\">\n";
    echo "  <meta property=\"og:locale\" content=\"es_419\">\n";
    echo "  <meta property=\"og:title\" content=\"{$titulo}\">\n";
    echo "  <meta property=\"og:description\" content=\"{$descripcion}\">\n";
    echo "  <meta property=\"og:url\" content=\"{$canonical}\">\n";
    echo "  <meta property=\"og:image\" content=\"{$ogImage}\">\n";
    echo "  <meta property=\"og:image:secure_url\" content=\"{$ogImage}\">\n";
    echo "  <meta property=\"og:image:type\" content=\"image/png\">\n";
    echo "  <meta property=\"og:image:width\" content=\"1200\">\n";
    echo "  <meta property=\"og:image:height\" content=\"630\">\n";
    echo "  <meta property=\"og:image:alt\" content=\"{$ogImageAlt}\">\n";
    echo "  <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    echo "  <meta name=\"twitter:title\" content=\"{$titulo}\">\n";
    echo "  <meta name=\"twitter:description\" content=\"{$descripcion}\">\n";
    echo "  <meta name=\"twitter:image\" content=\"{$ogImage}\">\n";
    echo "  <meta name=\"twitter:image:alt\" content=\"{$ogImageAlt}\">\n";

    if (!empty($meta['json_ld']) && is_array($meta['json_ld'])) {
        emitirJsonLd($meta['json_ld']);
    }
}
