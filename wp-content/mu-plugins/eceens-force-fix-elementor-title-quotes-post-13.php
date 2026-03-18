<?php
/**
 * Plugin Name: Eceens - Force Fix Elementor Title Quotes (post 13)
 * Description: Directly fixes invalid _elementor_data JSON for post 13 by escaping quotes in HTML attributes inside stored strings. Remove after use.
 * Author: Eceens
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eceens_force_fix_elementor_quotes_string( $s ) {
	if ( ! is_string( $s ) || '' === $s ) {
		return $s;
	}

	// Target the exact known-bad attributes first (most reliable).
	$s = str_replace(
		[
			'class="accent-nusamen"',
			'id="typed-nusamen"',
		],
		[
			'class=\\\"accent-nusamen\\\"',
			'id=\\\"typed-nusamen\\\"',
		],
		$s
	);

	// Broader safety net: escape ANY html-like attr="..." to attr=\"...\" (only if it appears inside a JSON string).
	// This is conservative enough for Elementor text fields that may contain inline HTML.
	$s = preg_replace( '/\b([a-zA-Z][a-zA-Z0-9:_-]*)="([^"]*)"/', '$1=\\\\\"$2\\\\\"', $s );

	return $s;
}

add_action( 'admin_init', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['eceens-force-fix-el-13'] ) || '1' !== (string) $_GET['eceens-force-fix-el-13'] ) {
		return;
	}

	$post_id = 13;
	$current = get_post_meta( $post_id, '_elementor_data', true );
	if ( ! is_string( $current ) || '' === $current ) {
		update_option( 'eceens_force_fix_el_13_result', [ 'ok' => false, 'error' => '_elementor_data empty or not a string' ], false );
		wp_safe_redirect( remove_query_arg( 'eceens-force-fix-el-13' ) );
		exit;
	}

	$backup_key = '_eceens_elementor_data_pre_force_fix_' . gmdate( 'Ymd_His' );
	update_post_meta( $post_id, $backup_key, $current );

	$fixed = eceens_force_fix_elementor_quotes_string( $current );
	$changed = ( $fixed !== $current );

	$decoded = json_decode( $fixed, true );
	$json_ok = ( JSON_ERROR_NONE === json_last_error() ) && is_array( $decoded );
	$json_err = $json_ok ? '' : ( function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : (string) json_last_error() );

	if ( $json_ok ) {
		update_post_meta( $post_id, '_elementor_data', $fixed );
		delete_post_meta( $post_id, '_elementor_css' );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

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
			'eceens_force_fix_el_13_result',
			[
				'ok'          => true,
				'changed'     => $changed,
				'backup_key'  => $backup_key,
				'css_generated' => $css_generated,
			],
			false
		);
	} else {
		update_option(
			'eceens_force_fix_el_13_result',
			[
				'ok'         => false,
				'changed'    => $changed,
				'error'      => $json_err,
				'backup_key' => $backup_key,
			],
			false
		);
	}

	wp_safe_redirect( remove_query_arg( 'eceens-force-fix-el-13' ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$res = get_option( 'eceens_force_fix_el_13_result' );
	if ( ! is_array( $res ) ) {
		return;
	}
	delete_option( 'eceens_force_fix_el_13_result' );

	if ( empty( $res['ok'] ) ) {
		echo '<div class="notice notice-error"><p><strong>Force fix post 13:</strong> FAILED — ' . esc_html( (string) ( $res['error'] ?? '' ) ) . '</p>';
		echo '<p>Changed: ' . esc_html( ! empty( $res['changed'] ) ? 'yes' : 'no' ) . ' — Backup: <code>' . esc_html( (string) ( $res['backup_key'] ?? '' ) ) . '</code></p></div>';
		return;
	}

	echo '<div class="notice notice-success"><p><strong>Force fix post 13:</strong> OK</p>';
	echo '<p>Changed: ' . esc_html( ! empty( $res['changed'] ) ? 'yes' : 'no' ) . ' — CSS generated: ' . esc_html( ! empty( $res['css_generated'] ) ? 'yes' : 'no' ) . '</p>';
	echo '<p>Backup: <code>' . esc_html( (string) ( $res['backup_key'] ?? '' ) ) . '</code></p></div>';
}, 1 );

