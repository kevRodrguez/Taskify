<?php

declare(strict_types=1);

/**
 * Prueba manual del Validador con entradas vacías, largas y maliciosas.
 *
 * Ejecutar: php tests/validador_test.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Validation\ReglasFormularioTarea;
use App\Validation\Validador;

$fallos = 0;

$afirmar = static function (bool $condicion, string $mensaje) use (&$fallos): void {
    if ($condicion) {
        echo "[OK] {$mensaje}\n";
    } else {
        echo "[FALLO] {$mensaje}\n";
        $fallos++;
    }
};

echo "=== Pruebas de Validador ===\n\n";

// --- Reglas unitarias ---

$v = (new Validador())
    ->requerido('titulo', '')
    ->longitudMaxima('titulo', str_repeat('A', 121), 120)
    ->fechaValida('fecha_vencimiento', '2026-13-40')
    ->fechaNoAnterior('fecha_vencimiento', '2026-01-01', '2026-06-01')
    ->rangoNumerico('avance', 150, 0, 100)
    ->enLista('tipo', 'inyeccion', ['simple', 'compuesta']);

$afirmar(!$v->esValido(), 'Acumula errores con entradas inválidas');
$afirmar(isset($v->errores()['titulo']), 'Detecta título vacío');
$afirmar(isset($v->errores()['fecha_vencimiento']), 'Detecta fecha inválida o anterior');
$afirmar(isset($v->errores()['avance']), 'Detecta avance fuera de rango');
$afirmar(isset($v->errores()['tipo']), 'Rechaza tipo no permitido');

$payloadMalicioso = '<script>alert("xss")</script>' . str_repeat('X', 200);
$v2 = (new Validador())
    ->requerido('titulo', $payloadMalicioso)
    ->longitudMaxima('titulo', $payloadMalicioso, 120);

$afirmar(!$v2->esValido(), 'Rechaza título demasiado largo (payload malicioso)');
$afirmar(!isset($v2->errores()['titulo']) || str_contains($v2->errores()['titulo'], '120'), 'Mensaje de longitud en español');

$v3 = (new Validador())
    ->requerido('titulo', 'Tarea válida')
    ->fechaValida('fecha_vencimiento', '2026-12-31')
    ->fechaNoAnterior('fecha_vencimiento', '2026-12-31', '2026-01-01')
    ->rangoNumerico('avance', '50', 0, 100)
    ->enLista('tipo', 'simple', ['simple', 'compuesta', 'recurrente']);

$afirmar($v3->esValido(), 'Acepta datos correctos');

// --- Reglas del caso C (formulario de tarea) ---

$formularioInvalido = ReglasFormularioTarea::validar([
    'titulo' => '',
    'tipo' => 'no-existe',
    'fecha_vencimiento' => 'fecha-falsa',
    'avance' => '-5',
    'periodicidad' => 'Anual',
]);

$afirmar(!$formularioInvalido->esValido(), 'ReglasFormularioTarea rechaza formulario inválido');

$formularioValido = ReglasFormularioTarea::validar([
    'titulo' => 'Repasar capítulo 3',
    'tipo' => 'simple',
    'fecha_creacion' => '2026-09-01',
    'fecha_vencimiento' => '2026-09-30',
    'avance' => '25',
]);

$afirmar($formularioValido->esValido(), 'ReglasFormularioTarea acepta tarea simple válida');

echo "\n=== Resultado: " . ($fallos === 0 ? 'TODAS PASARON' : "{$fallos} FALLO(S)") . " ===\n";

exit($fallos === 0 ? 0 : 1);
