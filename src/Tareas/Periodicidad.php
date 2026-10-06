<?php

declare(strict_types=1);

namespace App\Tareas;

enum Periodicidad: string
{
    case DIARIA = 'Diaria';
    case SEMANAL = 'Semanal';
    case MENSUAL = 'Mensual';
}
