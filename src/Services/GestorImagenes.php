<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ImagenException;
use finfo;

/**
 * Guarda, reemplaza y elimina la imagen (evidencia) de una tarea.
 *
 * [INYECCION-DEPENDENCIAS] el directorio de destino llega por el constructor:
 * las páginas usan public/uploads y las pruebas un directorio temporal, sin
 * cambiar una línea de esta clase.
 *
 * [ENCAPSULAMIENTO] las reglas (tamaño, tipos permitidos, formato del nombre)
 * son constantes privadas; quien usa el servicio solo ve guardar/reemplazar/
 * eliminar/urlPublica y recibe un nombre de archivo o una ImagenException.
 */
final class GestorImagenes
{
    /** Tamaño máximo aceptado: 2 MB. */
    public const TAMANO_MAXIMO = 2 * 1024 * 1024;

    /** Tipos MIME permitidos → extensión con la que se guarda el archivo. */
    private const EXTENSIONES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Tipo que getimagesize() debe reconocer para cada MIME permitido. */
    private const TIPOS_IMAGEN = [
        'image/jpeg' => IMAGETYPE_JPEG,
        'image/png' => IMAGETYPE_PNG,
        'image/webp' => IMAGETYPE_WEBP,
    ];

    /** Formato de los nombres que genera guardar(): 32 hex + extensión permitida. */
    private const PATRON_NOMBRE = '/^[a-f0-9]{32}\.(jpg|png|webp)$/';

    private const URL_CARGAS = '/uploads/';
    private const URL_POR_DEFECTO = '/img/sin-imagen.svg';

    private readonly string $directorio;

    public function __construct(string $directorio)
    {
        $this->directorio = rtrim($directorio, '/\\');
    }

    /**
     * Valida un elemento de $_FILES y lo mueve al directorio de cargas.
     *
     * @param array<string, mixed> $archivo elemento de $_FILES (p. ej. $_FILES['imagen'])
     * @return string nombre único del archivo guardado (sin ruta)
     * @throws ImagenException si la imagen no es válida o no se pudo guardar
     */
    public function guardar(array $archivo): string
    {
        // [VALIDACION] cada comprobación lanza un mensaje distinto y en español.
        $temporal = $this->validarCarga($archivo);
        $this->validarTamano($temporal);
        $mime = $this->validarTipoReal($temporal);
        $this->validarImagen($temporal, $mime);
        $this->validarDirectorio();

        // [SEGURIDAD] el nombre del usuario se descarta: nombre aleatorio + extensión
        // derivada del tipo real, así nunca se guarda un ".php" ni se pisa otro archivo.
        $nombre = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONES[$mime];

        // [SEGURIDAD] move_uploaded_file solo acepta archivos que llegaron por HTTP POST.
        if (!move_uploaded_file($temporal, $this->ruta($nombre))) {
            throw new ImagenException('No se pudo guardar la imagen. Intente de nuevo.');
        }

        return $nombre;
    }

    /**
     * Guarda la imagen nueva y, solo si se guardó bien, borra la anterior.
     *
     * Si la nueva falla se lanza la excepción y la anterior queda intacta.
     *
     * @param array<string, mixed> $archivo
     * @throws ImagenException
     */
    public function reemplazar(?string $anterior, array $archivo): string
    {
        $nueva = $this->guardar($archivo);
        $this->eliminar($anterior);

        return $nueva;
    }

    /**
     * Borra una imagen del directorio de cargas. No hace nada si no existe.
     */
    public function eliminar(?string $nombre): void
    {
        $nombreSeguro = $this->nombreSeguro($nombre);
        if ($nombreSeguro === null) {
            return;
        }

        $ruta = $this->ruta($nombreSeguro);
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }

    /** Indica si la tarea tiene una imagen guardada que realmente existe en disco. */
    public function existe(?string $nombre): bool
    {
        $nombreSeguro = $this->nombreSeguro($nombre);

        return $nombreSeguro !== null && is_file($this->ruta($nombreSeguro));
    }

    /**
     * URL pública de la imagen, o la imagen por defecto si no hay o no existe.
     */
    public function urlPublica(?string $nombre): string
    {
        $nombreSeguro = $this->nombreSeguro($nombre);
        if ($nombreSeguro === null || !is_file($this->ruta($nombreSeguro))) {
            return self::URL_POR_DEFECTO;
        }

        return self::URL_CARGAS . rawurlencode($nombreSeguro);
    }

    /**
     * Comprueba la estructura del arreglo de $_FILES y el código de error de PHP.
     *
     * @param array<string, mixed> $archivo
     * @return string ruta del archivo temporal
     */
    private function validarCarga(array $archivo): string
    {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

        // Un input con name="imagen[]" llega como arreglos: solo se admite un archivo.
        if (!is_int($error)) {
            throw new ImagenException('Solo se permite subir una imagen a la vez.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new ImagenException($this->mensajeErrorCarga($error));
        }

        $temporal = $archivo['tmp_name'] ?? null;
        if (!is_string($temporal) || $temporal === '' || !is_file($temporal)) {
            throw new ImagenException('No se recibió ningún archivo de imagen.');
        }

        return $temporal;
    }

    private function mensajeErrorCarga(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo de 2 MB.',
            UPLOAD_ERR_PARTIAL => 'La imagen se subió de forma incompleta. Intente de nuevo.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ninguna imagen.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene una carpeta temporal para recibir la imagen.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir la imagen en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión del servidor detuvo la carga de la imagen.',
            default => 'Ocurrió un error desconocido al subir la imagen.',
        };
    }

    /** [VALIDACION] tamaño medido en disco: $archivo['size'] lo envía el cliente. */
    private function validarTamano(string $temporal): void
    {
        $tamano = filesize($temporal);

        if ($tamano === false || $tamano === 0) {
            throw new ImagenException('El archivo de imagen está vacío.');
        }

        if ($tamano > self::TAMANO_MAXIMO) {
            throw new ImagenException('La imagen supera el tamaño máximo de 2 MB.');
        }
    }

    /**
     * [SEGURIDAD] el tipo se detecta leyendo el contenido con finfo, nunca por la
     * extensión ni por $archivo['type'] (ambos los controla el cliente).
     */
    private function validarTipoReal(string $temporal): string
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);

        if (!is_string($mime) || !array_key_exists($mime, self::EXTENSIONES)) {
            throw new ImagenException('Solo se permiten imágenes JPG, PNG o WEBP.');
        }

        return $mime;
    }

    /**
     * [SEGURIDAD] getimagesize confirma que el archivo se puede interpretar como
     * imagen del mismo tipo: descarta archivos que solo imitan la cabecera.
     */
    private function validarImagen(string $temporal, string $mime): void
    {
        $info = getimagesize($temporal);

        if (
            $info === false
            || $info[0] <= 0
            || $info[1] <= 0
            || $info[2] !== self::TIPOS_IMAGEN[$mime]
        ) {
            throw new ImagenException('El archivo no es una imagen válida o está dañado.');
        }
    }

    private function validarDirectorio(): void
    {
        if (!is_dir($this->directorio) || !is_writable($this->directorio)) {
            // Mensaje genérico: la ruta real solo va al log del servidor.
            error_log('GestorImagenes: directorio no disponible: ' . $this->directorio);
            throw new ImagenException('No se pudo guardar la imagen en el servidor.');
        }
    }

    /**
     * [SEGURIDAD] basename() descarta cualquier "../" y el patrón solo deja pasar
     * nombres generados por guardar(): no se puede leer ni borrar otro archivo.
     */
    private function nombreSeguro(?string $nombre): ?string
    {
        if ($nombre === null || $nombre === '') {
            return null;
        }

        $base = basename($nombre);

        return preg_match(self::PATRON_NOMBRE, $base) === 1 ? $base : null;
    }

    private function ruta(string $nombre): string
    {
        return $this->directorio . DIRECTORY_SEPARATOR . $nombre;
    }
}
