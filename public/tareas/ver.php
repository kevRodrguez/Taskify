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
        // [CRUD-READ] el repositorio devuelve la tarea con sus subtareas ya cargadas.
        $tarea = (new TareaRepositorio(Conexion::obtener()))->buscarPorId($id);
    }

    if ($tarea === null) {
        Flash::set('error', 'No se encontró la tarea solicitada.');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    Flash::set('error', 'No se pudo cargar la tarea.');
}

// [PRG] sin tarea que mostrar se vuelve al listado con un mensaje flash.
if ($tarea === null) {
    header('Location: /tareas/index.php', true, 303);
    exit;
}

$avance = max(0.0, min(100.0, $tarea->calcularAvance()));
$avanceTexto = rtrim(rtrim(number_format($avance, 1, '.', ''), '0'), '.');
$estado = $tarea->obtenerEstado();
$claseBadge = 'badge badge-' . strtolower(str_replace('_', '-', $estado->name));

$nombreImagen = $tarea->getImagen();
if (is_string($nombreImagen) && $nombreImagen !== '') {
    // basename evita que un nombre guardado salga del directorio de cargas.
    $srcImagen = '/uploads/' . rawurlencode(basename($nombreImagen));
    $altImagen = 'Imagen de ' . $tarea->getTitulo();
} else {
    $srcImagen = '/img/sin-imagen.svg';
    $altImagen = 'Sin imagen para ' . $tarea->getTitulo();
}

$tituloPagina = $tarea->getTitulo();
require $raiz . '/views/layout/encabezado.php';
?>
        <h1><?= e($tarea->getTitulo()) ?></h1>

        <section class="bloque ficha" aria-labelledby="titulo-ficha">
            <h2 id="titulo-ficha">Ficha de la tarea</h2>
            <img class="imagen-ficha" src="<?= e($srcImagen) ?>" alt="<?= e($altImagen) ?>">
            <dl class="ficha-datos">
                <div>
                    <dt>Tipo</dt>
                    <dd><?= e($tarea->tipoLegible()) ?></dd>
                </div>
                <div>
                    <dt>Estado</dt>
                    <dd><span class="<?= e($claseBadge) ?>"><?= e($estado->value) ?></span></dd>
                </div>
                <div>
                    <dt>Avance</dt>
                    <dd>
                        <progress class="barra-avance" max="100" value="<?= e($avanceTexto) ?>"><?= e($avanceTexto) ?>%</progress>
                        <span class="avance-texto"><?= e($avanceTexto) ?>%</span>
                    </dd>
                </div>
                <div>
                    <dt>Descripción</dt>
                    <dd><?= e($tarea->getDescripcion() === '' ? 'Sin descripción.' : $tarea->getDescripcion()) ?></dd>
                </div>
                <div>
                    <dt>Fecha de creación</dt>
                    <dd><?= e($tarea->getFechaCreacion()->format('Y-m-d')) ?></dd>
                </div>
                <div>
                    <dt>Fecha de vencimiento</dt>
                    <dd><?= e($tarea->getFechaVencimiento()->format('Y-m-d')) ?></dd>
                </div>
                <?php foreach ($tarea->detalleEspecifico() as $etiqueta => $valor): ?>
                    <div>
                        <dt><?= e($etiqueta) ?></dt>
                        <dd><?= e($valor) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>

        <p class="acciones">
            <a class="btn" href="/tareas/editar.php?id=<?= e((string) $tarea->getId()) ?>">Editar</a>
            <?php if ($tarea->admiteSubtareas()): ?>
                <a class="btn btn-secundario" href="/subtareas/index.php?tarea_id=<?= e((string) $tarea->getId()) ?>">Gestionar subtareas</a>
            <?php endif; ?>
            <a class="btn btn-peligro" href="/tareas/eliminar.php?id=<?= e((string) $tarea->getId()) ?>">Eliminar</a>
            <a class="btn btn-secundario" href="/tareas/index.php">Volver al listado</a>
        </p>
<?php require $raiz . '/views/layout/pie.php'; ?>
