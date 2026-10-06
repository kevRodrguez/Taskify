<?php

declare(strict_types=1);

/**
 * Formulario de tarea compartido por crear.php y editar.php.
 *
 * Variables esperadas:
 * - string $accion          URL a la que se envía el formulario (POST)
 * - string $tipo            clave del tipo de tarea (campo oculto)
 * - list<array> $campos     campos propios del tipo (camposEspecificos())
 * - array<string, mixed> $valores   valores a mostrar (old del flash o datos actuales)
 * - array<string, string> $errores  mapa campo => mensaje
 * - int|null $idTarea       id al editar, null al crear
 * - string|null $imagenActual  nombre de la imagen guardada, si hay
 * - string $textoBoton
 * - string $urlCancelar
 *
 * Recorre descriptores de campo: no hay condicionales por tipo de tarea,
 * solo por clase de control (text, textarea, date, number, select).
 */

$campoComun = [
    ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'maxlength' => 120],
    ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'requerido' => false],
    ['nombre' => 'fecha_vencimiento', 'etiqueta' => 'Fecha de vencimiento', 'tipo' => 'date', 'requerido' => true],
];
$camposFormulario = array_merge($campoComun, $campos);
$imagenActual = $imagenActual ?? null;
?>
<form class="formulario" method="post" action="<?= e($accion) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(\App\Validation\Csrf::token()) ?>">
    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
    <?php if ($idTarea !== null): ?>
        <input type="hidden" name="id" value="<?= e((string) $idTarea) ?>">
    <?php endif; ?>

    <?php foreach ($camposFormulario as $campo): ?>
        <?php
        $nombre = (string) $campo['nombre'];
        $valor = (string) ($valores[$nombre] ?? '');
        $requerido = !empty($campo['requerido']);
        $invalido = array_key_exists($nombre, $errores);
        ?>
        <div class="campo">
            <label for="<?= e($nombre) ?>"><?= e((string) $campo['etiqueta']) ?></label>
            <?php if ($campo['tipo'] === 'textarea'): ?>
                <textarea id="<?= e($nombre) ?>" name="<?= e($nombre) ?>"
                    aria-describedby="<?= e($nombre) ?>-error"
                    aria-invalid="<?= $invalido ? 'true' : 'false' ?>"><?= e($valor) ?></textarea>
            <?php elseif ($campo['tipo'] === 'select'): ?>
                <select id="<?= e($nombre) ?>" name="<?= e($nombre) ?>"<?= $requerido ? ' required' : '' ?>
                    aria-describedby="<?= e($nombre) ?>-error"
                    aria-invalid="<?= $invalido ? 'true' : 'false' ?>">
                    <option value="">Seleccione una opción…</option>
                    <?php foreach ($campo['opciones'] as $valorOpcion => $textoOpcion): ?>
                        <option value="<?= e((string) $valorOpcion) ?>"<?= $valor === (string) $valorOpcion ? ' selected' : '' ?>><?= e((string) $textoOpcion) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input id="<?= e($nombre) ?>" name="<?= e($nombre) ?>" type="<?= e((string) $campo['tipo']) ?>"
                    value="<?= e($valor) ?>"<?= $requerido ? ' required' : '' ?>
                    <?= isset($campo['maxlength']) ? ' maxlength="' . e((string) $campo['maxlength']) . '"' : '' ?>
                    <?= isset($campo['min']) ? ' min="' . e((string) $campo['min']) . '"' : '' ?>
                    <?= isset($campo['max']) ? ' max="' . e((string) $campo['max']) . '"' : '' ?>
                    <?= isset($campo['step']) ? ' step="' . e((string) $campo['step']) . '"' : '' ?>
                    aria-describedby="<?= e($nombre) ?>-error"
                    aria-invalid="<?= $invalido ? 'true' : 'false' ?>">
            <?php endif; ?>
            <?php $campo = $nombre; require __DIR__ . '/campo_error.php'; ?>
        </div>
    <?php endforeach; ?>

    <?php
    $imagenTitulo = trim((string) ($valores['titulo'] ?? '')) ?: 'la tarea';
    require __DIR__ . '/campo_imagen.php';
    ?>

    <p>
        <button class="btn" type="submit"><?= e($textoBoton) ?></button>
        <a class="btn btn-secundario" href="<?= e($urlCancelar) ?>">Cancelar</a>
    </p>
</form>
