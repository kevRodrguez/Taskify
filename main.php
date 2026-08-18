<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Contratos\TareaInterface;
use App\Tareas\Periodicidad;
use App\Tareas\TareaCompuesta;
use App\Tareas\TareaRecurrente;
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

$reunionSemanal = new TareaRecurrente(
    'Reunión semanal de seguimiento',
    'Sincronizar avances del equipo cada semana',
    new DateTimeImmutable('+3 days'),
    Periodicidad::SEMANAL,
    60.0
);

$backupDiario = new TareaRecurrente(
    'Respaldo diario del proyecto',
    'Generar copia de seguridad del repositorio',
    new DateTimeImmutable('today'),
    Periodicidad::DIARIA,
    100.0
);

/** @var TareaInterface[] $tareas */
$tareas = [$disenoUI, $maquetadoUI, $moduloFrontend, $reunionSemanal, $backupDiario];

echo "=== Reporte de tareas (Taskify) ===" . PHP_EOL;

foreach ($tareas as $tarea) {
    printf(
        '- %-36s | Avance: %5.1f%% | Estado: %-12s | Vence: %s' . PHP_EOL,
        $tarea->getTitulo(),
        $tarea->calcularAvance(),
        $tarea->obtenerEstado()->value,
        $tarea->getFechaVencimiento()->format('Y-m-d')
    );
}

echo PHP_EOL . "=== Recurrencia: completar ciclo ===" . PHP_EOL;
printf(
    'Antes:  %s | Periodicidad: %s | Vence: %s | Avance: %.1f%% | Estado: %s' . PHP_EOL,
    $backupDiario->getTitulo(),
    $backupDiario->getPeriodicidad()->value,
    $backupDiario->getFechaVencimiento()->format('Y-m-d'),
    $backupDiario->calcularAvance(),
    $backupDiario->obtenerEstado()->value
);
$backupDiario->completarCiclo();
printf(
    'Después: %s | Periodicidad: %s | Vence: %s | Avance: %.1f%% | Estado: %s' . PHP_EOL,
    $backupDiario->getTitulo(),
    $backupDiario->getPeriodicidad()->value,
    $backupDiario->getFechaVencimiento()->format('Y-m-d'),
    $backupDiario->calcularAvance(),
    $backupDiario->obtenerEstado()->value
);

echo PHP_EOL . "=== Validación de encapsulamiento (demo en vivo) ===" . PHP_EOL;

try {
    new TareaSimple('', 'Sin título', new DateTimeImmutable());
} catch (InvalidArgumentException $e) {
    echo "Excepción capturada: {$e->getMessage()}" . PHP_EOL;
}
