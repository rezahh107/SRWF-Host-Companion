<?php
/**
 * WU-04 theme-source falsification probe.
 * Runs in its own WP-CLI process so the synthetic theme file exists before
 * this request's first get_block_templates() theme-path scan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$evidence_path = getenv( 'SRWF_WU04_THEME_EVIDENCE_PATH' );
$tested_sha    = getenv( 'SRWF_TESTED_COMMIT_SHA' );
if ( ! $evidence_path || ! $tested_sha ) {
	fwrite( STDERR, "SRWF_WU04_THEME_EVIDENCE_PATH and SRWF_TESTED_COMMIT_SHA are required.\n" );
	exit( 2 );
}

function srwf_wu04_theme_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-04 theme assertion failed: {$message}\n" );
		exit( 10 );
	}
}

function srwf_wu04_theme_create_page( $marker ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'SRWF WU-04 Theme Falsification',
			'post_content' => '<!-- wp:paragraph --><p>' . $marker . '</p><!-- /wp:paragraph -->',
		),
		true
	);
	srwf_wu04_theme_assert( ! is_wp_error( $id ) && $id > 0, 'Theme falsification page could not be created.' );
	return (int) $id;
}

function srwf_wu04_theme_snapshot( $page_id, $theme_file ) {
	$post = get_post( $page_id );
	clearstatcache( true, $theme_file );
	return array(
		'configuration_raw' => get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ),
		'page' => $post instanceof WP_Post ? array(
			'ID'                 => (int) $post->ID,
			'post_status'        => (string) $post->post_status,
			'post_content'       => (string) $post->post_content,
			'page_template_meta' => (string) get_post_meta( $post->ID, '_wp_page_template', true ),
			'page_template_slug' => (string) get_page_template_slug( $post->ID ),
		) : null,
		'theme_file' => array(
			'exists' => file_exists( $theme_file ),
			'hash'   => file_exists( $theme_file ) ? hash_file( 'sha256', $theme_file ) : '',
		),
	);
}

function srwf_wu04_theme_render_check_again() {
	$_GET = array( 'page' => \SRWF\HostCompanion\AdminSettings::PAGE_SLUG, 'srwf_check' => '1' );
	ob_start();
	\SRWF\HostCompanion\AdminSettings::render_page();
	$html = (string) ob_get_clean();
	$_GET = array();
	return $html;
}

function srwf_wu04_theme_reduce( $diagnostics ) {
	$evidence    = $diagnostics['resolution']['evidence'];
	$provider    = $evidence['provider'];
	$comparison  = $evidence['source_comparison'];
	$transformed = $evidence['transformed_resolved'];
	return array(
		'provider' => array(
			'observation'     => $provider['observation'] ?? '',
			'evidence_status' => $provider['evidence_status'] ?? 'NOT_PROVEN',
			'source'          => $provider['source'] ?? '',
			'origin'          => $provider['origin'] ?? '',
			'plugin'          => $provider['plugin'] ?? '',
			'wp_id'           => $provider['wp_id'] ?? 0,
			'template_status' => $provider['template_status'] ?? '',
			'has_theme_file'  => $provider['has_theme_file'] ?? false,
		),
		'source_comparison' => array(
			'status'                    => $comparison['status'] ?? 'NOT_PROVEN',
			'basis'                     => $comparison['basis'] ?? '',
			'reason'                    => $comparison['reason'] ?? '',
			'source_identity'           => $comparison['source_identity'] ?? '',
			'canonical_fingerprint'     => $comparison['canonical_fingerprint'] ?? '',
			'source_fingerprint'        => $comparison['source_fingerprint'] ?? '',
			'content_matches_canonical' => $comparison['content_matches_canonical'] ?? null,
		),
		'transformed_resolved' => array(
			'status'                => $transformed['status'] ?? 'NOT_PROVEN',
			'basis'                 => $transformed['basis'] ?? '',
			'transformation_status' => $transformed['transformation_status'] ?? 'NOT_PROVEN',
			'fingerprint'           => $transformed['fingerprint'] ?? '',
		),
	);
}

function srwf_wu04_theme_candidate() {
	$templates = get_block_templates( array( 'slug__in' => array( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG ) ), 'wp_template' );
	foreach ( $templates as $template ) {
		if ( $template instanceof WP_Block_Template && \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === (string) $template->slug ) {
			return $template;
		}
	}
	return null;
}

function srwf_wu04_theme_register_hook_probe() {
	$registered = register_block_type(
		'srwf-lab/wu04-hooked-probe',
		array( 'api_version' => 3, 'block_hooks' => array( 'core/template-part' => 'before' ) )
	);
	srwf_wu04_theme_assert( $registered instanceof WP_Block_Type, 'Theme hook-probe block could not be registered.' );
}

$canonical = \SRWF\HostCompanion\TemplateRegistrar::get_canonical_content();
srwf_wu04_theme_assert( is_string( $canonical ) && '' !== $canonical, 'Canonical source unavailable.' );
$serialization_variant = str_replace(
	'<!-- wp:template-part {"slug":"header","tagName":"header"} /-->',
	'<!-- wp:template-part { "slug": "header", "tagName": "header" } /-->',
	$canonical
);
$material_drift = $canonical . "\n<!-- wp:paragraph --><p>SRWF_WU04_THEME_MATERIAL_DRIFT</p><!-- /wp:paragraph -->\n";

$folders    = get_block_theme_folders( get_stylesheet() );
$theme_dir  = wp_get_theme()->get_stylesheet_directory();
$theme_path = trailingslashit( $theme_dir ) . trailingslashit( $folders['wp_template'] );
$theme_file = $theme_path . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG . '.html';
srwf_wu04_theme_assert( ! file_exists( $theme_file ), 'Qualified theme unexpectedly already has SRWF template slug.' );
srwf_wu04_theme_assert( is_dir( $theme_path ) && is_writable( $theme_path ), 'Qualified theme template directory is not writable in disposable lab.' );

// Create the path before this request performs its first get_block_templates() lookup.
srwf_wu04_theme_assert( false !== file_put_contents( $theme_file, $serialization_variant ), 'Could not create raw-equivalent theme fixture.' );
clearstatcache( true, $theme_file );

$admin = get_user_by( 'login', 'runtime_admin' );
srwf_wu04_theme_assert( $admin instanceof WP_User, 'Runtime administrator unavailable.' );
wp_set_current_user( $admin->ID );
$page_id = srwf_wu04_theme_create_page( 'SRWF_WU04_THEME_PAGE' );
srwf_wu04_theme_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $page_id ), 'Could not configure theme fixture page.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $page_id );
srwf_wu04_theme_assert( ! empty( $assign['success'] ), 'Could not assign canonical slug to theme fixture page.' );
srwf_wu04_theme_register_hook_probe();

$evidence = array(
	'schema'            => 'srwf-host-companion-wu04-theme-drift-v1',
	'tested_commit_sha' => $tested_sha,
	'states'            => array(),
	'claims'            => array(),
	'capture_status'    => 'FAIL',
);

$before = srwf_wu04_theme_snapshot( $page_id, $theme_file );
$diag   = \SRWF\HostCompanion\TemplateDiagnostics::inspect();
srwf_wu04_theme_assert( \SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE === $diag['primary_state'], 'Raw-equivalent theme provider was not classified THEME_OVERRIDE.' );
$provider   = $diag['resolution']['evidence']['provider'];
$comparison = $diag['resolution']['evidence']['source_comparison'];
srwf_wu04_theme_assert( 'theme' === $provider['source'], 'Theme provider provenance mismatch.' );
srwf_wu04_theme_assert( 'PASS' === $comparison['status'] && true === $comparison['content_matches_canonical'], 'Raw-equivalent theme source produced a false mismatch.' );
srwf_wu04_theme_assert( 'RAW_THEME_FILE_CONTENT' === $comparison['basis'], 'Theme comparison did not use raw theme file source.' );
$candidate = srwf_wu04_theme_candidate();
srwf_wu04_theme_assert( $candidate instanceof WP_Block_Template && has_block( 'srwf-lab/wu04-hooked-probe', (string) $candidate->content ), 'Native Block Hooks did not transform returned theme candidate.' );
$html = srwf_wu04_theme_render_check_again();
srwf_wu04_theme_assert( false !== strpos( $html, 'پوسته فعال نسخه‌ای با همین نام قالب دارد' ), 'Theme Owner-facing diagnostic missing.' );
$after = srwf_wu04_theme_snapshot( $page_id, $theme_file );
srwf_wu04_theme_assert( $before === $after, 'Raw-equivalent theme diagnostics mutated sentinel state.' );
$evidence['states']['theme_override_block_hooks_equivalent'] = array(
	'status' => 'PASS',
	'expected_primary' => \SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE,
	'actual_primary' => $diag['primary_state'],
	'expected_page_state' => \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	'actual_page_state' => $diag['page']['code'],
	'expected_resolution' => \SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE,
	'actual_resolution' => $diag['resolution']['state'],
	'assignment_state' => $diag['assignment']['state'],
	'recommended_action' => $diag['recommended_action'],
	'resolution_evidence' => srwf_wu04_theme_reduce( $diag ),
	'non_mutation' => 'PASS',
	'check_again_exercised' => true,
	'block_hooks_observed' => true,
);
$evidence['claims']['theme_block_hooks_equivalent'] = 'PASS';

srwf_wu04_theme_assert( false !== file_put_contents( $theme_file, $material_drift ), 'Could not replace theme fixture with material drift.' );
clearstatcache( true, $theme_file );
$before = srwf_wu04_theme_snapshot( $page_id, $theme_file );
$diag   = \SRWF\HostCompanion\TemplateDiagnostics::inspect();
$comparison = $diag['resolution']['evidence']['source_comparison'];
srwf_wu04_theme_assert( \SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE === $diag['primary_state'], 'Material theme provider was not classified THEME_OVERRIDE.' );
srwf_wu04_theme_assert( 'PASS' === $comparison['status'] && false === $comparison['content_matches_canonical'], 'Material raw theme drift was suppressed.' );
$candidate = srwf_wu04_theme_candidate();
srwf_wu04_theme_assert( $candidate instanceof WP_Block_Template && has_block( 'srwf-lab/wu04-hooked-probe', (string) $candidate->content ), 'Native Block Hooks positive-control theme candidate did not transform.' );
$html = srwf_wu04_theme_render_check_again();
srwf_wu04_theme_assert( false !== strpos( $html, 'پوسته فعال نسخه‌ای با همین نام قالب دارد' ), 'Material theme Owner-facing diagnostic missing.' );
$after = srwf_wu04_theme_snapshot( $page_id, $theme_file );
srwf_wu04_theme_assert( $before === $after, 'Material theme diagnostics mutated sentinel state.' );
$evidence['states']['theme_override_block_hooks_material'] = array(
	'status' => 'PASS',
	'expected_primary' => \SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE,
	'actual_primary' => $diag['primary_state'],
	'expected_page_state' => \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	'actual_page_state' => $diag['page']['code'],
	'expected_resolution' => \SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE,
	'actual_resolution' => $diag['resolution']['state'],
	'assignment_state' => $diag['assignment']['state'],
	'recommended_action' => $diag['recommended_action'],
	'resolution_evidence' => srwf_wu04_theme_reduce( $diag ),
	'non_mutation' => 'PASS',
	'check_again_exercised' => true,
	'block_hooks_observed' => true,
);
$evidence['claims']['theme_material_positive_control'] = 'PASS';
$evidence['capture_status'] = 'PASS';

unregister_block_type( 'srwf-lab/wu04-hooked-probe' );
unlink( $theme_file );
clearstatcache( true, $theme_file );

$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu04_theme_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Could not write theme falsification evidence.' );
fwrite( STDOUT, "WU-04 theme raw-source / Block Hooks falsification assertions passed.\n" );
