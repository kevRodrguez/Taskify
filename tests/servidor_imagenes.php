<?php

declare(strict_types=1);

/**
 * Endpoint de apoyo para tests/gestor_imagenes_test.php.
 *
 * Lo levanta el propio test con `php -S` (nunca se sirve desde public/):
 * move_uploaded_file() solo funciona con archivos que llegan por HTTP POST,
 * así que la carga real se prueba con una petición multipart de verdad.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Exceptions\ImagenException;
use App\Services\GestorImagenes;

header('Content-Type: application/json; charset=UTF-8');

$directorio = getenv('TASKIFY_TEST_UPLOADS');
if (!is_string($directorio) || $directorio === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Falta TASKIFY_TEST_UPLOADS']);
    exit;
}

$gestor = new GestorImagenes($directorio);
$archivo = $_FILES['imagen'] ?? [];
$anterior = is_string($_POST['anterior'] ?? null) ? $_POST['anterior'] : null;

try {
    $nombre = ($_GET['accion'] ?? '') === 'reemplazar'
        ? $gestor->reemplazar($anterior, $archivo)
        : $gestor->guardar($archivo);
    echo json_encode(['ok' => true, 'nombre' => $nombre]);
} catch (ImagenException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
