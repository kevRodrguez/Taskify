<?php

declare(strict_types=1);

/**
 * Tabla reutilizable de tareas heterogéneas.
 *
 * Variables esperadas:
 * - TareaInterface[] $tareas
 * - string|null $mensajeVacio
 *
 * Recorre el arreglo solo con métodos del contrato (sin instanceof ni despacho por tipo).
 */

$tareas = $tareas ?? [];
$mensajeVacio = $mensajeVacio ?? 'No hay tareas para mostrar.';
?>
<div class="tabla-contenedor">
    <table class="tabla">
        <thead>
            <tr>
                <th scope="col">Imagen</th>
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
                <td colspan="6"><?= e($mensajeVacio) ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($tareas as $tarea): ?>
                <?php
                $titulo = $tarea->getTitulo();
                $avance = max(0.0, min(100.0, $tarea->calcularAvance()));
                $estado = $tarea->obtenerEstado(); // [POLIMORFISMO] la vista no distingue tipos
                $claseBadge = 'badge badge-' . strtolower(str_replace('_', '-', $estado->name));
                $id = $tarea->getId();
                $avanceTexto = rtrim(rtrim(number_format($avance, 1, '.', ''), '0'), '.');
                ?>
                <tr>
                    <td>
                        <?php
                        $imagenNombre = $tarea->getImagen();
                        $imagenTitulo = $titulo;
                        $imagenClase = 'miniatura';
                        $imagenTamano = 48;
                        require __DIR__ . '/imagen.php';
                        ?>
                    </td>
                    <td><?= e($titulo) ?></td>
                    <td><?= e($tarea->tipoLegible()) ?></td>
                    <td>
                        <progress class="barra-avance" max="100" value="<?= e($avanceTexto) ?>"><?= e($avanceTexto) ?>%</progress>
                        <span class="avance-texto"><?= e($avanceTexto) ?>%</span>
                    </td>
                    <td><span class="<?= e($claseBadge) ?>"><?= e($estado->value) ?></span></td>
                    <td>
                        <?php if (is_int($id)): ?>
                            <span class="acciones">
                                <a class="btn btn-secundario" href="/tareas/ver.php?id=<?= e((string) $id) ?>">Ver</a>
                                <a class="btn btn-secundario" href="/tareas/editar.php?id=<?= e((string) $id) ?>">Editar</a>
                                <a class="btn btn-peligro" href="/tareas/eliminar.php?id=<?= e((string) $id) ?>">Eliminar</a>
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
