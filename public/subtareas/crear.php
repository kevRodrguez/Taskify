<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Exceptions\ConexionException;
use App\Repositories\SubtareaRepositorio;
use App\Validation\Flash;

$raiz = dirname(__DIR__, 2);
$tareaId = filter_input(INPUT_GET, 'tarea_id', FILTER_VALIDATE_INT);
$errorCarga = null;
$padre = null;
$candidatas = [];
$errores = [];
$old = [];

if ($tareaId === false || $tareaId === null || $tareaId <= 0) {
    $errorCarga = 'Indique una tarea compuesta válida (?tarea_id=).';
} else {
    $erroresFlash = Flash::obtener('errores');
    $oldFlash = Flash::obtener('old');
    $errores = is_array($erroresFlash) ? $erroresFlash : [];
    $old = is_array($oldFlash) ? $oldFlash : [];

    try {
        $repo = new SubtareaRepositorio(Conexion::obtener());
        $padre = $repo->buscarTarea($tareaId);

        if ($padre === null) {
            $errorCarga = 'No se encontró la tarea indicada.';
        } elseif (!$padre->admiteSubtareas()) {
            $errorCarga = 'Solo las tareas compuestas pueden tener subtareas.';
        } else {
            $candidatas = $repo->listarCandidatas($tareaId);
        }
    } catch (ConexionException) {
        $errorCarga = 'No se pudo conectar con la base de datos.';
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $errorCarga = 'No se pudo cargar el formulario.';
    }
}

$tareaHijaSeleccionada = (string) ($old['tarea_hija_id'] ?? '');

$tituloPagina = 'Asignar subtarea';
require $raiz . '/views/layout/encabezado.php';
?>
        <h1>Asignar subtarea</h1>

        <?php if ($errorCarga !== null): ?>
            <p class="alerta-error" role="alert"><?= e($errorCarga) ?></p>
            <p><a class="btn btn-secundario" href="/tareas/index.php">Volver al listado de tareas</a></p>
        <?php else: ?>
            <p class="lead">Tarea compuesta: <strong><?= e($padre->getTitulo()) ?></strong></p>

            <?php if ($candidatas === []): ?>
                <p class="alerta-error" role="alert">No hay tareas disponibles para asignar como subtarea.</p>
                <p><a class="btn btn-secundario" href="/subtareas/index.php?tarea_id=<?= e((string) $padre->getId()) ?>">Volver al listado de subtareas</a></p>
            <?php else: ?>
                <form class="formulario" method="post" action="/subtareas/guardar.php">
                    <input type="hidden" name="_csrf" value="<?= e(\App\Validation\Csrf::token()) ?>">
                    <input type="hidden" name="tarea_padre_id" value="<?= e((string) $padre->getId()) ?>">

                    <div class="campo">
                        <label for="tarea_hija_id">Tarea a asignar como subtarea</label>
                        <select
                            id="tarea_hija_id"
                            name="tarea_hija_id"
                            required
                            aria-describedby="tarea_hija_id-error"
                            aria-invalid="<?= array_key_exists('tarea_hija_id', $errores) ? 'true' : 'false' ?>"
                        >
                            <option value="">Seleccione una tarea…</option>
                            <?php foreach ($candidatas as $candidata): ?>
                                <?php $valor = (string) $candidata['id']; ?>
                                <option value="<?= e($valor) ?>"<?= $tareaHijaSeleccionada === $valor ? ' selected' : '' ?>>
                                    <?= e($candidata['titulo']) ?> (id <?= e($valor) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php $campo = 'tarea_hija_id'; require $raiz . '/views/partials/campo_error.php'; ?>
                    </div>

                    <p>
                        <button class="btn" type="submit">Asignar subtarea</button>
                        <a class="btn btn-secundario" href="/subtareas/index.php?tarea_id=<?= e((string) $padre->getId()) ?>">Cancelar</a>
                    </p>
                </form>
            <?php endif; ?>
        <?php endif; ?>
<?php require $raiz . '/views/layout/pie.php'; ?>
