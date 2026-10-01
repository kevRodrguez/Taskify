<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Mensajes temporales en sesión para el patrón PRG (Post/Redirect/Get).
 */
final class Flash
{
    private const CLAVE = '_flash';

    /** [PRG] Guarda un mensaje que se mostrará tras la redirección. */
    public static function set(string $clave, mixed $valor): void
    {
        if (!isset($_SESSION[self::CLAVE]) || !is_array($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = [];
        }

        $_SESSION[self::CLAVE][$clave] = $valor;
    }

    /** [PRG] Lee el mensaje y lo elimina de la sesión (solo se muestra una vez). */
    public static function obtener(string $clave): mixed
    {
        if (!isset($_SESSION[self::CLAVE]) || !is_array($_SESSION[self::CLAVE])) {
            return null;
        }

        $valor = $_SESSION[self::CLAVE][$clave] ?? null;
        unset($_SESSION[self::CLAVE][$clave]);

        return $valor;
    }
}
