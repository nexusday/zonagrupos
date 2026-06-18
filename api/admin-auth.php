<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-sun.php';

function tokenAdmin(): string
{
    $user = sunConfig('ADMIN_USUARIO');
    $pass = sunConfig('ADMIN_CLAVE');
    if ($user === '' || $pass === '') {
        return '';
    }
    return hash('sha256', $user . '|' . $pass . '|zg-admin-v1');
}

function adminAutenticado(): bool
{
    $esperado = tokenAdmin();
    if ($esperado === '') {
        return false;
    }
    $recibido = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    return $recibido !== '' && hash_equals($esperado, $recibido);
}

function exigirAdmin(): void
{
    if (!adminAutenticado()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exito' => false, 'mensaje' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function intentarLoginAdmin(string $usuario, string $clave): ?string
{
    $userOk = sunConfig('ADMIN_USUARIO');
    $passOk = sunConfig('ADMIN_CLAVE');

    if ($userOk === '' || $passOk === '') {
        return null;
    }

    if (!hash_equals($userOk, $usuario) || !hash_equals($passOk, $clave)) {
        return null;
    }

    return tokenAdmin();
}

function rutaPanelAdmin(): string
{
    $ruta = trim(sunConfig('ADMIN_RUTA', 'zg-x7k9m2p'), '/');
    return '/' . $ruta;
}
