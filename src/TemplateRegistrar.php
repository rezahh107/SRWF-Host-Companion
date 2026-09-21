<?php

namespace SRWF\HostCompanion;

final class TemplateRegistrar {
	const TEMPLATE_NAME    = 'srwf-host-companion//registration-full-width';
	const TEMPLATE_SLUG    = 'registration-full-width';
	const TEMPLATE_VERSION = 1;
	const STYLE_HANDLE     = 'srwf-host-companion-registration-canvas';

	/**
	 * Attach canonical template registration and its surface-scoped canvas style
	 * to native WordPress lifecycle points.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_registration_canvas_style' ) );
	}

	/**
	 * Return the repository-owned canonical Registration template markup.
	 *
	 * @return string|false
	 */
	public static function get_canonical_content() {
		return file_get_contents( dirname( __DIR__ ) . '/templates/registration-full-width.html' );
	}

	/**
	 * Register the canonical Registration template.
	 *
	 * @return \WP_Block_Template|\WP_Error|null
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_template' ) ) {
			return null;
		}

		$content = self::get_canonical_content();

		if ( false === $content ) {
			return new \WP_Error(
				'srwf_host_companion_template_unreadable',
				__( 'The SRWF Registration template could not be loaded.', 'srwf-host-companion' )
			);
		}

		return register_block_template(
			self::TEMPLATE_NAME,
			array(
				'title'       => __( 'SRWF — Registration Full Width', 'srwf-host-companion' ),
				'description' => __( 'Canonical host template for the SRWF Registration page.', 'srwf-host-companion' ),
				'content'     => $content,
				'post_types'  => array( 'page' ),
			)
		);
	}

	/**
	 * Load the host-canvas gutter only on frontend pages that actually use the
	 * canonical Registration template.
	 *
	 * @return void
	 */
	public static function enqueue_registration_canvas_style() {
		if ( is_admin() || ! is_singular( 'page' ) ) {
			return;
		}

		$page_id = get_queried_object_id();
		if ( ! is_int( $page_id ) || $page_id <= 0 ) {
			return;
		}

		if ( self::TEMPLATE_SLUG !== get_page_template_slug( $page_id ) ) {
			return;
		}

		$plugin_file = dirname( __DIR__ ) . '/srwf-host-companion.php';
		$version     = defined( 'SRWF_HOST_COMPANION_VERSION' ) ? SRWF_HOST_COMPANION_VERSION : (string) self::TEMPLATE_VERSION;

		wp_enqueue_style(
			self::STYLE_HANDLE,
			plugins_url( 'assets/css/registration-canvas.css', $plugin_file ),
			array(),
			$version
		);
	}
}
