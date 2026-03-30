<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'the_title', 'eceens_manual_title_override', 10, 2 );

/**
 * Override frontend post title with manual title when available.
 *
 * Applies to FAQ and Content posts so Elementor "Post Title" widgets
 * (including accordions) render the manual title automatically.
 */
function eceens_manual_title_override( $title, $post_id ) {
    if ( is_admin() ) {
        return $title;
    }

    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, [ 'faq', 'content' ], true ) ) {
        return $title;
    }

    $meta_key     = "{$post_type}_manual_title";
    $manual_title = get_post_meta( $post_id, $meta_key, true );
    if ( $manual_title === '' ) {
        return $title;
    }

    return $manual_title;
}
