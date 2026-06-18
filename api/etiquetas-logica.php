<?php
declare(strict_types=1);

/**
 * Etiquetas del grupo — campo separado (no se extraen de la descripción).
 */
function normalizarListaEtiquetas(mixed $entrada): array
{
    if (is_string($entrada)) {
        $entrada = preg_split('/[\s,;]+/', $entrada, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    if (!is_array($entrada)) {
        return [];
    }

    $unicas = [];
    foreach ($entrada as $item) {
        $nombre = mb_strtolower(ltrim(trim((string) $item), '#'), 'UTF-8');
        if ($nombre === '' || !preg_match('/^[\p{L}\p{N}_]{2,30}$/u', $nombre)) {
            continue;
        }
        $unicas[$nombre] = $nombre;
    }

    return array_values($unicas);
}

/** @deprecated Solo compatibilidad; usar normalizarListaEtiquetas */
function extraerEtiquetas(string $texto): array
{
    return normalizarListaEtiquetas($texto);
}

function colorEtiqueta(string $nombre): string
{
    $paleta = ['#7c3aed', '#06b6d4', '#ec4899', '#f59e0b', '#22c55e', '#3b82f6', '#ef4444', '#a855f7'];
    $indice = abs(crc32($nombre)) % count($paleta);
    return $paleta[$indice];
}

function guardarEtiquetasGrupo(PDO $bd, int $grupoId, array $nombres): void
{
    $bd->prepare('DELETE FROM grupo_etiquetas WHERE grupo_id = :id')->execute([':id' => $grupoId]);

    if ($nombres === []) {
        return;
    }

    $stmtBuscar = $bd->prepare('SELECT id FROM etiquetas WHERE nombre = :nombre LIMIT 1');
    $stmtCrear  = $bd->prepare('INSERT INTO etiquetas (nombre) VALUES (:nombre)');
    $stmtUnir   = $bd->prepare(
        'INSERT IGNORE INTO grupo_etiquetas (grupo_id, etiqueta_id) VALUES (:grupo_id, :etiqueta_id)'
    );
    $stmtUsos   = $bd->prepare('UPDATE etiquetas SET usos = usos + 1 WHERE id = :id');

    foreach ($nombres as $nombre) {
        $stmtBuscar->execute([':nombre' => $nombre]);
        $fila = $stmtBuscar->fetch();

        if ($fila) {
            $etiquetaId = (int) $fila['id'];
        } else {
            $stmtCrear->execute([':nombre' => $nombre]);
            $etiquetaId = (int) $bd->lastInsertId();
        }

        $stmtUnir->execute([':grupo_id' => $grupoId, ':etiqueta_id' => $etiquetaId]);
        $stmtUsos->execute([':id' => $etiquetaId]);
    }
}

function obtenerEtiquetasGrupo(PDO $bd, int $grupoId): array
{
    $stmt = $bd->prepare(
        'SELECT e.nombre, e.usos FROM etiquetas e
         INNER JOIN grupo_etiquetas ge ON ge.etiqueta_id = e.id
         WHERE ge.grupo_id = :grupo_id
         ORDER BY e.usos DESC, e.nombre ASC'
    );
    $stmt->execute([':grupo_id' => $grupoId]);

    return array_map(static function (array $fila): array {
        return [
            'nombre' => $fila['nombre'],
            'usos'   => (int) $fila['usos'],
            'color'  => colorEtiqueta($fila['nombre']),
        ];
    }, $stmt->fetchAll());
}

function obtenerEtiquetasMultiples(PDO $bd, array $grupoIds): array
{
    if ($grupoIds === []) {
        return [];
    }

    $marcadores = implode(',', array_fill(0, count($grupoIds), '?'));
    $stmt = $bd->prepare(
        "SELECT ge.grupo_id, e.nombre, e.usos
         FROM grupo_etiquetas ge
         INNER JOIN etiquetas e ON e.id = ge.etiqueta_id
         WHERE ge.grupo_id IN ({$marcadores})
         ORDER BY e.usos DESC"
    );
    $stmt->execute($grupoIds);

    $mapa = [];
    foreach ($stmt->fetchAll() as $fila) {
        $gid = (int) $fila['grupo_id'];
        $mapa[$gid][] = [
            'nombre' => $fila['nombre'],
            'usos'   => (int) $fila['usos'],
            'color'  => colorEtiqueta($fila['nombre']),
        ];
    }

    return $mapa;
}
