<?php get_header(); ?>

<?php
if (have_posts()):
    while (have_posts()):
        the_post();
        the_content();
    endwhile;
else:
    echo '<p>Inga produkter hittades</p>';
endif;

?>
<?php
$args = array(
    'post_type' => 'kollektion',
    'posts_per_page' => 10,
);
$kollektioner = new WP_Query($args);
if ($kollektioner->have_posts()) {
    echo '<ul>';
    while ($kollektioner->have_posts()) {
        $kollektioner->the_post();
        echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
    }
    echo '</ul>';
} else {
    echo 'Inga kollektioner funna.';
}
wp_reset_postdata();
?>

<?php get_footer(); ?>