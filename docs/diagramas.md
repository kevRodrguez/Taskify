# Diagramas — Taskify Fase 2

GitHub renderiza los bloques `mermaid` de este archivo. Versiones editables en Lucid:

- Diagrama entidad-relación: https://lucid.app/lucidchart/f1aba68c-0bc0-4c68-812b-634985441960/edit
- Diagrama de clases: https://lucid.app/lucidchart/158a493d-6bca-4d64-8fe3-6c44347f3373/edit

## Entidad-relación (fuente: `database/schema.sql`)

```mermaid
erDiagram
    tareas ||--o{ subtareas : "padre (compuesta)"
    tareas ||--o{ subtareas : "hija (cualquier tipo)"
    tareas {
        INT_UNSIGNED id PK
        ENUM tipo "simple, compuesta, recurrente"
        VARCHAR_120 titulo
        TEXT descripcion
        DATE fecha_creacion
        DATE fecha_vencimiento
        TINYINT_UNSIGNED avance "NULL en compuesta"
        ENUM periodicidad "solo recurrente"
        VARCHAR_64 imagen "solo el nombre del archivo"
    }
    subtareas {
        INT_UNSIGNED id PK
        INT_UNSIGNED tarea_padre_id FK
        INT_UNSIGNED tarea_hija_id FK
    }
```

## Clases

```mermaid
classDiagram
direction TB
class TareaInterface {
  <<interface>>
  +calcularAvance() float
  +obtenerEstado() EstadoTarea
  +tipoLegible() string
  +aFila() array
  +desdeArray(datos) static
  +camposEspecificos() array
  +detalleEspecifico() array
  +toArray() array
}
class TareaBase {
  <<abstract>>
  -id int
  -titulo string
  -descripcion string
  -imagen string
  -fechaCreacion DateTimeImmutable
  -fechaVencimiento DateTimeImmutable
  +setTitulo(titulo)
  +setImagen(nombre)
  #determinarEstadoPorAvance(avance) EstadoTarea
}
class TareaSimple { <<final>> -avance float +setAvance(avance) }
class TareaCompuesta { <<final>> -subtareas TareaInterface[] +agregarSubtarea(tarea) +getSubtareas() array }
class TareaRecurrente { <<final>> -avance float -proximaFecha DateTimeImmutable +completarCiclo() }
class Periodicidad { <<enum>> Diaria Semanal Mensual }
class EstadoTarea { <<enum>> PENDIENTE EN_PROGRESO COMPLETADA }
class Tablero { +agregarTarea(tarea) +agruparPorEstado() array }
class TareaFactory { +tipos() array +desdeFila(fila) TareaInterface +desdeFormulario(datos) TareaInterface }
class TareaRepositorio { -pdo PDO +crear(tarea) int +listar() array +buscarPorId(id) +actualizar(id, tarea) +eliminar(id) }
class SubtareaRepositorio { -pdo PDO +agregar(padreId, hijaId) +listarPorPadre(padreId) array +quitar(padreId, hijaId) }
class Conexion { -pdo PDO +obtener() PDO }
class Validador { +requerido() +longitudMaxima() +fechaValida() +fechaNoAnterior() +rangoNumerico() +enLista() +esValido() bool +errores() array }
class Csrf { +token() string +verificar(token) bool }
class Flash { +set(clave, valor) +obtener(clave) }
class GestorImagenes { -directorio string +guardar(archivo) string +reemplazar(anterior, archivo) string +eliminar(nombre) +urlPublica(nombre) string }
class ExportadorJson { +exportar(tablero, ruta) +importar(ruta) array }
TareaInterface <|.. TareaBase
TareaBase <|-- TareaSimple
TareaBase <|-- TareaCompuesta
TareaBase <|-- TareaRecurrente
TareaCompuesta o-- TareaInterface : subtareas 0..*
TareaRecurrente --> Periodicidad
TareaBase ..> EstadoTarea
Tablero o-- TareaInterface : 0..*
ExportadorJson ..> Tablero
TareaFactory ..> TareaSimple
TareaFactory ..> TareaCompuesta
TareaFactory ..> TareaRecurrente
TareaRepositorio --> Conexion
SubtareaRepositorio --> Conexion
TareaRepositorio ..> TareaFactory
SubtareaRepositorio ..> TareaRepositorio
Conexion ..> ConexionException
GestorImagenes ..> ImagenException
TareaBase ..> DominioException
```
