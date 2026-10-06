<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Exceptions\ConexionException;
use App\Repositories\SubtareaRepositorio;

$raiz = dirname(__DIR__, 2);
$tareaId = filter_input(INPUT_GET, 'tarea_id', FILTER_VALIDATE_INT);
$errorCarga = null;
$padre = null;
$subtareas = [];

if ($tareaId === false || $tareaId === null || $tareaId <= 0) {
    $errorCarga = 'Indique una tarea compuesta válida (?tarea_id=).';
} else {
    try {
        $repo = new SubtareaRepositorio(Conexion::obtener());
        $padre = $repo->buscarTarea($tareaId);

        if ($padre === null) {
            $errorCarga = 'No se encontró la tarea indicada.';
        } elseif (!$padre->admiteSubtareas()) {
            $errorCarga = 'Solo las tareas compuestas pueden tener subtareas.';
        } else {
            $subtareas = $repo->listarPorPadre($tareaId);
        }
    } catch (ConexionException) {
        $errorCarga = 'No se pudo conectar con la base de datos.';
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $errorCarga = 'No se pudieron cargar las subtareas.';
    }
}

$tituloPagina = 'Subtareas';
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Subtareas</h1>

        <?php if ($errorCarga !== null): ?>
            <p class="alerta-error" role="alert"><?= e($errorCarga) ?></p>
            <p><a class="btn btn-secundario" href="/tareas/index.php">Volver al listado de tareas</a></p>
        <?php else: ?>
            <p class="lead">
                Tarea compuesta: <strong><?= e($padre->getTitulo()) ?></strong>
                (id <?= e((string) $padre->getId()) ?>)
            </p>

            <p>
                <a class="btn" href="/subtareas/crear.php?tarea_id=<?= e((string) $padre->getId()) ?>">Asignar subtarea</a>
                <a class="btn btn-secundario" href="/tareas/ver.php?id=<?= e((string) $padre->getId()) ?>">Ver tarea padre</a>
            </p>

            <section class="bloque" aria-labelledby="titulo-subtareas">
                <h2 id="titulo-subtareas">Listado de subtareas</h2>
                <?php
                $tareas = $subtareas;
                $tareaPadreId = (int) $padre->getId();
                $mensajeVacio = 'Esta tarea compuesta aún no tiene subtareas asignadas.';
                require $raiz . '/views/partials/tabla_subtareas.php';
                ?>
            </section>
        <?php endif; ?>
<?php require $raiz . '/views/layout/pie.php'; ?>
