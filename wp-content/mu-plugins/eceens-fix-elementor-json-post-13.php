<?php
/**
 * Plugin Name: Eceens - Fix Elementor JSON (post 13)
 * Description: Attempts to repair invalid _elementor_data JSON for post ID 13 (unslash/stripslashes/control chars/double-encoded). Backs up before writing. Remove after use.
 * Author: Eceens
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eceens_try_decode_elementor_json( $raw ) {
	if ( ! is_string( $raw ) || '' === $raw ) {
		return [ false, null, 'empty' ];
	}

	$decoded = json_decode( $raw, true );
	if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
		return [ true, $raw, 'as-is' ];
	}

	return [ false, null, function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : (string) json_last_error() ];
}

function eceens_elementor_json_variants( $raw ) {
	$variants = [];

	$variants['as-is'] = $raw;
	$variants['wp_unslash'] = wp_unslash( $raw );
	$variants['stripslashes'] = stripslashes( $raw );
	$variants['stripslashes_twice'] = stripslashes( stripslashes( $raw ) );
	$variants['trim'] = trim( $raw );
	$variants['rtrim_nulls'] = rtrim( $raw, "\x00" );

	// Remove non-whitespace ASCII control chars that can break JSON.
	// Keep \t \n \r (valid JSON whitespace outside strings).
	$variants['remove_ctrl'] = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $raw );
	$variants['remove_ctrl_trim'] = trim( (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $raw ) );

	// Hard fix for the known broken snippet we saw in the editor: unescaped quotes inside HTML attributes.
	$variants['hard_fix_typed_span_snippet'] = str_replace(
		[
			'class="accent-nusamen"',
			'id="typed-nusamen"',
		],
		[
			'class=\\\\\"accent-nusamen\\\\\"',
			'id=\\\\\"typed-nusamen\\\\\"',
		],
		$raw
	);

	// Fix a common corruption: embedded HTML with unescaped quotes in attributes, e.g. class="..." inside a JSON string.
	// Convert class="x" and id="y" → class=\"x\" / id=\"y\".
	$variants['escape_html_attr_quotes'] = preg_replace( '/\b(class|id)="([^"]*)"/', '$1=\\\\\"$2\\\\\"', $raw );
	$variants['escape_html_attr_quotes_unslash'] = preg_replace( '/\b(class|id)="([^"]*)"/', '$1=\\\\\"$2\\\\\"', wp_unslash( $raw ) );
	// More general: escape any HTML-like attribute="..." to attribute=\"...\".
	// This avoids JSON breakage when Elementor titles/text contain raw HTML with quotes.
	$variants['escape_all_html_attr_quotes'] = preg_replace( '/\b([a-zA-Z][a-zA-Z0-9:_-]*)="([^"]*)"/', '$1=\\\\\"$2\\\\\"', $raw );
	$variants['escape_all_html_attr_quotes_unslash'] = preg_replace( '/\b([a-zA-Z][a-zA-Z0-9:_-]*)="([^"]*)"/', '$1=\\\\\"$2\\\\\"', wp_unslash( $raw ) );

	// Extract between first '[' and last ']' (common when trailing junk breaks JSON).
	$first_bracket = is_string( $raw ) ? strpos( $raw, '[' ) : false;
	$last_bracket  = is_string( $raw ) ? strrpos( $raw, ']' ) : false;
	if ( false !== $first_bracket && false !== $last_bracket && $last_bracket > $first_bracket ) {
		$variants['slice_brackets'] = substr( $raw, $first_bracket, $last_bracket - $first_bracket + 1 );
		$variants['slice_brackets_unslash'] = wp_unslash( $variants['slice_brackets'] );
		$variants['slice_brackets_remove_ctrl'] = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $variants['slice_brackets'] );
	}

	// If it's a JSON-encoded string that itself contains JSON (starts with a quote).
	if ( is_string( $raw ) && strlen( $raw ) > 2 && '"' === $raw[0] ) {
		$inner = json_decode( $raw, true );
		if ( is_string( $inner ) && '' !== $inner ) {
			$variants['double_encoded_inner'] = $inner;
			$variants['double_encoded_unslash'] = wp_unslash( $inner );
			$variants['double_encoded_stripslashes'] = stripslashes( $inner );
			$inner_first = strpos( $inner, '[' );
			$inner_last  = strrpos( $inner, ']' );
			if ( false !== $inner_first && false !== $inner_last && $inner_last > $inner_first ) {
				$variants['double_encoded_slice_brackets'] = substr( $inner, $inner_first, $inner_last - $inner_first + 1 );
			}
		}
	}

	// De-dupe while preserving labels.
	$seen = [];
	$out  = [];
	foreach ( $variants as $label => $val ) {
		if ( ! is_string( $val ) ) {
			continue;
		}
		// Skip no-op variants to keep logs readable.
		if ( $val === $raw && ! in_array( $label, [ 'as-is' ], true ) ) {
			continue;
		}
		$key = md5( $val );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$out[ $label ] = $val;
	}
	return $out;
}

add_action( 'admin_init', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['eceens-fix-el-13-json'] ) || '1' !== (string) $_GET['eceens-fix-el-13-json'] ) {
		return;
	}

	$post_id = 13;
	if ( ! get_post( $post_id ) ) {
		update_option( 'eceens_fix_el_13_json_result', [ 'ok' => false, 'error' => 'Post 13 not found.' ], false );
		wp_safe_redirect( remove_query_arg( 'eceens-fix-el-13-json' ) );
		exit;
	}

	$current = get_post_meta( $post_id, '_elementor_data', true );
	$backup_key = '_eceens_elementor_data_pre_fix_' . gmdate( 'Ymd_His' );
	update_post_meta( $post_id, $backup_key, $current );

	// Also consider the latest backup key if present.
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
	rsort( $backup_keys, SORT_STRING );
	$latest_backup_key = ! empty( $backup_keys ) ? $backup_keys[0] : '';
	$latest_backup_val = ( '' !== $latest_backup_key ) ? get_post_meta( $post_id, $latest_backup_key, true ) : '';

	$candidates = [
		'current' => $current,
	];
	if ( is_string( $latest_backup_val ) && '' !== $latest_backup_val ) {
		$candidates['latest_backup'] = $latest_backup_val;
	}

	$attempt_log = [];
	$fixed = null;
	$fixed_label = '';
	$fixed_source = '';

	foreach ( $candidates as $source => $raw ) {
		foreach ( eceens_elementor_json_variants( (string) $raw ) as $label => $variant ) {
			[ $ok, $valid_json, $why ] = eceens_try_decode_elementor_json( $variant );
			$attempt_log[] = [
				'source' => $source,
				'label'  => $label,
				'len'    => strlen( $variant ),
				'ok'     => $ok,
				'why'    => $ok ? 'ok' : $why,
			];
			if ( $ok ) {
				$fixed = $valid_json;
				$fixed_label = $label;
				$fixed_source = $source;
				break 2;
			}
		}
	}

	if ( ! is_string( $fixed ) || '' === $fixed ) {
		update_option(
			'eceens_fix_el_13_json_result',
			[
				'ok'        => false,
				'error'     => 'No valid JSON variant found.',
				'backup_key'=> $backup_key,
				'latest_backup_key' => $latest_backup_key,
				'attempts'  => $attempt_log,
			],
			false
		);
		wp_safe_redirect( remove_query_arg( 'eceens-fix-el-13-json' ) );
		exit;
	}

	update_post_meta( $post_id, '_elementor_data', $fixed );
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
		'eceens_fix_el_13_json_result',
		[
			'ok'        => true,
			'source'    => $fixed_source,
			'label'     => $fixed_label,
			'backup_key'=> $backup_key,
			'latest_backup_key' => $latest_backup_key,
			'css_generated' => $css_generated,
		],
		false
	);

	wp_safe_redirect( remove_query_arg( 'eceens-fix-el-13-json' ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$res = get_option( 'eceens_fix_el_13_json_result' );
	if ( ! is_array( $res ) ) {
		return;
	}
	// Keep the result until explicitly cleared, so it's not missed during redirects.
	if ( isset( $_GET['eceens-clear-fix-el-13-json'] ) && '1' === (string) $_GET['eceens-clear-fix-el-13-json'] ) {
		delete_option( 'eceens_fix_el_13_json_result' );
		echo '<div class="notice notice-info"><p>Fix-result cleared.</p></div>';
		return;
	}

	if ( empty( $res['ok'] ) ) {
		echo '<div class="notice notice-error"><p><strong>Fix Elementor JSON (post 13) mislukt.</strong> ' . esc_html( (string) ( $res['error'] ?? '' ) ) . '</p>';
		echo '<p>Backup key: <code>' . esc_html( (string) ( $res['backup_key'] ?? '' ) ) . '</code></p>';
		if ( ! empty( $res['latest_backup_key'] ) ) {
			echo '<p>Latest backup key: <code>' . esc_html( (string) $res['latest_backup_key'] ) . '</code></p>';
		}
		if ( ! empty( $res['attempts'] ) && is_array( $res['attempts'] ) ) {
			$attempts = array_slice( $res['attempts'], 0, 50 );
			echo '<details style="margin-top:8px;"><summary>Attempts (first 50)</summary><pre style="white-space:pre-wrap;max-width:100%;">' . esc_html( print_r( $attempts, true ) ) . '</pre></details>';
		}
		$clear_url = add_query_arg( 'eceens-clear-fix-el-13-json', '1', admin_url() );
		echo '<p style="margin-top:10px;"><a href="' . esc_url( $clear_url ) . '">Clear this fix result</a></p>';
		echo '</div>';
		return;
	}

	echo '<div class="notice notice-success"><p><strong>Fix Elementor JSON (post 13) gelukt.</strong> Source: ' . esc_html( (string) $res['source'] ) . ', variant: ' . esc_html( (string) $res['label'] ) . '. CSS generated: ' . esc_html( ! empty( $res['css_generated'] ) ? 'yes' : 'no' ) . '.</p>';
	echo '<p>Backup key: <code>' . esc_html( (string) ( $res['backup_key'] ?? '' ) ) . '</code></p></div>';
}, 1 );

