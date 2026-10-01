<?php

declare(strict_types=1);

/**
 * Campo de carga de imagen para los formularios de tarea (crear y editar).
 *
 * Variables esperadas:
 * - array<string, string> $errores  mapa campo => mensaje (usa la clave 'imagen')
 * - string|null $imagenActual       nombre de la imagen guardada, al editar
 * - string|null $imagenTitulo       título de la tarea, para el texto alternativo
 *
 * El formulario que lo incluye debe tener enctype="multipart/form-data".
 * La vista previa (public/js/preview.js) es solo apoyo visual: la validación
 * real la hace GestorImagenes en el servidor.
 */

use App\Services\GestorImagenes;

$gestorImagenes ??= new GestorImagenes(dirname(__DIR__, 2) . '/public/uploads');

$imagenActual = $imagenActual ?? null;
$imagenInvalida = isset($errores) && is_array($errores) && array_key_exists('imagen', $errores);
?>
<div class="campo campo-imagen">
    <label for="imagen">Imagen (JPG, PNG o WEBP, máx. 2 MB)</label>

    <?php if ($gestorImagenes->existe($imagenActual)): ?>
        <figure class="imagen-actual">
            <?php
            $imagenNombre = $imagenActual;
            $imagenTitulo = $imagenTitulo ?? 'la tarea';
            $imagenClase = 'miniatura';
            $imagenTamano = 48;
            require __DIR__ . '/imagen.php';
            ?>
            <figcaption>Imagen actual. Si elige un archivo nuevo, la reemplazará.</figcaption>
        </figure>
    <?php endif; ?>

    <?php /* MAX_FILE_SIZE va antes del input: PHP corta la carga con UPLOAD_ERR_FORM_SIZE. */ ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= e(GestorImagenes::TAMANO_MAXIMO) ?>">
    <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp"
        data-vista-previa="imagen-vista-previa"
        data-tamano-maximo="<?= e(GestorImagenes::TAMANO_MAXIMO) ?>"
        aria-describedby="imagen-ayuda imagen-error"
        aria-invalid="<?= $imagenInvalida ? 'true' : 'false' ?>">
    <small id="imagen-ayuda" class="ayuda">Opcional. Se valida el tipo real del archivo, no solo su extensión.</small>

    <img id="imagen-vista-previa" class="vista-previa" src="/img/sin-imagen.svg"
        alt="Vista previa de la imagen seleccionada" width="96" height="96" hidden>

    <?php $campo = 'imagen'; require __DIR__ . '/campo_error.php'; ?>
</div>
<script src="/js/preview.js" defer></script>
