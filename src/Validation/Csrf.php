<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Token CSRF almacenado en sesión para formularios POST.
 */
final class Csrf
{
    private const CLAVE = '_csrf_token';

    /** [SEGURIDAD] Devuelve el token actual; lo genera con random_bytes si aún no existe. */
    public static function token(): string
    {
        if (!isset($_SESSION[self::CLAVE]) || !is_string($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLAVE];
    }

    /** [SEGURIDAD] Compara el token recibido con el de sesión usando hash_equals. */
    public static function verificar(?string $token): bool
    {
        if ($token === null || !isset($_SESSION[self::CLAVE]) || !is_string($_SESSION[self::CLAVE])) {
            return false;
        }

        return hash_equals($_SESSION[self::CLAVE], $token);
    }
}
