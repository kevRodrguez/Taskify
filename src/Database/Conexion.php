<?php

declare(strict_types=1);

namespace App\Database;

use App\Exceptions\ConexionException;
use PDO;
use PDOException;

/**
 * Punto único de acceso a la base de datos (PDO compartido).
 */
final class Conexion // [ENCAPSULAMIENTO] el PDO es privado; solo se expone obtener()
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function obtener(): PDO
    {
        if (self::$pdo === null) {
            $rutaConfig = __DIR__ . '/../../config/config.php';
            if (!is_file($rutaConfig)) {
                throw new ConexionException(
                    'Falta el archivo de configuración. Copia config/config.example.php a config/config.php.'
                );
            }

            $cfg = require $rutaConfig;

            try {
                self::$pdo = new PDO($cfg['dsn'], $cfg['usuario'], $cfg['clave'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // [SEGURIDAD] consultas preparadas reales
                ]);
            } catch (PDOException $e) {
                error_log('Error de conexión: ' . $e->getMessage());
                // [SEGURIDAD] el mensaje de PDO (host, usuario) no llega al usuario
                throw new ConexionException('No se pudo conectar con la base de datos.', 0, $e);
            }
        }

        return self::$pdo;
    }
}
