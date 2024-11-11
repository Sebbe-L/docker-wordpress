<?php
/*
Plugin Name: My Collection Plugin
Description: Allows users to create collections of products.
Version: 1.0
Author: Sebbe
*/


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
        'show_in_rest' => true
    );

    register_post_type('kollektion', $args);
}
add_action('init', 'register_collection_post_type');