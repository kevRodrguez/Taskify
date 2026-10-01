<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Factories\TareaFactory;
use App\Validation\Flash;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$raiz = dirname(__DIR__, 2);
$tiposDisponibles = TareaFactory::tipos();
$tipoPedido = filter_input(INPUT_GET, 'tipo', FILTER_UNSAFE_RAW);
$tipo = is_string($tipoPedido) && TareaFactory::existe($tipoPedido) ? $tipoPedido : null;

$errores = [];
$valores = ['fecha_vencimiento' => date('Y-m-d'), 'avance' => '0'];

if ($tipo !== null) {
    // [PRG] tras un POST con errores, guardar.php deja errores y valores previos en el flash.
    $erroresFlash = Flash::obtener('errores');
    $oldFlash = Flash::obtener('old');
    $errores = is_array($erroresFlash) ? $erroresFlash : [];
    $valores = is_array($oldFlash) ? $oldFlash : $valores;
}

$tituloPagina = 'Nueva tarea';
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Nueva tarea</h1>

        <?php if ($tipo === null): ?>
            <p class="lead">Elija el tipo de tarea que desea crear.</p>
            <ul class="accesos">
                <?php foreach ($tiposDisponibles as $clave => $etiqueta): ?>
                    <li>
                        <a href="/tareas/crear.php?tipo=<?= e(rawurlencode($clave)) ?>"><?= e($etiqueta) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><a class="btn btn-secundario" href="/tareas/index.php">Volver al listado</a></p>
        <?php else: ?>
            <p class="lead">Tipo de tarea: <strong><?= e($tiposDisponibles[$tipo]) ?></strong></p>
            <?php
            $accion = '/tareas/guardar.php';
            $campos = TareaFactory::camposDe($tipo);
            $idTarea = null;
            $imagenActual = null;
            $textoBoton = 'Crear tarea';
            $urlCancelar = '/tareas/index.php';
            require $raiz . '/views/partials/formulario_tarea.php';
            ?>
        <?php endif; ?>
<?php require $raiz . '/views/layout/pie.php'; ?>
