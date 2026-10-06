<?php

declare(strict_types=1);

/**
 * Imagen de una tarea, con la imagen por defecto cuando no tiene o no existe.
 *
 * Variables esperadas:
 * - string|null $imagenNombre  nombre guardado (TareaInterface::getImagen())
 * - string      $imagenTitulo  título de la tarea, para el texto alternativo
 * - string      $imagenClase   clase CSS: 'miniatura' (por defecto) o 'imagen-ficha'
 * - int|null    $imagenTamano  ancho y alto en píxeles (opcional)
 *
 * Los nombres llevan el prefijo "imagen" para no pisar variables del archivo
 * que incluye el partial (p. ej. dentro del foreach de tabla_tareas.php).
 */

use App\Services\GestorImagenes;

$gestorImagenes ??= new GestorImagenes(dirname(__DIR__, 2) . '/public/uploads');

$imagenNombre = $imagenNombre ?? null;
$imagenTitulo = (string) ($imagenTitulo ?? 'la tarea');
$imagenClase = (string) ($imagenClase ?? 'miniatura');
$imagenTamano = $imagenTamano ?? null;

$imagenAlt = $gestorImagenes->existe($imagenNombre)
    ? 'Imagen de ' . $imagenTitulo
    : 'Sin imagen para ' . $imagenTitulo;
?>
<img class="<?= e($imagenClase) ?>" src="<?= e($gestorImagenes->urlPublica($imagenNombre)) ?>" alt="<?= e($imagenAlt) ?>"<?php if (is_int($imagenTamano)): ?> width="<?= e($imagenTamano) ?>" height="<?= e($imagenTamano) ?>"<?php endif; ?> loading="lazy">
