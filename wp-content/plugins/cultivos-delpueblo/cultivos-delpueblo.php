<?php
/*
Plugin Name: Cultivos DelPueblo
Description: Plugin creado por Apadrinaunolivo para adaptar el proceso de compra al modelo negocio de somosdelpueblo.com
Version: 1.0
Author: Guillermo Cano
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function mostrar_categorias_productos() {
    ob_start();
    ?>
    <div id="mi-plugin-woocommerce">
         <div id="seleccion-categorias">
            <form id="categorias-form">
                <?php
                $categorias = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                ));

                foreach ($categorias as $categoria) {
                    $imagen_id = get_term_meta($categoria->term_id, 'thumbnail_id', true);
                    $imagen_url = wp_get_attachment_url($imagen_id);
                    ?>
                    <div class="categoria-item">
                        <input type="checkbox" name="categorias[]" value="<?php echo $categoria->term_id; ?>" id="categoria-<?php echo $categoria->term_id; ?>">
                        <label for="categoria-<?php echo $categoria->term_id; ?>">
                            <?php if ($imagen_url) : ?>
                                <img src="<?php echo $imagen_url; ?>" alt="<?php echo $categoria->name; ?>">
                            <?php endif; ?>
                            <span><?php echo $categoria->name; ?></span>
                        </label>
                    </div>
                    <?php
                }
                ?>
            </form>
            <button type="button" id="siguiente-productos">Siguiente</button>
        </div>
        
        <!-- Segunda sección: Listado de productos -->
        <div id="listado-productos">
            <div id="productos-container"></div>
            <button type="button" id="volver-categorias">Volver</button>
            <button type="button" id="siguiente-checkout">Siguiente</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('mostrar_categorias', 'mostrar_categorias_productos');



function obtener_productos_por_categorias() {
    if (isset($_POST['categorias'])) {
        $categorias = array_map('intval', $_POST['categorias']);
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $categorias,
                ),
            ),
        );

        $productos = new WP_Query($args);


        if ($productos->have_posts()) {
            while ($productos->have_posts()) {
                $productos->the_post();
                $producto = wc_get_product(get_the_ID());


                // Obtener todos los meta fields del producto
                $all_meta = get_post_meta(get_the_ID());
                error_log(print_r($all_meta, true)); // Depuración: Ver todos los meta fields

                // Obtener el timestamp de la fecha de preorden
                $timestamp = get_post_meta(get_the_ID(), '_wpro_date', true);

                error_log("Timestamp: " . $timestamp);

                // Convertir el timestamp a una fecha legible
                $fecha_legible = $timestamp ? date('d/m/Y', $timestamp) : 'No disponible';

                error_log("Fecha fecha_legible: " . $fecha_legible);

                // Obtener el meta _wpro_date_label
                $wpro_date_label = get_post_meta(get_the_ID(), '_wpro_date_label', true);

                error_log("DateLabel: " . $wpro_date_label);

                // Reemplazar {availability_date} por $fecha_legible
                if ($wpro_date_label) {
                    $wpro_date_label = str_replace('{availability_date}', $fecha_legible, $wpro_date_label);
                } else {
                    $wpro_date_label = 'Disponible a partir del ' . $fecha_legible; // Mensaje por defecto
                }          

                ?>
                <div class="producto-item">
                    <img src="<?php echo wp_get_attachment_url($producto->get_image_id()); ?>" alt="<?php echo get_the_title(); ?>">
                    <h3><?php echo get_the_title(); ?></h3>
                    <p><?php echo $producto->get_short_description(); ?></p>
                    <input type="number" name="cantidad[<?php echo get_the_ID(); ?>]" min="0" value="0">
                    <?php if ($wpro_date_label): ?>
                        <strong><p class="wpro-date-label"><?php echo esc_html($wpro_date_label); ?></p></strong>
                    <?php endif; ?>
                    <!-- Mostrar la fecha de preorden -->
                </div>
                <?php
            }
        } else {
            echo '<p>No se encontraron productos.</p>';
        }

        wp_die();
    }
}

add_action('wp_ajax_obtener_productos', 'obtener_productos_por_categorias');
add_action('wp_ajax_nopriv_obtener_productos', 'obtener_productos_por_categorias');



function culvitos_delpueblo_scripts() {
    // Registrar el script
    wp_register_script(
        'culvitos-delpueblo-script', // Handle único para el script
        plugins_url('js/cultivos.js', __FILE__), // Ruta al archivo JS
        array('jquery'), // Dependencias (jQuery en este caso)
        '1.0', // Versión del script
        true // Cargar en el footer (true) o en el header (false)
    );

    // Encolar el script
    wp_enqueue_script('culvitos-delpueblo-script');

    error_log(admin_url('admin-ajax.php'));


    // Pasar variables de PHP a JavaScript (opcional)
    wp_localize_script(
        'culvitos-delpueblo-script', // Handle del script
        'culvitos_delpueblo_vars', // Nombre del objeto JavaScript
        array(
            'ajax_url' => admin_url('admin-ajax.php'), // URL para AJAX
            'checkout_url' => wc_get_checkout_url(), // URL del checkout
        )
    );

    wp_enqueue_script('culvitos-delpueblo-script');
}

add_action('wp_enqueue_scripts', 'culvitos_delpueblo_scripts');


function culvitos_delpueblo_styles() {
    // Registrar el archivo CSS
    wp_register_style(
        'culvitos-delpueblo-styles', // Handle único para el CSS
        plugins_url('css/styles.css', __FILE__), // Ruta al archivo CSS
        array(), // Dependencias (ninguna en este caso)
        '1.0', // Versión del archivo CSS
        'all' // Medios a los que se aplica (all, screen, print, etc.)
    );

    // Encolar el archivo CSS
    wp_enqueue_style('culvitos-delpueblo-styles');
}

add_action('wp_enqueue_scripts', 'culvitos_delpueblo_styles');


function agregar_al_carrito() {
    if (isset($_POST['cantidades'])) {
        foreach ($_POST['cantidades'] as $producto_id => $cantidad) {
            if ($cantidad > 0) {
                WC()->cart->add_to_cart($producto_id, $cantidad);
            }
        }
        wp_die();
    }
}

add_action('wp_ajax_agregar_al_carrito', 'agregar_al_carrito');
add_action('wp_ajax_nopriv_agregar_al_carrito', 'agregar_al_carrito');