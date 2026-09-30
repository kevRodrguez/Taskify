<?php

declare(strict_types=1);

// Plantilla de configuración. Copiar a config/config.php y ajustar credenciales.
// config/config.php NO se sube al repositorio (ver .gitignore).
return [
    'dsn'     => 'mysql:host=localhost;port=3306;dbname=taskify;charset=utf8mb4',
    'usuario' => 'root',
    'clave'   => '',
];
