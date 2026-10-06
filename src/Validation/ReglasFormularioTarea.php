<?php

declare(strict_types=1);

namespace App\Validation;

use App\Factories\TareaFactory;
use App\Tareas\TareaBase;

/**
 * Reglas del caso C aplicadas al formulario de tareas.
 *
 * [VALIDACION] Centraliza validación de servidor para título, fechas, avance,
 * periodicidad y tipo antes de delegar en el dominio (TareaFactory / TareaBase).
 */
final class ReglasFormularioTarea
{
    /**
     * @param array<string, mixed> $datos
     */
    public static function validar(array $datos): Validador
    {
        $validador = new Validador();
        $tipo = is_string($datos['tipo'] ?? null) ? trim($datos['tipo']) : '';

        $validador
            ->requerido('titulo', $datos['titulo'] ?? null)
            ->longitudMaxima('titulo', is_string($datos['titulo'] ?? null) ? $datos['titulo'] : '', TareaBase::TITULO_MAX)
            ->requerido('tipo', $datos['tipo'] ?? null)
            ->enLista('tipo', $tipo, array_keys(TareaFactory::tipos()))
            ->requerido('fecha_vencimiento', $datos['fecha_vencimiento'] ?? null)
            ->fechaValida('fecha_vencimiento', $datos['fecha_vencimiento'] ?? null);

        $fechaCreacion = $datos['fecha_creacion'] ?? date('Y-m-d');
        $validador->fechaNoAnterior('fecha_vencimiento', $datos['fecha_vencimiento'] ?? null, $fechaCreacion);

        if (TareaFactory::existe($tipo)) {
            // [POLIMORFISMO] cada tipo declara sus campos; aquí no se pregunta qué tipo es.
            foreach (TareaFactory::camposDe($tipo) as $campo) {
                self::validarCampo($validador, $campo, $datos[$campo['nombre']] ?? null);
            }
        }

        return $validador;
    }

    /**
     * Aplica las reglas que declara el descriptor del campo (ver camposEspecificos()).
     *
     * @param array<string, mixed> $campo
     */
    private static function validarCampo(Validador $validador, array $campo, mixed $valor): void
    {
        $nombre = (string) $campo['nombre'];

        if (!empty($campo['requerido'])) {
            $validador->requerido($nombre, $valor);
        }

        if (isset($campo['min'], $campo['max'])) {
            $validador->rangoNumerico($nombre, $valor, $campo['min'], $campo['max']);
        }

        if (isset($campo['opciones'])) {
            $validador->enLista($nombre, $valor, array_keys($campo['opciones']));
        }
    }
}
