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
function create_collection_form_shortcode()
{
    if (!is_user_logged_in()) {
        return '<p>Du måste vara inloggad för att skapa en kollektion.</p>';
    }

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    $products = get_posts($args);

    ob_start();
    ?>
    <form id="create-collection-form" method="post">
        <p><label for="collection-title">Kollektionstitel</label><br>
            <input type="text" name="collection_title" id="collection-title" required>
        </p>

        <p><label for="collection-description">Beskrivning</label><br>
            <textarea name="collection_description" id="collection-description" rows="4"></textarea>
        </p>

        <p><label for="collection-products">Välj produkter att lägga till i kollektionen:</label><br>
            <select name="collection_products[]" id="collection-products" multiple size="6">
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product->ID; ?>"><?php echo $product->post_title; ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p><input type="submit" name="create_collection" value="Skapa Kollektion"></p>
    </form>
    <?php

    if (isset($_POST['create_collection'])) {
        $title = sanitize_text_field($_POST['collection_title']);
        $description = sanitize_textarea_field($_POST['collection_description']);
        $selected_products = array_map('intval', $_POST['collection_products']);
        $user_id = get_current_user_id();


        $collection_id = wp_insert_post(array(
            'post_type' => 'kollektion',
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ));

        if ($collection_id) {
            $term = wp_insert_term(
                $title,
                'kollektion',
                array(
                    'slug' => sanitize_title($title)
                )
            );

            if (!is_wp_error($term)) {
                $term_id = $term['term_id'];

                if (!empty($selected_products)) {
                    foreach ($selected_products as $product_id) {
                        wp_set_object_terms($product_id, $term_id, 'kollektion', true);
                    }
                }
            } else {
                echo '<p>Ett fel inträffade vid skapandet av kollektionens taxonomiterm.</p>';
            }

            echo '<p>Kollektionen har skapats och produkter har lagts till.</p>';
        } else {
            echo '<p>Ett fel inträffade. Försök igen.</p>';
        }
    }

    return ob_get_clean();
}
function create_custom_user_role()
{

    add_role(
        'collection_creator',
        'Kollektion Skapare',
        array(
            'read' => true,
            'edit_kollektion' => true,
            'edit_posts' => false,
            'edit_others_posts' => false,
            'publish_posts' => false,
            'delete_posts' => false,
        )
    );
}
function restrict_admin_access()
{
    if (current_user_can('collection_creator') && is_admin()) {
        wp_redirect(home_url());
        exit;
    }
}

function display_filtered_collections($atts)
{
    $atts = shortcode_atts(array(
        'year' => isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '',
        'month' => isset($_GET['month']) ? sanitize_text_field($_GET['month']) : '',
        'kollektion_kategori' => isset($_GET['kollektion_kategori']) ? sanitize_text_field($_GET['kollektion_kategori']) : '',
    ), $atts, 'filtered_collections');

    $args = array(
        'post_type' => 'kollektion',
        'posts_per_page' => -1,
    );

    if (!empty($atts['year']) || !empty($atts['month'])) {
        $args['date_query'] = array(
            array(
                'year' => $atts['year'],
                'month' => $atts['month'],
            ),
        );
    }

    if (!empty($atts['kollektion_kategori'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'kollektion',
                'field' => 'slug',
                'terms' => $atts['kollektion_kategori'],
            ),
        );
    }

    $collections = new WP_Query($args);
    $output = '<ul>';

    if ($collections->have_posts()) {
        while ($collections->have_posts()) {
            $collections->the_post();
            $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
    } else {
        $output .= '<li>Inga kollektioner hittades.</li>';
    }

    $output .= '</ul>';
    wp_reset_postdata();

    return $output;
}
function collection_filter_form()
{
    ob_start();
    ?>
    <form method="GET" action="">
        <label for="year">År:</label>
        <select name="year" id="year">
            <option value="">Alla</option>
            <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
                <option value="<?php echo $i; ?>" <?php selected(isset($_GET['year']) ? $_GET['year'] : '', $i); ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>

        <label for="month">Månad:</label>
        <select name="month" id="month">
            <option value="">Alla</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php selected(isset($_GET['month']) ? $_GET['month'] : '', $m); ?>>
                    <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                </option>
            <?php endfor; ?>
        </select>


        <button type="submit">Filtrera</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('collection_filter_form', 'collection_filter_form');
add_shortcode('filtered_collections', 'display_filtered_collections');
add_action('init', 'restrict_admin_access');
add_action('init', 'create_custom_user_role');
add_shortcode('create_collection_form', 'create_collection_form_shortcode');

add_action('wp_ajax_add_multiple_products_to_cart', 'add_multiple_products_to_cart');
add_action('wp_ajax_nopriv_add_multiple_products_to_cart', 'add_multiple_products_to_cart');
add_action('wp_footer', 'add_to_cart_script');
add_shortcode('collection_products', 'display_collection_products');

