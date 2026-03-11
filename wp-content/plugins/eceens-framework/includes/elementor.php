<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'elementor/query/eceens_faq_featured', 'eceens_elementor_faq_featured' );
add_action( 'elementor/query/eceens_content_featured', 'eceens_elementor_content_featured' );
add_action( 'elementor/query/eceens_homepage_faq_featured', 'eceens_elementor_homepage_faq_featured' );
add_action( 'elementor/query/eceens_homepage_content_featured', 'eceens_elementor_homepage_content_featured' );
add_action( 'elementor/query/eceens_faq_current_category', 'eceens_elementor_faq_current_category' );
add_action( 'elementor/query/eceens_content_current_category', 'eceens_elementor_content_current_category' );

function eceens_elementor_faq_featured( $query ) {
    $query->set( 'post_type', 'faq' );
    $query->set( 'posts_per_page', 6 );
    $query->set( 'meta_query', [
        [
            'key'   => 'faq_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'faq_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_content_featured( $query ) {
    $query->set( 'post_type', 'content' );
    $query->set( 'posts_per_page', 6 );
    $query->set( 'meta_query', [
        [
            'key'   => 'content_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'content_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_homepage_faq_featured( $query ) {
    $query->set( 'post_type', 'faq' );
    $query->set( 'posts_per_page', 3 );
    $query->set( 'meta_query', [
        [
            'key'   => 'faq_homepage_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'faq_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_homepage_content_featured( $query ) {
    $query->set( 'post_type', 'content' );
    $query->set( 'posts_per_page', 3 );
    $query->set( 'meta_query', [
        [
            'key'   => 'content_homepage_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'content_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_faq_current_category( $query ) {
    $term = get_queried_object();
    if ( ! $term || ! isset( $term->taxonomy ) || $term->taxonomy !== 'faq_categorie' ) {
        return;
    }
    $query->set( 'post_type', 'faq' );
    $query->set( 'tax_query', [
        [
            'taxonomy' => 'faq_categorie',
            'field'    => 'term_id',
            'terms'    => [ $term->term_id ],
        ],
    ]);
    $query->set( 'meta_key', 'faq_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_content_current_category( $query ) {
    $term = get_queried_object();
    if ( ! $term || ! isset( $term->taxonomy ) || $term->taxonomy !== 'content_categorie' ) {
        return;
    }
    $query->set( 'post_type', 'content' );
    $query->set( 'tax_query', [
        [
            'taxonomy' => 'content_categorie',
            'field'    => 'term_id',
            'terms'    => [ $term->term_id ],
        ],
    ]);
    $query->set( 'meta_key', 'content_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}
