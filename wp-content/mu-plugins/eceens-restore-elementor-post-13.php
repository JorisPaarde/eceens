<?php
/**
 * Plugin Name: Eceens - Restore Elementor Post 13 (recovery)
 * Description: Restores _elementor_data for post ID 13 from the latest _eceens_elementor_data_backup_* meta and tries to regenerate CSS. Remove after use.
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

	if ( ! isset( $_GET['eceens-restore-el-13'] ) || '1' !== (string) $_GET['eceens-restore-el-13'] ) {
		return;
	}

	$post_id = 13;

	if ( ! get_post( $post_id ) ) {
		update_option( 'eceens_restore_el_13_error', 'Post 13 not found.', false );
		wp_safe_redirect( remove_query_arg( 'eceens-restore-el-13' ) );
		exit;
	}

	$all_meta = get_post_meta( $post_id );
	$keys     = array_keys( is_array( $all_meta ) ? $all_meta : [] );
	$backup_keys = array_values(
		array_filter(
			$keys,
			function ( $k ) {
				return is_string( $k ) && str_starts_with( $k, '_eceens_elementor_data_backup_' );
			}
		)
	);

	if ( empty( $backup_keys ) ) {
		update_option( 'eceens_restore_el_13_error', 'Geen backup meta gevonden (_eceens_elementor_data_backup_*).', false );
		wp_safe_redirect( remove_query_arg( 'eceens-restore-el-13' ) );
		exit;
	}

	rsort( $backup_keys, SORT_STRING );
	$latest_key = $backup_keys[0];
	$backup_val = get_post_meta( $post_id, $latest_key, true );

	if ( '' === (string) $backup_val ) {
		update_option( 'eceens_restore_el_13_error', 'Backup meta is leeg.', false );
		wp_safe_redirect( remove_query_arg( 'eceens-restore-el-13' ) );
		exit;
	}

	update_post_meta( $post_id, '_elementor_data', $backup_val );
	update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
	delete_post_meta( $post_id, '_elementor_css' );

	$css_generated = false;
	if ( class_exists( '\\Elementor\\Plugin' ) && class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
		try {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		} catch ( Throwable $e ) {
			// ignore
		}
		try {
			$css = new \Elementor\Core\Files\CSS\Post( $post_id );
			$css->update();
			$css_generated = true;
		} catch ( Throwable $e ) {
			$css_generated = false;
		}
	}

	update_option(
		'eceens_restore_el_13_done',
		[
			'latest_key'    => $latest_key,
			'css_generated' => $css_generated,
		],
		false
	);

	wp_safe_redirect( remove_query_arg( 'eceens-restore-el-13' ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$err = (string) get_option( 'eceens_restore_el_13_error' );
	if ( '' !== $err ) {
		delete_option( 'eceens_restore_el_13_error' );
		echo '<div class="notice notice-error"><p><strong>Restore mislukt:</strong> ' . esc_html( $err ) . '</p></div>';
		return;
	}

	$done = get_option( 'eceens_restore_el_13_done' );
	if ( ! is_array( $done ) ) {
		return;
	}
	delete_option( 'eceens_restore_el_13_done' );

	echo '<div class="notice notice-success"><p><strong>Elementor data teruggezet voor post 13.</strong> Latest backup: ' . esc_html( (string) ( $done['latest_key'] ?? '' ) ) . '. CSS generated: ' . esc_html( ! empty( $done['css_generated'] ) ? 'yes' : 'no' ) . '.</p></div>';
} );

