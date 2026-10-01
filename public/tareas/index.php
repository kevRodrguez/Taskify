<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Exceptions\ConexionException;
use App\Repositories\TareaRepositorio;

$raiz = dirname(__DIR__, 2);
$tareas = [];
$errorCarga = null;

try {
    // [CRUD-READ] la página pide las tareas al repositorio; nunca escribe SQL.
    $tareas = (new TareaRepositorio(Conexion::obtener()))->listar();
} catch (ConexionException) {
    $errorCarga = 'No se pudo conectar con la base de datos.';
} catch (Throwable $e) {
    error_log($e->getMessage());
    $errorCarga = 'No se pudieron cargar las tareas.';
}

$tituloPagina = 'Tareas';
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Tareas</h1>

        <p>
            <a class="btn" href="/tareas/crear.php">Nueva tarea</a>
        </p>

        <?php if ($errorCarga !== null): ?>
            <p class="alerta-error" role="alert"><?= e($errorCarga) ?></p>
        <?php else: ?>
            <section class="bloque" aria-labelledby="titulo-listado">
                <h2 id="titulo-listado">Listado de tareas (<?= e((string) count($tareas)) ?>)</h2>
                <?php
                $mensajeVacio = 'Todavía no hay tareas registradas. Cree la primera con "Nueva tarea".';
                require $raiz . '/views/partials/tabla_tareas.php';
                ?>
            </section>
        <?php endif; ?>
<?php require $raiz . '/views/layout/pie.php'; ?>
