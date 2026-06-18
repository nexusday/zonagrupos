<?php
declare(strict_types=1);

require_once __DIR__ . '/entorno.php';
require_once __DIR__ . '/respuestas.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/etiquetas-logica.php';
require_once __DIR__ . '/geo.php';
require_once __DIR__ . '/logger.php';

enviarCabecerasCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$plataformasValidas = ['whatsapp', 'telegram', 'discord'];

function generarSlug(string $nombre, int $id): string
{
    $texto = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre);
    if ($texto === false || $texto === '') {
        $texto = $nombre;
    }
    $texto = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($texto));
    $texto = trim($texto, '-');
    if ($texto === '') {
        $texto = 'grupo';
    }
    return substr($texto, 0, 90) . '-' . $id;
}

function mapearGrupo(array $fila, array $etiquetas = [], bool $incluirEnlace = false, ?array $paisVisitante = null): array
{
    $slug = $fila['slug'] ?? ('grupo-' . $fila['id']);
    $grupo = [
        'id'               => (int) $fila['id'],
        'slug'             => $slug,
        'url'              => '/grupo/' . $slug,
        'nombre'           => $fila['nombre'],
        'descripcion'      => $fila['descripcion'],
        'plataforma'       => $fila['plataforma'],
        'likes'            => (int) $fila['likes'],
        'visitas'          => (int) $fila['visitas'],
        'etiquetas'        => $etiquetas,
        'pais'             => [
            'codigo' => $fila['pais_codigo'] ?? 'LAT',
            'nombre' => $fila['pais_nombre'] ?? 'Latinoamérica',
        ],
        'restriccion_pais' => $fila['restriccion_pais'] ?? 'todos',
        'creado_en'        => $fila['creado_en'],
    ];

    if ($incluirEnlace) {
        $grupo['enlace'] = $fila['enlace'];
    }

    if ($paisVisitante !== null) {
        $grupo['pais_visitante'] = $paisVisitante;
        $grupo['puede_unirse'] = puedeUnirseAlGrupo($fila, $paisVisitante);
    }

    return $grupo;
}

function buscarGrupoPorSlugOId(PDO $bd, string $slug = '', int $id = 0): ?array
{
    if ($slug !== '') {
        $stmt = $bd->prepare('SELECT * FROM grupos WHERE slug = :slug AND activo = 1 LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $fila = $stmt->fetch();
        if ($fila) {
            return $fila;
        }
    }

    if ($id > 0) {
        $stmt = $bd->prepare('SELECT * FROM grupos WHERE id = :id AND activo = 1 LIMIT 1');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        if ($fila) {
            return $fila;
        }
    }

    return null;
}

/** Cuenta visita solo en página de detalle; 1 por visitante cada 24 h */
function registrarVisitaGrupo(PDO $bd, int $grupoId, string $huella): int
{
    try {
        $stmtDup = $bd->prepare(
            "SELECT id FROM visitas_registro
             WHERE grupo_id = :grupo_id AND huella = :huella
               AND creado_en > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             LIMIT 1"
        );
        $stmtDup->execute([':grupo_id' => $grupoId, ':huella' => $huella]);
        if ($stmtDup->fetch()) {
            return 0;
        }

        $bd->prepare(
            'INSERT INTO visitas_registro (grupo_id, huella) VALUES (:grupo_id, :huella)'
        )->execute([':grupo_id' => $grupoId, ':huella' => $huella]);
        $bd->prepare('UPDATE grupos SET visitas = visitas + 1 WHERE id = :id')->execute([':id' => $grupoId]);

        return 1;
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'visitas_registro')) {
            $bd->prepare('UPDATE grupos SET visitas = visitas + 1 WHERE id = :id')->execute([':id' => $grupoId]);
            return 1;
        }
        throw $e;
    }
}

function validarEnlacePlataforma(string $enlace, string $plataforma): bool
{
    if (!filter_var($enlace, FILTER_VALIDATE_URL)) {
        return false;
    }

    $patrones = [
        'whatsapp' => '/(chat\.whatsapp\.com|wa\.me)/i',
        'telegram' => '/(t\.me|telegram\.me)/i',
        'discord'  => '/(discord\.gg|discord\.com\/invite)/i',
    ];

    $patron = $patrones[$plataforma] ?? null;

    return $patron !== null && preg_match($patron, $enlace) === 1;
}

try {
    $bd = obtenerConexion();
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = $_GET['accion'] ?? '';

    if ($metodo === 'GET' && $accion === 'detalle') {
        $slug = trim($_GET['slug'] ?? '');
        $id   = (int) ($_GET['id'] ?? 0);

        $fila = buscarGrupoPorSlugOId($bd, $slug, $id);
        if (!$fila) {
            responderError('Grupo no encontrado.', 404);
        }

        $grupoId = (int) $fila['id'];
        $huella = obtenerHuellaCliente();
        $incremento = registrarVisitaGrupo($bd, $grupoId, $huella);
        $fila['visitas'] = (int) $fila['visitas'] + $incremento;

        $paisVisitante = obtenerPaisVisitante();

        $stmtLike = $bd->prepare(
            'SELECT id FROM likes_registro WHERE grupo_id = :grupo_id AND huella = :huella'
        );
        $stmtLike->execute([':grupo_id' => $grupoId, ':huella' => $huella]);
        $yaDioLike = (bool) $stmtLike->fetch();

        $grupo = mapearGrupo(
            $fila,
            obtenerEtiquetasGrupo($bd, $grupoId),
            true,
            $paisVisitante
        );
        $grupo['ya_dio_like'] = $yaDioLike;

        registrarLog('info', 'Detalle de grupo', ['id' => $grupoId, 'slug' => $fila['slug']]);

        responderJson(['exito' => true, 'grupo' => $grupo]);
    }

    if ($metodo === 'GET' && $accion === '') {
        $busqueda   = trim($_GET['busqueda'] ?? '');
        $plataforma = trim($_GET['plataforma'] ?? '');
        $etiqueta   = trim(ltrim($_GET['etiqueta'] ?? '', '#'));
        $orden      = trim($_GET['orden'] ?? 'recientes');
        $pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina  = min(50, max(6, (int) ($_GET['por_pagina'] ?? 12)));
        $desplazamiento = ($pagina - 1) * $porPagina;

        $condiciones = ['g.activo = 1'];
        $parametros  = [];
        $joins       = '';

        if ($busqueda !== '') {
            $terminoEtiqueta = ltrim($busqueda, '#');
            if (str_starts_with($busqueda, '#') && $terminoEtiqueta !== '') {
                $joins = 'INNER JOIN grupo_etiquetas ge ON ge.grupo_id = g.id
                          INNER JOIN etiquetas e ON e.id = ge.etiqueta_id';
                $condiciones[] = 'e.nombre = :etiqueta_busqueda';
                $parametros[':etiqueta_busqueda'] = mb_strtolower($terminoEtiqueta, 'UTF-8');
            } else {
                $condiciones[] = '(g.nombre LIKE :busqueda OR g.descripcion LIKE :busqueda2)';
                $parametros[':busqueda']  = '%' . $busqueda . '%';
                $parametros[':busqueda2'] = '%' . $busqueda . '%';
            }
        }

        if ($plataforma !== '' && in_array($plataforma, $plataformasValidas, true)) {
            $condiciones[] = 'g.plataforma = :plataforma';
            $parametros[':plataforma'] = $plataforma;
        }

        if ($etiqueta !== '') {
            if ($joins === '') {
                $joins = 'INNER JOIN grupo_etiquetas ge ON ge.grupo_id = g.id
                          INNER JOIN etiquetas e ON e.id = ge.etiqueta_id';
            }
            $condiciones[] = 'e.nombre = :etiqueta_filtro';
            $parametros[':etiqueta_filtro'] = mb_strtolower($etiqueta, 'UTF-8');
        }

        $ordenSql = match ($orden) {
            'populares' => 'g.likes DESC, g.visitas DESC',
            'visitas'   => 'g.visitas DESC',
            default     => 'g.creado_en DESC',
        };

        $where = implode(' AND ', $condiciones);
        $from  = "FROM grupos g {$joins}";

        $stmtConteo = $bd->prepare("SELECT COUNT(DISTINCT g.id) AS total {$from} WHERE {$where}");
        $stmtConteo->execute($parametros);
        $total = (int) $stmtConteo->fetch()['total'];

        $sql = "SELECT DISTINCT g.* {$from} WHERE {$where} ORDER BY {$ordenSql}
                LIMIT :limite OFFSET :desplazamiento";

        $stmt = $bd->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $stmt->execute();

        $filas = $stmt->fetchAll();
        $ids = array_map(static fn ($f) => (int) $f['id'], $filas);
        $mapaEtiquetas = obtenerEtiquetasMultiples($bd, $ids);

        $grupos = [];
        foreach ($filas as $fila) {
            $id = (int) $fila['id'];
            $grupos[] = mapearGrupo($fila, $mapaEtiquetas[$id] ?? []);
        }

        $huella = obtenerHuellaCliente();
        if ($ids !== []) {
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $stmtLikes = $bd->prepare(
                "SELECT grupo_id FROM likes_registro WHERE huella = ? AND grupo_id IN ({$marcadores})"
            );
            $stmtLikes->execute(array_merge([$huella], $ids));
            $likesDelUsuario = array_column($stmtLikes->fetchAll(), 'grupo_id');
            foreach ($grupos as &$grupo) {
                $grupo['ya_dio_like'] = in_array($grupo['id'], $likesDelUsuario, true);
            }
            unset($grupo);
        }

        responderJson([
            'exito'      => true,
            'grupos'     => $grupos,
            'paginacion' => [
                'pagina'        => $pagina,
                'por_pagina'    => $porPagina,
                'total'         => $total,
                'total_paginas' => (int) ceil(max(1, $total) / $porPagina),
            ],
        ]);
    }

    if ($metodo === 'GET' && $accion === 'estadisticas') {
        $stmt = $bd->query(
            "SELECT
                (SELECT COUNT(*) FROM grupos WHERE activo = 1) AS total_grupos,
                (SELECT COALESCE(SUM(likes), 0) FROM grupos WHERE activo = 1) AS total_likes,
                (SELECT COALESCE(SUM(visitas), 0) FROM grupos WHERE activo = 1) AS total_visitas"
        );
        $stats = $stmt->fetch();

        responderJson([
            'exito' => true,
            'estadisticas' => [
                'total_grupos'  => (int) $stats['total_grupos'],
                'total_likes'   => (int) $stats['total_likes'],
                'total_visitas' => (int) $stats['total_visitas'],
            ],
        ]);
    }

    if ($metodo === 'POST' && $accion === 'crear') {
        $datos = leerCuerpoJson();
        registrarLog('info', 'Intento publicar grupo', ['datos' => array_keys($datos)]);

        $nombre      = trim($datos['nombre'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');
        $enlace      = trim($datos['enlace'] ?? '');
        $plataforma  = trim($datos['plataforma'] ?? 'whatsapp');
        $restriccion = ($datos['restriccion_pais'] ?? 'todos') === 'solo_pais' ? 'solo_pais' : 'todos';

        if ($nombre === '' || mb_strlen($nombre) < 3) {
            responderError('El nombre debe tener al menos 3 caracteres.');
        }

        if (mb_strlen($descripcion) < 3) {
            responderError('La descripción es obligatoria.');
        }

        $etiquetas = normalizarListaEtiquetas($datos['etiquetas'] ?? []);
        if ($etiquetas === []) {
            responderError('Agrega al menos una etiqueta (ej: gaming, amigos).');
        }

        if (count($etiquetas) > 10) {
            responderError('Máximo 10 etiquetas por grupo.');
        }

        if ($enlace === '') {
            responderError('Debes ingresar el enlace del grupo.');
        }

        if (!in_array($plataforma, $plataformasValidas, true)) {
            responderError('Plataforma no válida.');
        }

        if (!validarEnlacePlataforma($enlace, $plataforma)) {
            $mensajes = [
                'whatsapp' => 'El enlace debe ser de WhatsApp (chat.whatsapp.com o wa.me).',
                'telegram' => 'El enlace debe ser de Telegram (t.me).',
                'discord'  => 'El enlace debe ser de Discord (discord.gg).',
            ];
            responderError($mensajes[$plataforma] ?? 'Solo se permiten enlaces de WhatsApp, Telegram o Discord.');
        }

        $stmtExiste = $bd->prepare('SELECT id, activo FROM grupos WHERE enlace = :enlace LIMIT 1');
        $stmtExiste->execute([':enlace' => $enlace]);
        $grupoExistente = $stmtExiste->fetch();

        if ($grupoExistente && (int) $grupoExistente['activo'] === 1) {
            responderError('Ese enlace ya está registrado.');
        }

        $pais = obtenerPaisVisitante();

        $bd->beginTransaction();

        if ($grupoExistente && (int) $grupoExistente['activo'] === 0) {
            $nuevoId = (int) $grupoExistente['id'];
            $slug = generarSlug($nombre, $nuevoId);

            $bd->prepare(
                'UPDATE grupos SET
                    nombre = :nombre,
                    descripcion = :descripcion,
                    plataforma = :plataforma,
                    pais_codigo = :pais_codigo,
                    pais_nombre = :pais_nombre,
                    restriccion_pais = :restriccion_pais,
                    slug = :slug,
                    activo = 1,
                    likes = 0,
                    visitas = 0,
                    creado_en = NOW()
                 WHERE id = :id'
            )->execute([
                ':nombre'           => $nombre,
                ':descripcion'      => $descripcion,
                ':plataforma'       => $plataforma,
                ':pais_codigo'      => $pais['codigo'],
                ':pais_nombre'      => $pais['nombre'],
                ':restriccion_pais' => $restriccion,
                ':slug'             => $slug,
                ':id'               => $nuevoId,
            ]);

            guardarEtiquetasGrupo($bd, $nuevoId, $etiquetas);
            $bd->commit();

            registrarLog('info', 'Grupo republicado (reactivado)', ['id' => $nuevoId, 'slug' => $slug]);
        } else {
            $stmt = $bd->prepare(
                'INSERT INTO grupos (nombre, descripcion, enlace, plataforma, pais_codigo, pais_nombre, restriccion_pais)
                 VALUES (:nombre, :descripcion, :enlace, :plataforma, :pais_codigo, :pais_nombre, :restriccion_pais)'
            );
            $stmt->execute([
                ':nombre'           => $nombre,
                ':descripcion'      => $descripcion,
                ':enlace'           => $enlace,
                ':plataforma'       => $plataforma,
                ':pais_codigo'      => $pais['codigo'],
                ':pais_nombre'      => $pais['nombre'],
                ':restriccion_pais' => $restriccion,
            ]);

            $nuevoId = (int) $bd->lastInsertId();
            $slug = generarSlug($nombre, $nuevoId);

            $bd->prepare('UPDATE grupos SET slug = :slug WHERE id = :id')
               ->execute([':slug' => $slug, ':id' => $nuevoId]);

            guardarEtiquetasGrupo($bd, $nuevoId, $etiquetas);
            $bd->commit();

            registrarLog('info', 'Grupo publicado', ['id' => $nuevoId, 'slug' => $slug, 'pais' => $pais['codigo']]);
        }

        $stmtGrupo = $bd->prepare('SELECT * FROM grupos WHERE id = :id');
        $stmtGrupo->execute([':id' => $nuevoId]);
        $grupo = mapearGrupo($stmtGrupo->fetch(), obtenerEtiquetasGrupo($bd, $nuevoId), true);
        $grupo['ya_dio_like'] = false;
        $grupo['puede_unirse'] = true;

        responderJson([
            'exito'   => true,
            'mensaje' => '¡Grupo publicado correctamente!',
            'grupo'   => $grupo,
        ], 201);
    }

    if ($metodo === 'POST' && $accion === 'unirse') {
        $datos = leerCuerpoJson();
        $grupoId = (int) ($datos['grupo_id'] ?? 0);
        $slug = trim($datos['slug'] ?? '');

        $fila = buscarGrupoPorSlugOId($bd, $slug, $grupoId);
        if (!$fila) {
            responderError('Grupo no encontrado.', 404);
        }

        $paisVisitante = obtenerPaisVisitante();
        if (!puedeUnirseAlGrupo($fila, $paisVisitante)) {
            registrarLog('info', 'Acceso denegado por país', [
                'grupo' => $fila['id'],
                'visitante' => $paisVisitante['codigo'],
                'grupo_pais' => $fila['pais_codigo'],
            ]);
            responderError(
                'Este grupo es solo para personas en ' . $fila['pais_nombre'] .
                '. Tu ubicación detectada: ' . $paisVisitante['nombre'] . '.',
                403
            );
        }

        registrarLog('info', 'Unirse al grupo', ['id' => $fila['id']]);

        responderJson([
            'exito'  => true,
            'enlace' => $fila['enlace'],
        ]);
    }

    if ($metodo === 'POST' && $accion === 'like') {
        $datos = leerCuerpoJson();
        $grupoId = (int) ($datos['grupo_id'] ?? 0);
        $huella  = obtenerHuellaCliente();

        if ($grupoId <= 0) {
            responderError('Grupo no válido.');
        }

        $stmtGrupo = $bd->prepare('SELECT id, likes FROM grupos WHERE id = :id AND activo = 1');
        $stmtGrupo->execute([':id' => $grupoId]);
        $grupo = $stmtGrupo->fetch();

        if (!$grupo) {
            responderError('El grupo no existe.', 404);
        }

        $stmtLike = $bd->prepare(
            'SELECT id FROM likes_registro WHERE grupo_id = :grupo_id AND huella = :huella'
        );
        $stmtLike->execute([':grupo_id' => $grupoId, ':huella' => $huella]);

        if ($stmtLike->fetch()) {
            responderJson([
                'exito'       => true,
                'mensaje'     => 'Ya habías dado like a este grupo.',
                'likes'       => (int) $grupo['likes'],
                'ya_dio_like' => true,
            ]);
        }

        $bd->beginTransaction();
        $bd->prepare('INSERT INTO likes_registro (grupo_id, huella) VALUES (:grupo_id, :huella)')
           ->execute([':grupo_id' => $grupoId, ':huella' => $huella]);
        $bd->prepare('UPDATE grupos SET likes = likes + 1 WHERE id = :id')->execute([':id' => $grupoId]);
        $bd->commit();

        responderJson([
            'exito'       => true,
            'mensaje'     => '¡Gracias por tu like!',
            'likes'       => (int) $grupo['likes'] + 1,
            'ya_dio_like' => true,
        ]);
    }

    responderError('Ruta no encontrada.', 404);

} catch (PDOException $e) {
    if (isset($bd) && $bd->inTransaction()) {
        $bd->rollBack();
    }
    registrarLog('error', 'PDO: ' . $e->getMessage());
    if (str_contains($e->getMessage(), 'uq_enlace')) {
        responderError('Ese enlace ya está registrado.');
    }
    if (str_contains($e->getMessage(), 'Unknown column')) {
        responderError('Base de datos desactualizada. Ejecuta: npm run actualizar-bd', 500);
    }
    responderError('Error de base de datos.', 500);
} catch (Throwable $e) {
    if (isset($bd) && $bd->inTransaction()) {
        $bd->rollBack();
    }
    registrarLog('error', $e->getMessage(), ['archivo' => $e->getFile(), 'linea' => $e->getLine()]);
    $detalle = (getenv('APP_DEBUG') === '1') ? $e->getMessage() : 'Error interno del servidor.';
    responderError($detalle, 500);
}
