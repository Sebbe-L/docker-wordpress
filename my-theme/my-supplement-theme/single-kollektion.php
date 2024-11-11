<?php
get_header();


if (have_posts()):
    while (have_posts()):
        the_post();
        echo '<h1>' . get_the_title() . '</h1>';
        the_content();
    endwhile;
endif;

if (class_exists('WooCommerce')) {
    $kollektion_slug = get_post_field('post_name', get_the_ID());

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
        echo '<h2>Produkter i denna kollektion:</h2>';
        echo '<ul class="kollektion-products">';
        while ($products->have_posts()) {
            $products->the_post();
            global $product;
            echo '<li>';
            echo '<a href="' . get_permalink() . '">';
            if (has_post_thumbnail()) {
                echo get_the_post_thumbnail(get_the_ID(), 'woocommerce_thumbnail');
            }
            echo '<h3>' . get_the_title() . '</h3>';
            echo '</a>';
            echo '<p>' . wc_price($product->get_price()) . '</p>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo 'Inga produkter hittades i denna kollektion.';
    }
    echo '<button class="add-to-cart-all" onclick="addAllToCart(\'' . esc_js($kollektion_slug) . '\')">Lägg till alla produkter i varukorgen</button>';

    wp_reset_postdata();
} else {
    echo 'WooCommerce är inte aktiverat.';
}

get_footer();
?>