<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'eceens_faq_category_pills', 'eceens_faq_category_pills_shortcode' );

function eceens_faq_category_pills_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'link' => '',
    ], $atts, 'eceens_faq_category_pills' );

    $terms = get_terms( [
        'taxonomy'   => 'faq_categorie',
        'hide_empty' => true,
    ]);

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    $out = '<div class="eceens-faq-pills">';

    foreach ( $terms as $term ) {
        $color = get_term_meta( $term->term_id, 'eceens_term_color', true );
        if ( ! $color ) {
            $color = '#2A50FF';
        }

        $text_color = eceens_contrast_color( $color );

        if ( $atts['link'] === 'anchor' ) {
            $href = '#faq-cat-' . $term->term_id;
        } else {
            $href = get_term_link( $term );
        }

        $out .= sprintf(
            '<a class="eceens-faq-pill" href="%s" style="background:%s;color:%s;">%s</a>',
            esc_url( $href ),
            esc_attr( $color ),
            esc_attr( $text_color ),
            esc_html( $term->name )
        );
    }

    $out .= '</div>';

    if ( ! wp_style_is( 'eceens-faq-pills-inline', 'done' ) ) {
        $out .= '<style>
.eceens-faq-pills{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0}
.eceens-faq-pill{display:inline-block;padding:6px 16px;border-radius:20px;text-decoration:none;font-size:14px;font-weight:500;line-height:1.4;transition:opacity .2s}
.eceens-faq-pill:hover{opacity:.85}
</style>';
        wp_add_inline_style( 'wp-block-library', '' );
    }

    return $out;
}

/**
 * Return white or dark text depending on background luminance.
 */
function eceens_contrast_color( $hex ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );
    $luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
    return $luminance > 0.55 ? '#222222' : '#ffffff';
}
