<?php

declare(strict_types=1);

namespace App\Estado;

enum EstadoTarea: string
{
    case PENDIENTE = 'Pendiente';
    case EN_PROGRESO = 'En progreso';
    case COMPLETADA = 'Completada';
}
