<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Conexion;
use App\Exceptions\DominioException;
use App\Exceptions\ImagenException;
use App\Factories\TareaFactory;
use App\Repositories\TareaRepositorio;
use App\Services\GestorImagenes;
use App\Validation\Csrf;
use App\Validation\Flash;
use App\Validation\ReglasFormularioTarea;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tareas/crear.php', true, 303);
    exit;
}

$tipo = is_string($_POST['tipo'] ?? null) ? $_POST['tipo'] : '';
$urlFormulario = '/tareas/crear.php' . (TareaFactory::existe($tipo) ? '?tipo=' . rawurlencode($tipo) : '');

// [SEGURIDAD] [CSRF] se rechaza cualquier POST sin el token de la sesión.
if (!Csrf::verificar(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    Flash::set('error', 'La solicitud no es válida. Intente de nuevo.');
    header('Location: ' . $urlFormulario, true, 303);
    exit;
}

if (!TareaFactory::existe($tipo)) {
    Flash::set('error', 'Seleccione un tipo de tarea válido.');
    header('Location: /tareas/crear.php', true, 303);
    exit;
}

// Solo se aceptan los campos comunes y los que declara el tipo (lista blanca).
$nombresPermitidos = ['tipo', 'titulo', 'descripcion', 'fecha_vencimiento'];
foreach (TareaFactory::camposDe($tipo) as $campo) {
    $nombresPermitidos[] = $campo['nombre'];
}
$datos = [];
foreach ($nombresPermitidos as $nombre) {
    $datos[$nombre] = is_string($_POST[$nombre] ?? null) ? $_POST[$nombre] : '';
}

// [VALIDACION] validación de servidor, siempre, aunque el navegador ya haya validado.
$validador = ReglasFormularioTarea::validar($datos);
if (!$validador->esValido()) {
    Flash::set('errores', $validador->errores());
    Flash::set('old', $datos);
    header('Location: ' . $urlFormulario, true, 303);
    exit;
}

$gestor = new GestorImagenes(dirname(__DIR__) . '/uploads');
$imagenNueva = null;

try {
    $tarea = TareaFactory::desdeFormulario($datos);

    $archivo = $_FILES['imagen'] ?? null;
    if (is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $imagenNueva = $gestor->guardar($archivo);
        $tarea->setImagen($imagenNueva);
    }

    // [CRUD-CREATE]
    $id = (new TareaRepositorio(Conexion::obtener()))->crear($tarea);

    Flash::set('exito', 'Tarea creada correctamente.');
    header('Location: /tareas/ver.php?id=' . $id, true, 303);
} catch (ImagenException $e) {
    Flash::set('errores', ['imagen' => $e->getMessage()]);
    Flash::set('old', $datos);
    header('Location: ' . $urlFormulario, true, 303);
} catch (DominioException $e) {
    Flash::set('error', $e->getMessage());
    Flash::set('old', $datos);
    header('Location: ' . $urlFormulario, true, 303);
} catch (Throwable $e) {
    // Si falla la base de datos no se deja huérfana la imagen ya subida.
    $gestor->eliminar($imagenNueva);
    error_log($e->getMessage());
    Flash::set('error', 'No se pudo guardar la tarea.');
    Flash::set('old', $datos);
    header('Location: ' . $urlFormulario, true, 303);
}

exit;
