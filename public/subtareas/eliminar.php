<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Repositories\SubtareaRepositorio;
use App\Validation\Csrf;
use App\Validation\Flash;
use App\Validation\ReglasFormularioSubtarea;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tareas/index.php', true, 303);
    exit;
}

$datos = [
    'tarea_padre_id' => $_POST['tarea_padre_id'] ?? null,
    'tarea_hija_id' => $_POST['tarea_hija_id'] ?? null,
];

$padreId = filter_var($datos['tarea_padre_id'], FILTER_VALIDATE_INT) ?: 0;

if (!Csrf::verificar(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    Flash::set('error', 'La solicitud no es válida. Intente de nuevo.');
    header('Location: /subtareas/index.php?tarea_id=' . $padreId, true, 303);
    exit;
}

$validador = ReglasFormularioSubtarea::validar($datos);

if (!$validador->esValido()) {
    Flash::set('error', 'Datos de subtarea no válidos.');
    header('Location: /subtareas/index.php?tarea_id=' . $padreId, true, 303);
    exit;
}

$padreId = (int) $datos['tarea_padre_id'];
$hijaId = (int) $datos['tarea_hija_id'];

try {
    (new SubtareaRepositorio(Conexion::obtener()))->quitar($padreId, $hijaId);
    Flash::set('exito', 'Subtarea quitada del proyecto compuesto.');
} catch (Throwable $e) {
    error_log($e->getMessage());
    Flash::set('error', 'No se pudo quitar la subtarea.');
}

header('Location: /subtareas/index.php?tarea_id=' . $padreId, true, 303);
exit;
