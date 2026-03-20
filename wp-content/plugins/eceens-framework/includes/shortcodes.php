<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'eceens_faq_category_pills', 'eceens_faq_category_pills_shortcode' );
add_shortcode( 'eceens_post_category_pills', 'eceens_post_category_pills_shortcode' );
add_shortcode( 'eceens_category_pills', 'eceens_category_pills_shortcode' );
add_shortcode( 'eceens_current_category_name', 'eceens_current_category_name_shortcode' );
add_shortcode( 'eceens_current_category_description', 'eceens_current_category_description_shortcode' );
add_shortcode( 'eceens_current_category_color', 'eceens_current_category_color_shortcode' );
add_shortcode( 'eceens_category_loop', 'eceens_category_loop_shortcode' );

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
    // Transparanter pill look: lichte/tinted background en tekst exact in categoriekleur.
    $text_color = $color;
    $style      = sprintf(
        'background:#fff;color:%s;border-color:%s',
        esc_attr( $color ),
        esc_attr( $text_color ),
        esc_attr( $color )
    );
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

/**
 * Get the current term: loop context first, then queried object.
 */
function eceens_get_current_term() {
    if ( ! empty( $GLOBALS['eceens_current_term'] ) ) {
        return $GLOBALS['eceens_current_term'];
    }
    $obj = get_queried_object();
    if ( $obj && isset( $obj->taxonomy ) ) {
        return $obj;
    }
    return null;
}

/**
 * [eceens_current_category_name]
 * [eceens_current_category_name pill="yes"]  — pill met categoriekleur (voor in loop)
 * [eceens_current_category_name tag="h2"]
 * [eceens_current_category_name tag="span" color="yes"]
 */
function eceens_current_category_name_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'tag'   => '',
        'color' => 'no',
        'pill'  => 'no',
    ], $atts, 'eceens_current_category_name' );

    $term = eceens_get_current_term();
    if ( ! $term ) {
        return '';
    }

    $name = esc_html( $term->name );
    $as_pill = ( $atts['color'] === 'yes' || $atts['pill'] === 'yes' );

    if ( $as_pill ) {
        $color      = eceens_get_term_color( $term );
        $text_color = eceens_contrast_color( $color );
        $style      = sprintf( 'background:%s;color:%s', esc_attr( $color ), esc_attr( $text_color ) );
        $name       = sprintf( '<span class="eceens-category-label eceens-current-category-pill" style="%s">%s</span>', $style, $name );
    }

    if ( $atts['tag'] ) {
        $tag = sanitize_key( $atts['tag'] );
        return sprintf( '<%s class="eceens-current-category-name">%s</%s>', $tag, $name, $tag );
    }

    return $name;
}

/**
 * [eceens_current_category_description]
 * [eceens_current_category_description class="mijn-klasse"]
 */
function eceens_current_category_description_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'class' => '',
    ], $atts, 'eceens_current_category_description' );

    $term = eceens_get_current_term();
    if ( ! $term || empty( $term->description ) ) {
        return '';
    }

    $classes = array_merge(
        [ 'eceens-current-category-description' ],
        array_filter( array_map( 'sanitize_html_class', explode( ' ', $atts['class'] ) ) )
    );

    return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . wp_kses_post( $term->description ) . '</div>';
}

/**
 * [eceens_current_category_color]
 *
 * Injects CSS variables and helper classes for the current category color.
 * In a loop context: scoped per card. On archive pages: scoped to :root.
 */
function eceens_current_category_color_shortcode( $atts ) {
    $term = eceens_get_current_term();
    if ( ! $term ) {
        return '';
    }

    $color      = eceens_get_term_color( $term );
    $text_color = eceens_contrast_color( $color );

    if ( ! empty( $GLOBALS['eceens_current_term'] ) ) {
        static $color_instance = 0;
        $color_instance++;
        $id = 'eceens-lc-' . $color_instance;
        return sprintf(
            '<style>'
            . '#%s{--eceens-cat-color:%s;--eceens-cat-text:%s}'
            . '#%s .eceens-cat-bg{background:var(--eceens-cat-color)!important;color:var(--eceens-cat-text)!important}'
            . '#%s .eceens-cat-text{color:var(--eceens-cat-color)!important}'
            . '#%s .eceens-cat-border{border-color:var(--eceens-cat-color)!important}'
            . '</style>'
            . '<script>document.currentScript.parentElement.closest(".eceens-loop-card").id="%s"</script>',
            $id, esc_attr( $color ), esc_attr( $text_color ),
            $id, $id, $id, $id
        );
    }

    return sprintf(
        '<style>'
        . ':root{--eceens-cat-color:%s;--eceens-cat-text:%s}'
        . '.eceens-cat-bg{background:var(--eceens-cat-color)!important;color:var(--eceens-cat-text)!important}'
        . '.eceens-cat-text{color:var(--eceens-cat-color)!important}'
        . '.eceens-cat-border{border-color:var(--eceens-cat-color)!important}'
        . '</style>',
        esc_attr( $color ),
        esc_attr( $text_color )
    );
}

/**
 * [eceens_category_loop taxonomy="faq_categorie" columns="3" gap="20px"]
 *
 * Outputs a grid of category cards: pill (naam) + beschrijving. Geen Elementor-template,
 * zodat editor en live identiek zijn. Styling via Elementor Custom CSS of .eceens-loop-card.
 */
function eceens_category_loop_shortcode( $atts ) {
    static $loop_instance = 0;
    $loop_instance++;

    $atts = shortcode_atts( [
        'taxonomy' => 'faq_categorie',
        'columns'  => '3',
        'gap'      => '20px',
        'columns_tablet' => '',
        'columns_mobile' => '',
        'gap_tablet'     => '',
        'gap_mobile'     => '',
        'parent'   => '',
        'orderby'  => 'name',
        'order'    => 'ASC',
        'link'     => 'yes',
    ], $atts, 'eceens_category_loop' );

    $taxonomy = sanitize_key( $atts['taxonomy'] );
    $term_args_base = [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'orderby'    => $atts['orderby'],
        'order'      => $atts['order'],
    ];

    if ( $atts['parent'] !== '' ) {
        $parent = $atts['parent'];
        if ( ! is_numeric( $parent ) ) {
            $parent_term = get_term_by( 'slug', $parent, $taxonomy );
            $parent = $parent_term ? $parent_term->term_id : 0;
        }
        $term_args_base['parent'] = absint( $parent );
    }

    $ordered_terms = [];
    if ( $atts['parent'] !== '' ) {
        $terms = get_terms( $term_args_base );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $ordered_terms = array_map( function ( $t ) {
                return [ $t, false ];
            }, $terms );
        }
    } else {
        $term_args_base['parent'] = 0;
        $parents = get_terms( $term_args_base );
        if ( is_wp_error( $parents ) ) {
            $parents = [];
        }
        foreach ( $parents as $parent_term ) {
            $ordered_terms[] = [ $parent_term, true ];
            $children = get_terms( [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'parent'     => $parent_term->term_id,
                'orderby'    => $atts['orderby'],
                'order'      => $atts['order'],
            ] );
            if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
                foreach ( $children as $child ) {
                    $ordered_terms[] = [ $child, false ];
                }
            }
        }
    }

    if ( empty( $ordered_terms ) ) {
        return '';
    }

    $id   = 'eceens-cl-' . $loop_instance;
    $cols = absint( $atts['columns'] ) ?: 3;
    $gap  = esc_attr( $atts['gap'] );

    $out  = '<style>';
    $out .= sprintf( '#%s{display:grid;grid-template-columns:repeat(%d,1fr);gap:%s;width:100%%}', $id, $cols, $gap );

    // Responsive columns/gap.
    $tablet_css = [];
    if ( $atts['columns_tablet'] !== '' ) {
        $ct = absint( $atts['columns_tablet'] );
        if ( $ct ) {
            $tablet_css[] = sprintf( 'grid-template-columns:repeat(%d,1fr)', $ct );
        }
    }
    if ( $atts['gap_tablet'] !== '' ) {
        $tablet_css[] = sprintf( 'gap:%s', esc_attr( $atts['gap_tablet'] ) );
    }
    if ( ! empty( $tablet_css ) ) {
        $out .= sprintf( '@media(max-width:1024px){#%s{%s}}', esc_attr( $id ), implode( ';', $tablet_css ) );
    }

    $mobile_css = [];
    if ( $atts['columns_mobile'] !== '' ) {
        $cm = absint( $atts['columns_mobile'] );
        if ( $cm ) {
            $mobile_css[] = sprintf( 'grid-template-columns:repeat(%d,1fr)', $cm );
        }
    }
    if ( $atts['gap_mobile'] !== '' ) {
        $mobile_css[] = sprintf( 'gap:%s', esc_attr( $atts['gap_mobile'] ) );
    }
    if ( ! empty( $mobile_css ) ) {
        $out .= sprintf( '@media(max-width:767px){#%s{%s}}', esc_attr( $id ), implode( ';', $mobile_css ) );
    }

    $out .= '</style>';
    $out .= sprintf( '<div id="%s" class="eceens-category-loop">', esc_attr( $id ) );

    foreach ( $ordered_terms as list( $term, $is_parent ) ) {
        $color      = eceens_get_term_color( $term );
        $text_color = eceens_contrast_color( $color );
        $name       = esc_html( $term->name );
        $desc       = ! empty( $term->description ) ? wp_kses_post( $term->description ) : '';
        $link_url   = get_term_link( $term );
        $card_style = sprintf( '--eceens-cat-color:%s;--eceens-cat-text:%s', esc_attr( $color ), esc_attr( $text_color ) );
        // Lichter/transparent: background als tinted mix, tekst in de eigen categoriekleur.
        // (Elementor/loop CSS zet border op currentColor, dus border volgt automatisch.)
        $pill_style = sprintf(
            'background:#fff;color:%s;border-color:%s',
            esc_attr( $color ),
            esc_attr( $color )
        );

        $card_class = 'eceens-loop-card';
        if ( ! $is_parent ) {
            $card_class .= ' eceens-loop-subcard';
        }

        $inner = '<span class="eceens-category-label eceens-loop-pill" style="' . esc_attr( $pill_style ) . '">' . $name . '</span>';
        $inner .= '<div class="eceens-loop-body">';
        if ( $desc ) {
            $inner .= '<div class="eceens-loop-description">' . $desc . '</div>';
        }
        if ( $atts['link'] === 'yes' && ! is_wp_error( $link_url ) ) {
            $inner .= '<span class="eceens-loop-more">Lees meer →</span>';
        }
        $inner .= '</div>';

        if ( $atts['link'] === 'yes' && ! is_wp_error( $link_url ) ) {
            $out .= '<a href="' . esc_url( $link_url ) . '" class="' . esc_attr( $card_class ) . '" style="' . esc_attr( $card_style ) . '">' . $inner . '</a>';
        } else {
            $out .= '<div class="' . esc_attr( $card_class ) . '" style="' . esc_attr( $card_style ) . '">' . $inner . '</div>';
        }
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
