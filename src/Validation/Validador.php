<?php

declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;

/**
 * Validador de formularios con reglas encadenables y mensajes por campo.
 *
 * [VALIDACION] Acumula todos los errores antes de decidir si el formulario es válido.
 */
final class Validador
{
    /** @var array<string, string> */
    private array $errores = [];

    /** [VALIDACION] El campo debe tener un valor no vacío (tras trim). */
    public function requerido(string $campo, mixed $valor): self
    {
        if ($this->tieneError($campo)) {
            return $this;
        }

        $texto = is_string($valor) ? trim($valor) : '';
        if ($valor === null || $texto === '') {
            $this->errores[$campo] = 'Este campo es obligatorio.';
        }

        return $this;
    }

    /** [VALIDACION] Longitud máxima en caracteres (multibyte). */
    public function longitudMaxima(string $campo, mixed $valor, int $maximo): self
    {
        if ($this->tieneError($campo) || !is_string($valor)) {
            return $this;
        }

        if (mb_strlen($valor) > $maximo) {
            $this->errores[$campo] = "No puede superar los {$maximo} caracteres.";
        }

        return $this;
    }

    /** [VALIDACION] Formato de fecha AAAA-MM-DD y fecha calendario válida. */
    public function fechaValida(string $campo, mixed $valor): self
    {
        if ($this->tieneError($campo)) {
            return $this;
        }

        if (!$this->esFechaValida($valor)) {
            $this->errores[$campo] = 'Ingrese una fecha válida (AAAA-MM-DD).';
        }

        return $this;
    }

    /**
     * [VALIDACION] La fecha no puede ser anterior a $fechaMinima.
     *
     * @param mixed $fechaMinima Texto Y-m-d o DateTimeImmutable.
     */
    public function fechaNoAnterior(string $campo, mixed $valor, mixed $fechaMinima): self
    {
        if ($this->tieneError($campo) || !$this->esFechaValida($valor)) {
            return $this;
        }

        $fecha = $this->aFecha($valor);
        $minima = $this->aFecha($fechaMinima);

        if ($fecha === null || $minima === null) {
            return $this;
        }

        if ($fecha < $minima) {
            $this->errores[$campo] = 'La fecha no puede ser anterior a la fecha de creación.';
        }

        return $this;
    }

    /**
     * [VALIDACION] Valor numérico dentro del rango inclusivo [min, max].
     */
    public function rangoNumerico(string $campo, mixed $valor, int|float $min, int|float $max): self
    {
        if ($this->tieneError($campo)) {
            return $this;
        }

        if (is_string($valor)) {
            $valor = trim($valor);
        }

        if ($valor === '' || $valor === null || !is_numeric($valor)) {
            $this->errores[$campo] = "Debe ser un número entre {$min} y {$max}.";

            return $this;
        }

        $numero = (float) $valor;
        if ($numero < $min || $numero > $max) {
            $this->errores[$campo] = "Debe estar entre {$min} y {$max}.";
        }

        return $this;
    }

    /**
     * [VALIDACION] El valor debe pertenecer a la lista de opciones permitidas.
     *
     * @param list<string|int> $permitidos
     */
    public function enLista(string $campo, mixed $valor, array $permitidos): self
    {
        if ($this->tieneError($campo)) {
            return $this;
        }

        $texto = is_string($valor) ? trim($valor) : (string) $valor;
        $opciones = array_map(static fn (string|int $op): string => (string) $op, $permitidos);

        if (!in_array($texto, $opciones, true)) {
            $this->errores[$campo] = 'Seleccione una opción válida.';
        }

        return $this;
    }

    public function esValido(): bool
    {
        return $this->errores === [];
    }

    /** @return array<string, string> */
    public function errores(): array
    {
        return $this->errores;
    }

    private function tieneError(string $campo): bool
    {
        return array_key_exists($campo, $this->errores);
    }

    private function esFechaValida(mixed $valor): bool
    {
        return $this->aFecha($valor) !== null;
    }

    private function aFecha(mixed $valor): ?DateTimeImmutable
    {
        if ($valor instanceof DateTimeImmutable) {
            return $valor->setTime(0, 0);
        }

        $texto = is_string($valor) ? trim($valor) : '';
        if ($texto === '') {
            return null;
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);

        if ($fecha === false || $fecha->format('Y-m-d') !== $texto) {
            return null;
        }

        return $fecha;
    }
}
