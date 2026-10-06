<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando una imagen subida no se puede aceptar o guardar
 * (error de carga, tamaño excedido, tipo no permitido, archivo dañado…).
 *
 * El mensaje siempre está en español y es seguro para mostrarlo al usuario
 * junto al campo `imagen`: nunca incluye rutas del servidor.
 */
final class ImagenException extends RuntimeException
{
}
