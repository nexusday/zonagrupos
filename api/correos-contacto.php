<?php
declare(strict_types=1);

require_once __DIR__ . '/mail.php';

function registrarCorreoContacto(PDO $bd, string $correo, string $nombreGrupo): void
{
    $correo = validarCorreoPublicacion($correo);
    if ($correo === '') {
        return;
    }

    $nombreGrupo = mb_substr(trim($nombreGrupo), 0, 120);

    $bd->prepare(
        'INSERT INTO correos_contacto (correo, grupos_publicados, ultimo_grupo_nombre)
         VALUES (:correo, 1, :nombre)
         ON DUPLICATE KEY UPDATE
           grupos_publicados = grupos_publicados + 1,
           ultimo_grupo_nombre = :nombre2,
           actualizado_en = NOW()'
    )->execute([
        ':correo'   => $correo,
        ':nombre'   => $nombreGrupo,
        ':nombre2'  => $nombreGrupo,
    ]);
}

function obtenerCorreosContacto(PDO $bd, int $limite = 500): array
{
    $stmt = $bd->prepare(
        'SELECT correo, grupos_publicados, ultimo_grupo_nombre, creado_en, actualizado_en
         FROM correos_contacto
         ORDER BY actualizado_en DESC
         LIMIT :limite'
    );
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    return array_map(static function (array $fila): array {
        return [
            'correo'              => $fila['correo'],
            'grupos_publicados'   => (int) $fila['grupos_publicados'],
            'ultimo_grupo_nombre' => $fila['ultimo_grupo_nombre'],
            'creado_en'           => $fila['creado_en'],
            'actualizado_en'      => $fila['actualizado_en'],
        ];
    }, $stmt->fetchAll());
}

function obtenerTodosCorreosContacto(PDO $bd): array
{
    $filas = $bd->query('SELECT correo FROM correos_contacto ORDER BY correo ASC')->fetchAll();
    $correos = [];
    foreach ($filas as $fila) {
        $correo = validarCorreoPublicacion($fila['correo'] ?? '');
        if ($correo !== '') {
            $correos[] = $correo;
        }
    }
    return $correos;
}

function normalizarListaCorreos(array $lista): array
{
    $unicos = [];
    foreach ($lista as $item) {
        $correo = validarCorreoPublicacion(is_string($item) ? $item : '');
        if ($correo !== '') {
            $unicos[$correo] = true;
        }
    }
    return array_keys($unicos);
}
