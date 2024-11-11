<?php
function my_supplement_theme_setup()
{
    add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'my_supplement_theme_setup');

function my_supplement_enqueue_styles()
{
    wp_enqueue_style('my-supplement-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'my_supplement_enqueue_styles');
function register_collection_post_type()
{
    $labels = array(
        'name' => __('Kollektioner'),
        'singular_name' => __('Kollektion'),
        'menu_name' => __('Kollektioner'),
        'name_admin_bar' => __('Kollektion'),
        'add_new' => __('Lägg till Ny'),
        'add_new_item' => __('Lägg till Ny Kollektion'),
        'new_item' => __('Ny Kollektion'),
        'edit_item' => __('Redigera Kollektion'),
        'view_item' => __('Visa Kollektion'),
        'all_items' => __('Alla Kollektioner'),
        'search_items' => __('Sök Kollektioner'),
        'not_found' => __('Inga Kollektioner hittades'),
        'not_found_in_trash' => __('Inga Kollektioner i papperskorgen'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'kollektioner'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_position' => 20,
        'menu_icon' => 'dashicons-portfolio',
        'show_in_rest' => true,
    );

    register_post_type('kollektion', $args);
}
add_action('init', 'register_collection_post_type');

function register_collection_taxonomy()
{
    $args = array(
        'label' => 'Kollektioner',
        'public' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'kollektioner'),
    );
    register_taxonomy('kollektion', 'product', $args);
}
add_action('init', 'register_collection_taxonomy');
function display_collection_products($atts)
{
    $atts = shortcode_atts(array(
        'kollektion' => '',
    ), $atts, 'collection_products');

    $args = array(
        'post_type' => 'product',
        'tax_query' => array(
            array(
                'taxonomy' => 'kollektion',
                'field' => 'slug',
                'terms' => $atts['kollektion'],
            ),
        ),
    );

    $products = new WP_Query($args);
    $output = '';

    if ($products->have_posts()) {
        $output .= '<ul class="products">';
        while ($products->have_posts()) {
            $products->the_post();
            $output .= '<li>' . get_the_title() . '</li>';
        }
        $output .= '</ul>';

        $output .= '<button class="add-to-cart" onclick="addAllToCart(\'' . esc_js($atts['kollektion']) . '\')">Ta allt till varukorgen</button>';
    } else {
        $output .= 'Inga produkter hittades.';
    }
    wp_reset_postdata();

    return $output;
}
function add_to_cart_script()
{
    ?>
    <script type="text/javascript">
        function addAllToCart(kollektion) {
            jQuery.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: {
                    action: 'add_multiple_products_to_cart',
                    kollektion: kollektion
                },
                success: function (response) {
                    alert('Alla produkter från kollektionen har lagts till i varukorgen!');
                    window.location.href = '/cart';
                },
                error: function () {
                    alert('Ett fel inträffade. Försök igen.');
                }
            });
        }
    </script>
    <?php
}
function add_multiple_products_to_cart()
{
    if (!isset($_POST['kollektion'])) {
        wp_send_json_error('Ingen kollektion angiven.');
        wp_die();
    }

    $kollektion_slug = sanitize_text_field($_POST['kollektion']);

    $args = array(
        'post_type' => 'product',
        'tax_query' => array(
            array(
                'taxonomy' => 'kollektion',
                'field' => 'slug',
                'terms' => $kollektion_slug,
            ),
        ),
    );

    $products = new WP_Query($args);

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            WC()->cart->add_to_cart(get_the_ID());
        }
        wp_send_json_success('Produkter har lagts till i varukorgen.');
    } else {
        wp_send_json_error('Inga produkter hittades i denna kollektion.');
    }

    wp_die();
}
add_action('wp_ajax_add_multiple_products_to_cart', 'add_multiple_products_to_cart');
add_action('wp_ajax_nopriv_add_multiple_products_to_cart', 'add_multiple_products_to_cart');
add_action('wp_footer', 'add_to_cart_script');
add_shortcode('collection_products', 'display_collection_products');

