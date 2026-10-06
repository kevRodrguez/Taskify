# Validaciones — Taskify Fase 2

Documento exigido por el informe: reglas en cliente (HTML5), servidor y mensajes mostrados al usuario.

## Cómo probar la validación del servidor (obligatorio)

1. Abrir un formulario (crear tarea o asignar subtarea).
2. Agregar el atributo **`novalidate`** al `<form>` (ya incluido en subtareas; usarlo también al probar tareas).
3. Enviar datos inválidos a propósito (campos vacíos, fechas imposibles, avance 999).
4. Confirmar que el servidor rechaza el envío, conserva los valores y muestra mensajes por campo.

Sin `novalidate`, el navegador bloquea el envío antes de llegar al PHP y no se demuestra la validación del servidor.

---

## Formulario de tareas (`public/tareas/`)

| Campo | Regla en el cliente (HTML5) | Regla en el servidor | Mensaje mostrado |
|---|---|---|---|
| `titulo` | `required`, `maxlength="120"` | `requerido()`, `longitudMaxima(..., 120)` + dominio `TareaBase` | Este campo es obligatorio. / No puede superar los 120 caracteres. |
| `tipo` | `required` en `<select>` | `requerido()`, `enLista()` con tipos de `TareaFactory` | Este campo es obligatorio. / Seleccione una opción válida. |
| `fecha_vencimiento` | `type="date"`, `required` | `requerido()`, `fechaValida()`, `fechaNoAnterior(..., fecha_creacion)` | Este campo es obligatorio. / Ingrese una fecha válida (AAAA-MM-DD). / La fecha no puede ser anterior a la fecha de creación. |
| `avance` | `type="number"`, `min="0"`, `max="100"`, `required` (simple y recurrente) | `requerido()`, `rangoNumerico(..., 0, 100)` + dominio | Este campo es obligatorio. / Debe estar entre 0 y 100. |
| `periodicidad` | `required` en `<select>` (recurrente) | `requerido()`, `enLista()` Diaria/Semanal/Mensual | Este campo es obligatorio. / Seleccione una opción válida. |
| `_csrf` | — | `Csrf::verificar()` con `hash_equals` | La solicitud no es válida. Intente de nuevo. |

Implementación servidor: `App\Validation\ReglasFormularioTarea` → `Validador`.

---

## Formulario de subtareas (`public/subtareas/`)

| Campo | Regla en el cliente (HTML5) | Regla en el servidor | Mensaje mostrado |
|---|---|---|---|
| `tarea_padre_id` | Campo oculto (viene del contexto) | `requerido()`, `rangoNumerico(..., 1, PHP_INT_MAX)` | Este campo es obligatorio. / Debe estar entre 1 y … |
| `tarea_hija_id` | `required` en `<select>` | `requerido()`, `rangoNumerico(..., 1, PHP_INT_MAX)` + reglas de dominio en `SubtareaRepositorio` | Este campo es obligatorio. / Solo las tareas compuestas pueden tener subtareas. / Esa subtarea ya está asignada… / No se puede asignar: crearía un ciclo… |
| `_csrf` | — | `Csrf::verificar()` | La solicitud no es válida. Intente de nuevo. |

Implementación servidor: `App\Validation\ReglasFormularioSubtarea` + `App\Tareas\ReglasSubtarea` + `SubtareaRepositorio`.

---

## Clase `Validador` — reglas disponibles

| Método | Uso |
|---|---|
| `requerido(campo, valor)` | No vacío tras `trim` |
| `longitudMaxima(campo, valor, max)` | Máximo de caracteres (multibyte) |
| `fechaValida(campo, valor)` | Formato `Y-m-d` y fecha real |
| `fechaNoAnterior(campo, valor, fechaMinima)` | Vencimiento ≥ creación |
| `rangoNumerico(campo, valor, min, max)` | Número en rango inclusivo |
| `enLista(campo, valor, permitidos)` | Valor en lista blanca |
| `esValido(): bool` | `true` si no hay errores |
| `errores(): array` | Mapa `campo → mensaje` en español |

Prueba automatizada: `php tests/validador_test.php`
