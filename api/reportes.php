<?php
declare(strict_types=1);

require_once __DIR__ . '/entorno.php';
require_once __DIR__ . '/respuestas.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/logger.php';

enviarCabecerasCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderError('Método no permitido.', 405);
}

$accion = $_GET['accion'] ?? 'crear';
if ($accion !== 'crear') {
    responderError('Acción no válida.', 404);
}

$motivosValidos = ['spam', 'inapropiado', 'enlace_roto', 'estafa', 'otro'];

try {
    $datos = leerCuerpoJson();
    $grupoId = (int) ($datos['grupo_id'] ?? 0);
    $motivo  = trim($datos['motivo'] ?? 'otro');
    $detalle = trim($datos['detalle'] ?? '');
    $huella  = obtenerHuellaCliente();

    if ($grupoId <= 0) {
        responderError('Grupo no válido.');
    }

    if (!in_array($motivo, $motivosValidos, true)) {
        responderError('Motivo no válido.');
    }

    if (mb_strlen($detalle) > 500) {
        responderError('El detalle es demasiado largo.');
    }

    $bd = obtenerConexion();

    $stmt = $bd->prepare('SELECT id FROM grupos WHERE id = :id AND activo = 1');
    $stmt->execute([':id' => $grupoId]);
    if (!$stmt->fetch()) {
        responderError('El grupo no existe.', 404);
    }

    $stmtDup = $bd->prepare(
        "SELECT id FROM reportes
         WHERE grupo_id = :grupo_id AND huella = :huella
           AND creado_en > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         LIMIT 1"
    );
    $stmtDup->execute([':grupo_id' => $grupoId, ':huella' => $huella]);
    if ($stmtDup->fetch()) {
        responderError('Ya reportaste este grupo recientemente.');
    }

    $bd->prepare(
        'INSERT INTO reportes (grupo_id, motivo, detalle, huella) VALUES (:grupo_id, :motivo, :detalle, :huella)'
    )->execute([
        ':grupo_id' => $grupoId,
        ':motivo'   => $motivo,
        ':detalle'  => $detalle,
        ':huella'   => $huella,
    ]);

    registrarLog('info', 'Reporte de grupo', ['grupo_id' => $grupoId, 'motivo' => $motivo]);

    responderJson(['exito' => true, 'mensaje' => 'Gracias. Revisaremos tu reporte.'], 201);

} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'reportes')) {
        responderError('Sistema de reportes no disponible aún.', 503);
    }
    responderError('Error al enviar reporte.', 500);
}
