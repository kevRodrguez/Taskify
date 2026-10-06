<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Estado\EstadoTarea;
use App\Repositories\TareaRepositorio;
use App\Tablero\Tablero;

$tareas = [];
$errorCarga = null;

if (class_exists(TareaRepositorio::class) && class_exists(Conexion::class)) {
    try {
        $tareas = (new TareaRepositorio(Conexion::obtener()))->listar();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $errorCarga = 'No se pudieron leer las tareas.';
    }
}

$tablero = new Tablero();
foreach ($tareas as $tarea) {
    $tablero->agregarTarea($tarea);
}

$porEstado = $tablero->agruparPorEstado();

$tituloPagina = 'Reporte del tablero';
$raiz = dirname(__DIR__, 2);
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Reporte del tablero</h1>
        <p class="lead">Las tareas se agrupan por su estado. El cálculo de avance y de estado lo resuelve cada tarea.</p>

        <?php if ($errorCarga !== null): ?>
            <p class="alerta-error" role="alert"><?= e($errorCarga) ?></p>
        <?php endif; ?>

        <?php foreach (EstadoTarea::cases() as $estado): ?>
            <?php
            $tareas = $porEstado[$estado->value] ?? [];
            $mensajeVacio = 'No hay tareas en estado ' . $estado->value . '.';
            $slug = strtolower(str_replace('_', '-', $estado->name));
            ?>
            <section class="bloque grupo-estado grupo-<?= e($slug) ?>" aria-labelledby="estado-<?= e($slug) ?>">
                <h2 id="estado-<?= e($slug) ?>"><?= e($estado->value) ?></h2>
                <?php require $raiz . '/views/partials/tabla_tareas.php'; ?>
            </section>
        <?php endforeach; ?>
<?php require $raiz . '/views/layout/pie.php'; ?>
