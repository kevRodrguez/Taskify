<?php

declare(strict_types=1);

/**
 * Alerta de éxito o error a partir del mensaje flash (PRG).
 *
 * Lee y borra las claves "exito" y "error" mediante Flash::obtener().
 */

$mensajeExito = null;
$mensajeError = null;

if (class_exists(\App\Validation\Flash::class)) {
    $mensajeExito = \App\Validation\Flash::obtener('exito');
    $mensajeError = \App\Validation\Flash::obtener('error');
}

$normalizarFlash = static function (mixed $valor): ?string {
    if (is_array($valor)) {
        $partes = [];
        foreach ($valor as $parte) {
            if (is_scalar($parte) && (string) $parte !== '') {
                $partes[] = (string) $parte;
            }
        }
        $valor = implode(' ', $partes);
    }

    if (!is_scalar($valor)) {
        return null;
    }

    $texto = trim((string) $valor);

    return $texto === '' ? null : $texto;
};

$textoExito = $normalizarFlash($mensajeExito);
$textoError = $normalizarFlash($mensajeError);
?>
<?php if ($textoExito !== null): ?>
    <p class="alerta-exito" role="status"><?= e($textoExito) ?></p>
<?php endif; ?>
<?php if ($textoError !== null): ?>
    <p class="alerta-error" role="alert"><?= e($textoError) ?></p>
<?php endif; ?>
