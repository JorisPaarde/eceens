<?php
/**
 * Plugin Name: Eceens Framework
 * Description: Custom Post Types, taxonomies, meta boxes, sorting, Elementor helpers and shortcodes for Eceens.
 * Version:     1.5.0
 * Author:      Eceens
 * Text Domain: eceens-framework
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ECEENS_FW_VERSION', '1.5.0' );
define( 'ECEENS_FW_PATH', plugin_dir_path( __FILE__ ) );
define( 'ECEENS_FW_URL', plugin_dir_url( __FILE__ ) );

require_once ECEENS_FW_PATH . 'includes/register.php';
require_once ECEENS_FW_PATH . 'includes/metaboxes.php';
require_once ECEENS_FW_PATH . 'includes/term-color.php';
require_once ECEENS_FW_PATH . 'includes/sorting.php';
require_once ECEENS_FW_PATH . 'includes/elementor.php';
require_once ECEENS_FW_PATH . 'includes/shortcodes.php';

/* --- Front-end styles --- */

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'eceens-pills',
        ECEENS_FW_URL . 'assets/pills.css',
        [],
        ECEENS_FW_VERSION
    );

    if ( eceens_is_faq_page() ) {
        wp_enqueue_style(
            'eceens-floating-button',
            ECEENS_FW_URL . 'assets/floating-button.css',
            [],
            ECEENS_FW_VERSION
        );
    }
});

add_action( 'wp_footer', function () {
    if ( eceens_is_faq_page() ) {
        echo '<a class="eceens-floating-btn elementor-button elementor-size-md" href="/contact/">Stel je vraag</a>';
    }
});

function eceens_is_faq_page() {
    if ( is_post_type_archive( 'faq' ) || is_singular( 'faq' ) || is_tax( 'faq_categorie' ) ) {
        return true;
    }
    return is_page( 'vragen-en-zorgen' );
}

/* --- Flush rewrite rules on activation --- */

register_activation_hook( __FILE__, function () {
    require_once ECEENS_FW_PATH . 'includes/register.php';
    eceens_register_post_types();
    eceens_register_taxonomies();
    flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
