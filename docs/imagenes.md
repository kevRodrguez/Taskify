# Imágenes de tareas (evidencia)

Servicio: `App\Services\GestorImagenes` · Excepción: `App\Exceptions\ImagenException`
Partials: `views/partials/imagen.php`, `views/partials/campo_imagen.php` · JS de apoyo: `public/js/preview.js`

## API del servicio

| Método | Qué hace |
|---|---|
| `__construct(string $directorio)` | Recibe el directorio de cargas (`public/uploads` en la app, uno temporal en las pruebas). `[INYECCION-DEPENDENCIAS]` |
| `guardar(array $archivo): string` | Valida un elemento de `$_FILES`, lo mueve con `move_uploaded_file()` y devuelve solo el nombre generado. |
| `reemplazar(?string $anterior, array $archivo): string` | Guarda la nueva imagen y borra la anterior **solo si** la nueva se guardó bien. |
| `eliminar(?string $nombre): void` | Borra la imagen; ignora `null`, nombres con `../` y archivos que no generó el servicio. |
| `existe(?string $nombre): bool` | `true` si la imagen está guardada en disco. |
| `urlPublica(?string $nombre): string` | `/uploads/<nombre>` o `/img/sin-imagen.svg` si no hay imagen o el archivo ya no existe. |

Todos los errores salen como `ImagenException` con un mensaje en español listo para mostrarse junto al campo `imagen` (nunca incluye rutas del servidor).

## Validaciones en `guardar()` (en este orden)

| # | Comprobación | Mensaje |
|---|---|---|
| 1 | Un solo archivo (no `imagen[]`) | Solo se permite subir una imagen a la vez. |
| 2 | `error === UPLOAD_ERR_OK`, con un mensaje distinto por código (`INI_SIZE`/`FORM_SIZE`, `PARTIAL`, `NO_FILE`, `NO_TMP_DIR`, `CANT_WRITE`, `EXTENSION`) | p. ej. La imagen supera el tamaño máximo de 2 MB. |
| 3 | Tamaño real en disco (`filesize`, no `$archivo['size']`) > 0 | El archivo de imagen está vacío. |
| 4 | Tamaño ≤ 2 MB | La imagen supera el tamaño máximo de 2 MB. |
| 5 | Tipo **real** con `finfo` ∈ JPG/PNG/WEBP (la extensión y `$archivo['type']` se ignoran) | Solo se permiten imágenes JPG, PNG o WEBP. |
| 6 | `getimagesize()` reconoce el mismo tipo y dimensiones > 0 | El archivo no es una imagen válida o está dañado. |
| 7 | El directorio existe y es escribible | No se pudo guardar la imagen en el servidor. |

El nombre final es `bin2hex(random_bytes(16))` + extensión derivada del tipo real (`.jpg`, `.png`, `.webp`): el nombre que envía el usuario se descarta.

## Uso en las páginas

```php
$gestor = new GestorImagenes(dirname(__DIR__) . '/uploads');

// Crear
$tarea->setImagen($gestor->guardar($_FILES['imagen']));

// Actualizar
$tarea->setImagen($gestor->reemplazar($original->getImagen(), $_FILES['imagen']));

// Eliminar la tarea
$gestor->eliminar($tarea->getImagen());
```

En las vistas:

```php
// Miniatura en tablas
$imagenNombre = $tarea->getImagen();
$imagenTitulo = $tarea->getTitulo();
$imagenClase = 'miniatura';
$imagenTamano = 48;
require $raiz . '/views/partials/imagen.php';

// Campo de carga en el formulario (requiere enctype="multipart/form-data")
$imagenActual = $tarea->getImagen(); // null al crear
$imagenTitulo = $tarea->getTitulo();
require $raiz . '/views/partials/campo_imagen.php';
```

`campo_imagen.php` incluye el `MAX_FILE_SIZE` oculto, `accept="image/jpeg,image/png,image/webp"`, label `for`/`id`, la imagen actual al editar, el error por campo y la vista previa (`preview.js`). La vista previa también avisa en el navegador si el archivo no es una imagen permitida o pasa de 2 MB, pero es solo apoyo: con `novalidate` o sin JavaScript el servidor vuelve a validar todo.

## Configuración de PHP

Los valores por defecto de PHP sirven: `upload_max_filesize = 2M` y `post_max_size = 8M`. Si `upload_max_filesize` es menor que 2M, PHP corta la carga antes y el usuario ve el mensaje de `UPLOAD_ERR_INI_SIZE`. Si una petición supera `post_max_size`, PHP descarta todo el POST (incluido el token CSRF) y la página responde "La solicitud no es válida".

## Pruebas

```bash
php tests/gestor_imagenes_test.php
```

Cubre, entre otros: archivo de 3 MB, `.php` renombrado a `.jpg`, archivo vacío, archivo con cabecera PNG falsa y código PHP, imagen válida (PNG, JPG y WEBP), reemplazo fallido y correcto, borrado y `urlPublica()` con path traversal. La segunda parte levanta `php -S` con `tests/servidor_imagenes.php` y sube archivos por HTTP POST real, porque `move_uploaded_file()` rechaza archivos que no llegaron por una carga. Todo ocurre en un directorio temporal; `public/uploads` no se toca.

### Prueba manual en la app

1. `php -S localhost:8000 -t public` y abrir **Nueva tarea**.
2. Subir un archivo de más de 2 MB → error junto al campo, la tarea no se crea.
3. Renombrar un `.php` a `.jpg` y subirlo → "Solo se permiten imágenes JPG, PNG o WEBP."
4. Subir un archivo vacío → "El archivo de imagen está vacío."
5. Subir una imagen válida → la tarea se crea y la miniatura aparece en el listado.
6. Editar la tarea con otra imagen → en `public/uploads/` queda solo la nueva.
7. Eliminar la tarea → su archivo desaparece de `public/uploads/`.
8. `git status` → ningún archivo de `public/uploads/` aparece (solo se versiona `.gitkeep`).
