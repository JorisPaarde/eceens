<?php
/**
 * Plugin Name: Eceens - Reset Elementor Homepage (recovery)
 * Description: One-click recovery to reset Elementor data for the site's front page (backs up _elementor_data first). No Elementor core edits.
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

	if ( ! isset( $_GET['eceens-reset-home-elementor'] ) || '1' !== (string) $_GET['eceens-reset-home-elementor'] ) {
		return;
	}

	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id <= 0 ) {
		wp_die( esc_html__( 'No static front page is set (Settings → Reading).', 'eceens' ) );
	}

	// Backup Elementor data so we can restore manually if needed.
	$existing = get_post_meta( $front_id, '_elementor_data', true );
	if ( '' !== (string) $existing && null !== $existing ) {
		$backup_key = '_eceens_elementor_data_backup_' . gmdate( 'Ymd_His' );
		update_post_meta( $front_id, $backup_key, $existing );
	}

	// Remove Elementor generated artifacts for this page.
	delete_post_meta( $front_id, '_elementor_css' );
	delete_post_meta( $front_id, '_elementor_data' );
	delete_post_meta( $front_id, '_elementor_controls_usage' );
	delete_post_meta( $front_id, '_elementor_page_settings' );

	// Ensure editor opens in builder mode again.
	update_post_meta( $front_id, '_elementor_edit_mode', 'builder' );

	// Flag so we can show a notice after redirect.
	update_option( 'eceens_reset_home_elementor_done', time(), false );

	wp_safe_redirect( remove_query_arg( 'eceens-reset-home-elementor' ) );
	exit;
} );

add_action( 'admin_notices', function () {
	$ts = (int) get_option( 'eceens_reset_home_elementor_done' );
	if ( ! $ts ) {
		return;
	}
	delete_option( 'eceens_reset_home_elementor_done' );
	?>
	<div class="notice notice-success">
		<p><strong>Homepage Elementor data gereset.</strong> De vorige `_elementor_data` is geback-upt in post meta (key begint met `_eceens_elementor_data_backup_`). Open nu de homepage opnieuw in Elementor.</p>
	</div>
	<?php
} );

