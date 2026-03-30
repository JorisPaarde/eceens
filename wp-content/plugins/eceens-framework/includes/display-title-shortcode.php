<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'eceens_display_title', 'eceens_display_title_shortcode' );

/**
 * Display title with manual-title override.
 *
 * Usage:
 *   [eceens_display_title]
 *   [eceens_display_title type="faq"]
 *   [eceens_display_title tag="h2" class="my-title"]
 */
function eceens_display_title_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'type'  => '',
        'tag'   => '',
        'class' => '',
    ], $atts, 'eceens_display_title' );

    $post_id   = eceens_current_post_id();
    $post_type = $atts['type'] ?: eceens_current_post_type();

    if ( ! $post_id || ! in_array( $post_type, [ 'faq', 'content' ], true ) ) {
        return '';
    }

    $meta_key     = "{$post_type}_manual_title";
    $manual_title = get_post_meta( $post_id, $meta_key, true );
    $title        = $manual_title !== '' ? $manual_title : get_the_title( $post_id );

    if ( $title === '' ) {
        return '';
    }

    if ( $atts['tag'] ) {
        $tag        = tag_escape( $atts['tag'] );
        $classes    = array_filter( array_map( 'sanitize_html_class', explode( ' ', $atts['class'] ) ) );
        $class_attr = $classes ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';
        return sprintf( '<%1$s%2$s>%3$s</%1$s>', $tag, $class_attr, esc_html( $title ) );
    }

    return esc_html( $title );
}
