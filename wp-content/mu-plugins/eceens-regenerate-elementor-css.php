<?php
/**
 * Plugin Name: Eceens - Regenerate Elementor CSS (recovery)
 * Description: One-time helper to force regenerate Elementor CSS files for homepage + active kit. Remove after use.
 * Author: Eceens
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eceens_run_elementor_css_regeneration() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
		update_option( 'eceens_regenerate_elementor_css_log', [ 'error' => 'Elementor not loaded.' ], false );
		update_option( 'eceens_regenerate_elementor_css_done', time(), false );
		return;
	}

	$ids = [];

	// Homepage doc (the one you're editing).
	$ids[] = 13;

	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id > 0 ) {
		$ids[] = $front_id;
	}

	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( $kit_id > 0 ) {
		$ids[] = $kit_id;
	}

	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	$log = [
		'ids'           => $ids,
		'generated'     => [],
		'errors'        => [],
		'cache_cleared' => false,
	];

	try {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		$log['cache_cleared'] = true;
	} catch ( Throwable $e ) {
		$log['errors'][] = 'cache_clear: ' . $e->getMessage();
	}

	foreach ( $ids as $id ) {
		try {
			if ( class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
				$css = new \Elementor\Core\Files\CSS\Post( $id );
				$css->update();
				$log['generated'][] = $id;
			} else {
				$log['errors'][] = "missing_css_class_for_$id";
			}
		} catch ( Throwable $e ) {
			$log['errors'][] = $id . ': ' . $e->getMessage();
		}
	}

	update_option( 'eceens_regenerate_elementor_css_log', $log, false );
	update_option( 'eceens_regenerate_elementor_css_done', time(), false );
}

// Trigger from wp-admin (works if Elementor is loaded on that request).
add_action( 'admin_init', function () {
	if ( ! isset( $_GET['eceens-regenerate-elementor-css'] ) || '1' !== (string) $_GET['eceens-regenerate-elementor-css'] ) {
		return;
	}
	eceens_run_elementor_css_regeneration();
	wp_safe_redirect( remove_query_arg( 'eceens-regenerate-elementor-css' ) );
	exit;
} );

// Trigger from inside Elementor editor request (Elementor is guaranteed loaded here).
add_action( 'elementor/init', function () {
	if ( ! is_admin() ) {
		return;
	}
	if ( ! isset( $_GET['eceens-regenerate-elementor-css'] ) || '1' !== (string) $_GET['eceens-regenerate-elementor-css'] ) {
		return;
	}
	eceens_run_elementor_css_regeneration();
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$ts = (int) get_option( 'eceens_regenerate_elementor_css_done' );
	if ( ! $ts ) {
		return;
	}
	delete_option( 'eceens_regenerate_elementor_css_done' );
	$log = get_option( 'eceens_regenerate_elementor_css_log' );
	?>
	<div class="notice notice-success">
		<p><strong>Elementor CSS regeneratie uitgevoerd.</strong></p>
		<pre style="white-space:pre-wrap;max-width:100%;"><?php echo esc_html( print_r( $log, true ) ); ?></pre>
	</div>
	<?php
} );

