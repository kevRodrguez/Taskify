<?php

declare(strict_types=1);

/**
 * Layout superior. Único lugar que abre la sesión.
 *
 * Variable opcional: string $tituloPagina
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// [SEGURIDAD] deja el token CSRF en la sesión para los formularios de las demás ramas.
if (class_exists(\App\Validation\Csrf::class)) {
    \App\Validation\Csrf::token();
}

$tituloPagina = $tituloPagina ?? 'Taskify';
$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$enlaces = [
    ['href' => '/', 'texto' => 'Inicio'],
    ['href' => '/tareas/index.php', 'texto' => 'Tareas'],
    ['href' => '/tareas/crear.php', 'texto' => 'Nueva tarea'],
    ['href' => '/reportes/tablero.php', 'texto' => 'Reporte'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina) ?> · Taskify</title>
    <link rel="stylesheet" href="/css/estilos.css">
</head>
<body>
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <header class="encabezado">
        <div class="contenedor encabezado-interno">
            <a class="marca" href="/">Taskify</a>
            <nav aria-label="Principal">
                <ul class="menu">
                    <?php foreach ($enlaces as $enlace): ?>
                        <?php
                        $activo = $rutaActual === $enlace['href']
                            || ($enlace['href'] === '/' && $rutaActual === '/index.php');
                        ?>
                        <li>
                            <a href="<?= e($enlace['href']) ?>"<?= $activo ? ' aria-current="page"' : '' ?>><?= e($enlace['texto']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main id="contenido" class="contenedor">
        <?php require __DIR__ . '/../partials/mensajes.php'; ?>
