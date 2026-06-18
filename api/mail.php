<?php
declare(strict_types=1);

require_once __DIR__ . '/cargar-sun.php';
require_once __DIR__ . '/cargar-config.php';
require_once __DIR__ . '/seo.php';

function mailConfig(string $clave, string $defecto = ''): string
{
    $deSun = sunConfig($clave, '');
    if ($deSun !== '') {
        return $deSun;
    }
    return envConfig($clave, $defecto);
}

function configuracionCorreo(): ?array
{
    $host = mailConfig('MAIL_HOST');
    $usuario = mailConfig('MAIL_USUARIO');
    $clave = mailConfig('MAIL_CLAVE');

    if ($host === '' || $usuario === '' || $clave === '') {
        return null;
    }

    $seguridad = strtolower(mailConfig('MAIL_SEGURIDAD', 'ssl'));
    if (!in_array($seguridad, ['ssl', 'tls', 'starttls'], true)) {
        $seguridad = 'ssl';
    }

    return [
        'host'      => $host,
        'port'      => (int) mailConfig('MAIL_PORT', $seguridad === 'ssl' ? '465' : '587'),
        'usuario'   => $usuario,
        'clave'     => $clave,
        'desde'     => mailConfig('MAIL_DESDE', $usuario),
        'nombre'    => mailConfig('MAIL_NOMBRE', 'ZonaGrupos'),
        'seguridad' => $seguridad,
    ];
}

function validarCorreoPublicacion(string $correo): string
{
    $correo = strtolower(trim($correo));
    if ($correo === '' || mb_strlen($correo) > 254) {
        return '';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    return $correo;
}

function enviarCorreoGrupoPublicado(string $para, string $nombreGrupo, string $slug): bool
{
    $config = configuracionCorreo();
    if ($config === null) {
        return false;
    }

    $base = urlBaseApp();
    $urlGrupo = $base . '/grupo/' . rawurlencode($slug);
    $nombreSeguro = htmlspecialchars($nombreGrupo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $asunto = 'Tu grupo ya está en ZonaGrupos';

    $texto = "¡Hola!\n\n"
        . "Tu grupo \"{$nombreGrupo}\" se agregó correctamente a ZonaGrupos.\n\n"
        . "Comparte la página de tu grupo para que más personas lo encuentren:\n"
        . "{$urlGrupo}\n\n"
        . "Gracias por publicar en ZonaGrupos.\n"
        . "— Equipo ZonaGrupos\n"
        . "{$base}\n";

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="font-family:system-ui,sans-serif;line-height:1.5;color:#1a1a1a;max-width:520px;margin:0 auto;padding:24px;">'
        . '<p>¡Hola!</p>'
        . "<p>Tu grupo <strong>{$nombreSeguro}</strong> se agregó correctamente a <strong>ZonaGrupos</strong>.</p>"
        . '<p>Comparte la página de tu grupo para que más personas lo encuentren:</p>'
        . '<p style="margin:24px 0;"><a href="' . htmlspecialchars($urlGrupo, ENT_QUOTES, 'UTF-8') . '" '
        . 'style="display:inline-block;background:#25d366;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;">Ver mi grupo</a></p>'
        . '<p style="font-size:14px;color:#555;">O copia este enlace:<br>'
        . '<a href="' . htmlspecialchars($urlGrupo, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($urlGrupo, ENT_QUOTES, 'UTF-8') . '</a></p>'
        . '<p style="margin-top:32px;font-size:13px;color:#888;">Gracias por publicar en ZonaGrupos.<br>— Equipo ZonaGrupos</p>'
        . '</body></html>';

    return enviarCorreoSmtp($config, $para, $asunto, $html, $texto);
}

function enviarCorreoGenerico(string $para, string $asunto, string $mensaje): bool
{
    $config = configuracionCorreo();
    if ($config === null) {
        return false;
    }

    $para = validarCorreoPublicacion($para);
    $asunto = trim($asunto);
    $mensaje = trim($mensaje);

    if ($para === '' || $asunto === '' || $mensaje === '') {
        return false;
    }

    $mensajeHtml = nl2br(htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="font-family:system-ui,sans-serif;line-height:1.6;color:#1a1a1a;max-width:560px;margin:0 auto;padding:24px;">'
        . $mensajeHtml
        . '<p style="margin-top:32px;font-size:13px;color:#888;">— ZonaGrupos</p>'
        . '</body></html>';

    return enviarCorreoSmtp($config, $para, $asunto, $html, $mensaje);
}

function programarCorreoGrupoPublicado(string $para, string $nombreGrupo, string $slug): void
{
    $script = dirname(__DIR__) . '/scripts/enviar-correo-fondo.php';
    if (!is_readable($script)) {
        enviarCorreoGrupoPublicado($para, $nombreGrupo, $slug);
        return;
    }

    $payload = base64_encode(json_encode([
        'tipo'   => 'grupo_publicado',
        'para'   => $para,
        'nombre' => $nombreGrupo,
        'slug'   => $slug,
    ], JSON_UNESCAPED_UNICODE));

    $php = getenv('PHP_BIN') ?: '';
    if ($php === '' && defined('PHP_BINARY')) {
        $php = PHP_BINARY;
    }
    if ($php === '') {
        $php = 'php';
    }

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = 'cmd /C start /B "" ' . escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($payload);
        $handle = @popen($cmd, 'r');
        if ($handle === false) {
            enviarCorreoGrupoPublicado($para, $nombreGrupo, $slug);
        } else {
            pclose($handle);
        }
        return;
    }

    if (!function_exists('exec')) {
        enviarCorreoGrupoPublicado($para, $nombreGrupo, $slug);
        return;
    }

    exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($payload) . ' > /dev/null 2>&1 &');
}

function enviarCorreoSmtp(array $config, string $para, string $asunto, string $html, string $textoPlano = ''): bool
{
    if (!in_array('ssl', stream_get_transports(), true) && !in_array('tls', stream_get_transports(), true)) {
        if (function_exists('registrarLog')) {
            registrarLog('error', 'PHP sin OpenSSL: no se pueden enviar correos en este entorno', ['para' => $para]);
        }
        return false;
    }

    try {
        $cliente = new ClienteSmtp($config);
        $cliente->conectar();
        $cliente->autenticar();
        $cliente->enviar(
            $config['desde'],
            $config['nombre'],
            $para,
            codificarAsuntoMime($asunto),
            construirCuerpoMime($config['desde'], $config['nombre'], $para, $asunto, $html, $textoPlano)
        );
        $cliente->cerrar();
        return true;
    } catch (Throwable $e) {
        if (function_exists('registrarLog')) {
            registrarLog('error', 'Error al enviar correo', ['error' => $e->getMessage(), 'para' => $para]);
        }
        return false;
    }
}

function codificarAsuntoMime(string $asunto): string
{
    if (preg_match('/[^\x20-\x7E]/', $asunto)) {
        return '=?UTF-8?B?' . base64_encode($asunto) . '?=';
    }
    return $asunto;
}

function construirCuerpoMime(
    string $desde,
    string $nombreDesde,
    string $para,
    string $asunto,
    string $html,
    string $textoPlano
): string {
    $boundary = 'zg_' . bin2hex(random_bytes(12));
    $textoPlano = $textoPlano !== '' ? $textoPlano : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
    $nombreCodificado = codificarNombreMime($nombreDesde);

    $cabeceras = [
        'Date: ' . gmdate('D, d M Y H:i:s') . ' GMT',
        'From: ' . $nombreCodificado . ' <' . $desde . '>',
        'To: <' . $para . '>',
        'Subject: ' . codificarAsuntoMime($asunto),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $cuerpo = implode("\r\n", $cabeceras) . "\r\n\r\n";
    $cuerpo .= '--' . $boundary . "\r\n";
    $cuerpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $cuerpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $cuerpo .= chunk_split(base64_encode($textoPlano)) . "\r\n";
    $cuerpo .= '--' . $boundary . "\r\n";
    $cuerpo .= "Content-Type: text/html; charset=UTF-8\r\n";
    $cuerpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $cuerpo .= chunk_split(base64_encode($html)) . "\r\n";
    $cuerpo .= '--' . $boundary . '--';

    return $cuerpo;
}

function codificarNombreMime(string $nombre): string
{
    if (preg_match('/[^\x20-\x7E]/', $nombre)) {
        return '=?UTF-8?B?' . base64_encode($nombre) . '?=';
    }
    return $nombre;
}

final class ClienteSmtp
{
    private $socket;
    private string $host;
    private int $port;
    private string $usuario;
    private string $clave;
    private string $seguridad;

    public function __construct(array $config)
    {
        $this->host = $config['host'];
        $this->port = (int) $config['port'];
        $this->usuario = $config['usuario'];
        $this->clave = $config['clave'];
        $this->seguridad = $config['seguridad'];
    }

    public function conectar(): void
    {
        $contexto = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        if ($this->seguridad === 'ssl') {
            $destino = 'ssl://' . $this->host . ':' . $this->port;
            $this->socket = @stream_socket_client($destino, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $contexto);
            if ($this->socket === false) {
                throw new RuntimeException("No se pudo conectar a {$destino}: {$errstr} ({$errno})");
            }
            stream_set_timeout($this->socket, 30);
            $this->leerRespuesta([220]);
            $this->enviarComando('EHLO ' . gethostname());
            return;
        }

        $destino = 'tcp://' . $this->host . ':' . $this->port;
        $this->socket = @stream_socket_client($destino, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $contexto);
        if ($this->socket === false) {
            throw new RuntimeException("No se pudo conectar a {$destino}: {$errstr} ({$errno})");
        }
        stream_set_timeout($this->socket, 30);
        $this->leerRespuesta([220]);
        $this->enviarComando('EHLO ' . gethostname());
        $this->enviarComando('STARTTLS');
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('No se pudo iniciar TLS con el servidor SMTP.');
        }
        $this->enviarComando('EHLO ' . gethostname());
    }

    public function autenticar(): void
    {
        $this->enviarComando('AUTH LOGIN');
        $this->enviarComando(base64_encode($this->usuario));
        $this->enviarComando(base64_encode($this->clave));
    }

    public function enviar(string $desde, string $nombreDesde, string $para, string $asunto, string $mensajeCompleto): void
    {
        $this->enviarComando('MAIL FROM:<' . $desde . '>');
        $this->enviarComando('RCPT TO:<' . $para . '>');
        $this->enviarComando('DATA');
        $this->escribir($mensajeCompleto . "\r\n.\r\n");
        $this->leerRespuesta([250]);
    }

    public function cerrar(): void
    {
        if (is_resource($this->socket)) {
            $this->enviarComando('QUIT');
            fclose($this->socket);
        }
    }

    private function enviarComando(string $comando): void
    {
        $this->escribir($comando . "\r\n");
        $codigo = $this->codigoEsperadoPorComando($comando);
        $this->leerRespuesta($codigo);
    }

    private function codigoEsperadoPorComando(string $comando): array
    {
        if (str_starts_with($comando, 'MAIL FROM') || str_starts_with($comando, 'RCPT TO')) {
            return [250, 251];
        }
        if (str_starts_with($comando, 'DATA')) {
            return [354];
        }
        if (str_starts_with($comando, 'AUTH LOGIN')) {
            return [334];
        }
        if (str_starts_with($comando, 'STARTTLS')) {
            return [220];
        }
        if (str_starts_with($comando, 'QUIT')) {
            return [221];
        }
        if (str_starts_with($comando, 'EHLO')) {
            return [250];
        }
        return [235, 250, 334];
    }

    private function escribir(string $datos): void
    {
        $escrito = fwrite($this->socket, $datos);
        if ($escrito === false) {
            throw new RuntimeException('Error al escribir en el socket SMTP.');
        }
    }

    private function leerRespuesta(array $codigosValidos): string
    {
        $respuesta = '';
        while (($linea = fgets($this->socket, 515)) !== false) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }

        if ($respuesta === '') {
            throw new RuntimeException('Sin respuesta del servidor SMTP.');
        }

        $codigo = (int) substr($respuesta, 0, 3);
        if (!in_array($codigo, $codigosValidos, true)) {
            throw new RuntimeException('SMTP inesperado: ' . trim($respuesta));
        }

        return $respuesta;
    }
}
