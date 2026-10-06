<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Repositories\TareaRepositorio;
use App\Services\GestorImagenes;
use App\Validation\Csrf;
use App\Validation\Flash;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$raiz = dirname(__DIR__, 2);
$esPost = $_SERVER['REQUEST_METHOD'] === 'POST';

// GET muestra la confirmación; el borrado solo ocurre por POST.
$id = filter_var($esPost ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null), FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    Flash::set('error', 'No se encontró la tarea solicitada.');
    header('Location: /tareas/index.php', true, 303);
    exit;
}

try {
    $repositorio = new TareaRepositorio(Conexion::obtener());
    $tarea = $repositorio->buscarPorId($id);
} catch (Throwable $e) {
    error_log($e->getMessage());
    Flash::set('error', 'No se pudo cargar la tarea.');
    header('Location: /tareas/index.php', true, 303);
    exit;
}

if ($tarea === null) {
    Flash::set('error', 'No se encontró la tarea solicitada.');
    header('Location: /tareas/index.php', true, 303);
    exit;
}

if ($esPost) {
    // [SEGURIDAD] [CSRF] un enlace o una imagen de otro sitio no puede borrar tareas.
    if (!Csrf::verificar(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
        Flash::set('error', 'La solicitud no es válida. Intente de nuevo.');
        header('Location: /tareas/eliminar.php?id=' . $id, true, 303);
        exit;
    }

    try {
        // [CRUD-DELETE] primero la fila; la imagen solo se borra si la BD ya la eliminó.
        $repositorio->eliminar($id);
        (new GestorImagenes(dirname(__DIR__) . '/uploads'))->eliminar($tarea->getImagen());

        Flash::set('exito', 'Tarea eliminada correctamente.');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        Flash::set('error', 'No se pudo eliminar la tarea.');
    }

    // [PRG]
    header('Location: /tareas/index.php', true, 303);
    exit;
}

$tituloPagina = 'Eliminar tarea';
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Eliminar tarea</h1>

        <section class="bloque" aria-labelledby="titulo-confirmacion">
            <h2 id="titulo-confirmacion">¿Eliminar &laquo;<?= e($tarea->getTitulo()) ?>&raquo;?</h2>
            <p class="lead">
                Tipo: <?= e($tarea->tipoLegible()) ?>. Esta acción no se puede deshacer
                y también borra la imagen de la tarea.
                <?php if ($tarea->admiteSubtareas()): ?>
                    Las tareas asignadas como subtareas no se eliminan; solo se quita su relación con esta.
                <?php endif; ?>
            </p>

            <form class="formulario" method="post" action="/tareas/eliminar.php">
                <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                <input type="hidden" name="id" value="<?= e((string) $tarea->getId()) ?>">
                <p>
                    <button class="btn btn-peligro" type="submit">Sí, eliminar</button>
                    <a class="btn btn-secundario" href="/tareas/ver.php?id=<?= e((string) $tarea->getId()) ?>">Cancelar</a>
                </p>
            </form>
        </section>
<?php require $raiz . '/views/layout/pie.php'; ?>
