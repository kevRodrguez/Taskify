<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Repositories\TareaRepositorio;
use App\Validation\Flash;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$raiz = dirname(__DIR__, 2);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$tarea = null;

try {
    if ($id !== false && $id !== null && $id > 0) {
        // [CRUD-READ]
        $tarea = (new TareaRepositorio(Conexion::obtener()))->buscarPorId($id);
    }

    if ($tarea === null) {
        Flash::set('error', 'No se encontró la tarea solicitada.');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    Flash::set('error', 'No se pudo cargar la tarea.');
}

if ($tarea === null) {
    header('Location: /tareas/index.php', true, 303);
    exit;
}

// [PRG] valores previos y errores si la actualización anterior falló.
$erroresFlash = Flash::obtener('errores');
$oldFlash = Flash::obtener('old');
$errores = is_array($erroresFlash) ? $erroresFlash : [];
$fila = $tarea->aFila();
$valores = is_array($oldFlash) ? $oldFlash : $fila;

$tituloPagina = 'Editar tarea';
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Editar tarea</h1>
        <p class="lead">Tipo de tarea: <strong><?= e($tarea->tipoLegible()) ?></strong> (el tipo no se puede cambiar).</p>

        <?php
        $accion = '/tareas/actualizar.php';
        $tipo = (string) $fila['tipo'];
        $campos = $tarea::camposEspecificos();
        $idTarea = $tarea->getId();
        $imagenActual = $tarea->getImagen();
        $textoBoton = 'Guardar cambios';
        $urlCancelar = '/tareas/ver.php?id=' . $tarea->getId();
        require $raiz . '/views/partials/formulario_tarea.php';
        ?>
<?php require $raiz . '/views/layout/pie.php'; ?>
