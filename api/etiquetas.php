<?php
declare(strict_types=1);

require_once __DIR__ . '/entorno.php';
require_once __DIR__ . '/respuestas.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/etiquetas-logica.php';

enviarCabecerasCors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderError('Método no permitido.', 405);
}

try {
    $bd = obtenerConexion();
    $accion = $_GET['accion'] ?? 'tendencia';
    $limite = min(30, max(5, (int) ($_GET['limite'] ?? 15)));

    if ($accion === 'tendencia') {
        $stmt = $bd->prepare(
            'SELECT nombre, usos FROM etiquetas
             WHERE usos > 0
             ORDER BY usos DESC, nombre ASC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $etiquetas = array_map(static function (array $fila): array {
            return [
                'nombre' => $fila['nombre'],
                'usos'   => (int) $fila['usos'],
                'color'  => colorEtiqueta($fila['nombre']),
            ];
        }, $stmt->fetchAll());

        responderJson(['exito' => true, 'etiquetas' => $etiquetas]);
    }

    if ($accion === 'buscar') {
        $termino = trim(ltrim($_GET['q'] ?? '', '#'));
        if ($termino === '') {
            responderJson(['exito' => true, 'etiquetas' => []]);
        }

        $stmt = $bd->prepare(
            'SELECT nombre, usos FROM etiquetas
             WHERE nombre LIKE :termino
             ORDER BY usos DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':termino', $termino . '%');
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $etiquetas = array_map(static function (array $fila): array {
            return [
                'nombre' => $fila['nombre'],
                'usos'   => (int) $fila['usos'],
                'color'  => colorEtiqueta($fila['nombre']),
            ];
        }, $stmt->fetchAll());

        responderJson(['exito' => true, 'etiquetas' => $etiquetas]);
    }

    responderError('Acción no encontrada.', 404);
} catch (Throwable $e) {
    responderError('No se pudieron cargar las etiquetas.', 500);
}
