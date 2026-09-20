<?php

namespace SRWF\HostCompanion;

final class TemplateRegistrar {
	const TEMPLATE_NAME    = 'srwf-host-companion//registration-full-width';
	const TEMPLATE_SLUG    = 'registration-full-width';
	const TEMPLATE_VERSION = 1;

	/**
	 * Attach canonical template registration to the native WordPress lifecycle.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register' ) );
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

		$content = file_get_contents( dirname( __DIR__ ) . '/templates/registration-full-width.html' );

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
}
