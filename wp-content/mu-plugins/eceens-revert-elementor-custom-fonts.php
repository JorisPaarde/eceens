<?php
/**
 * Plugin Name: Eceens - Revert Elementor Custom Fonts (one-time)
 * Description: One-time cleanup of Elementor custom fonts (DB + uploaded assets) to recover a broken editor after a custom font upload.
 * Author: Eceens
 * Version: 1.0.0
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
	if ( get_option( $flag ) ) {
		return;
	}

	// Elementor not active/loaded: still allow cleanup of artifacts/options.
	global $wpdb;

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
			}
		}
	}

	// 2) Delete likely option keys used by Elementor's Fonts Manager / Custom Fonts.
	$like_patterns = [
		'elementor%font%',
		'elementor%fonts%',
		'elementor_custom_fonts',
		'elementor_fonts_manager_font_groups',
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
		delete_option( $name );
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
				@unlink( $file );
			}
		}
	}

	// Mark done.
	update_option( $flag, time(), false );
}, 1 );

