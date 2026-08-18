# Taskify

Sistema de gestión de tareas y proyectos desarrollado en PHP 8.1+ como proyecto académico. Modela tareas simples, compuestas y recurrentes usando Programación Orientada a Objetos y el patrón Composite.

**Repositorio:** https://github.com/kevRodrguez/Taskify

## Requisitos

- PHP 8.1 o superior
- [Composer](https://getcomposer.org/)

## Instalación y ejecución

```bash
git clone https://github.com/kevRodrguez/Taskify.git
cd Taskify
composer dump-autoload
php main.php
```

## Estructura del proyecto

```
src/
├── Contratos/     Interfaces (contratos polimórficos)
├── Estado/        Enums y tipos transversales
├── Tareas/        Jerarquía de clases de tarea
└── Tablero/       Agrupación de tareas por estado
main.php           Punto de entrada / demo en consola
```

El autoload PSR-4 mapea el namespace `App\` a la carpeta `src/` (ver `composer.json`).

## Modelo de clases

```
TareaInterface
    ↑ implements
TareaBase (abstracta)
    ↑ extends
    ├── TareaSimple
    ├── TareaCompuesta
    └── TareaRecurrente (pendiente)

Tablero → agrupa TareaInterface[] por estado
```

## Referencia de clases

### `App\Contratos\TareaInterface`

Contrato que define el comportamiento común de todas las tareas.

| Método | Descripción |
|---|---|
| `calcularAvance(): float` | Retorna el porcentaje de avance (0.0–100.0) |
| `obtenerEstado(): EstadoTarea` | Retorna el estado actual de la tarea |
| `getTitulo(): string` | Título de la tarea |
| `getDescripcion(): string` | Descripción de la tarea |
| `getFechaVencimiento(): DateTimeImmutable` | Fecha límite (inmutable) |

### `App\Estado\EstadoTarea`

Enum con los estados posibles:

| Caso | Valor | Condición |
|---|---|---|
| `PENDIENTE` | Pendiente | Avance 0% |
| `EN_PROGRESO` | En progreso | Avance 1%–99% |
| `COMPLETADA` | Completada | Avance 100% |

### `App\Tareas\TareaBase` (abstracta)

Atributos compartidos: título, descripción y fecha de vencimiento (`readonly`).

| Método | Descripción |
|---|---|
| `setTitulo(string)` | Valida que el título no esté vacío; lanza `InvalidArgumentException` |
| `setDescripcion(string)` | Actualiza la descripción |
| `determinarEstadoPorAvance(float)` | Traduce un porcentaje al `EstadoTarea` correspondiente |
| `calcularAvance()` | *Abstracto* — lo implementa cada subclase |
| `obtenerEstado()` | *Abstracto* — lo implementa cada subclase |

### `App\Tareas\TareaSimple`

Tarea con avance manual. El usuario define el porcentaje directamente.

| Método | Descripción |
|---|---|
| `setAvance(float)` | Valida rango 0–100; lanza `InvalidArgumentException` si es inválido |
| `calcularAvance()` | Retorna el avance almacenado |
| `obtenerEstado()` | Deriva el estado a partir del avance |

### `App\Tareas\TareaCompuesta`

Tarea que contiene subtareas (patrón Composite). Su avance es el **promedio** del avance de sus subtareas.

| Método | Descripción |
|---|---|
| `agregarSubtarea(TareaInterface)` | Agrega una subtarea al arreglo interno |
| `calcularAvance()` | Promedia `calcularAvance()` de cada subtarea polimórficamente |
| `obtenerEstado()` | Deriva el estado a partir del avance promediado |

### `App\Tablero\Tablero`

Tablero que recibe tareas de cualquier tipo y las agrupa por estado sin usar `instanceof`.

| Método | Descripción |
|---|---|
| `agregarTarea(TareaInterface)` | Registra una tarea en el tablero |
| `obtenerTareas(): TareaInterface[]` | Retorna todas las tareas registradas |
| `agruparPorEstado(): array` | Agrupa por estado usando `obtenerEstado()` polimórficamente |
| `agruparPorEstadoOrdenado(): array` | Igual, ordenado: Pendiente → En progreso → Completada |

## Pilares de POO en el código

| Pilar | Dónde |
|---|---|
| **Abstracción** | `TareaInterface` + `TareaBase` |
| **Encapsulamiento** | Propiedades `private`/`readonly`, validaciones con excepciones |
| **Herencia** | `TareaSimple`, `TareaCompuesta` extienden `TareaBase` con `parent::__construct()` |
| **Polimorfismo** | `Tablero::agruparPorEstado()` y `main.php` recorren `TareaInterface[]` sin `instanceof` |

## Convenciones del equipo

- **Commits:** Conventional Commits (`feat:`, `fix:`, `refactor:`, `docs:`)
- **Ramas:** `feature/<nombre>` → PR hacia `dev`
- **Namespaces:** `App\Contratos`, `App\Estado`, `App\Tareas`, `App\Tablero`

## Integrantes

- Ramos Castaneda Jimmy Ernesto — 2023-RC-607
- Retana Hernandez Gustavo Adolfo — 2023-RH-601
- Rodríguez Posada Kevin Fernando — 2023-RP-601
- Escobar Preza Bryan Steven — 2023-EP-603
- Galán Gómez Josué Andrés — 2023-GG-605
