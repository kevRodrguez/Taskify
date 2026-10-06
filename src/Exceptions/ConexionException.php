<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Error al conectar con la base de datos. Su mensaje es genérico a propósito:
 * el detalle técnico de PDO nunca se muestra al usuario.
 */
final class ConexionException extends RuntimeException
{
}
