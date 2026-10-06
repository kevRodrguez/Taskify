/*
 * Vista previa de la imagen elegida en views/partials/campo_imagen.php.
 *
 * Solo es apoyo para el usuario: avisa antes de enviar si el archivo no es
 * JPG/PNG/WEBP o pasa de 2 MB. El servidor (GestorImagenes) vuelve a validar
 * todo, así que con novalidate o sin JavaScript la seguridad no cambia.
 */
(function () {
    'use strict';

    var TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp'];

    function iniciar(input) {
        var vista = document.getElementById(input.dataset.vistaPrevia);
        if (!vista) {
            return;
        }

        var tamanoMaximo = Number(input.dataset.tamanoMaximo) || 0;
        var srcInicial = vista.getAttribute('src');
        var urlActual = null;

        input.addEventListener('change', function () {
            if (urlActual) {
                URL.revokeObjectURL(urlActual);
                urlActual = null;
            }
            input.setCustomValidity('');
            vista.hidden = true;
            vista.src = srcInicial;

            var archivo = input.files && input.files[0];
            if (!archivo) {
                return;
            }

            if (TIPOS_PERMITIDOS.indexOf(archivo.type) === -1) {
                input.setCustomValidity('Solo se permiten imágenes JPG, PNG o WEBP.');
                input.reportValidity();
                return;
            }

            if (tamanoMaximo > 0 && archivo.size > tamanoMaximo) {
                input.setCustomValidity('La imagen supera el tamaño máximo de 2 MB.');
                input.reportValidity();
                return;
            }

            urlActual = URL.createObjectURL(archivo);
            vista.src = urlActual;
            vista.hidden = false;
        });
    }

    document.querySelectorAll('input[type="file"][data-vista-previa]').forEach(iniciar);
})();
