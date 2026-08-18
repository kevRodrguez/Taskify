<?php

declare(strict_types=1);

namespace App;

use App\Estado\EstadoTarea;
use DateTimeImmutable;

interface TareaInterface
{
    public function calcularAvance(): float;

    public function obtenerEstado(): EstadoTarea;

    public function getTitulo(): string;

    public function getDescripcion(): string;

    public function getFechaVencimiento(): DateTimeImmutable;
}
