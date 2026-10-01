<?php

declare(strict_types=1);

/**
 * Tabla de subtareas con acción para quitar la relación (no borra la tarea hija).
 *
 * Variables esperadas:
 * - TareaInterface[] $tareas
 * - int $tareaPadreId
 * - string|null $mensajeVacio
 */

$tareas = $tareas ?? [];
$mensajeVacio = $mensajeVacio ?? 'No hay subtareas asignadas.';
$tareaPadreId = $tareaPadreId ?? 0;
?>
<div class="tabla-contenedor">
    <table class="tabla">
        <thead>
            <tr>
                <th scope="col">Título</th>
                <th scope="col">Tipo</th>
                <th scope="col">Avance</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($tareas === []): ?>
            <tr>
                <td colspan="5"><?= e($mensajeVacio) ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($tareas as $tarea): ?>
                <?php
                $titulo = $tarea->getTitulo();
                $avance = max(0.0, min(100.0, $tarea->calcularAvance()));
                $estado = $tarea->obtenerEstado();
                $claseBadge = 'badge badge-' . strtolower(str_replace('_', '-', $estado->name));
                $avanceTexto = rtrim(rtrim(number_format($avance, 1, '.', ''), '0'), '.');
                $hijaId = $tarea->getId();
                ?>
                <tr>
                    <td><?= e($titulo) ?></td>
                    <td><?= e($tarea->tipoLegible()) ?></td>
                    <td>
                        <progress class="barra-avance" max="100" value="<?= e($avanceTexto) ?>"><?= e($avanceTexto) ?>%</progress>
                        <span class="avance-texto"><?= e($avanceTexto) ?>%</span>
                    </td>
                    <td><span class="<?= e($claseBadge) ?>"><?= e($estado->value) ?></span></td>
                    <td>
                        <?php if (is_int($hijaId)): ?>
                            <span class="acciones">
                                <a class="btn btn-secundario" href="/tareas/ver.php?id=<?= e((string) $hijaId) ?>">Ver</a>
                                <form method="post" action="/subtareas/eliminar.php">
                                    <input type="hidden" name="_csrf" value="<?= e(\App\Validation\Csrf::token()) ?>">
                                    <input type="hidden" name="tarea_padre_id" value="<?= e((string) $tareaPadreId) ?>">
                                    <input type="hidden" name="tarea_hija_id" value="<?= e((string) $hijaId) ?>">
                                    <button class="btn btn-peligro" type="submit">Quitar</button>
                                </form>
                            </span>
                        <?php else: ?>
                            No disponible
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
