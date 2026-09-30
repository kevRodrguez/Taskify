<?php

declare(strict_types=1);

/**
 * Error de un campo de formulario.
 *
 * Variables esperadas:
 * - string $campo
 * - array<string, mixed> $errores  mapa campo => mensaje
 */

if (!isset($campo, $errores) || !is_array($errores) || !array_key_exists($campo, $errores)) {
    return;
}

$mensajeCampo = $errores[$campo];
if (is_array($mensajeCampo)) {
    $mensajeCampo = $mensajeCampo[0] ?? '';
}

if (!is_scalar($mensajeCampo) || (string) $mensajeCampo === '') {
    return;
}
?>
<p class="campo-error mensaje-error" id="<?= e((string) $campo) ?>-error"><?= e((string) $mensajeCampo) ?></p>
