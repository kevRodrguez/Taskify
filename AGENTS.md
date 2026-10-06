# Taskify — Contexto del proyecto para agentes de IA

> Documento de referencia para agentes de Cursor y otros asistentes de IA que trabajen en este repositorio.
> Última actualización: agosto 2026.

---

## 1. Resumen ejecutivo

**Taskify** es un gestor de tareas y proyectos desarrollado en **PHP 8.1+** como proyecto académico del curso **Desarrollo de Páginas Web con Software Libre** (Universidad Católica de El Salvador, Ing. Karla Michelle López de Quintana).

El sistema modela tareas heterogéneas (simples, compuestas y recurrentes) usando **Programación Orientada a Objetos** y el patrón **Composite**. No usa base de datos: todo opera en memoria para evidenciar el modelo de clases. El entregable final incluirá una GUI web; el **primer avance** solo requiere consola + diseño de clases.

| Campo | Valor |
|---|---|
| Repositorio | https://github.com/kevRodrguez/Taskify |
| Rama estable | `main` |
| Rama de integración del equipo | `dev` |
| Convención de ramas | `feature/<nombre-corto-en-kebab-case>` |
| Autoload | PSR-4 vía Composer (`App\` → `src/`) |

---

## 2. Integrantes del equipo

| Nombre | Carnet |
|---|---|
| Ramos Castaneda Jimmy Ernesto | 2023-RC-607 |
| Retana Hernandez Gustavo Adolfo | 2023-RH-601 |
| Rodríguez Posada Kevin Fernando | 2023-RP-601 |
| Escobar Preza Bryan Steven | 2023-EP-603 |
| Galán Gómez Josué Andrés | 2023-GG-605 |

**Importante:** cada integrante debe tener commits propios visibles en GitHub. El docente evalúa la autoría real en el historial.

---

## 3. Problema y alcance (propuesta original)

### Problema

En entornos de trabajo dinámicos, los equipos gestionan actividades de distinta naturaleza: tareas simples, proyectos con subtareas y rutinas repetitivas. El reto es consolidarlas en un **único tablero** que calcule progreso global y las agrupe por estado, sin hacer el sistema rígido ni difícil de escalar.

### Qué SÍ hará el sistema

- Crear tareas simples, compuestas (con subtareas) y recurrentes.
- Calcular avance de forma **polimórfica** (compuestas promedian subtareas).
- Determinar fechas de vencimiento según la naturaleza de la tarea.
- Generar un **Tablero** que agrupe tareas heterogéneas por estado sin `instanceof`.
- Interfaz gráfica web (solo para el **entregable final**, no para el primer avance).

### Qué NO hará

- Persistencia en base de datos.
- Dependencias externas más allá de Composer para autoload.

### Restricciones técnicas

- PHP 8.1 o superior.
- Composer con autoload PSR-4.
- Sin frameworks obligatorios.

---

## 4. Modelo de clases (diseño objetivo)

```
TareaInterface (interfaz)
    ↑ implements
TareaBase (clase abstracta)
    ↑ extends
    ├── TareaSimple      ✅ implementada
    ├── TareaCompuesta   ✅ implementada
    └── TareaRecurrente  ✅ implementada

Tablero                  ✅ implementado (agrupa TareaInterface[] por estado)
ExportadorJson           ✅ implementado (App\Reportes, exporta/importa JSON)
```

### Responsabilidades por clase

| Clase | Namespace | Rol |
|---|---|---|
| `TareaInterface` | `App\Contratos` | Contrato: `calcularAvance()`, `obtenerEstado()`, getters |
| `EstadoTarea` | `App\Estado` | Enum: `PENDIENTE`, `EN_PROGRESO`, `COMPLETADA` |
| `TareaBase` | `App\Tareas` | Atributos comunes, validaciones, lógica compartida de estado |
| `TareaSimple` | `App\Tareas` | Avance manual (0–100) |
| `TareaCompuesta` | `App\Tareas` | Composición de `TareaInterface[]`, promedia avance |
| `TareaRecurrente` | `App\Tareas` | Recalcula fecha de vencimiento según periodicidad |
| `Tablero` | `App\Tablero` | Colección heterogénea, agrupa por `obtenerEstado()` |
| `ExportadorJson` | `App\Reportes` | Exporta/importa el tablero como JSON vía `toArray()` polimórfico |

---

## 5. Estado actual de implementación

### ✅ Completado

```
src/
  Contratos/TareaInterface.php
  Estado/EstadoTarea.php
  Tableros/... (ver Tablero/Tablero.php)
  Tareas/TareaBase.php
  Tareas/TareaSimple.php
  Tareas/TareaCompuesta.php
  Tareas/TareaRecurrente.php
  Tareas/Periodicidad.php
  Tablero/Tablero.php
  Reportes/ExportadorJson.php
main.php
composer.json
.gitignore
```

**Ejecutar:**

```bash
composer dump-autoload
php main.php
```

`main.php` crea tareas de prueba, arma un `Tablero`, recorre un arreglo `TareaInterface[]` polimórficamente, demuestra recurrencia (`completarCiclo()`), exporta/importa el tablero a JSON con `ExportadorJson`, y demuestra una excepción de encapsulamiento en vivo.

### 📄 Manejo de archivos (JSON) — detalle de implementación

Rama: `feature/exportacion-archivo`. Cumple el criterio "Manejo de archivos (avance)" de la rúbrica (8 pts) con implementación real, no solo diseño.

- `TareaInterface::toArray(): array` — nuevo método del contrato; cada tarea concreta sabe representarse a sí misma.
- `TareaBase::toArray()` — implementación por defecto con los campos comunes (título, descripción, fechaVencimiento, avance, estado).
- `TareaCompuesta::toArray()` — llama `parent::toArray()` y agrega `subtareas` recorriendo sus `TareaInterface[]` recursivamente.
- `TareaRecurrente::toArray()` — llama `parent::toArray()` y agrega `periodicidad`.
- `App\Reportes\ExportadorJson` — `exportar(Tablero, string): void` serializa `$tablero->obtenerTareas()` con `array_map` + `toArray()` (sin `instanceof`) y escribe con `json_encode(..., JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)`. `importar(string): array` lee el JSON y devuelve datos crudos (pensado para el entregable final, cuando se reconstruyan objetos).
- El archivo generado en la demo (`export/tablero.json`) no se versiona (ver `.gitignore`).

### 🔮 Para el entregable final (no priorizar ahora)

- GUI web frontend.
- Posible persistencia o integración más amplia (según evolucione el curso).

---

## 6. Mapa de conceptos POO en el código

Usar estas referencias exactas durante la exposición y al implementar nuevas clases.

### Abstracción (12 pts)

- **Interfaz:** `src/Contratos/TareaInterface.php` — define el contrato polimórfico.
- **Clase abstracta:** `src/Tareas/TareaBase.php` — atributos comunes + métodos abstractos `calcularAvance()` y `obtenerEstado()`.

### Encapsulamiento (12 pts)

- Propiedades `private` en `TareaBase` (`$titulo`, `$descripcion`).
- Propiedad `private readonly DateTimeImmutable $fechaVencimiento` — inmutable tras construcción.
- Validación con excepción en `TareaBase::setTitulo()` — lanza `InvalidArgumentException` si el título está vacío.
- Validación en `TareaSimple::setAvance()` — rango 0–100.
- Demo en vivo: bloque `try/catch` al final de `main.php`.

### Herencia (10 pts)

- `TareaSimple` y `TareaCompuesta` extienden `TareaBase`.
- Uso de `parent::__construct(...)` en ambas subclases.
- Método compartido `determinarEstadoPorAvance()` en la clase base, usado por subclases.

### Polimorfismo (14 pts — el criterio más pesado)

- **Punto clave actual:** `main.php` líneas 45–51 — `foreach ($tareas as $tarea)` llama `$tarea->calcularAvance()` y `$tarea->obtenerEstado()` sin `instanceof`.
- **Punto clave futuro:** `Tablero` debe recorrer un arreglo heterogéneo (Simple + Compuesta + Recurrente) y agrupar por estado de la misma forma.
- **Evitar:** `instanceof`, `switch` por tipo concreto, o condicionales `if ($tarea instanceof TareaSimple)`.

---

## 7. Estructura del repositorio

```
Taskify/
├── AGENTS.md              ← este archivo
├── composer.json          ← autoload PSR-4: "App\\" → "src/"
├── main.php               ← punto de entrada / demo en consola
├── .gitignore             ← excluye vendor/, .env, .DS_Store
└── src/
    ├── Contratos/         ← interfaces (contratos)
    ├── Estado/            ← enums y tipos de dominio transversales
    └── Tareas/            ← jerarquía de clases de tarea
```

**Criterio de organización:** carpetas por **responsabilidad de dominio**, no por tipo técnico (no usar `Interfaces/`, `Abstract/`, etc.).

---

## 8. Convenciones de desarrollo

### Commits — Conventional Commits

```
feat: descripción breve
feat(tareas): agregar TareaRecurrente
fix(tablero): corregir agrupación de tareas completadas
refactor(src): reorganizar carpetas por responsabilidad
docs: actualizar AGENTS.md
```

- Un commit = un cambio lógico. **Nunca** un solo commit "subida final".
- Mínimo 5 commits descriptivos para la rúbrica.

### Ramas y flujo

1. Crear rama desde `dev`: `feature/nombre-funcionalidad`
2. Implementar y commitear con la cuenta GitHub del autor.
3. Abrir PR hacia **`dev`** (no hacia `main` directamente).
4. `main` permanece como rama estable.

### Código PHP

- `declare(strict_types=1);` en todos los archivos.
- Namespaces bajo `App\`.
- Clases concretas de tarea marcadas como `final` cuando no se espera más herencia.
- Preferir `DateTimeImmutable` para fechas.
- Validaciones en setters; lanzar `InvalidArgumentException` con mensajes en español.
- Sin dependencias en `composer.json` más allá del autoload (por ahora).

---

## 9. Primer avance — guía de presentación

**Modalidad:** grupal, 6–8 minutos, exposición oral con pantalla compartida (código + GitHub en vivo).

### Secciones obligatorias (en orden)

1. **Introducción y alcance** — problema, qué está resuelto, qué falta.
2. **Repositorio GitHub** — historial de commits, explicar ≥2 commits, `.gitignore`, autoría visible.
3. **Estructura y Composer** — carpetas `src/`, `composer.json`, ejecutar `composer dump-autoload && php main.php` en vivo.
4. **POO evidenciado** — señalar línea por línea: abstracción, encapsulamiento, herencia, polimorfismo.
5. **Manejo de archivos** — implementación real o diseño planeado (clase, formato JSON, fecha estimada).
6. **Demostración en vivo** — salida real de `php main.php`, sin resultados preparados.
7. **Dificultades y próximos pasos** — al menos un obstáculo real + plan breve.

### Rúbrica (100 puntos)

| Criterio | Puntos |
|---|---|
| Repositorio GitHub | 20 |
| Estructura del proyecto y Composer | 12 |
| Abstracción | 12 |
| Encapsulamiento | 12 |
| Herencia | 10 |
| Polimorfismo | 14 |
| Manejo de archivos (avance) | 8 |
| Claridad de la exposición | 6 |
| Cumplimiento de tiempo y formato | 2 |
| Propuesta enviada | 4 |

### Penalizaciones a evitar

| Situación | Penalización |
|---|---|
| Repositorio inaccesible antes de la exposición | −15 |
| Un solo commit o mensajes no descriptivos | −10 |
| Solo un integrante expone | −10 |
| `php main.php` falla en vivo | −10 |
| Diapositivas con código en imagen | −5 |
| Exceder tiempo sin autorización | −3 |

### Preguntas guía del docente

1. ¿Dónde ocurre polimorfismo real sin `instanceof`?
2. ¿Por qué una propiedad es `readonly`?
3. Si agregamos un tipo de tarea nuevo, ¿qué archivos tocar y cuáles no?
4. Explicar un commit específico: qué cambió y por qué se separó.
5. ¿Qué validación de encapsulamiento pueden demostrar lanzando una excepción en vivo?

---

## 10. Historial de commits relevante

Referencia para explicar el trabajo incremental en la exposición:

| Commit | Descripción |
|---|---|
| `6cd093f` | first commit |
| `ce9d89a` | feat(estado): enum EstadoTarea |
| `053255c` | feat(contratos): interfaz TareaInterface |
| `1808aa6` | feat(tareas): clase abstracta TareaBase |
| `f3857c7` | feat(tareas): TareaSimple |
| `5358519` | refactor(src): reorganizar carpetas por responsabilidad |
| `135228c` | feat(tareas): TareaCompuesta con composición |
| `22440d6` | feat: main.php con demo polimórfica |
| `9e482ca` | Merge PR #1 → dev |

---

## 11. Instrucciones para agentes de IA

### Antes de escribir código

1. Leer este archivo completo.
2. Verificar la rama actual (`dev` o `feature/*`).
3. Confirmar qué piezas ya existen en `src/` antes de crear clases duplicadas.
4. Respetar namespaces y convenciones existentes.

### Al implementar nuevas clases

- **`TareaRecurrente`** (✅ implementada): extiende `TareaBase`. Como `fechaVencimiento` es `readonly` en la base, la fecha vigente vive en `$proximaFecha` (campo propio) y se recorre en `completarCiclo()`.
- **`Tablero`** (✅ implementado): agrupa `TareaInterface[]` por `EstadoTarea` sin condicionales por tipo. Integrado en `main.php`.
- **Exportación JSON** (✅ implementado): `App\Reportes\ExportadorJson`, con `toArray()` agregado a `TareaInterface` para que cada tarea se serialice a sí misma (ver §5). Usa `json_encode`/`json_decode` nativos de PHP.

### Lo que NO debes hacer

- No agregar base de datos, ORM ni frameworks sin que el equipo lo pida.
- No usar `instanceof` para dispatch polimórfico.
- No commitear `vendor/` (está en `.gitignore`).
- No hacer PRs a `main` cuando el flujo del equipo es hacia `dev`.
- No priorizar la GUI web para el primer avance.
- No crear commits a menos que el usuario lo pida explícitamente.

### Verificación antes de entregar cambios

```bash
composer dump-autoload
php main.php
```

La salida debe ejecutarse sin errores. Si se agregó validación nueva, confirmar que la demo de excepción en `main.php` sigue funcionando.

---

## 12. Referencias externas

| Documento | Contenido |
|---|---|
| Propuesta del proyecto (`taskify-propuesta.pdf`) | Caso C, modelo de clases, justificación de pilares POO |
| Rúbrica del primer avance (`primer avance.pdf`) | Secciones de exposición, criterios de evaluación, penalizaciones |
| Notas de continuación (`Taskify-como-continuar.md`) | Estado post-PR, convenciones, tareas pendientes |

---

## 13. Próximos pasos recomendados (orden sugerido)

1. ~~`feature/tablero`~~ — ✅ hecho, desbloqueó el criterio de Polimorfismo (14 pts).
2. ~~`feature/tarea-recurrente`~~ — ✅ hecho, completa el modelo de clases de la propuesta.
3. ~~`feature/exportacion-archivo`~~ — ✅ hecho, cumple manejo de archivos con implementación real (`ExportadorJson` + `toArray()`).
4. ~~Actualizar `main.php` para demostrar Tablero + exportación en la exposición.~~ — ✅ hecho.
5. Abrir PR de `feature/exportacion-archivo` hacia `dev` (pendiente: revisar y mergear).
6. Ensayo de presentación (10 min): GitHub → código → terminal en vivo.
