<?php
declare(strict_types=1);

/** Asegura cadena UTF-8 válida */
function asegurarUtf8(string $texto): string
{
    if (function_exists('mb_check_encoding') && !mb_check_encoding($texto, 'UTF-8')) {
        $convertido = @mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
        if (is_string($convertido)) {
            return $convertido;
        }
    }

    return $texto;
}

/**
 * Texto permitido al publicar: letras latinas (español), números, puntuación común y emojis.
 * Bloquea tipografías especiales, cirílico, árabe, etc.
 */
function textoPublicacionValido(string $texto, bool $permitirSaltos = false): bool
{
    $texto = asegurarUtf8($texto);
    if ($texto === '') {
        return false;
    }

    if (preg_match('/\p{Cyrillic}|\p{Arabic}|\p{Han}|\p{Hangul}|\p{Hebrew}|\p{Devanagari}|\p{Thai}/u', $texto)) {
        return false;
    }

    if (preg_match('/[\x{1D400}-\x{1D7FF}\x{2100}-\x{214F}\x{2460}-\x{24FF}\x{FF00}-\x{FFEF}]/u', $texto)) {
        return false;
    }

    $ctrl = $permitirSaltos ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
    if (preg_match($ctrl, $texto)) {
        return false;
    }

    $permitidos = $permitirSaltos ? '\n\r' : '';
    $patron = '/^(?:[\p{Script=Latin}\p{N}\p{M}\s.,!?¿¡:;\-\'"()@#&%+°/\\[\\]«»' . $permitidos . ']|\p{Extended_Pictographic}|\x{200D}|\x{FE0F})+$/u';

    return preg_match($patron, $texto) === 1;
}

function normalizarTextoPublicacion(string $texto): string
{
    return trim(asegurarUtf8($texto));
}

function mensajeTextoInvalido(): string
{
    return 'Usa letras normales y emojis. No se permiten tipografías especiales ni caracteres raros.';
}

function etiquetaClasificacion(string $clasificacion): string
{
    return $clasificacion === 'adulto' ? 'Contenido sexual (+18)' : 'Grupo general';
}
