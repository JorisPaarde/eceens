<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'eceens_register_post_types' );
add_action( 'init', 'eceens_register_taxonomies' );

function eceens_register_post_types() {

    /* ── FAQ ─────────────────────────────────────────────── */
    register_post_type( 'faq', [
        'labels' => [
            'name'               => 'FAQ',
            'singular_name'      => 'FAQ',
            'add_new'            => 'Nieuwe FAQ',
            'add_new_item'       => 'Nieuwe FAQ toevoegen',
            'edit_item'          => 'FAQ bewerken',
            'view_item'          => 'FAQ bekijken',
            'all_items'          => 'Alle FAQ\'s',
            'search_items'       => 'FAQ zoeken',
            'not_found'          => 'Geen FAQ gevonden',
            'not_found_in_trash' => 'Geen FAQ in prullenbak',
        ],
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-editor-help',
        'supports'     => [ 'title', 'editor', 'thumbnail', 'revisions' ],
        'rewrite'      => [ 'slug' => 'faq' ],
    ]);

    /* ── Content ─────────────────────────────────────────── */
    register_post_type( 'content', [
        'labels' => [
            'name'               => 'Content',
            'singular_name'      => 'Content',
            'add_new'            => 'Nieuwe Content',
            'add_new_item'       => 'Nieuwe Content toevoegen',
            'edit_item'          => 'Content bewerken',
            'view_item'          => 'Content bekijken',
            'all_items'          => 'Alle Content',
            'search_items'       => 'Content zoeken',
            'not_found'          => 'Geen Content gevonden',
            'not_found_in_trash' => 'Geen Content in prullenbak',
        ],
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-media-document',
        'supports'     => [ 'title', 'editor', 'thumbnail', 'revisions' ],
        'rewrite'      => [ 'slug' => 'content' ],
    ]);
}

function eceens_register_taxonomies() {

    /* ── FAQ Categorie (hierarchical with sub-categories) ── */
    register_taxonomy( 'faq_categorie', 'faq', [
        'labels' => [
            'name'          => 'FAQ Categorieën',
            'singular_name' => 'FAQ Categorie',
            'add_new_item'  => 'Nieuwe FAQ Categorie',
            'edit_item'     => 'FAQ Categorie bewerken',
            'search_items'  => 'FAQ Categorie zoeken',
            'all_items'     => 'Alle FAQ Categorieën',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'faq-categorie' ],
    ]);

    /* ── Content Categorie (hierarchical with sub-categories) */
    register_taxonomy( 'content_categorie', 'content', [
        'labels' => [
            'name'          => 'Content Categorieën',
            'singular_name' => 'Content Categorie',
            'add_new_item'  => 'Nieuwe Content Categorie',
            'edit_item'     => 'Content Categorie bewerken',
            'search_items'  => 'Content Categorie zoeken',
            'all_items'     => 'Alle Content Categorieën',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'content-categorie' ],
    ]);
}
