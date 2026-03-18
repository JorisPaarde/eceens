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

add_action( 'admin_init', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['eceens-regenerate-elementor-css'] ) || '1' !== (string) $_GET['eceens-regenerate-elementor-css'] ) {
		return;
	}

	if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
		wp_die( 'Elementor not loaded.' );
	}

	$ids = [];

	// Homepage doc (what you opened earlier).
	$ids[] = 13;

	// Static front page if set.
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id > 0 ) {
		$ids[] = $front_id;
	}

	// Active Kit (site settings).
	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( $kit_id > 0 ) {
		$ids[] = $kit_id;
	}

	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	$log = [
		'ids'        => $ids,
		'generated'  => [],
		'errors'     => [],
		'cache_cleared' => false,
	];

	// Clear cache first.
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
				$css->enqueue();
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

	wp_safe_redirect( remove_query_arg( 'eceens-regenerate-elementor-css' ) );
	exit;
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

