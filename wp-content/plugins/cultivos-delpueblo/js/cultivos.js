jQuery(document).ready(function($) {
    // Acceder a las variables pasadas desde PHP
    var ajax_url = culvitos_delpueblo_vars.ajax_url;
    var checkout_url = culvitos_delpueblo_vars.checkout_url;

    // Manejar el cambio de estado del checkbox
    $('.categoria-item input[type="checkbox"]').on('change', function() {
        if ($(this).is(':checked')) {
            $(this).closest('.categoria-item').find('img').removeClass('blanco-y-negro');
        } else {
            $(this).closest('.categoria-item').find('img').addClass('blanco-y-negro');
        }
    });

    // Ejemplo de uso en una solicitud AJAX
    $('#siguiente-productos').on('click', function() {
        console.log("Botón 'siguiente-productos' clickeado");

        var categorias = $('input[name="categorias[]"]:checked').map(function() {
            return this.value;
        }).get();

        console.log("Categorías seleccionadas:", categorias);

        if (categorias.length > 0) {
            $.ajax({
                url: ajax_url, // Usar la variable pasada desde PHP
                type: 'POST',
                data: {
                    action: 'obtener_productos',
                    categorias: categorias
                },
                success: function(response) {
                    console.log("Respuesta del servidor:", response);

                    $('#productos-container')
                        .removeClass('ocultar') // Eliminar la clase "ocultar"
                        .html(response) // Actualizar el contenido
                        .addClass('mostrar'); // Añadir la clase "mostrar"

                    // Ocultar la sección de categorías con transición
                    $('#seleccion-categorias').addClass('ocultar');

                    // Mostrar la sección de productos con transición
                    setTimeout(function() {
                        $('#listado-productos').addClass('mostrar');
                    }, 500); // Esperar 500ms para que la animación de ocultar termine

                    // Invertir el orden de los divs
                    //$('#mi-plugin-woocommerce').addClass('invertir-orden');
                },
                error: function(xhr, status, error) {
                    console.error("Error en la solicitud AJAX:", status, error);
                }
            });
        } else {
            alert('Por favor, selecciona al menos una categoría.');
        }
    });

    // Volver a la selección de categorías
    $('#volver-categorias').on('click', function() {
        // Ocultar la sección de productos con transición
        $('#listado-productos').removeClass('mostrar');
        $('#productos-container').removeClass('mostrar');
        $('#productos-container').addClass('ocultar');
        $('#listado-productos').addClass('ocultar');

        // Mostrar la sección de categorías con transición
        setTimeout(function() {
            $('#seleccion-categorias').removeClass('ocultar');
        }, 500); // Esperar 500ms para que la animación de ocultar termine

        // Desmarcar todos los checkboxes
        $('input[name="categorias[]"]').prop('checked', false);

        // Invertir el orden de los divs
        $('#mi-plugin-woocommerce').removeClass('invertir-orden');
    });

    // Redirigir al formulario de envío
    $('#siguiente-checkout').on('click', function() {
        var cantidades = {};

        // Recorrer todos los inputs de cantidad
        $('input[name^="cantidad"]').each(function() {
            var producto_id = $(this).attr('name').match(/\[(.*?)\]/)[1]; // Extraer el ID del producto
            var cantidad = $(this).val(); // Obtener la cantidad

            if (cantidad > 0) {
                cantidades[producto_id] = cantidad; // Guardar la cantidad si es mayor que 0
            }
        });

        if (Object.keys(cantidades).length > 0) {
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'agregar_al_carrito',
                    cantidades: cantidades
                },
                success: function(response) {
                    window.location.href = checkout_url; // Redirigir al checkout
                }
            });
        } else {
            alert('Por favor, selecciona al menos un producto.');
        }
    });

    // Abrir el popup al hacer clic en el botón "Quick View"
    $(document).on('click', '.quick-view-btn', function() {
        var productId = $(this).data('product-id'); // Obtener el ID del producto
        $('#quick-view-popup').fadeIn(); // Mostrar el popup

        // Cargar la información del producto mediante AJAX
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'obtener_info_producto',
                product_id: productId
            },
            success: function(response) {
                $('#quick-view-product-info').html(response); // Mostrar la información en el popup
            }
        });
    });

    // Cerrar el popup al hacer clic en el botón de cerrar o fuera del popup
    $(document).on('click', '.close-btn, #quick-view-popup', function(e) {
        if (e.target === this) {
            $('#quick-view-popup').fadeOut(); // Ocultar el popup
        }
    });

    // Manejar el evento de clic en el botón cantidad-menos
    $(document).on('click', '.cantidad-menos', function() {
        var input = $(this).next('input[type="number"]');
        var currentValue = parseInt(input.val());
        if (currentValue > 0) {
            input.val(currentValue - 1);
        }
    });

    // Manejar el evento de clic en el botón cantidad-mas
    $(document).on('click', '.cantidad-mas', function() {
        var input = $(this).prev('input[type="number"]');
        var currentValue = parseInt(input.val());
        input.val(currentValue + 1);
    });
});
