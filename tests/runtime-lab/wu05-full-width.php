<?php
/**
 * WU-05 exact-target browser-geometry fixture setup.
 *
 * Creates synthetic public pages, persists the canonical Registration role,
 * assigns the canonical template through the product adapter, and writes the
 * pre-browser portion of the machine-readable qualification artifact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$evidence_path = getenv( 'SRWF_WU05_EVIDENCE_PATH' );
$tested_commit = getenv( 'SRWF_TESTED_COMMIT_SHA' );
$base_url      = rtrim( (string) getenv( 'SRWF_WU05_BASE_URL' ), '/' );

if ( ! $evidence_path || ! $tested_commit || ! $base_url ) {
	fwrite( STDERR, "WU-05 requires evidence path, tested commit, and base URL.\n" );
	exit( 2 );
}

/**
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function srwf_wu05_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-05 assertion failed: {$message}\n" );
		exit( 10 );
	}
}

srwf_wu05_assert( class_exists( 'SRWF\\HostCompanion\\Configuration', false ), 'Configuration class is not loaded.' );
srwf_wu05_assert( class_exists( 'SRWF\\HostCompanion\\TemplateRegistrar', false ), 'TemplateRegistrar class is not loaded.' );
srwf_wu05_assert( class_exists( 'SRWF\\HostCompanion\\PageTemplateAssignment', false ), 'PageTemplateAssignment class is not loaded.' );

$theme = wp_get_theme();
srwf_wu05_assert( 'twentytwentyfive' === $theme->get_stylesheet(), 'Twenty Twenty-Five is not active.' );

$templates = get_block_templates(
	array(
		'slug__in' => array( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG ),
	),
	'wp_template'
);

$canonical = null;
foreach ( $templates as $template ) {
	if (
		$template instanceof WP_Block_Template
		&& \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === $template->slug
		&& 'plugin' === $template->source
		&& 'plugin' === $template->origin
		&& 'srwf-host-companion' === $template->plugin
	) {
		$canonical = $template;
		break;
	}
}

srwf_wu05_assert( $canonical instanceof WP_Block_Template, 'Canonical Registration plugin template is not an eligible frontend provider.' );

$navigation_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'SRWF WU-05 Navigation Target',
		'post_content' => '<!-- wp:paragraph --><p>SRWF_WU05_NAVIGATION_TARGET</p><!-- /wp:paragraph -->',
	),
	true
);
srwf_wu05_assert( ! is_wp_error( $navigation_id ) && is_int( $navigation_id ) && $navigation_id > 0, 'Navigation fixture page was not created.' );

$registration_content = <<<'HTML'
<!-- wp:group {"className":"srwf-wu05-application-region","layout":{"type":"default"}} -->
<div class="wp-block-group srwf-wu05-application-region">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">SRWF WU-05 Synthetic Registration</h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p>SRWF_WU05_APPLICATION_MARKER</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
HTML;

$page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'SRWF WU-05 Registration',
		'post_content' => $registration_content,
	),
	true
);
srwf_wu05_assert( ! is_wp_error( $page_id ) && is_int( $page_id ) && $page_id > 0, 'Registration fixture page was not created.' );

srwf_wu05_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $page_id ), 'Schema-v1 Registration configuration was not persisted.' );
$assignment = \SRWF\HostCompanion\PageTemplateAssignment::assign( $page_id );
srwf_wu05_assert( ! empty( $assignment['success'] ), 'Canonical Registration template assignment failed.' );
srwf_wu05_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $page_id ), 'Template assignment readback mismatch.' );
srwf_wu05_assert( $page_id === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Registration configuration readback mismatch.' );

$page_url = add_query_arg( 'page_id', $page_id, $base_url . '/' );

$evidence = array(
	'schema'            => 'srwf-host-companion-wu05-full-width-v1',
	'evidence_class'    => 'DISPOSABLE_CI_REAL_BROWSER',
	'tested_commit_sha' => $tested_commit,
	'observed_at_utc'   => gmdate( 'c' ),
	'environment'       => array(
		'wordpress_version'  => get_bloginfo( 'version' ),
		'php_version'        => PHP_VERSION,
		'theme_name'         => $theme->get( 'Name' ),
		'theme_stylesheet'   => $theme->get_stylesheet(),
		'theme_version'      => $theme->get( 'Version' ),
		'locale'             => get_locale(),
		'expected_direction' => 'rtl',
	),
	'fixture'           => array(
		'registration_page_id' => $page_id,
		'navigation_page_id'   => $navigation_id,
		'page_url'             => $page_url,
		'synthetic_data_only'  => true,
	),
	'canonical_template' => array(
		'api_identity'       => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME,
		'assignment_slug'    => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG,
		'provider_id'        => $canonical->id,
		'provider_source'    => $canonical->source,
		'provider_origin'    => $canonical->origin,
		'provider_plugin'    => $canonical->plugin,
		'registered'         => true,
		'assigned_readback'  => \SRWF\HostCompanion\PageTemplateAssignment::read( $page_id ),
		'render_marker'      => 'srwf-host-companion-registration-shell',
	),
	'layout_hypothesis' => array(
		'name'        => 'H1',
		'mechanism'   => 'NATIVE_BLOCK_LAYOUT_ALIGNFULL_PLUS_LOCAL_100_PERCENT_LAYOUT',
		'product_css' => false,
		'product_js'  => false,
		'status'      => 'PENDING_BROWSER_FALSIFICATION',
	),
	'dependencies'      => array(
		'gravity_forms'   => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
		'orbital'         => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
		'gtb'             => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
		'vazir_vazirmatn' => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
	),
	'viewports'         => array(),
	'claims'            => array(
		'canonical_assignment_render'           => 'PENDING_BROWSER',
		'full_width_geometry'                   => 'PENDING_BROWSER',
		'horizontal_overflow'                   => 'PENDING_BROWSER',
		'header_footer_navigation'              => 'PENDING_BROWSER',
		'rtl_frontend'                          => 'PENDING_BROWSER',
		'wu06_admin_rtl_accessibility_security' => 'NOT_RUN',
		'wu07_owner_browser_e2e_comprehension'  => 'NOT_RUN',
		'production_host_qualification'         => 'NOT_PROVEN',
	),
	'overall_status'    => 'PENDING_BROWSER',
);

$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu05_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Unable to write WU-05 evidence.' );

printf( "%d\n", $page_id );
