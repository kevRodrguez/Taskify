<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ImagenException;

/** STUB LOCAL del contrato de Andrés (feature/gestor-imagenes): no subir. */
final class GestorImagenes
{
    public function __construct(private readonly string $directorio)
    {
    }

    public function guardar(array $archivo): string
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ImagenException('No se pudo subir la imagen.');
        }
        if (($archivo['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new ImagenException('La imagen no puede superar los 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensiones[$mime])) {
            throw new ImagenException('Solo se permiten imágenes JPG, PNG o WEBP.');
        }
        $nombre = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
        if (!move_uploaded_file($archivo['tmp_name'], rtrim($this->directorio, '/\\') . '/' . $nombre)) {
            throw new ImagenException('No se pudo guardar la imagen.');
        }

        return $nombre;
    }

    public function reemplazar(?string $anterior, array $archivo): string
    {
        $nuevo = $this->guardar($archivo);
        $this->eliminar($anterior);

        return $nuevo;
    }

    public function eliminar(?string $nombre): void
    {
        if ($nombre === null || $nombre === '') {
            return;
        }
        $ruta = rtrim($this->directorio, '/\\') . '/' . basename($nombre);
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}
