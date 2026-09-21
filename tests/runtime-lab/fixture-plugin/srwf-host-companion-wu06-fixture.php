<?php
/**
 * WU-06 qualification-only runtime fixture hooks.
 *
 * Installed only in the disposable CI lab. It never ships with the product.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function srwf_wu06_fixture_mode() {
	return (string) get_option( 'srwf_wu06_fixture_mode', '' );
}

function srwf_wu06_unknown_candidates( $templates, $query, $template_type ) {
	if ( 'unknown' !== srwf_wu06_fixture_mode() ) {
		return $templates;
	}

	if ( 'wp_template' !== $template_type || ! isset( $query['slug__in'] ) || ! is_array( $query['slug__in'] ) ) {
		return $templates;
	}

	if ( ! in_array( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG, $query['slug__in'], true ) ) {
		return $templates;
	}

	$object                 = new WP_Block_Template();
	$object->id             = get_stylesheet() . '//' . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG;
	$object->theme          = get_stylesheet();
	$object->slug           = \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG;
	$object->type           = 'wp_template';
	$object->title          = 'SRWF WU-06 Unknown Candidate';
	$object->content        = (string) \SRWF\HostCompanion\TemplateRegistrar::get_canonical_content();
	$object->source         = 'external-wu06-fixture';
	$object->origin         = null;
	$object->plugin         = null;
	$object->wp_id          = null;
	$object->status         = 'publish';
	$object->has_theme_file = false;
	$object->is_custom      = true;
	$object->post_types     = array( 'page' );

	return array( $object );
}

add_action(
	'init',
	static function () {
		if ( ! class_exists( 'SRWF\\HostCompanion\\TemplateRegistrar', false ) ) {
			return;
		}

		$mode = srwf_wu06_fixture_mode();

		if ( 'missing_template' === $mode && function_exists( 'unregister_block_template' ) ) {
			unregister_block_template( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME );
		}

		if ( 'unknown' === $mode ) {
			add_filter( 'pre_get_block_templates', 'srwf_wu06_unknown_candidates', 10, 3 );
		}
	},
	20
);
