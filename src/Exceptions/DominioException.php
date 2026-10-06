<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Se lanza cuando se viola una invariante del dominio (título vacío, avance
 * fuera de 0–100, vencimiento anterior a la creación, etc.).
 *
 * Extiende InvalidArgumentException para que el código de la Fase 1 que
 * captura esa clase siga funcionando.
 */
final class DominioException extends InvalidArgumentException
{
}
