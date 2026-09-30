<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

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
$porTipo = [];
foreach ($tareas as $tarea) {
    $tipo = $tarea->tipoLegible();
    $porTipo[$tipo] = ($porTipo[$tipo] ?? 0) + 1;
}

$tituloPagina = 'Inicio';
$raiz = dirname(__DIR__);
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Taskify</h1>
        <p class="lead">Organiza en un solo tablero las tareas simples, los proyectos con subtareas y las rutinas que se repiten.</p>

        <?php if ($errorCarga !== null): ?>
            <p class="alerta-error" role="alert"><?= e($errorCarga) ?></p>
        <?php endif; ?>

        <section class="bloque" aria-labelledby="titulo-resumen">
            <h2 id="titulo-resumen">Resumen por estado</h2>
            <dl class="conteo">
                <?php foreach (EstadoTarea::cases() as $estado): ?>
                    <?php $cantidad = count($porEstado[$estado->value] ?? []); ?>
                    <div>
                        <dt><?= e($estado->value) ?></dt>
                        <dd><?= e((string) $cantidad) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>

        <section class="bloque" aria-labelledby="titulo-tipos">
            <h2 id="titulo-tipos">Resumen por tipo</h2>
            <?php if ($porTipo === []): ?>
                <p>No hay tareas para resumir. <a href="/tareas/crear.php">Crear una tarea</a>.</p>
            <?php else: ?>
                <ul class="lista-tipos">
                    <?php foreach ($porTipo as $tipo => $cantidad): ?>
                        <li><span><?= e((string) $tipo) ?></span> <strong><?= e((string) $cantidad) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="bloque" aria-labelledby="titulo-modulos">
            <h2 id="titulo-modulos">Módulos</h2>
            <ul class="accesos">
                <li>
                    <a href="/tareas/index.php">Ver tareas</a>
                    <p>Listado de todas las tareas del tablero.</p>
                </li>
                <li>
                    <a href="/tareas/crear.php">Crear una tarea</a>
                    <p>Registrar una tarea simple, compuesta o recurrente.</p>
                </li>
                <li>
                    <a href="/reportes/tablero.php">Abrir el reporte</a>
                    <p>Agrupar el tablero por pendiente, en progreso y completada.</p>
                </li>
            </ul>
        </section>
<?php require $raiz . '/views/layout/pie.php'; ?>
