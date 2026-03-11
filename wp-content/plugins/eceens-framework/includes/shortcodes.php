<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'eceens_faq_category_pills', 'eceens_faq_category_pills_shortcode' );
add_shortcode( 'eceens_post_category_pills', 'eceens_post_category_pills_shortcode' );
add_shortcode( 'eceens_category_pills', 'eceens_category_pills_shortcode' );

/**
 * Capture the real post ID/type each time WordPress sets up a loop post.
 * Elementor's Loop Grid calls the_post() for each item BEFORE it renders
 * the template, which overrides global $post with the template. This hook
 * fires first, so we store the real post data here.
 */
add_action( 'the_post', function ( $post ) {
    if ( in_array( $post->post_type, [ 'faq', 'content' ], true ) ) {
        $GLOBALS['eceens_loop_post_id']   = $post->ID;
        $GLOBALS['eceens_loop_post_type'] = $post->post_type;
    }
} );

/**
 * Get the current post ID, compatible with Elementor Loop Items.
 */
function eceens_current_post_id() {
    $post_id   = get_the_ID();
    $post_type = get_post_type( $post_id );

    if ( in_array( $post_type, [ 'faq', 'content' ], true ) ) {
        return $post_id;
    }

    if ( ! empty( $GLOBALS['eceens_loop_post_id'] ) ) {
        return $GLOBALS['eceens_loop_post_id'];
    }

    return $post_id;
}

/**
 * Get the current post type, compatible with Elementor Loop Items.
 */
function eceens_current_post_type() {
    $post_id   = get_the_ID();
    $post_type = get_post_type( $post_id );

    if ( in_array( $post_type, [ 'faq', 'content' ], true ) ) {
        return $post_type;
    }

    if ( ! empty( $GLOBALS['eceens_loop_post_type'] ) ) {
        return $GLOBALS['eceens_loop_post_type'];
    }

    return $post_type;
}

/**
 * Render a single pill element.
 */
function eceens_render_pill( $term, $link_mode, $extra_style = '' ) {
    $color      = eceens_get_term_color( $term );
    $text_color = eceens_contrast_color( $color );
    $style      = sprintf( 'background:%s;color:%s', esc_attr( $color ), esc_attr( $text_color ) );
    if ( $extra_style ) {
        $style .= ';' . $extra_style;
    }

    if ( $link_mode === 'none' ) {
        return sprintf(
            '<span class="eceens-faq-pill" style="%s">%s</span>',
            $style,
            esc_html( $term->name )
        );
    }

    $href = $link_mode === 'anchor'
        ? '#faq-cat-' . $term->term_id
        : get_term_link( $term );

    return sprintf(
        '<a class="eceens-faq-pill" href="%s" style="%s">%s</a>',
        esc_url( $href ),
        $style,
        esc_html( $term->name )
    );
}

/**
 * All FAQ categories (for overview pages).
 */
function eceens_faq_category_pills_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'link' => '',
    ], $atts, 'eceens_faq_category_pills' );

    $terms = get_terms( [
        'taxonomy'   => 'faq_categorie',
        'hide_empty' => true,
        'parent'     => 0,
    ]);

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    $out = '<div class="eceens-faq-pills">';

    foreach ( $terms as $term ) {
        $out .= eceens_render_pill( $term, $atts['link'] );
    }

    $out .= '</div>';
    eceens_enqueue_pill_css();
    return $out;
}

/**
 * Per-post category pills for Elementor Loop Items.
 *
 * Attributes:
 *   level = "parent" (default) | "child" | "all"
 *   link  = "" (archive, default) | "anchor" | "none"
 *   type  = "" (auto) | "faq" | "content"
 */
function eceens_post_category_pills_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'level' => 'parent',
        'link'  => '',
        'type'  => '',
    ], $atts, 'eceens_post_category_pills' );

    $post_id   = eceens_current_post_id();
    $post_type = $atts['type'] ?: eceens_current_post_type();

    $taxonomy_map = [
        'faq'     => 'faq_categorie',
        'content' => 'content_categorie',
    ];

    if ( ! isset( $taxonomy_map[ $post_type ] ) ) {
        return '';
    }

    $terms = get_the_terms( $post_id, $taxonomy_map[ $post_type ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    if ( $atts['level'] === 'parent' ) {
        $terms = array_filter( $terms, function ( $t ) { return $t->parent === 0; } );
    } elseif ( $atts['level'] === 'child' ) {
        $terms = array_filter( $terms, function ( $t ) { return $t->parent !== 0; } );
    }

    if ( empty( $terms ) ) {
        return '';
    }

    $out = '<div class="eceens-faq-pills">';

    foreach ( $terms as $term ) {
        $out .= eceens_render_pill( $term, $atts['link'] );
    }

    $out .= '</div>';
    eceens_enqueue_pill_css();
    return $out;
}

/**
 * All categories for a given type, output as individual pills.
 *
 * Usage:
 *   [eceens_category_pills type="faq"]
 *   [eceens_category_pills type="content"]
 *   [eceens_category_pills type="faq" level="all"]
 *   [eceens_category_pills type="faq" link="anchor"]
 *   [eceens_category_pills type="faq" link="none"]
 *
 * Attributes:
 *   type  = "faq" | "content" (required)
 *   level = "parent" (default, top-level only) | "child" | "all"
 *   link  = "" (archive link, default) | "anchor" | "none"
 */
function eceens_category_pills_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'type'           => 'faq',
        'level'          => 'parent',
        'parent'         => '',
        'link'           => '',
        'columns'        => '',
        'gap'            => '',
        'gap_tablet'     => '',
        'gap_mobile'     => '',
        'padding'        => '',
        'radius'         => '',
        'size'           => '',
        'size_tablet'    => '',
        'size_mobile'    => '',
        'padding_tablet' => '',
        'padding_mobile' => '',
        'columns_tablet' => '',
        'columns_mobile' => '',
    ], $atts, 'eceens_category_pills' );

    $taxonomy_map = [
        'faq'     => 'faq_categorie',
        'content' => 'content_categorie',
    ];

    if ( ! isset( $taxonomy_map[ $atts['type'] ] ) ) {
        return '';
    }

    $tax = $taxonomy_map[ $atts['type'] ];

    $args = [
        'taxonomy'   => $tax,
        'hide_empty' => true,
    ];

    if ( $atts['parent'] !== '' ) {
        $parent_term = is_numeric( $atts['parent'] )
            ? get_term( absint( $atts['parent'] ), $tax )
            : get_term_by( 'slug', sanitize_title( $atts['parent'] ), $tax );

        if ( ! $parent_term || is_wp_error( $parent_term ) ) {
            return '';
        }
        $args['parent'] = $parent_term->term_id;
    } elseif ( $atts['level'] === 'parent' ) {
        $args['parent'] = 0;
    } elseif ( $atts['level'] === 'child' ) {
        $args['childless'] = false;
        $args['child_of']  = 0;
    }

    $terms = get_terms( $args );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    if ( $atts['level'] === 'child' && $atts['parent'] === '' ) {
        $terms = array_filter( $terms, function ( $t ) { return $t->parent !== 0; } );
    }

    static $instance = 0;
    $instance++;
    $id = 'eceens-cp-' . $instance;

    $desktop_wrapper = [];
    $desktop_pill    = [];
    $tablet_rules    = [];
    $mobile_rules    = [];

    $cols = absint( $atts['columns'] );
    if ( $cols ) {
        $desktop_wrapper[] = sprintf( 'display:grid;grid-template-columns:repeat(%d,1fr)', $cols );
    }
    if ( $atts['gap'] !== '' ) {
        $desktop_wrapper[] = sprintf( 'gap:%s', esc_attr( $atts['gap'] ) );
    }
    if ( $atts['padding'] !== '' ) {
        $desktop_pill[] = sprintf( 'padding:%s', esc_attr( $atts['padding'] ) );
    }
    if ( $atts['radius'] !== '' ) {
        $desktop_pill[] = sprintf( 'border-radius:%s', esc_attr( $atts['radius'] ) );
    }
    if ( $atts['size'] !== '' ) {
        $desktop_pill[] = sprintf( 'font-size:%s', esc_attr( $atts['size'] ) );
    }

    if ( $atts['size_tablet'] !== '' ) {
        $tablet_rules[] = sprintf( '#%s .eceens-faq-pill{font-size:%s}', $id, esc_attr( $atts['size_tablet'] ) );
    }
    if ( $atts['padding_tablet'] !== '' ) {
        $tablet_rules[] = sprintf( '#%s .eceens-faq-pill{padding:%s}', $id, esc_attr( $atts['padding_tablet'] ) );
    }
    if ( $atts['columns_tablet'] !== '' ) {
        $ct = absint( $atts['columns_tablet'] );
        if ( $ct ) {
            $tablet_rules[] = sprintf( '#%s{grid-template-columns:repeat(%d,1fr)}', $id, $ct );
        }
    }
    if ( $atts['gap_tablet'] !== '' ) {
        $tablet_rules[] = sprintf( '#%s{gap:%s}', $id, esc_attr( $atts['gap_tablet'] ) );
    }
    if ( $atts['size_mobile'] !== '' ) {
        $mobile_rules[] = sprintf( '#%s .eceens-faq-pill{font-size:%s}', $id, esc_attr( $atts['size_mobile'] ) );
    }
    if ( $atts['padding_mobile'] !== '' ) {
        $mobile_rules[] = sprintf( '#%s .eceens-faq-pill{padding:%s}', $id, esc_attr( $atts['padding_mobile'] ) );
    }
    if ( $atts['columns_mobile'] !== '' ) {
        $cm = absint( $atts['columns_mobile'] );
        if ( $cm ) {
            $mobile_rules[] = sprintf( '#%s{grid-template-columns:repeat(%d,1fr)}', $id, $cm );
        }
    }
    if ( $atts['gap_mobile'] !== '' ) {
        $mobile_rules[] = sprintf( '#%s{gap:%s}', $id, esc_attr( $atts['gap_mobile'] ) );
    }

    $css = '';
    if ( $desktop_wrapper ) {
        $css .= sprintf( '#%s{%s}', $id, implode( ';', $desktop_wrapper ) );
    }
    if ( $desktop_pill ) {
        $css .= sprintf( '#%s .eceens-faq-pill{%s}', $id, implode( ';', $desktop_pill ) );
    }
    if ( $tablet_rules ) {
        $css .= '@media(max-width:1024px){' . implode( '', $tablet_rules ) . '}';
    }
    if ( $mobile_rules ) {
        $css .= '@media(max-width:767px){' . implode( '', $mobile_rules ) . '}';
    }

    $out = '';
    if ( $css ) {
        $out .= '<style>' . $css . '</style>';
    }

    $out .= sprintf( '<div id="%s" class="eceens-category-pills">', esc_attr( $id ) );

    foreach ( $terms as $term ) {
        $out .= eceens_render_pill( $term, $atts['link'] );
    }

    $out .= '</div>';
    return $out;
}

function eceens_enqueue_pill_css() {
}

/**
 * Get the color for a term. Falls back to parent term color, then default.
 */
function eceens_get_term_color( $term ) {
    $color = get_term_meta( $term->term_id, 'eceens_term_color', true );
    if ( $color ) {
        return $color;
    }
    if ( $term->parent ) {
        $parent_color = get_term_meta( $term->parent, 'eceens_term_color', true );
        if ( $parent_color ) {
            return $parent_color;
        }
    }
    return '#2A50FF';
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
