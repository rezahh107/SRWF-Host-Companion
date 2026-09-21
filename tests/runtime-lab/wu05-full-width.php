<?php
/**
 * WU-05 exact-target browser-geometry fixture setup.
 *
 * Creates synthetic Registration and Inbox pages, persists both bounded roles,
 * assigns the same canonical Full Width template through the product adapter,
 * and writes the pre-browser machine-readable qualification artifact.
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
		'post_content' => '<!-- wp:paragraph --><p>SRWF WU-05 navigation target</p><!-- /wp:paragraph -->',
	),
	true
);
srwf_wu05_assert( ! is_wp_error( $navigation_id ) && is_int( $navigation_id ) && $navigation_id > 0, 'Navigation fixture page was not created.' );

function srwf_wu05_create_host_page( $title, $marker, $class_name ) {
	$content = sprintf(
		'<!-- wp:group {"className":"%1$s","layout":{"type":"default"}} --><div class="wp-block-group %1$s"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">%2$s</h1><!-- /wp:heading --><!-- wp:paragraph --><p data-srwf-wu05-application-marker="%3$s">Synthetic application content for host-canvas geometry qualification.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
		esc_attr( $class_name ),
		esc_html( $title ),
		esc_attr( $marker )
	);

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
		),
		true
	);

	srwf_wu05_assert( ! is_wp_error( $page_id ) && is_int( $page_id ) && $page_id > 0, $title . ' fixture page was not created.' );
	return (int) $page_id;
}

$registration_page_id = srwf_wu05_create_host_page( 'SRWF WU-05 Registration', 'registration', 'srwf-wu05-application-region' );
$inbox_page_id        = srwf_wu05_create_host_page( 'SRWF WU-05 Inbox', 'inbox', 'srwf-wu05-inbox-application-region' );

srwf_wu05_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $registration_page_id ), 'Registration configuration was not persisted.' );
$registration_assignment = \SRWF\HostCompanion\PageTemplateAssignment::assign( $registration_page_id );
srwf_wu05_assert( ! empty( $registration_assignment['success'] ), 'Canonical Registration template assignment failed.' );
srwf_wu05_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $registration_page_id ), 'Registration assignment readback mismatch.' );

srwf_wu05_assert( \SRWF\HostCompanion\Configuration::set_inbox_page_id( $inbox_page_id ), 'Inbox configuration was not persisted.' );
$inbox_assignment = \SRWF\HostCompanion\PageTemplateAssignment::assign( $inbox_page_id );
srwf_wu05_assert( ! empty( $inbox_assignment['success'] ), 'Canonical Inbox template assignment failed.' );
srwf_wu05_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $inbox_page_id ), 'Inbox assignment readback mismatch.' );
srwf_wu05_assert( $registration_page_id === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Inbox configuration changed Registration mapping.' );
srwf_wu05_assert( $inbox_page_id === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Inbox configuration readback mismatch.' );

$registration_page_url = add_query_arg( 'page_id', $registration_page_id, $base_url . '/' );
$inbox_page_url        = add_query_arg( 'page_id', $inbox_page_id, $base_url . '/' );

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
		'registration_page_id' => $registration_page_id,
		'inbox_page_id'        => $inbox_page_id,
		'navigation_page_id'   => $navigation_id,
		'page_url'             => $registration_page_url,
		'registration_page_url'=> $registration_page_url,
		'inbox_page_url'       => $inbox_page_url,
		'synthetic_data_only'  => true,
	),
	'canonical_template' => array(
		'api_identity'                    => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME,
		'assignment_slug'                 => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG,
		'provider_id'                     => $canonical->id,
		'provider_source'                 => $canonical->source,
		'provider_origin'                 => $canonical->origin,
		'provider_plugin'                 => $canonical->plugin,
		'registered'                      => true,
		'assigned_readback'               => \SRWF\HostCompanion\PageTemplateAssignment::read( $registration_page_id ),
		'assigned_readback_registration'  => \SRWF\HostCompanion\PageTemplateAssignment::read( $registration_page_id ),
		'assigned_readback_inbox'         => \SRWF\HostCompanion\PageTemplateAssignment::read( $inbox_page_id ),
		'render_marker'                   => 'srwf-host-companion-registration-shell',
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
		'gravity_flow'    => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
		'gpp'             => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
		'orbital'         => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
		'gtb'             => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
		'vazir_vazirmatn' => array( 'availability' => 'ENVIRONMENT_UNAVAILABLE', 'claim' => 'NOT_PROVEN' ),
	),
	'viewports'         => array(),
	'inbox_viewports'   => array(),
	'claims'            => array(
		'canonical_assignment_render'           => 'PENDING_BROWSER',
		'full_width_geometry'                   => 'PENDING_BROWSER',
		'horizontal_overflow'                   => 'PENDING_BROWSER',
		'header_footer_navigation'              => 'PENDING_BROWSER',
		'rtl_frontend'                          => 'PENDING_BROWSER',
		'inbox_canonical_assignment_render'     => 'PENDING_BROWSER',
		'inbox_full_width_geometry'             => 'PENDING_BROWSER',
		'inbox_horizontal_overflow'             => 'PENDING_BROWSER',
		'inbox_header_footer_navigation'        => 'PENDING_BROWSER',
		'inbox_rtl_frontend'                    => 'PENDING_BROWSER',
		'real_gravity_flow_inbox_integration'   => 'NOT_PROVEN',
		'gpp_integration'                       => 'NOT_PROVEN',
		'wu06_admin_rtl_accessibility_security' => 'NOT_RUN',
		'wu07_owner_browser_e2e_comprehension'  => 'NOT_RUN',
		'human_comprehension'                   => 'NOT_PROVEN',
		'production_host_confirmation'          => 'NOT_PROVEN',
		'production_host_qualification'         => 'NOT_PROVEN',
	),
	'overall_status'    => 'PENDING_BROWSER',
);

$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu05_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Unable to write WU-05 evidence.' );

printf( "%d\n", $registration_page_id );
