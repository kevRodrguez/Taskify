<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Tablero\Tablero;
use App\Tareas\TareaCompuesta;
use App\Tareas\TareaSimple;

$disenoUI = new TareaSimple(
    'Diseñar wireframes de la GUI',
    'Bocetar las pantallas principales del tablero',
    new DateTimeImmutable('+2 days'),
    100.0
);

$maquetadoUI = new TareaSimple(
    'Maquetar componentes en HTML/CSS',
    'Convertir los wireframes en vistas navegables',
    new DateTimeImmutable('+5 days'),
    30.0
);

$documentacion = new TareaSimple(
    'Redactar manual de usuario',
    'Documentar el uso del tablero para el usuario final',
    new DateTimeImmutable('+7 days')
);

$moduloFrontend = new TareaCompuesta(
    'Módulo de interfaz gráfica',
    'Agrupa todo el trabajo de frontend del tablero',
    new DateTimeImmutable('+7 days')
);
$moduloFrontend->agregarSubtarea($disenoUI);
$moduloFrontend->agregarSubtarea($maquetadoUI);
$moduloFrontend->agregarSubtarea($documentacion);

$tablero = new Tablero();
$tablero->agregarTarea($disenoUI);
$tablero->agregarTarea($maquetadoUI);
$tablero->agregarTarea($documentacion);
$tablero->agregarTarea($moduloFrontend);

echo '=== Tablero de tareas (Taskify) ===' . PHP_EOL;

foreach ($tablero->agruparPorEstadoOrdenado() as $estado => $tareasDelEstado) {
    echo PHP_EOL . "[{$estado}]" . PHP_EOL;

    foreach ($tareasDelEstado as $tarea) {
        printf(
            '- %-32s | Avance: %5.1f%%' . PHP_EOL,
            $tarea->getTitulo(),
            $tarea->calcularAvance()
        );
    }
}

echo PHP_EOL . '=== Validación de encapsulamiento (demo en vivo) ===' . PHP_EOL;

try {
    new TareaSimple('', 'Sin título', new DateTimeImmutable());
} catch (InvalidArgumentException $e) {
    echo "Excepción capturada: {$e->getMessage()}" . PHP_EOL;
}
