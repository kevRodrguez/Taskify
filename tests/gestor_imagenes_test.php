<?php

declare(strict_types=1);

/**
 * Pruebas de GestorImagenes: archivo de 3 MB, .php renombrado a .jpg,
 * archivo vacío, imagen válida, reemplazo y borrado seguro.
 *
 * Ejecutar: php tests/gestor_imagenes_test.php
 *
 * Parte 1 llama al servicio directamente. Parte 2 levanta `php -S` con
 * tests/servidor_imagenes.php y sube archivos por HTTP POST, porque
 * move_uploaded_file() rechaza todo lo que no llegó por una carga real.
 * Todo se escribe en un directorio temporal: public/uploads no se toca.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Exceptions\ImagenException;
use App\Services\GestorImagenes;

$fallos = 0;

$afirmar = static function (bool $condicion, string $mensaje) use (&$fallos): void {
    if ($condicion) {
        echo "[OK] {$mensaje}\n";
    } else {
        echo "[FALLO] {$mensaje}\n";
        $fallos++;
    }
};

/** Ejecuta guardar() y devuelve el mensaje de la ImagenException, o null si no lanzó. */
$errorAlGuardar = static function (GestorImagenes $gestor, array $archivo): ?string {
    try {
        $gestor->guardar($archivo);

        return null;
    } catch (ImagenException $e) {
        return $e->getMessage();
    }
};

$archivosEn = static fn (string $dir): array => array_values(array_diff(scandir($dir) ?: [], ['.', '..']));

// --- Directorios y archivos de prueba ---

$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'taskify_img_' . bin2hex(random_bytes(4));
$uploads = $base . DIRECTORY_SEPARATOR . 'uploads';
$fixtures = $base . DIRECTORY_SEPARATOR . 'fixtures';
mkdir($uploads, 0777, true);
mkdir($fixtures, 0777, true);
// Los error_log() del servicio van a un archivo temporal, no a la salida de la prueba.
ini_set('error_log', $base . DIRECTORY_SEPARATOR . 'php_errors.log');

// PNG válido de 1x1 píxel (no requiere la extensión GD).
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

$fx = [
    'valida' => $fixtures . '/valida.png',
    'valida2' => $fixtures . '/valida2.png',
    'grande' => $fixtures . '/grande.png',
    'php' => $fixtures . '/malicioso.jpg',
    'poliglota' => $fixtures . '/poliglota.png',
    'vacio' => $fixtures . '/vacio.jpg',
];
file_put_contents($fx['valida'], $png);
file_put_contents($fx['valida2'], $png);
// Imagen válida inflada a 3 MB: debe rechazarse por tamaño aunque sea una imagen real.
file_put_contents($fx['grande'], $png . str_repeat("\0", 3 * 1024 * 1024));
file_put_contents($fx['php'], "<?php system(\$_GET['cmd'] ?? 'id'); ?>\n");
// Cabecera PNG + código PHP: finfo lo reporta como image/png, pero no es una
// imagen real (0x0 píxeles); lo debe frenar la comprobación con getimagesize.
file_put_contents($fx['poliglota'], "\x89PNG\r\n\x1a\n\0\0\0\rIHDR" . str_repeat("\0", 8) . "<?php echo 'hola'; ?>");
file_put_contents($fx['vacio'], '');

$fila = static fn (string $ruta, string $nombre): array => [
    'name' => $nombre,
    'type' => 'image/jpeg', // lo envía el cliente: el servicio no debe creerle
    'tmp_name' => $ruta,
    'error' => UPLOAD_ERR_OK,
    'size' => 1024,          // también lo envía el cliente
];

$gestor = new GestorImagenes($uploads);

echo "=== Parte 1: validaciones directas ===\n\n";

$codigos = [
    UPLOAD_ERR_INI_SIZE,
    UPLOAD_ERR_PARTIAL,
    UPLOAD_ERR_NO_FILE,
    UPLOAD_ERR_NO_TMP_DIR,
    UPLOAD_ERR_CANT_WRITE,
    UPLOAD_ERR_EXTENSION,
];
$mensajes = [];
foreach ($codigos as $codigo) {
    $mensajes[] = $errorAlGuardar($gestor, ['error' => $codigo, 'tmp_name' => '', 'size' => 0]);
}
$afirmar(!in_array(null, $mensajes, true), 'Todo código UPLOAD_ERR_* distinto de OK lanza ImagenException');
$afirmar(count(array_unique($mensajes)) === count($codigos), 'Cada código de error tiene un mensaje distinto');
$afirmar(
    $errorAlGuardar($gestor, ['error' => UPLOAD_ERR_FORM_SIZE]) === $mensajes[0],
    'UPLOAD_ERR_FORM_SIZE (MAX_FILE_SIZE del formulario) se informa como exceso de 2 MB'
);

$msg = $errorAlGuardar($gestor, ['error' => [UPLOAD_ERR_OK], 'tmp_name' => [$fx['valida']]]);
$afirmar($msg !== null && str_contains($msg, 'una imagen'), "Rechaza varios archivos a la vez: {$msg}");

$msg = $errorAlGuardar($gestor, $fila($fx['vacio'], 'vacio.jpg'));
$afirmar($msg !== null && str_contains($msg, 'vacío'), "Archivo vacío: {$msg}");

$msg = $errorAlGuardar($gestor, $fila($fx['grande'], 'grande.png'));
$afirmar($msg !== null && str_contains($msg, '2 MB'), "Archivo de 3 MB: {$msg}");

$msg = $errorAlGuardar($gestor, $fila($fx['php'], 'malicioso.jpg'));
$afirmar($msg !== null && str_contains($msg, 'JPG, PNG o WEBP'), ".php renombrado a .jpg: {$msg}");

$msg = $errorAlGuardar($gestor, $fila($fx['poliglota'], 'poliglota.png'));
$afirmar($msg !== null && str_contains($msg, 'no es una imagen válida'), "Cabecera PNG falsa con código PHP: {$msg}");

$msg = $errorAlGuardar($gestor, $fila($fx['valida'], 'valida.png'));
$afirmar(
    $msg !== null && $archivosEn($uploads) === [],
    "Imagen válida que no llegó por HTTP POST la rechaza move_uploaded_file: {$msg}"
);

$msg = $errorAlGuardar(new GestorImagenes($base . '/no-existe'), $fila($fx['valida'], 'valida.png'));
$afirmar(
    $msg !== null && !str_contains($msg, $base),
    "Directorio inexistente: mensaje genérico sin rutas: {$msg}"
);

// Borrado y URL seguros.
$victima = $base . DIRECTORY_SEPARATOR . 'no-borrar.txt';
file_put_contents($victima, 'importante');
$gestor->eliminar('../no-borrar.txt');
$gestor->eliminar(null);
$gestor->eliminar('');
$afirmar(is_file($victima), 'eliminar("../no-borrar.txt") no sale del directorio de cargas');

file_put_contents($uploads . '/.gitkeep', '');
$gestor->eliminar('.gitkeep');
$afirmar(is_file($uploads . '/.gitkeep'), 'eliminar() ignora nombres que no generó el servicio (.gitkeep)');
unlink($uploads . '/.gitkeep');

$afirmar($gestor->urlPublica(null) === '/img/sin-imagen.svg', 'urlPublica(null) devuelve la imagen por defecto');
$afirmar($gestor->urlPublica('../../etc/passwd') === '/img/sin-imagen.svg', 'urlPublica() con path traversal devuelve la imagen por defecto');
$afirmar(
    $gestor->urlPublica(str_repeat('a', 32) . '.png') === '/img/sin-imagen.svg',
    'urlPublica() de un archivo que ya no existe devuelve la imagen por defecto'
);

echo "\n=== Parte 2: cargas reales por HTTP POST ===\n\n";

if (!extension_loaded('curl')) {
    echo "[OMITIDO] La extensión curl no está disponible; se omite la parte 2.\n";
} else {
    // Puerto libre elegido por el sistema operativo.
    $sonda = stream_socket_server('tcp://127.0.0.1:0');
    $puerto = (int) substr(strrchr((string) stream_socket_get_name($sonda, false), ':'), 1);
    fclose($sonda);

    $log = $base . DIRECTORY_SEPARATOR . 'servidor.log';
    $servidor = proc_open(
        [
            PHP_BINARY,
            '-d', 'upload_max_filesize=2M',
            '-d', 'post_max_size=8M',
            '-S', "127.0.0.1:{$puerto}",
            __DIR__ . DIRECTORY_SEPARATOR . 'servidor_imagenes.php',
        ],
        [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']],
        $tuberias,
        dirname(__DIR__),
        array_merge(getenv(), ['TASKIFY_TEST_UPLOADS' => $uploads])
    );

    for ($i = 0; $i < 50; $i++) {
        $conexion = @fsockopen('127.0.0.1', $puerto, $codigoError, $textoError, 0.1);
        if ($conexion !== false) {
            fclose($conexion);
            break;
        }
        usleep(100_000);
    }

    $subir = static function (string $ruta, string $nombre, string $tipo, string $accion = 'guardar', ?string $anterior = null) use ($puerto): array {
        $curl = curl_init("http://127.0.0.1:{$puerto}/?accion={$accion}");
        $campos = ['imagen' => new CURLFile($ruta, $tipo, $nombre)];
        if ($anterior !== null) {
            $campos['anterior'] = $anterior;
        }
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $campos,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $respuesta = curl_exec($curl);

        return is_string($respuesta) ? (json_decode($respuesta, true) ?? ['ok' => false, 'error' => $respuesta]) : ['ok' => false, 'error' => 'sin respuesta'];
    };

    $r = $subir($fx['valida'], 'mi foto.png', 'image/png');
    $nombre = $r['nombre'] ?? '';
    $afirmar(
        $r['ok'] && preg_match('/^[a-f0-9]{32}\.png$/', $nombre) === 1 && is_file($uploads . '/' . $nombre),
        "Imagen válida guardada con nombre aleatorio: {$nombre}"
    );
    $afirmar($gestor->urlPublica($nombre) === '/uploads/' . $nombre, 'urlPublica() apunta a /uploads/<nombre>');

    $r = $subir($fx['grande'], 'grande.png', 'image/png');
    $afirmar(!$r['ok'] && str_contains($r['error'] ?? '', '2 MB'), 'Archivo de 3 MB por HTTP: ' . ($r['error'] ?? ''));

    $r = $subir($fx['php'], 'malicioso.jpg', 'image/jpeg');
    $afirmar(!$r['ok'], '.php renombrado a .jpg por HTTP: ' . ($r['error'] ?? ''));

    $r = $subir($fx['vacio'], 'vacio.jpg', 'image/jpeg');
    $afirmar(!$r['ok'], 'Archivo vacío por HTTP: ' . ($r['error'] ?? ''));

    $afirmar($archivosEn($uploads) === [$nombre], 'Ningún archivo rechazado quedó en el directorio de cargas');

    $r = $subir($fx['php'], 'malicioso.jpg', 'image/jpeg', 'reemplazar', $nombre);
    $afirmar(!$r['ok'] && is_file($uploads . '/' . $nombre), 'Reemplazo fallido conserva la imagen anterior');

    $r = $subir($fx['valida2'], 'nueva.png', 'image/png', 'reemplazar', $nombre);
    $nueva = $r['nombre'] ?? '';
    $afirmar(
        $r['ok'] && is_file($uploads . '/' . $nueva) && !is_file($uploads . '/' . $nombre),
        'Reemplazo correcto guarda la nueva y borra la anterior'
    );

    $gestor->eliminar($nueva);
    $afirmar($archivosEn($uploads) === [], 'eliminar() borra la imagen guardada');

    if (function_exists('imagecreatetruecolor')) {
        $lienzo = imagecreatetruecolor(4, 4);
        imagejpeg($lienzo, $fixtures . '/foto.jpg');
        $r = $subir($fixtures . '/foto.jpg', 'foto.jpg', 'image/jpeg');
        $afirmar($r['ok'] && str_ends_with($r['nombre'] ?? '', '.jpg'), 'JPG válido aceptado con extensión .jpg');
        if (function_exists('imagewebp')) {
            imagewebp($lienzo, $fixtures . '/foto.webp');
            $r = $subir($fixtures . '/foto.webp', 'foto.webp', 'image/webp');
            $afirmar($r['ok'] && str_ends_with($r['nombre'] ?? '', '.webp'), 'WEBP válido aceptado con extensión .webp');
        }
    }

    proc_terminate($servidor);
    proc_close($servidor);
}

// --- Limpieza ---

$borrar = static function (string $ruta) use (&$borrar): void {
    if (is_dir($ruta)) {
        foreach (array_diff(scandir($ruta) ?: [], ['.', '..']) as $hijo) {
            $borrar($ruta . DIRECTORY_SEPARATOR . $hijo);
        }
        rmdir($ruta);
    } elseif (is_file($ruta)) {
        unlink($ruta);
    }
};
$borrar($base);

echo "\n" . ($fallos === 0 ? 'Todas las pruebas pasaron.' : "Fallaron {$fallos} prueba(s).") . "\n";
exit($fallos === 0 ? 0 : 1);
