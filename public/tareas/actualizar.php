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
    header('Location: /tareas/index.php', true, 303);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    Flash::set('error', 'No se encontró la tarea solicitada.');
    header('Location: /tareas/index.php', true, 303);
    exit;
}
$urlFormulario = '/tareas/editar.php?id=' . $id;

// [SEGURIDAD] [CSRF]
if (!Csrf::verificar(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    Flash::set('error', 'La solicitud no es válida. Intente de nuevo.');
    header('Location: ' . $urlFormulario, true, 303);
    exit;
}

$gestor = new GestorImagenes(dirname(__DIR__) . '/uploads');
$datos = [];

try {
    $repositorio = new TareaRepositorio(Conexion::obtener());
    $original = $repositorio->buscarPorId($id);
    if ($original === null) {
        Flash::set('error', 'No se encontró la tarea solicitada.');
        header('Location: /tareas/index.php', true, 303);
        exit;
    }

    // El tipo y la fecha de creación salen del registro, no del formulario.
    $filaOriginal = $original->aFila();

    $nombresPermitidos = ['titulo', 'descripcion', 'fecha_vencimiento'];
    foreach ($original::camposEspecificos() as $campo) {
        $nombresPermitidos[] = $campo['nombre'];
    }
    foreach ($nombresPermitidos as $nombre) {
        $datos[$nombre] = is_string($_POST[$nombre] ?? null) ? $_POST[$nombre] : '';
    }
    $datos['tipo'] = (string) $filaOriginal['tipo'];
    $datos['fecha_creacion'] = $filaOriginal['fecha_creacion'];

    // [VALIDACION]
    $validador = ReglasFormularioTarea::validar($datos);
    if (!$validador->esValido()) {
        Flash::set('errores', $validador->errores());
        Flash::set('old', $datos);
        header('Location: ' . $urlFormulario, true, 303);
        exit;
    }

    $tarea = TareaFactory::desdeFormulario($datos);
    $tarea->setImagen($original->getImagen());

    $archivo = $_FILES['imagen'] ?? null;
    if (is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        // reemplazar() guarda la nueva y borra la anterior solo si la nueva se guardó bien.
        $tarea->setImagen($gestor->reemplazar($original->getImagen(), $archivo));
    }

    // [CRUD-UPDATE]
    $repositorio->actualizar($id, $tarea);

    Flash::set('exito', 'Tarea actualizada correctamente.');
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
    error_log($e->getMessage());
    Flash::set('error', 'No se pudo actualizar la tarea.');
    Flash::set('old', $datos);
    header('Location: ' . $urlFormulario, true, 303);
}

exit;
