<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'elementor/query/eceens_faq_featured', 'eceens_elementor_faq_featured' );
add_action( 'elementor/query/eceens_content_featured', 'eceens_elementor_content_featured' );

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
