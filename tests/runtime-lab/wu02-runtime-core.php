<?php
/**
 * WU-02 exact-target disposable integration probe.
 *
 * Run through WP-CLI eval-file with SRWF_WU02_PHASE=active or inactive.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$phase         = getenv( 'SRWF_WU02_PHASE' ) ?: 'active';
$evidence_path = getenv( 'SRWF_WU02_EVIDENCE_PATH' );
$unrelated_id  = (int) getenv( 'SRWF_WU02_UNRELATED_PAGE_ID' );
$legacy_id     = (int) getenv( 'SRWF_WU02_LEGACY_PAGE_ID' );
$page_marker   = 'SRWF_WU02_PAGE_CONTENT_MARKER';
$template_slug = 'registration-full-width';
$template_name = 'srwf-host-companion//registration-full-width';

if ( ! $evidence_path ) {
	fwrite( STDERR, "SRWF_WU02_EVIDENCE_PATH is required.\n" );
	exit( 2 );
}

function srwf_wu02_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-02 assertion failed: {$message}\n" );
		exit( 10 );
	}
}

function srwf_wu02_read_evidence( $path ) {
	if ( ! file_exists( $path ) ) {
		return array();
	}

	$decoded = json_decode( (string) file_get_contents( $path ), true );
	return is_array( $decoded ) ? $decoded : array();
}

function srwf_wu02_write_evidence( $path, $evidence ) {
	$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $encoded || false === file_put_contents( $path, $encoded . "\n" ) ) {
		fwrite( STDERR, "Unable to write WU-02 evidence.\n" );
		exit( 11 );
	}
}

$evidence = srwf_wu02_read_evidence( $evidence_path );

if ( 'active' === $phase ) {
	srwf_wu02_assert( class_exists( 'SRWF\\HostCompanion\\Configuration', false ), 'Configuration class was not loaded by the plugin.' );
	srwf_wu02_assert( class_exists( 'SRWF\\HostCompanion\\TemplateRegistrar', false ), 'TemplateRegistrar class was not loaded by the plugin.' );
	srwf_wu02_assert( class_exists( 'SRWF\\HostCompanion\\PageTemplateAssignment', false ), 'PageTemplateAssignment class was not loaded by the plugin.' );

	$theme = wp_get_theme();
	$evidence = array(
		'schema'          => 'srwf-host-companion-wu02-runtime-core-v2',
		'evidence_class'  => 'DISPOSABLE_CI_RUNTIME_LAB',
		'observed_at_utc' => gmdate( 'c' ),
		'environment'     => array(
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
			'theme_stylesheet'  => get_stylesheet(),
			'theme_name'        => $theme->get( 'Name' ),
			'theme_version'     => $theme->get( 'Version' ),
		),
		'claims'          => array(),
	);

	srwf_wu02_assert( $unrelated_id > 0 && $legacy_id > 0, 'Pre-activation sentinel pages are unavailable.' );
	$unrelated_before = get_page_template_slug( $unrelated_id );
	$legacy_before    = get_post_meta( $legacy_id, '_wp_page_template', true );
	srwf_wu02_assert( '' === $unrelated_before, 'Unrelated page changed during plugin load/registration.' );
	srwf_wu02_assert( 'legacy-existing-template' === $legacy_before, 'Legacy page assignment changed during plugin load/registration.' );

	$expected_default = array(
		'schema_version' => 2,
		'roles'          => array(
			'registration' => array( 'page_id' => 0 ),
			'inbox'        => array( 'page_id' => 0 ),
		),
	);

	delete_option( \SRWF\HostCompanion\Configuration::OPTION_NAME );
	srwf_wu02_assert( $expected_default === \SRWF\HostCompanion\Configuration::get(), 'Absent configuration did not normalize to schema v2 defaults.' );
	srwf_wu02_assert( null === get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ), 'Configuration read wrote persistent state.' );
	srwf_wu02_assert( 0 === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Absent Registration page ID did not normalize to zero.' );
	srwf_wu02_assert( 0 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Absent Inbox page ID did not normalize to zero.' );

	$legacy_v1 = array(
		'schema_version' => 1,
		'roles'          => array(
			'registration' => array( 'page_id' => 456 ),
		),
	);
	update_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, $legacy_v1, false );
	$legacy_normalized = \SRWF\HostCompanion\Configuration::get();
	srwf_wu02_assert( 2 === $legacy_normalized['schema_version'], 'Valid schema v1 did not normalize in memory to schema v2.' );
	srwf_wu02_assert( 456 === $legacy_normalized['roles']['registration']['page_id'], 'Schema-v1 Registration page was not preserved during normalization.' );
	srwf_wu02_assert( 0 === $legacy_normalized['roles']['inbox']['page_id'], 'Schema-v1 Inbox default was not zero.' );
	srwf_wu02_assert( $legacy_v1 === get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ), 'Reading schema v1 persisted a migration.' );

	srwf_wu02_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( 321 ), 'Registration setter failed on legacy state.' );
	$legacy_registration_write = get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null );
	srwf_wu02_assert( 1 === $legacy_registration_write['schema_version'], 'Registration-only legacy write performed an unnecessary schema migration.' );
	srwf_wu02_assert( 321 === $legacy_registration_write['roles']['registration']['page_id'], 'Registration-only legacy write did not persist the page ID.' );
	srwf_wu02_assert( 0 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Registration-only write changed Inbox default.' );

	srwf_wu02_assert( \SRWF\HostCompanion\Configuration::set_inbox_page_id( 654 ), 'First explicit Inbox persistence failed.' );
	$round_trip = \SRWF\HostCompanion\Configuration::get();
	$stored_v2  = get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null );
	srwf_wu02_assert( 2 === $stored_v2['schema_version'], 'First explicit Inbox persistence did not upgrade storage to schema v2.' );
	srwf_wu02_assert( $stored_v2 === $round_trip, 'Persisted schema v2 did not read back canonically.' );
	srwf_wu02_assert( 321 === $round_trip['roles']['registration']['page_id'], 'Inbox setter did not preserve Registration.' );
	srwf_wu02_assert( 654 === $round_trip['roles']['inbox']['page_id'], 'Inbox page ID did not round-trip.' );
	srwf_wu02_assert( array( 'registration', 'inbox' ) === array_keys( $round_trip['roles'] ), 'Schema v2 role cardinality changed unexpectedly.' );
	srwf_wu02_assert( array( 'page_id' ) === array_keys( $round_trip['roles']['registration'] ), 'Registration cardinality is not exactly one page_id.' );
	srwf_wu02_assert( array( 'page_id' ) === array_keys( $round_trip['roles']['inbox'] ), 'Inbox cardinality is not exactly one page_id.' );

	srwf_wu02_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( 777 ), 'Schema-v2 Registration setter failed.' );
	srwf_wu02_assert( 777 === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Schema-v2 Registration setter did not persist.' );
	srwf_wu02_assert( 654 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Registration setter did not preserve Inbox.' );
	srwf_wu02_assert( \SRWF\HostCompanion\Configuration::set_inbox_page_id( 888 ), 'Schema-v2 Inbox setter failed.' );
	srwf_wu02_assert( 777 === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Inbox setter did not preserve Registration.' );
	srwf_wu02_assert( 888 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Schema-v2 Inbox setter did not persist.' );

	update_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, 'malformed', false );
	srwf_wu02_assert( $expected_default === \SRWF\HostCompanion\Configuration::get(), 'Scalar malformed configuration became truth.' );
	update_option(
		\SRWF\HostCompanion\Configuration::OPTION_NAME,
		array( 'schema_version' => 99, 'roles' => array( 'registration' => array( 'page_id' => 77 ), 'inbox' => array( 'page_id' => 88 ) ) ),
		false
	);
	srwf_wu02_assert( $expected_default === \SRWF\HostCompanion\Configuration::get(), 'Unsupported schema version became truth.' );
	update_option(
		\SRWF\HostCompanion\Configuration::OPTION_NAME,
		array( 'schema_version' => 2, 'roles' => array( 'registration' => array( 'page_id' => 77 ) ) ),
		false
	);
	srwf_wu02_assert( $expected_default === \SRWF\HostCompanion\Configuration::get(), 'Malformed schema v2 missing Inbox became truth.' );
	update_option(
		\SRWF\HostCompanion\Configuration::OPTION_NAME,
		array( 'schema_version' => 1, 'roles' => array( 'registration' => array( 'page_id' => array( 1, 2 ) ) ) ),
		false
	);
	srwf_wu02_assert( $expected_default === \SRWF\HostCompanion\Configuration::get(), 'Multi-page malformed Registration value became truth.' );
	update_option(
		\SRWF\HostCompanion\Configuration::OPTION_NAME,
		array(
			'schema_version' => 1,
			'roles'          => array(
				'registration' => array( 'page_id' => 654, 'page_ids' => array( 654, 655 ) ),
			),
			'unexpected'     => 'ignored',
		),
		false
	);
	$normalized_extra = \SRWF\HostCompanion\Configuration::get();
	srwf_wu02_assert( 654 === $normalized_extra['roles']['registration']['page_id'], 'Valid canonical page_id was not preserved.' );
	srwf_wu02_assert( 0 === $normalized_extra['roles']['inbox']['page_id'], 'Schema-v1 Inbox default changed during normalization.' );
	srwf_wu02_assert( array( 'page_id' ) === array_keys( $normalized_extra['roles']['registration'] ), 'Unexpected multi-page key leaked into canonical truth.' );
	srwf_wu02_assert( ! isset( $normalized_extra['unexpected'] ), 'Unexpected top-level key leaked into canonical truth.' );

	srwf_wu02_assert( \SRWF\HostCompanion\Configuration::set_inbox_page_id( 888 ), 'Could not restore a valid schema-v2 Inbox sentinel after malformed-state probes.' );
	srwf_wu02_assert( 654 === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Restoring the Inbox sentinel did not preserve the normalized Registration page.' );
	srwf_wu02_assert( 888 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Restored Inbox sentinel did not persist.' );

	\SRWF\HostCompanion\Configuration::get();
	srwf_wu02_assert( '' === get_page_template_slug( $unrelated_id ), 'Configuration read assigned an unrelated page.' );
	srwf_wu02_assert( 'legacy-existing-template' === get_post_meta( $legacy_id, '_wp_page_template', true ), 'Configuration read rewrote a legacy page.' );

	$header_part = get_block_template( get_stylesheet() . '//header', 'wp_template_part' );
	$footer_part = get_block_template( get_stylesheet() . '//footer', 'wp_template_part' );
	srwf_wu02_assert( $header_part instanceof WP_Block_Template, 'TT25 header template part did not resolve on the pinned target tuple.' );
	srwf_wu02_assert( $footer_part instanceof WP_Block_Template, 'TT25 footer template part did not resolve on the pinned target tuple.' );

	$registered = get_block_templates( array( 'post_type' => 'page' ), 'wp_template' );
	$matched    = null;
	foreach ( $registered as $candidate ) {
		if ( $template_slug === $candidate->slug && 'SRWF — Registration Full Width' === $candidate->title ) {
			$matched = $candidate;
			break;
		}
	}
	srwf_wu02_assert( $matched instanceof WP_Block_Template, 'Canonical Registration template is not registered for page.' );
	srwf_wu02_assert( $template_name === \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME, 'Registration API identity changed.' );
	srwf_wu02_assert( in_array( 'page', (array) $matched->post_types, true ), 'Canonical Registration template is not eligible for page.' );

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'SRWF WU-02 Synthetic Runtime Probe',
			'post_content' => '<!-- wp:paragraph --><p>' . $page_marker . '</p><!-- /wp:paragraph -->',
		),
		true
	);
	srwf_wu02_assert( ! is_wp_error( $page_id ) && $page_id > 0, 'Synthetic page could not be created.' );
	$page = get_post( $page_id );
	$page_templates = wp_get_theme()->get_page_templates( $page );
	srwf_wu02_assert( isset( $page_templates[ $template_slug ] ), 'Page-template map does not expose the proven assignment slug.' );
	srwf_wu02_assert( 'SRWF — Registration Full Width' === $page_templates[ $template_slug ], 'Page-template map title does not match canonical template.' );

	$non_page_id = wp_insert_post(
		array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_title'  => 'SRWF WU-02 Non-page Probe',
		),
		true
	);
	srwf_wu02_assert( ! is_wp_error( $non_page_id ) && $non_page_id > 0, 'Synthetic non-page target could not be created.' );
	$invalid_result  = \SRWF\HostCompanion\PageTemplateAssignment::assign( 0 );
	$non_page_result = \SRWF\HostCompanion\PageTemplateAssignment::assign( (int) $non_page_id );
	srwf_wu02_assert( false === $invalid_result['success'] && 'invalid_page_id' === $invalid_result['code'], 'Invalid page ID silently succeeded.' );
	srwf_wu02_assert( false === $non_page_result['success'] && 'target_not_page' === $non_page_result['code'], 'Non-page target silently succeeded.' );
	srwf_wu02_assert( '' === get_post_meta( $non_page_id, '_wp_page_template', true ), 'Non-page target was mutated.' );

	$assignment = \SRWF\HostCompanion\PageTemplateAssignment::assign( (int) $page_id );
	srwf_wu02_assert( true === $assignment['success'] && 'assigned' === $assignment['code'], 'Canonical assignment adapter failed.' );
	srwf_wu02_assert( $template_slug === $assignment['actual_slug'], 'Assignment adapter readback mismatch.' );
	srwf_wu02_assert( $template_slug === get_post_meta( $page_id, '_wp_page_template', true ), 'Persisted _wp_page_template does not match proven slug.' );
	srwf_wu02_assert( $template_slug === get_page_template_slug( $page_id ), 'get_page_template_slug() does not match proven slug.' );
	srwf_wu02_assert( $template_slug === \SRWF\HostCompanion\PageTemplateAssignment::read( (int) $page_id ), 'Adapter read() does not match proven slug.' );

	$resolved_id = get_stylesheet() . '//' . $template_slug;
	$resolved    = get_block_template( $resolved_id, 'wp_template' );
	srwf_wu02_assert( $resolved instanceof WP_Block_Template, 'Assigned plugin template did not resolve while active.' );
	srwf_wu02_assert( $template_slug === $resolved->slug, 'Resolved active template slug mismatch.' );
	srwf_wu02_assert( 'srwf-host-companion' === $resolved->plugin, 'Resolved active template is not owned by SRWF Host Companion.' );

	srwf_wu02_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $page_id ), 'Canonical Registration mapping did not persist.' );
	srwf_wu02_assert( 888 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Final Registration mapping rewrote the configured Inbox role.' );

	$evidence['bootstrap'] = array(
		'plugin_active' => is_plugin_active( 'srwf-host-companion/srwf-host-companion.php' ),
		'classes_loaded'=> true,
	);
	$evidence['configuration'] = array(
		'option_name'       => \SRWF\HostCompanion\Configuration::OPTION_NAME,
		'normalized_config' => \SRWF\HostCompanion\Configuration::get(),
	);
	$evidence['template'] = array(
		'registration_identity' => $template_name,
		'assignment_slug'       => $template_slug,
		'registered_for_page'   => true,
		'resolved_id'           => $resolved_id,
		'header_part_resolved'  => true,
		'footer_part_resolved'  => true,
	);
	$evidence['page'] = array(
		'id'  => (int) $page_id,
		'url' => get_permalink( $page_id ),
	);
	$evidence['assignment'] = $assignment;
	$evidence['sentinels'] = array(
		'unrelated_page_id' => $unrelated_id,
		'legacy_page_id'    => $legacy_id,
	);
	$evidence['claims']['bootstrap_runtime_proven']              = true;
	$evidence['claims']['config_runtime_proven']                 = true;
	$evidence['claims']['schema_v1_read_without_mutation']       = true;
	$evidence['claims']['inbox_explicit_schema_v2_upgrade']      = true;
	$evidence['claims']['role_setters_preserve_other_role']      = true;
	$evidence['claims']['malformed_config_fails_closed']         = true;
	$evidence['claims']['template_registration_runtime_proven']  = true;
	$evidence['claims']['assignment_runtime_proven']             = true;
	$evidence['claims']['no_hidden_mutation_runtime_proven']     = true;
	$evidence['claims']['active_render_runtime_proven']          = false;
	$evidence['claims']['deactivation_runtime_proven']           = false;

	srwf_wu02_write_evidence( $evidence_path, $evidence );
	fwrite( STDOUT, (string) $page_id . "\n" );
	exit( 0 );
}

if ( 'inactive' === $phase ) {
	$page_id = isset( $evidence['page']['id'] ) ? (int) $evidence['page']['id'] : 0;
	srwf_wu02_assert( $page_id > 0 && get_post( $page_id ), 'Assigned synthetic page is unavailable after deactivation.' );
	srwf_wu02_assert( ! is_plugin_active( 'srwf-host-companion/srwf-host-companion.php' ), 'Product plugin is still active.' );
	srwf_wu02_assert( $template_slug === get_post_meta( $page_id, '_wp_page_template', true ), 'Persisted assignment changed after deactivation.' );
	srwf_wu02_assert( $template_slug === get_page_template_slug( $page_id ), 'Page-template readback changed after deactivation.' );
	$resolved = get_block_template( get_stylesheet() . '//' . $template_slug, 'wp_template' );
	srwf_wu02_assert( null === $resolved, 'Plugin template still resolves after deactivation.' );
	srwf_wu02_assert( '' === get_page_template_slug( $unrelated_id ), 'Unrelated page changed after plugin deactivation.' );
	srwf_wu02_assert( 'legacy-existing-template' === get_post_meta( $legacy_id, '_wp_page_template', true ), 'Legacy page was rewritten by plugin lifecycle.' );

	$evidence['after_deactivation'] = array(
		'plugin_active'             => false,
		'raw_wp_page_template_meta'=> get_post_meta( $page_id, '_wp_page_template', true ),
		'get_page_template_slug'   => get_page_template_slug( $page_id ),
		'resolved_template'        => null,
	);
	srwf_wu02_write_evidence( $evidence_path, $evidence );
	exit( 0 );
}

fwrite( STDERR, "Unknown SRWF_WU02_PHASE.\n" );
exit( 3 );
