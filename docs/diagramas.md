# Diagramas — Taskify Fase 2

Las imágenes se generan a partir de los bloques `mermaid` de este archivo (GitHub también los renderiza).

## Entidad-relación (fuente: `database/schema.sql`)

![Diagrama entidad-relación: tareas y subtareas](diagramas/entidad-relacion.png)

```mermaid
erDiagram
    tareas ||--o{ subtareas : "es padre de"
    tareas ||--o{ subtareas : "es hija en"
    tareas {
        INT_UNSIGNED id PK "AUTO_INCREMENT"
        ENUM tipo "simple, compuesta, recurrente - NOT NULL"
        VARCHAR_120 titulo "NOT NULL, CHECK no vacio"
        TEXT descripcion "NULL"
        DATE fecha_creacion "NOT NULL"
        DATE fecha_vencimiento "NOT NULL, CHECK >= fecha_creacion"
        TINYINT_UNSIGNED avance "NULL en compuesta, CHECK 0 a 100"
        ENUM periodicidad "Diaria, Semanal, Mensual - solo recurrente"
        VARCHAR_64 imagen "NULL, solo el nombre del archivo"
    }
    subtareas {
        INT_UNSIGNED id PK "AUTO_INCREMENT"
        INT_UNSIGNED tarea_padre_id FK "NOT NULL, ON DELETE CASCADE"
        INT_UNSIGNED tarea_hija_id FK "NOT NULL, ON DELETE CASCADE"
    }
```

## Clases

![Diagrama de clases de Taskify](diagramas/clases.png)

```mermaid
classDiagram
direction TB

class TareaInterface {
  <<interface>>
  +calcularAvance() float
  +obtenerEstado() EstadoTarea
  +tipoLegible() string
  +aFila() array
  +desdeArray(datos)$ static
  +camposEspecificos()$ array
  +detalleEspecifico() array
  +admiteSubtareas() bool
  +agregarSubtarea(subtarea) void
  +toArray() array
}

class TareaBase {
  <<abstract>>
  -int id
  -string titulo
  -string descripcion
  -string imagen
  -DateTimeImmutable fechaCreacion
  -DateTimeImmutable fechaVencimiento
  +setTitulo(titulo) void
  +setImagen(imagen) void
  +etiquetaTipo()* string
  #determinarEstadoPorAvance(avance) EstadoTarea
  #validarAvance(avance)$ float
}

class TareaSimple {
  <<final>>
  -float avance
  +setAvance(avance) void
}

class TareaCompuesta {
  <<final>>
  -TareaInterface[] subtareas
  +agregarSubtarea(subtarea) void
  +getSubtareas() array
}

class TareaRecurrente {
  <<final>>
  -float avance
  -DateTimeImmutable proximaFecha
  -Periodicidad periodicidad
  +completarCiclo() void
}

class Periodicidad {
  <<enumeration>>
  DIARIA
  SEMANAL
  MENSUAL
}

class EstadoTarea {
  <<enumeration>>
  PENDIENTE
  EN_PROGRESO
  COMPLETADA
}

class Tablero {
  +agregarTarea(tarea) void
  +obtenerTareas() array
  +agruparPorEstado() array
}

class ExportadorJson {
  +exportar(tablero, ruta) void
  +importar(ruta) array
}

class TareaFactory {
  <<fabrica>>
  -CLASES array
  +tipos()$ array
  +existe(tipo)$ bool
  +camposDe(tipo)$ array
  +desdeFila(fila)$ TareaInterface
  +desdeFormulario(datos)$ TareaInterface
}

class Conexion {
  -PDO pdo$
  +obtener()$ PDO
}

class TareaRepositorio {
  -PDO pdo
  +crear(tarea) int
  +listar() array
  +buscarPorId(id) TareaInterface
  +actualizar(id, tarea) void
  +eliminar(id) void
}

class SubtareaRepositorio {
  -PDO pdo
  +agregar(padreId, hijaId) void
  +listarPorPadre(padreId) array
  +listarCandidatas(padreId) array
  +buscarTarea(id) TareaInterface
  +quitar(padreId, hijaId) void
}

class ReglasSubtarea {
  +exigirPadreAdmiteSubtareas(padre)$ void
  +exigirPadreDistintoDeHija(padreId, hijaId)$ void
}

class Validador {
  -array errores
  +requerido(campo, valor) Validador
  +longitudMaxima(campo, valor, max) Validador
  +fechaValida(campo, valor) Validador
  +fechaNoAnterior(campo, valor, minima) Validador
  +rangoNumerico(campo, valor, min, max) Validador
  +enLista(campo, valor, permitidos) Validador
  +esValido() bool
  +errores() array
}

class ReglasFormularioTarea {
  +validar(datos)$ Validador
}

class ReglasFormularioSubtarea {
  +validar(datos)$ Validador
}

class Csrf {
  +token()$ string
  +verificar(token)$ bool
}

class Flash {
  +set(clave, valor)$ void
  +obtener(clave)$ mixed
}

class GestorImagenes {
  <<servicio>>
  -string directorio
  +guardar(archivo) string
  +reemplazar(anterior, archivo) string
  +eliminar(nombre) void
  +existe(nombre) bool
  +urlPublica(nombre) string
}

class DominioException
class ConexionException
class ImagenException

TareaInterface <|.. TareaBase
TareaBase <|-- TareaSimple
TareaBase <|-- TareaCompuesta
TareaBase <|-- TareaRecurrente
TareaCompuesta o-- "0..*" TareaInterface : subtareas
TareaRecurrente --> Periodicidad
TareaInterface ..> EstadoTarea
TareaBase ..> DominioException
Tablero o-- "0..*" TareaInterface
ExportadorJson ..> Tablero
TareaFactory ..> TareaSimple : crea
TareaFactory ..> TareaCompuesta : crea
TareaFactory ..> TareaRecurrente : crea
TareaRepositorio ..> TareaFactory
TareaRepositorio --> SubtareaRepositorio
SubtareaRepositorio ..> TareaFactory
SubtareaRepositorio ..> ReglasSubtarea
TareaRepositorio ..> Conexion : PDO inyectado
SubtareaRepositorio ..> Conexion : PDO inyectado
Conexion ..> ConexionException
ReglasFormularioTarea ..> Validador
ReglasFormularioTarea ..> TareaFactory
ReglasFormularioSubtarea ..> Validador
GestorImagenes ..> ImagenException
```
