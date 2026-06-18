<?php
declare(strict_types=1);

require_once __DIR__ . '/entorno.php';
require_once __DIR__ . '/respuestas.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/etiquetas-logica.php';
require_once __DIR__ . '/texto.php';
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/correos-contacto.php';

enviarCabecerasCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

try {
    $bd = obtenerConexion();

    if ($metodo === 'POST' && $accion === 'login') {
        $datos = leerCuerpoJson();
        $usuario = trim($datos['usuario'] ?? '');
        $clave   = $datos['clave'] ?? '';

        if ($usuario === '' || $clave === '') {
            responderError('Usuario y clave requeridos.');
        }

        if (!intentarLoginAdmin($usuario, $clave)) {
            registrarLog('info', 'Login admin fallido', ['usuario' => $usuario]);
            responderError('Credenciales incorrectas.', 401);
        }

        registrarLog('info', 'Login admin OK');
        responderJson(['exito' => true, 'mensaje' => 'Sesión iniciada.', 'token' => tokenAdmin()]);
    }

    if ($metodo === 'POST' && $accion === 'logout') {
        responderJson(['exito' => true, 'mensaje' => 'Sesión cerrada.']);
    }

    if ($metodo === 'GET' && $accion === 'sesion') {
        responderJson(['exito' => true, 'autenticado' => adminAutenticado()]);
    }

    exigirAdmin();

    if ($metodo === 'GET' && $accion === 'estadisticas') {
        $stmt = $bd->query(
            "SELECT
                (SELECT COUNT(*) FROM grupos WHERE activo = 1) AS grupos_activos,
                (SELECT COUNT(*) FROM grupos WHERE activo = 0) AS grupos_eliminados,
                (SELECT COUNT(*) FROM reportes WHERE estado = 'pendiente') AS reportes_pendientes,
                (SELECT COUNT(*) FROM reportes) AS reportes_total"
        );
        $s = $stmt->fetch();
        responderJson(['exito' => true, 'estadisticas' => [
            'grupos_activos'     => (int) $s['grupos_activos'],
            'grupos_eliminados'  => (int) $s['grupos_eliminados'],
            'reportes_pendientes'=> (int) $s['reportes_pendientes'],
            'reportes_total'     => (int) $s['reportes_total'],
        ]]);
    }

    if ($metodo === 'GET' && $accion === 'grupos') {
        $orden = $_GET['orden'] ?? 'recientes';
        $incluirInactivos = ($_GET['todos'] ?? '') === '1';

        $ordenSql = match ($orden) {
            'likes'    => 'g.likes DESC, g.visitas DESC',
            'visitas'  => 'g.visitas DESC, g.likes DESC',
            'nombre'   => 'g.nombre ASC',
            'reportes' => 'reportes_pendientes DESC, g.creado_en DESC',
            default    => 'g.creado_en DESC',
        };

        $where = $incluirInactivos ? '1=1' : 'g.activo = 1';

        $sql = "SELECT g.*,
                (SELECT COUNT(*) FROM reportes r WHERE r.grupo_id = g.id AND r.estado = 'pendiente') AS reportes_pendientes
                FROM grupos g
                WHERE {$where}
                ORDER BY {$ordenSql}
                LIMIT 200";

        $filas = $bd->query($sql)->fetchAll();
        $ids = array_map(static fn ($f) => (int) $f['id'], $filas);
        $mapaEtiquetas = obtenerEtiquetasMultiples($bd, $ids);

        $grupos = [];
        foreach ($filas as $fila) {
            $g = mapearGrupoAdmin($fila, $mapaEtiquetas[(int) $fila['id']] ?? []);
            $grupos[] = $g;
        }

        responderJson(['exito' => true, 'grupos' => $grupos]);
    }

    if ($metodo === 'POST' && $accion === 'eliminar') {
        $datos = leerCuerpoJson();
        $grupoId = (int) ($datos['grupo_id'] ?? 0);
        if ($grupoId <= 0) {
            responderError('Grupo no válido.');
        }

        $stmt = $bd->prepare('UPDATE grupos SET activo = 0 WHERE id = :id');
        $stmt->execute([':id' => $grupoId]);

        if ($stmt->rowCount() === 0) {
            responderError('Grupo no encontrado.', 404);
        }

        registrarLog('info', 'Admin eliminó grupo', ['id' => $grupoId]);
        responderJson(['exito' => true, 'mensaje' => 'Grupo eliminado.']);
    }

    if ($metodo === 'POST' && $accion === 'restaurar') {
        $datos = leerCuerpoJson();
        $grupoId = (int) ($datos['grupo_id'] ?? 0);
        $bd->prepare('UPDATE grupos SET activo = 1 WHERE id = :id')->execute([':id' => $grupoId]);
        responderJson(['exito' => true, 'mensaje' => 'Grupo restaurado.']);
    }

    if ($metodo === 'POST' && $accion === 'clasificacion') {
        $datos = leerCuerpoJson();
        $grupoId = (int) ($datos['grupo_id'] ?? 0);
        $clasificacion = ($datos['clasificacion'] ?? 'normal') === 'adulto' ? 'adulto' : 'normal';

        if ($grupoId <= 0) {
            responderError('Grupo no válido.');
        }

        $stmtExiste = $bd->prepare('SELECT id FROM grupos WHERE id = :id LIMIT 1');
        $stmtExiste->execute([':id' => $grupoId]);
        if (!$stmtExiste->fetch()) {
            responderError('Grupo no encontrado.', 404);
        }

        $bd->prepare('UPDATE grupos SET clasificacion = :clasificacion WHERE id = :id')
           ->execute([':clasificacion' => $clasificacion, ':id' => $grupoId]);

        registrarLog('info', 'Admin cambió clasificación', ['id' => $grupoId, 'clasificacion' => $clasificacion]);
        responderJson([
            'exito'         => true,
            'mensaje'       => 'Clasificación actualizada.',
            'clasificacion' => $clasificacion,
            'etiqueta'      => etiquetaClasificacion($clasificacion),
        ]);
    }

    if ($metodo === 'GET' && $accion === 'reportes') {
        $estado = $_GET['estado'] ?? 'pendiente';
        $params = [];
        $where = '1=1';
        if (in_array($estado, ['pendiente', 'revisado', 'descartado'], true)) {
            $where = 'r.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = "SELECT r.*, g.nombre AS grupo_nombre, g.slug AS grupo_slug, g.plataforma
                FROM reportes r
                INNER JOIN grupos g ON g.id = r.grupo_id
                WHERE {$where}
                ORDER BY r.creado_en DESC
                LIMIT 100";
        $stmt = $bd->prepare($sql);
        $stmt->execute($params);

        $reportes = array_map(static function (array $f): array {
            return [
                'id'           => (int) $f['id'],
                'grupo_id'     => (int) $f['grupo_id'],
                'grupo_nombre' => $f['grupo_nombre'],
                'grupo_slug'   => $f['grupo_slug'],
                'plataforma'   => $f['plataforma'],
                'motivo'       => $f['motivo'],
                'detalle'      => $f['detalle'],
                'estado'       => $f['estado'],
                'creado_en'    => $f['creado_en'],
            ];
        }, $stmt->fetchAll());

        responderJson(['exito' => true, 'reportes' => $reportes]);
    }

    if ($metodo === 'POST' && $accion === 'reporte_estado') {
        $datos = leerCuerpoJson();
        $id = (int) ($datos['id'] ?? 0);
        $estado = $datos['estado'] ?? '';
        if (!in_array($estado, ['revisado', 'descartado', 'pendiente'], true)) {
            responderError('Estado no válido.');
        }
        $bd->prepare('UPDATE reportes SET estado = :estado WHERE id = :id')
           ->execute([':estado' => $estado, ':id' => $id]);
        responderJson(['exito' => true, 'mensaje' => 'Reporte actualizado.']);
    }

    if ($metodo === 'GET' && $accion === 'correo_config') {
        $config = configuracionCorreo();
        $totalCorreos = 0;
        try {
            $totalCorreos = (int) $bd->query('SELECT COUNT(*) FROM correos_contacto')->fetchColumn();
        } catch (PDOException) {
            $totalCorreos = 0;
        }
        responderJson([
            'exito'            => true,
            'configurado'      => $config !== null,
            'desde'            => $config['desde'] ?? '',
            'nombre_remitente' => $config['nombre'] ?? 'ZonaGrupos',
            'total_correos'    => $totalCorreos,
        ]);
    }

    if ($metodo === 'GET' && $accion === 'correos') {
        try {
            $correos = obtenerCorreosContacto($bd);
        } catch (PDOException) {
            responderError('Lista de correos no disponible. Ejecuta: npm run actualizar-bd', 500);
        }
        responderJson(['exito' => true, 'correos' => $correos, 'total' => count($correos)]);
    }

    if ($metodo === 'POST' && $accion === 'enviar_correo') {
        $datos = leerCuerpoJson();
        $asunto = trim($datos['asunto'] ?? '');
        $mensaje = trim($datos['mensaje'] ?? '');
        $todos = !empty($datos['todos']);
        $lista = is_array($datos['correos'] ?? null) ? $datos['correos'] : [];
        $manual = validarCorreoPublicacion($datos['para'] ?? '');

        if ($asunto === '' || mb_strlen($asunto) > 200) {
            responderError('El título es obligatorio (máximo 200 caracteres).');
        }
        if ($mensaje === '' || mb_strlen($mensaje) > 5000) {
            responderError('El mensaje es obligatorio (máximo 5000 caracteres).');
        }
        if (configuracionCorreo() === null) {
            responderError('El envío de correo no está configurado en el servidor.');
        }

        $destinos = [];
        if ($todos) {
            try {
                $destinos = obtenerTodosCorreosContacto($bd);
            } catch (PDOException) {
                responderError('Lista de correos no disponible. Ejecuta: npm run actualizar-bd', 500);
            }
        } else {
            $destinos = normalizarListaCorreos($lista);
        }

        if ($manual !== '') {
            $destinos = normalizarListaCorreos(array_merge($destinos, [$manual]));
        }

        if ($destinos === []) {
            responderError('Selecciona al menos un correo o marca enviar a todos.');
        }

        if (count($destinos) > 100) {
            responderError('Máximo 100 correos por envío. Divide la lista en varios envíos.');
        }

        $enviados = 0;
        $fallidos = [];

        foreach ($destinos as $para) {
            if (enviarCorreoGenerico($para, $asunto, $mensaje)) {
                $enviados++;
            } else {
                $fallidos[] = $para;
            }
            usleep(250000);
        }

        registrarLog('info', 'Admin envió correos', [
            'asunto'   => $asunto,
            'total'    => count($destinos),
            'enviados' => $enviados,
            'fallidos' => count($fallidos),
        ]);

        if ($enviados === 0) {
            responderError('No se pudo enviar a ningún destinatario.');
        }

        $texto = $enviados === 1
            ? 'Correo enviado a 1 persona.'
            : "Correo enviado a {$enviados} personas.";

        if ($fallidos !== []) {
            $texto .= ' No se pudo enviar a ' . count($fallidos) . '.';
        }

        responderJson([
            'exito'    => true,
            'mensaje'  => $texto,
            'enviados' => $enviados,
            'fallidos' => $fallidos,
        ]);
    }

    responderError('Acción no encontrada.', 404);

} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'reportes')) {
        responderError('Tabla reportes no existe. Ejecuta: npm run actualizar-reportes', 500);
    }
    registrarLog('error', 'Admin PDO: ' . $e->getMessage());
    responderError('Error de base de datos.', 500);
} catch (Throwable $e) {
    registrarLog('error', 'Admin: ' . $e->getMessage());
    responderError('Error interno.', 500);
}

function mapearGrupoAdmin(array $fila, array $etiquetas): array
{
    return [
        'id'                 => (int) $fila['id'],
        'slug'               => $fila['slug'],
        'url'                => '/grupo/' . ($fila['slug'] ?? 'grupo-' . $fila['id']),
        'nombre'             => $fila['nombre'],
        'descripcion'        => $fila['descripcion'],
        'plataforma'         => $fila['plataforma'],
        'likes'              => (int) $fila['likes'],
        'visitas'            => (int) $fila['visitas'],
        'activo'             => (bool) $fila['activo'],
        'pais'               => ['codigo' => $fila['pais_codigo'], 'nombre' => $fila['pais_nombre']],
        'clasificacion'      => $fila['clasificacion'] ?? 'normal',
        'clasificacion_etiqueta' => etiquetaClasificacion($fila['clasificacion'] ?? 'normal'),
        'reportes_pendientes'=> (int) ($fila['reportes_pendientes'] ?? 0),
        'correo_publicador'  => $fila['correo_publicador'] ?? null,
        'etiquetas'          => $etiquetas,
        'creado_en'          => $fila['creado_en'],
    ];
}
