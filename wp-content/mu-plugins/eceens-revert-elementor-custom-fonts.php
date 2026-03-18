<?php
/**
 * Plugin Name: Eceens - Revert Elementor Custom Fonts (one-time)
 * Description: One-time cleanup of Elementor custom fonts (DB + uploaded assets) to recover a broken editor after a custom font upload.
 * Author: Eceens
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', function () {
	if ( ! is_admin() ) {
		return;
	}

	// Run once.
	$flag = 'eceens_elementor_custom_fonts_reverted_20260318';
	$force = isset( $_GET['eceens-revert-fonts'] ) && '1' === (string) $_GET['eceens-revert-fonts'];
	if ( ! $force && get_option( $flag ) ) {
		return;
	}

	// Elementor not active/loaded: still allow cleanup of artifacts/options.
	global $wpdb;

	$log = [
		'deleted_font_posts'   => 0,
		'deleted_options'      => 0,
		'deleted_transients'   => 0,
		'deleted_font_files'   => [],
		'deleted_css_files'    => 0,
		'elementor_cache_cleared' => false,
	];

	// 1) Delete Elementor custom font posts (different versions use different CPT keys).
	$cpts = [ 'elementor_font', 'elementor_fonts', 'elementor_custom_font', 'elementor_custom_fonts' ];
	foreach ( $cpts as $cpt ) {
		$posts = get_posts(
			[
				'post_type'      => $cpt,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
				$log['deleted_font_posts']++;
			}
		}
	}

	// 2) Delete likely option keys used by Elementor's Fonts Manager / Custom Fonts.
	$like_patterns = [
		// Option names.
		'elementor%font%',
		'elementor%fonts%',
		'elementor_custom_fonts',
		'elementor_fonts_manager_font_groups',
		'elementor_font%',
		// Some installs store font-face rules / manager state under generic elementor options.
		'elementor%typography%',
	];

	$option_names = [];
	foreach ( $like_patterns as $pattern ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$pattern
			)
		);
		if ( ! empty( $names ) ) {
			$option_names = array_merge( $option_names, $names );
		}
	}
	$option_names = array_values( array_unique( array_filter( $option_names ) ) );
	foreach ( $option_names as $name ) {
		if ( delete_option( $name ) ) {
			$log['deleted_options']++;
		}
	}

	// 2b) Delete options that *contain* references to Elementor custom fonts (by value).
	$value_like_patterns = [
		'%elementor/custom-fonts/%',
		'%custom-fonts%',
		'%font_face%',
		'%font-face%',
		'%elementor-font%',
	];
	foreach ( $value_like_patterns as $pattern ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s",
				$pattern
			)
		);
		if ( empty( $names ) ) {
			continue;
		}
		foreach ( array_unique( $names ) as $name ) {
			if ( delete_option( $name ) ) {
				$log['deleted_options']++;
			}
		}
	}

	// 2c) Clear Elementor transients/cache keys that can keep stale font data around.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$transients = $wpdb->get_col(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_elementor%' OR option_name LIKE '_site_transient_elementor%'"
	);
	if ( ! empty( $transients ) ) {
		foreach ( $transients as $t ) {
			// Delete both transient and its timeout (WP stores paired options).
			if ( str_starts_with( $t, '_site_transient_' ) ) {
				$key = substr( $t, strlen( '_site_transient_' ) );
				if ( delete_site_transient( $key ) ) {
					$log['deleted_transients']++;
				}
			} elseif ( str_starts_with( $t, '_transient_' ) ) {
				$key = substr( $t, strlen( '_transient_' ) );
				if ( delete_transient( $key ) ) {
					$log['deleted_transients']++;
				}
			}
		}
	}

	// 3) Delete uploaded custom font assets, if present.
	$uploads = wp_get_upload_dir();
	$paths   = [
		trailingslashit( $uploads['basedir'] ) . 'elementor/custom-fonts',
		trailingslashit( $uploads['basedir'] ) . 'elementor/fonts',
	];

	foreach ( $paths as $dir ) {
		if ( is_dir( $dir ) ) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $files as $file ) {
				/** @var SplFileInfo $file */
				if ( $file->isDir() ) {
					@rmdir( $file->getRealPath() );
				} else {
					$log['deleted_font_files'][] = $file->getRealPath();
					@unlink( $file->getRealPath() );
				}
			}
			@rmdir( $dir );
		}
	}

	// 4) Remove generated Elementor CSS files so they rebuild cleanly.
	$css_dir = trailingslashit( $uploads['basedir'] ) . 'elementor/css';
	if ( is_dir( $css_dir ) ) {
		$css_files = glob( trailingslashit( $css_dir ) . '*.css' );
		if ( is_array( $css_files ) ) {
			foreach ( $css_files as $file ) {
				if ( @unlink( $file ) ) {
					$log['deleted_css_files']++;
				}
			}
		}
	}

	// 5) If Elementor is loaded, clear its internal cache (no core edits).
	if ( class_exists( '\\Elementor\\Plugin' ) ) {
		try {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
			$log['elementor_cache_cleared'] = true;
		} catch ( Throwable $e ) {
			// Best-effort; ignore.
		}
	}

	// Mark done (or re-done).
	update_option( $flag, time(), false );

	// Admin notice to confirm cleanup ran, and provide a rerun link.
	add_action( 'admin_notices', function () use ( $flag, $force, $log ) {
		$r = add_query_arg( 'eceens-revert-fonts', '1', admin_url() );
		?>
		<div class="notice notice-warning">
			<p><strong>Elementor custom fonts cleanup uitgevoerd.</strong></p>
			<ul style="margin:0 0 0 18px;list-style:disc;">
				<li><?php echo esc_html( 'Font posts verwijderd: ' . (int) $log['deleted_font_posts'] ); ?></li>
				<li><?php echo esc_html( 'Opties verwijderd: ' . (int) $log['deleted_options'] ); ?></li>
				<li><?php echo esc_html( 'Transients verwijderd: ' . (int) $log['deleted_transients'] ); ?></li>
				<li><?php echo esc_html( 'Elementor CSS verwijderd: ' . (int) $log['deleted_css_files'] ); ?></li>
				<li><?php echo esc_html( 'Elementor cache cleared: ' . ( $log['elementor_cache_cleared'] ? 'yes' : 'no' ) ); ?></li>
			</ul>
			<p style="margin-top:10px;">
				<a href="<?php echo esc_url( $r ); ?>">Opnieuw uitvoeren (force)</a>
				<?php if ( $force ) : ?>
					<?php echo ' — ' . esc_html( $flag . ' updated' ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}, 1 );
}, 1 );

