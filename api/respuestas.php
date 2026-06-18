<?php
declare(strict_types=1);

function enviarCabecerasCors(): void
{
    $config = require __DIR__ . '/configuracion.php';
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origen !== '' && in_array($origen, $config['origenes'], true)) {
        header("Access-Control-Allow-Origin: {$origen}");
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token, X-Geo-Pais, X-Geo-Pais-Nombre');
    header('Content-Type: application/json; charset=utf-8');
}

function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function responderError(string $mensaje, int $codigo = 400): void
{
    responderJson(['exito' => false, 'mensaje' => $mensaje], $codigo);
}

function obtenerHuellaCliente(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'sin-ip';
    $agente = $_SERVER['HTTP_USER_AGENT'] ?? 'sin-agente';
    return hash('sha256', $ip . '|' . $agente);
}
