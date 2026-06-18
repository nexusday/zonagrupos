<?php
declare(strict_types=1);

if ($argc < 2) {
    exit(1);
}

$payload = json_decode(base64_decode($argv[1], true) ?: '', true);
if (!is_array($payload) || ($payload['tipo'] ?? '') !== 'grupo_publicado') {
    exit(1);
}

require_once dirname(__DIR__) . '/api/entorno.php';
require_once dirname(__DIR__) . '/api/logger.php';
require_once dirname(__DIR__) . '/api/mail.php';

$para = validarCorreoPublicacion($payload['para'] ?? '');
$nombre = trim($payload['nombre'] ?? '');
$slug = trim($payload['slug'] ?? '');

if ($para === '' || $nombre === '' || $slug === '') {
    exit(1);
}

if (!enviarCorreoGrupoPublicado($para, $nombre, $slug)) {
    registrarLog('warning', 'Correo en segundo plano no enviado', [
        'para' => $para,
        'slug' => $slug,
    ]);
    exit(1);
}

exit(0);
