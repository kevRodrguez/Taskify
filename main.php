<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Reportes\ExportadorJson;
use App\Tablero\Tablero;
use App\Tareas\TareaCompuesta;
use App\Tareas\Periodicidad;
use App\Tareas\TareaRecurrente;
use App\Tareas\TareaSimple;

/** Rellena con espacios contando caracteres (no bytes), para que tildes/ñ no desalineen columnas. */
function pad(string $texto, int $ancho): string
{
    return $texto . str_repeat(' ', max(0, $ancho - mb_strlen($texto)));
}

function seccion(string $titulo): string
{
    return PHP_EOL . "=== {$titulo} ===" . PHP_EOL;
}

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

$tablero = new Tablero();
$tablero->agregarTarea($disenoUI);
$tablero->agregarTarea($maquetadoUI);
$tablero->agregarTarea($documentacion);
$tablero->agregarTarea($moduloFrontend);
$tablero->agregarTarea($reunionSemanal);
$tablero->agregarTarea($backupDiario);

echo seccion('Tablero de tareas (Taskify)');

foreach ($tablero->agruparPorEstadoOrdenado() as $estado => $tareasDelEstado) {
    echo PHP_EOL . "[{$estado}]" . PHP_EOL;
    echo '  ' . pad('Título', 34) . pad('Avance', 9) . 'Vence' . PHP_EOL;
    echo '  ' . str_repeat('-', 58) . PHP_EOL;

    foreach ($tareasDelEstado as $tarea) {
        echo '  '
            . pad($tarea->getTitulo(), 34)
            . pad(sprintf('%5.1f%%', $tarea->calcularAvance()), 9)
            . $tarea->getFechaVencimiento()->format('Y-m-d')
            . PHP_EOL;
    }
}

echo seccion('Validación de encapsulamiento (demo en vivo)');

try {
    new TareaSimple('', 'Sin título', new DateTimeImmutable());
} catch (InvalidArgumentException $e) {
    echo "Excepción capturada: {$e->getMessage()}" . PHP_EOL;
}

echo seccion('Recurrencia: completar ciclo');
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

$cierreMensual = new TareaRecurrente(
    'Cierre contable mensual',
    'Conciliar movimientos al último día del mes',
    new DateTimeImmutable('2026-01-31'),
    Periodicidad::MENSUAL,
    100.0
);
echo seccion('Recurrencia mensual (día 31 sin desbordar a marzo)');
echo "Ancla: {$cierreMensual->getFechaVencimiento()->format('Y-m-d')}" . PHP_EOL;
$cierreMensual->completarCiclo();
echo "Ciclo 1: {$cierreMensual->getFechaVencimiento()->format('Y-m-d')} (febrero, último día válido)" . PHP_EOL;
$cierreMensual->setAvance(100.0);
$cierreMensual->completarCiclo();
echo "Ciclo 2: {$cierreMensual->getFechaVencimiento()->format('Y-m-d')} (marzo, se recupera el día 31)" . PHP_EOL;

try {
    new TareaSimple('', 'Sin título', new DateTimeImmutable());
} catch (InvalidArgumentException $e) {
    echo "Excepción capturada: {$e->getMessage()}" . PHP_EOL;
}

echo PHP_EOL . '=== Manejo de archivos: exportar tablero a JSON ===' . PHP_EOL;

$rutaExportacion = __DIR__ . '/export/tablero.json';
if (!is_dir(__DIR__ . '/export')) {
    mkdir(__DIR__ . '/export');
}

$exportador = new ExportadorJson();
$exportador->exportar($tablero, $rutaExportacion);
echo "Tablero exportado en: {$rutaExportacion}" . PHP_EOL;

$datosImportados = $exportador->importar($rutaExportacion);
printf('Tareas leídas de vuelta desde el archivo: %d' . PHP_EOL, count($datosImportados));
echo 'Primer registro crudo: ' . json_encode($datosImportados[0], JSON_UNESCAPED_UNICODE) . PHP_EOL;
