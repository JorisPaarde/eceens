<?php
/**
 * Plugin Name: Eceens - Inspect Elementor Post 13
 * Description: Shows meta/status for post ID 13 to debug missing post-13.css. Remove after debugging.
 * Author: Eceens
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$post_id = 13;
	$post    = get_post( $post_id );
	if ( ! $post ) {
		echo '<div class="notice notice-error"><p><strong>Eceens inspect:</strong> Post 13 not found.</p></div>';
		return;
	}

	$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
	$edit_mode      = get_post_meta( $post_id, '_elementor_edit_mode', true );
	$template_type  = get_post_meta( $post_id, '_elementor_template_type', true );

	$all_meta = get_post_meta( $post_id );
	$keys     = array_keys( is_array( $all_meta ) ? $all_meta : [] );
	sort( $keys, SORT_STRING );

	$backups = array_values(
		array_filter(
			$keys,
			function ( $k ) {
				return is_string( $k ) && str_starts_with( $k, '_eceens_elementor_data_backup_' );
			}
		)
	);
	rsort( $backups, SORT_STRING );

	$len = is_string( $elementor_data ) ? strlen( $elementor_data ) : 0;
	$has = ( '' !== (string) $elementor_data && null !== $elementor_data );

	$json_ok    = false;
	$json_err   = '';
	$root_count = null;
	$root_types = [];
	$bad_utf8   = null;
	$ctrl_hits  = [];
	$first_200  = '';
	$last_200   = '';
	$first_char = '';
	$last_char  = '';
	$counts     = [
		'[' => 0,
		']' => 0,
		'{' => 0,
		'}' => 0,
	];
	$base64 = '';
	if ( is_string( $elementor_data ) && '' !== $elementor_data ) {
		$first_200  = substr( $elementor_data, 0, 200 );
		$last_200   = substr( $elementor_data, max( 0, strlen( $elementor_data ) - 200 ) );
		$trimmed    = trim( $elementor_data );
		$first_char = ( '' !== $trimmed ) ? $trimmed[0] : '';
		$last_char  = ( '' !== $trimmed ) ? $trimmed[ strlen( $trimmed ) - 1 ] : '';

		$counts['['] = substr_count( $elementor_data, '[' );
		$counts[']'] = substr_count( $elementor_data, ']' );
		$counts['{'] = substr_count( $elementor_data, '{' );
		$counts['}'] = substr_count( $elementor_data, '}' );

		// For JS-side JSON.parse diagnostics (gives position in message).
		$base64 = base64_encode( $elementor_data );

		if ( function_exists( 'mb_check_encoding' ) ) {
			$bad_utf8 = ! mb_check_encoding( $elementor_data, 'UTF-8' );
		}

		// Find first few ASCII control chars (often break JSON).
		if ( preg_match_all( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $elementor_data, $m, PREG_OFFSET_CAPTURE ) ) {
			foreach ( array_slice( $m[0], 0, 5 ) as $hit ) {
				$ctrl_hits[] = [
					'char'   => $hit[0],
					'offset' => $hit[1],
				];
			}
		}

		$decoded = json_decode( $elementor_data, true );
		$je      = json_last_error();
		$json_ok = ( JSON_ERROR_NONE === $je ) && is_array( $decoded );
		if ( ! $json_ok ) {
			$json_err = function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : (string) $je;
		} else {
			$root_count = count( $decoded );
			foreach ( array_slice( $decoded, 0, 10 ) as $node ) {
				if ( is_array( $node ) && isset( $node['elType'] ) ) {
					$root_types[] = (string) $node['elType'];
				} elseif ( is_array( $node ) && isset( $node['widgetType'] ) ) {
					$root_types[] = (string) $node['widgetType'];
				} else {
					$root_types[] = 'unknown';
				}
			}
		}
	}

	$latest_backup_key = ! empty( $backups ) ? $backups[0] : '';
	$latest_backup_len = 0;
	if ( '' !== $latest_backup_key ) {
		$bv = get_post_meta( $post_id, $latest_backup_key, true );
		$latest_backup_len = is_string( $bv ) ? strlen( $bv ) : 0;
	}

	echo '<div class="notice notice-warning"><p><strong>Eceens inspect (post 13)</strong></p>';
	echo '<ul style="margin:0 0 0 18px;list-style:disc;">';
	echo '<li>title: ' . esc_html( get_the_title( $post_id ) ) . '</li>';
	echo '<li>post_type: ' . esc_html( (string) $post->post_type ) . '</li>';
	echo '<li>_elementor_edit_mode: ' . esc_html( (string) $edit_mode ) . '</li>';
	echo '<li>_elementor_template_type: ' . esc_html( (string) $template_type ) . '</li>';
	echo '<li>_elementor_data present: ' . esc_html( $has ? 'yes' : 'no' ) . ' (len ' . esc_html( (string) $len ) . ')</li>';
	echo '<li>_elementor_data JSON: ' . esc_html( $json_ok ? 'ok' : 'INVALID' ) . ( $json_ok ? '' : ' — ' . esc_html( $json_err ) ) . '</li>';
	if ( null !== $bad_utf8 ) {
		echo '<li>UTF-8 valid: ' . esc_html( $bad_utf8 ? 'no (invalid bytes found)' : 'yes' ) . '</li>';
	}
	if ( '' !== $first_char ) {
		echo '<li>first char (trimmed): <code>' . esc_html( $first_char ) . '</code></li>';
		echo '<li>last char (trimmed): <code>' . esc_html( $last_char ) . '</code></li>';
		echo '<li>bracket counts: [=' . esc_html( (string) $counts['['] ) . ', ]=' . esc_html( (string) $counts[']'] ) . ', {=' . esc_html( (string) $counts['{'] ) . ', }=' . esc_html( (string) $counts['}'] ) . '</li>';
		echo '<li>first 200: <code>' . esc_html( $first_200 ) . '</code></li>';
		echo '<li>last 200: <code>' . esc_html( $last_200 ) . '</code></li>';
	}
	if ( ! $json_ok && '' !== $base64 ) {
		?>
		<script>
		(function(){
			try {
				var b64 = <?php echo wp_json_encode( $base64 ); ?>;
				var bin = atob(b64);
				var bytes = new Uint8Array(bin.length);
				for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
				var txt = (window.TextDecoder ? new TextDecoder('utf-8').decode(bytes) : decodeURIComponent(escape(bin)));
				try {
					JSON.parse(txt);
					console.log('[Eceens inspect] JSON.parse OK (unexpected)');
				} catch (e) {
					var msg = (e && e.message) ? e.message : String(e);
					var m = msg.match(/position\s+(\d+)/i);
					var pos = m ? parseInt(m[1], 10) : null;
					var snip = '';
					if (pos !== null && !isNaN(pos)) {
						var start = Math.max(0, pos - 80);
						snip = txt.slice(start, pos + 80);
					}
					var box = document.createElement('div');
					box.style.marginTop = '8px';
					box.innerHTML =
						'<p><strong>Eceens JS JSON.parse error:</strong> <code>' + msg.replace(/</g,'&lt;') + '</code></p>' +
						(pos !== null ? '<p><strong>position:</strong> ' + pos + '</p>' : '') +
						(snip ? '<p><strong>context:</strong> <code>' + snip.replace(/</g,'&lt;') + '</code></p>' : '');
					// Append inside our notice (last <ul> parent).
					var notices = document.querySelectorAll('.notice');
					for (var j=0;j<notices.length;j++){
						if (notices[j].textContent && notices[j].textContent.indexOf('Eceens inspect (post 13)') !== -1) {
							notices[j].appendChild(box);
							break;
						}
					}
				}
			} catch (e) {}
		})();
		</script>
		<?php
	}
	if ( ! empty( $ctrl_hits ) ) {
		$first = $ctrl_hits[0];
		$off   = (int) $first['offset'];
		$start = max( 0, $off - 60 );
		$snip  = substr( $elementor_data, $start, 140 );
		echo '<li>first control char offset: ' . esc_html( (string) $off ) . '</li>';
		echo '<li>context around offset: <code>' . esc_html( $snip ) . '</code></li>';
	}
	if ( null !== $root_count ) {
		echo '<li>root elements: ' . esc_html( (string) $root_count ) . '</li>';
		if ( ! empty( $root_types ) ) {
			echo '<li>first root types: ' . esc_html( implode( ', ', $root_types ) ) . '</li>';
		}
	}
	echo '<li>backup keys: ' . esc_html( (string) count( $backups ) ) . '</li>';
	if ( '' !== $latest_backup_key ) {
		echo '<li>latest backup len: ' . esc_html( (string) $latest_backup_len ) . ' (' . esc_html( $latest_backup_key ) . ')</li>';
	}
	$fix_res = get_option( 'eceens_fix_el_13_json_result' );
	if ( is_array( $fix_res ) ) {
		$status = ! empty( $fix_res['ok'] ) ? 'ok' : 'FAILED';
		$label  = isset( $fix_res['label'] ) ? (string) $fix_res['label'] : '';
		echo '<li>last fixer result: ' . esc_html( $status . ( $label ? ' — ' . $label : '' ) ) . '</li>';
	}
	echo '</ul>';

	if ( ! empty( $backups ) ) {
		echo '<details style="margin-top:8px;"><summary>Backup meta keys</summary><pre style="white-space:pre-wrap;max-width:100%;">' . esc_html( implode( "\n", $backups ) ) . '</pre></details>';
	}

	echo '</div>';
}, 1 );
