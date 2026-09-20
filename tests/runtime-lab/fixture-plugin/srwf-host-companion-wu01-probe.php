<?php
/**
 * Plugin Name: SRWF Host Companion WU-01 Probe
 * Description: Disposable CI-only fixture for observing native WordPress block-template registration and page-template behavior.
 * Version: 0.0.0-wu01
 * Requires at least: 6.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		register_block_template(
			'srwf-host-companion//registration-full-width',
			array(
				'title'       => 'SRWF — Registration Full Width [WU-01 Probe]',
				'description' => 'Disposable WU-01 runtime-fact probe template.',
				'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>SRWF_WU01_TEMPLATE_MARKER</p><!-- /wp:paragraph --><!-- wp:post-content /--></div><!-- /wp:group -->',
				'post_types'  => array( 'page' ),
			)
		);
	}
);
