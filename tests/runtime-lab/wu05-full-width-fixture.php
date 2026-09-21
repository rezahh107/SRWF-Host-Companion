<?php

use SRWF\HostCompanion\Configuration;
use SRWF\HostCompanion\PageTemplateAssignment;
use SRWF\HostCompanion\TemplateRegistrar;

$evidence_path = getenv( 'SRWF_WU05_FIXTURE_EVIDENCE_PATH' );
if ( ! is_string( $evidence_path ) || '' === $evidence_path ) {
	throw new RuntimeException( 'SRWF_WU05_FIXTURE_EVIDENCE_PATH is required.' );
}

$page_content = <<<'HTML'
<!-- wp:group {"className":"srwf-wu05-application-region"} -->
<div class="wp-block-group srwf-wu05-application-region">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">SRWF WU-05 Synthetic Registration Fixture</h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p>SRWF_WU05_APPLICATION_CONTENT_MARKER</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
HTML;

$page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'SRWF WU-05 Registration Fixture',
		'post_name'    => 'srwf-wu05-registration-fixture',
		'post_content' => $page_content,
	),
	true
);

if ( is_wp_error( $page_id ) ) {
	throw new RuntimeException( 'Could not create WU-05 fixture page: ' . $page_id->get_error_code() );
}

$page_id = (int) $page_id;

if ( ! Configuration::set_registration_page_id( $page_id ) ) {
	throw new RuntimeException( 'Could not persist WU-05 registration fixture configuration.' );
}

$assignment = PageTemplateAssignment::assign( $page_id );
if ( empty( $assignment['success'] ) ) {
	throw new RuntimeException( 'Could not assign canonical WU-05 template: ' . ( $assignment['code'] ?? 'unknown' ) );
}

$template = get_block_template( TemplateRegistrar::TEMPLATE_NAME, 'wp_template' );
if ( ! $template instanceof WP_Block_Template ) {
	throw new RuntimeException( 'Canonical WU-05 template did not resolve from WordPress.' );
}

$evidence = array(
	'schema'            => 'srwf-host-companion-wu05-fixture-v1',
	'page_id'           => $page_id,
	'page_url'          => get_permalink( $page_id ),
	'configuration'     => Configuration::get(),
	'assignment'        => $assignment,
	'assignment_readback' => PageTemplateAssignment::read( $page_id ),
	'template'          => array(
		'name'   => $template->slug,
		'source' => $template->source,
		'origin' => $template->origin,
		'plugin' => $template->plugin,
	),
	'environment'       => array(
		'wordpress_version' => get_bloginfo( 'version' ),
		'php_version'       => PHP_VERSION,
		'theme_stylesheet'  => get_stylesheet(),
		'theme_version'     => wp_get_theme()->get( 'Version' ),
		'language'          => get_locale(),
		'is_rtl'            => is_rtl(),
	),
);

if ( false === file_put_contents( $evidence_path, wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" ) ) {
	throw new RuntimeException( 'Could not write WU-05 fixture evidence.' );
}

echo $evidence['page_url'] . "\n";
