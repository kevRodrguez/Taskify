<?php

declare(strict_types=1);

/**
 * Escapa un valor antes de imprimirlo en HTML.
 *
 * [SEGURIDAD] Toda salida visible pasa por htmlspecialchars
 * (ENT_QUOTES | ENT_SUBSTITUTE, UTF-8) para evitar XSS.
 */
function e(string|int|float|null $v): string
{
    $texto = $v === null ? '' : (string) $v;

    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
