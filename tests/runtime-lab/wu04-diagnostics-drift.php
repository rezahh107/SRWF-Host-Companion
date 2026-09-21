<?php
/**
 * WU-04 exact-target disposable diagnostics/drift qualification probe.
 * Theme-file Block Hooks falsification runs in a separate WP-CLI process
 * because Core memoizes discovered theme template paths per request.
 */

if ( ! defined( 'ABSPATH' ) ) exit( 1 );

$evidence_path = getenv( 'SRWF_WU04_EVIDENCE_PATH' );
$theme_evidence_path = getenv( 'SRWF_WU04_THEME_EVIDENCE_PATH' );
$tested_sha = getenv( 'SRWF_TESTED_COMMIT_SHA' );
if ( ! $evidence_path || ! $theme_evidence_path || ! $tested_sha ) {
	fwrite( STDERR, "WU-04 evidence paths and tested SHA are required.\n" );
	exit( 2 );
}

function srwf_wu04_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-04 assertion failed: {$message}\n" );
		exit( 10 );
	}
}

function srwf_wu04_create_page( $title, $status, $marker ) {
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => $status, 'post_title' => $title, 'post_content' => '<!-- wp:paragraph --><p>' . $marker . '</p><!-- /wp:paragraph -->' ), true );
	srwf_wu04_assert( ! is_wp_error( $id ) && $id > 0, 'Synthetic page could not be created: ' . $title );
	return (int) $id;
}

function srwf_wu04_snapshot( $object_id = 0, $template_post_id = 0 ) {
	$post = $object_id > 0 ? get_post( $object_id ) : null;
	$template_post = $template_post_id > 0 ? get_post( $template_post_id ) : null;
	$snapshot = array( 'configuration_raw' => get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ), 'object' => null, 'template_post' => null );
	if ( $post instanceof WP_Post ) {
		$snapshot['object'] = array(
			'ID' => (int) $post->ID,
			'post_type' => (string) $post->post_type,
			'post_status' => (string) $post->post_status,
			'post_content' => (string) $post->post_content,
			'page_template_meta' => (string) get_post_meta( $post->ID, '_wp_page_template', true ),
			'page_template_slug' => 'page' === $post->post_type ? (string) get_page_template_slug( $post->ID ) : '',
		);
	}
	if ( $template_post instanceof WP_Post ) {
		$terms = wp_get_object_terms( $template_post->ID, 'wp_theme', array( 'fields' => 'names' ) );
		$snapshot['template_post'] = array(
			'ID' => (int) $template_post->ID,
			'post_name' => (string) $template_post->post_name,
			'post_status' => (string) $template_post->post_status,
			'post_content' => (string) $template_post->post_content,
			'origin' => (string) get_post_meta( $template_post->ID, 'origin', true ),
			'wp_theme' => is_wp_error( $terms ) ? array() : array_values( $terms ),
		);
	}
	return $snapshot;
}

function srwf_wu04_render_settings( $check_again = true ) {
	$_GET = array( 'page' => \SRWF\HostCompanion\AdminSettings::PAGE_SLUG );
	if ( $check_again ) $_GET['srwf_check'] = '1';
	ob_start();
	\SRWF\HostCompanion\AdminSettings::render_page();
	$html = (string) ob_get_clean();
	$_GET = array();
	return $html;
}

function srwf_wu04_reduce_resolution_evidence( $diagnostics ) {
	$e = is_array( $diagnostics['resolution']['evidence'] ?? null ) ? $diagnostics['resolution']['evidence'] : array();
	$p = is_array( $e['provider'] ?? null ) ? $e['provider'] : array();
	$c = is_array( $e['source_comparison'] ?? null ) ? $e['source_comparison'] : array();
	$t = is_array( $e['transformed_resolved'] ?? null ) ? $e['transformed_resolved'] : array();
	return array(
		'provider' => array(
			'observation' => $p['observation'] ?? '', 'evidence_status' => $p['evidence_status'] ?? 'NOT_PROVEN', 'reason' => $p['reason'] ?? '',
			'found' => $p['found'] ?? false, 'id' => $p['id'] ?? '', 'source' => $p['source'] ?? '', 'origin' => $p['origin'] ?? '', 'plugin' => $p['plugin'] ?? '',
			'wp_id' => $p['wp_id'] ?? 0, 'template_status' => $p['template_status'] ?? '', 'has_theme_file' => $p['has_theme_file'] ?? false,
		),
		'source_comparison' => array(
			'status' => $c['status'] ?? 'NOT_PROVEN', 'basis' => $c['basis'] ?? 'NOT_AVAILABLE', 'reason' => $c['reason'] ?? '', 'source_identity' => $c['source_identity'] ?? '',
			'canonical_fingerprint' => $c['canonical_fingerprint'] ?? '', 'source_fingerprint' => $c['source_fingerprint'] ?? '', 'content_matches_canonical' => $c['content_matches_canonical'] ?? null,
		),
		'transformed_resolved' => array(
			'status' => $t['status'] ?? 'NOT_PROVEN', 'basis' => $t['basis'] ?? 'NOT_AVAILABLE', 'transformation_status' => $t['transformation_status'] ?? 'NOT_PROVEN', 'reason' => $t['reason'] ?? '', 'fingerprint' => $t['fingerprint'] ?? '',
		),
	);
}

function srwf_wu04_exercise_fixture( &$evidence, $name, $expected_primary, $expected_page, $expected_resolution, $object_id, $template_post_id, $owner_fragment ) {
	$before = srwf_wu04_snapshot( $object_id, $template_post_id );
	$diagnostics = \SRWF\HostCompanion\TemplateDiagnostics::inspect();
	srwf_wu04_assert( $expected_primary === $diagnostics['primary_state'], $name . ': primary state mismatch.' );
	if ( '' !== $expected_page ) srwf_wu04_assert( $expected_page === $diagnostics['page']['code'], $name . ': page state mismatch.' );
	if ( '' !== $expected_resolution ) srwf_wu04_assert( $expected_resolution === $diagnostics['resolution']['state'], $name . ': resolution state mismatch.' );
	$html = srwf_wu04_render_settings( true );
	srwf_wu04_assert( false !== strpos( $html, $owner_fragment ), $name . ': Owner-facing explanation missing.' );
	srwf_wu04_assert( false !== strpos( $html, 'بررسی دوباره' ) && false !== strpos( $html, 'جزئیات فنی' ), $name . ': read-only diagnostic controls missing.' );
	$after = srwf_wu04_snapshot( $object_id, $template_post_id );
	srwf_wu04_assert( $before === $after, $name . ': diagnostic inspection/render mutated persistent sentinel state.' );
	$evidence['states'][ $name ] = array(
		'status' => 'PASS', 'expected_primary' => $expected_primary, 'actual_primary' => $diagnostics['primary_state'],
		'expected_page_state' => $expected_page, 'actual_page_state' => $diagnostics['page']['code'],
		'expected_resolution' => $expected_resolution, 'actual_resolution' => $diagnostics['resolution']['state'],
		'assignment_state' => $diagnostics['assignment']['state'], 'recommended_action' => $diagnostics['recommended_action'],
		'resolution_evidence' => srwf_wu04_reduce_resolution_evidence( $diagnostics ), 'non_mutation' => 'PASS', 'check_again_exercised' => true,
	);
	return $diagnostics;
}

function srwf_wu04_create_db_override( $content, $status = 'publish' ) {
	$id = wp_insert_post( array( 'post_type' => 'wp_template', 'post_status' => $status, 'post_name' => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG, 'post_title' => 'SRWF WU-04 DB Override', 'post_content' => $content ), true );
	srwf_wu04_assert( ! is_wp_error( $id ) && $id > 0, 'Database override wp_template could not be created.' );
	$id = (int) $id;
	$terms = wp_set_object_terms( $id, get_stylesheet(), 'wp_theme', false );
	srwf_wu04_assert( ! is_wp_error( $terms ), 'Database override wp_theme term could not be assigned.' );
	update_post_meta( $id, 'origin', 'plugin' );
	clean_post_cache( $id );
	return $id;
}

function srwf_wu04_frontend_candidate() {
	$templates = get_block_templates( array( 'slug__in' => array( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG ) ), 'wp_template' );
	foreach ( $templates as $template ) {
		if ( $template instanceof WP_Block_Template && \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === (string) $template->slug ) return $template;
	}
	return null;
}

function srwf_wu04_unknown_candidates( $templates, $query, $template_type ) {
	if ( 'wp_template' !== $template_type || ! isset( $query['slug__in'] ) || ! is_array( $query['slug__in'] ) || ! in_array( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG, $query['slug__in'], true ) ) return $templates;
	$object = new WP_Block_Template();
	$object->id = get_stylesheet() . '//' . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG;
	$object->theme = get_stylesheet();
	$object->slug = \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG;
	$object->type = 'wp_template';
	$object->title = 'SRWF WU-04 Unknown Candidate';
	$object->content = (string) \SRWF\HostCompanion\TemplateRegistrar::get_canonical_content();
	$object->source = 'external-fixture';
	$object->origin = null;
	$object->plugin = null;
	$object->wp_id = null;
	$object->status = 'publish';
	$object->has_theme_file = false;
	$object->is_custom = true;
	$object->post_types = array( 'page' );
	return array( $object );
}

function srwf_wu04_register_hook_probe() {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'srwf-lab/wu04-hooked-probe' ) ) return;
	$registered = register_block_type( 'srwf-lab/wu04-hooked-probe', array( 'api_version' => 3, 'block_hooks' => array( 'core/template-part' => 'before' ) ) );
	srwf_wu04_assert( $registered instanceof WP_Block_Type, 'Hook-probe block could not be registered.' );
}
function srwf_wu04_unregister_hook_probe() {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'srwf-lab/wu04-hooked-probe' ) ) unregister_block_type( 'srwf-lab/wu04-hooked-probe' );
}

srwf_wu04_assert( class_exists( 'SRWF\\HostCompanion\\TemplateDiagnostics', false ) && class_exists( 'SRWF\\HostCompanion\\AdminSettings', false ), 'WU-04 classes were not loaded.' );
srwf_wu04_assert( function_exists( 'get_block_templates' ), 'WordPress get_block_templates() is unavailable.' );
$admin = get_user_by( 'login', 'runtime_admin' );
srwf_wu04_assert( $admin instanceof WP_User, 'Runtime administrator unavailable.' );
wp_set_current_user( $admin->ID );
srwf_wu04_assert( current_user_can( 'manage_options' ), 'Runtime administrator lacks manage_options.' );

$canonical = \SRWF\HostCompanion\TemplateRegistrar::get_canonical_content();
srwf_wu04_assert( is_string( $canonical ) && '' !== $canonical, 'Canonical template content unavailable.' );
$serialization_variant = str_replace( '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->', '<!-- wp:template-part { "slug": "header", "tagName": "header" } /-->', $canonical );
srwf_wu04_assert( $serialization_variant !== $canonical, 'Normalization fixture did not change raw serialization.' );
srwf_wu04_assert( \SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $serialization_variant ) === \SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $canonical ), 'Valid block serialization whitespace did not normalize.' );
$material_drift = $canonical . "\n<!-- wp:paragraph --><p>SRWF_WU04_TEMPLATE_DRIFT_MARKER</p><!-- /wp:paragraph -->\n";
srwf_wu04_assert( \SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $material_drift ) !== \SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $canonical ), 'Material block difference was erased by normalization.' );

$theme_json = file_get_contents( $theme_evidence_path );
srwf_wu04_assert( false !== $theme_json, 'Theme falsification evidence unavailable.' );
$theme_fixture = json_decode( $theme_json, true );
srwf_wu04_assert( is_array( $theme_fixture ) && 'PASS' === ( $theme_fixture['capture_status'] ?? '' ), 'Theme falsification fixture did not PASS.' );

$theme = wp_get_theme();
$evidence = array(
	'schema' => 'srwf-host-companion-wu04-diagnostics-drift-v2', 'evidence_class' => 'DISPOSABLE_CI_WORDPRESS_INTEGRATION', 'tested_commit_sha' => $tested_sha, 'observed_at_utc' => gmdate( 'c' ),
	'environment' => array( 'wordpress_version' => get_bloginfo( 'version' ), 'php_version' => PHP_VERSION, 'theme_stylesheet' => get_stylesheet(), 'theme_name' => $theme->get( 'Name' ), 'theme_version' => $theme->get( 'Version' ) ),
	'runtime_contract' => array(
		'provider_observation' => array( 'mechanism' => 'get_block_templates(slug__in) / frontend published-candidate model', 'draft_db_excluded' => 'PENDING', 'trash_db_excluded' => 'PENDING', 'published_db_selected' => 'PENDING', 'status' => 'PENDING' ),
		'raw_source_comparison' => array( 'mechanism' => 'raw provider source -> serialize_blocks(parse_blocks(content)) -> SHA-256', 'db_block_hooks_equivalent' => 'PENDING', 'db_material_positive_control' => 'PENDING', 'theme_block_hooks_equivalent' => $theme_fixture['claims']['theme_block_hooks_equivalent'] ?? 'FAIL', 'theme_material_positive_control' => $theme_fixture['claims']['theme_material_positive_control'] ?? 'FAIL', 'status' => 'PENDING' ),
		'canonical_provider' => array(),
		'normalization' => array( 'mechanism' => 'serialize_blocks(parse_blocks(content))', 'serialization_trivia_ignored' => true, 'material_block_drift_detected' => true, 'status' => 'PASS' ),
	),
	'limitations' => array( 'registered_plugin_direct_content_equivalence' => 'NOT_PROVEN_NATIVE_BLOCK_HOOKS_TRANSFORM' ),
	'states' => array(), 'unsupported_states' => array(),
	'claim_ceiling' => array( 'wu05_full_width_geometry' => 'NOT_PROVEN', 'wu06_browser_admin_qualification' => 'NOT_RUN', 'wu07_browser_e2e_comprehension' => 'NOT_RUN', 'production_qualification' => 'NOT_PROVEN' ),
);
foreach ( $theme_fixture['states'] as $name => $state ) $evidence['states'][ $name ] = $state;

$healthy_id = srwf_wu04_create_page( 'SRWF WU-04 Canonical', 'publish', 'SRWF_WU04_PAGE_CONTENT_CANONICAL' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $healthy_id ), 'Could not configure canonical fixture.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $healthy_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign canonical template.' );
$canonical_diag = srwf_wu04_exercise_fixture( $evidence, 'canonical', \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $healthy_id, 0, 'اتصال قالب مطابق انتظار است' );
$p = $canonical_diag['resolution']['evidence']['provider'];
$c = $canonical_diag['resolution']['evidence']['source_comparison'];
srwf_wu04_assert( 'plugin' === $p['source'] && 'plugin' === $p['origin'] && 'srwf-host-companion' === $p['plugin'], 'Canonical provider provenance mismatch.' );
srwf_wu04_assert( 'NOT_PROVEN' === $c['status'] && null === $c['content_matches_canonical'], 'Canonical raw/resolved content equivalence was overclaimed.' );
$evidence['runtime_contract']['canonical_provider'] = array( 'source' => $p['source'], 'origin' => $p['origin'], 'plugin' => $p['plugin'], 'provider_status' => 'PASS', 'content_comparison' => $c['status'] );

update_post_meta( $healthy_id, '_wp_page_template', 'legacy-wrong-template' );
srwf_wu04_exercise_fixture( $evidence, 'wrong_page_assignment', \SRWF\HostCompanion\TemplateDiagnostics::WRONG_PAGE_ASSIGNMENT, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $healthy_id, 0, 'قالب مورد انتظار به صفحه ثبت‌نام متصل نیست' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $healthy_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not restore canonical assignment.' );

$draft_id = srwf_wu04_create_page( 'SRWF WU-04 Draft', 'draft', 'SRWF_WU04_PAGE_CONTENT_DRAFT' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $draft_id ), 'Could not configure draft page.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $draft_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign draft page.' );
srwf_wu04_exercise_fixture( $evidence, 'page_not_published', \SRWF\HostCompanion\TemplateDiagnostics::PAGE_NOT_PUBLISHED, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_NOT_PUBLISHED, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $draft_id, 0, 'صفحه معتبر است اما منتشرشده نیست' );

$deleted_id = srwf_wu04_create_page( 'SRWF WU-04 Deleted', 'publish', 'SRWF_WU04_PAGE_CONTENT_DELETED' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $deleted_id ), 'Could not configure missing page.' );
wp_delete_post( $deleted_id, true );
srwf_wu04_exercise_fixture( $evidence, 'page_missing', 'PAGE_INVALID', \SRWF\HostCompanion\TemplateDiagnostics::PAGE_MISSING, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $deleted_id, 0, 'صفحه ثبت‌نام نیاز به اصلاح دارد' );

$trash_id = srwf_wu04_create_page( 'SRWF WU-04 Trash', 'draft', 'SRWF_WU04_PAGE_CONTENT_TRASH' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $trash_id ), 'Could not configure trashed page.' );
wp_trash_post( $trash_id );
srwf_wu04_exercise_fixture( $evidence, 'page_trashed', 'PAGE_INVALID', \SRWF\HostCompanion\TemplateDiagnostics::PAGE_TRASHED, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $trash_id, 0, 'صفحه ثبت‌نام نیاز به اصلاح دارد' );

$post_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'SRWF WU-04 Wrong Type', 'post_content' => 'SRWF_WU04_WRONG_TYPE_CONTENT' ), true );
srwf_wu04_assert( ! is_wp_error( $post_id ) && $post_id > 0, 'Wrong-type fixture could not be created.' );
$post_id = (int) $post_id;
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $post_id ), 'Could not configure wrong type.' );
srwf_wu04_exercise_fixture( $evidence, 'page_type_invalid', 'PAGE_INVALID', \SRWF\HostCompanion\TemplateDiagnostics::PAGE_TYPE_INVALID, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $post_id, 0, 'صفحه ثبت‌نام نیاز به اصلاح دارد' );

// Falsify current reviewed implementation: draft and trash DB templates are not frontend providers.
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $healthy_id ), 'Could not restore healthy configured page.' );
$draft_template_id = srwf_wu04_create_db_override( $material_drift, 'draft' );
$draft_provider = srwf_wu04_frontend_candidate();
srwf_wu04_assert( $draft_provider instanceof WP_Block_Template && 'plugin' === $draft_provider->source, 'Draft DB template incorrectly entered frontend candidate model.' );
$d = srwf_wu04_exercise_fixture( $evidence, 'db_draft_not_active', \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $healthy_id, $draft_template_id, 'اتصال قالب مطابق انتظار است' );
srwf_wu04_assert( 'plugin' === $d['resolution']['evidence']['provider']['source'], 'Diagnostics promoted draft DB template.' );
$evidence['runtime_contract']['provider_observation']['draft_db_excluded'] = 'PASS';
wp_delete_post( $draft_template_id, true );

$trash_template_id = srwf_wu04_create_db_override( $material_drift, 'publish' );
wp_trash_post( $trash_template_id );
clean_post_cache( $trash_template_id );
$trash_provider = srwf_wu04_frontend_candidate();
srwf_wu04_assert( $trash_provider instanceof WP_Block_Template && 'plugin' === $trash_provider->source, 'Trashed DB template incorrectly entered frontend candidate model.' );
$d = srwf_wu04_exercise_fixture( $evidence, 'db_trash_not_active', \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::CANONICAL, $healthy_id, $trash_template_id, 'اتصال قالب مطابق انتظار است' );
srwf_wu04_assert( 'plugin' === $d['resolution']['evidence']['provider']['source'], 'Diagnostics promoted trashed DB template.' );
$evidence['runtime_contract']['provider_observation']['trash_db_excluded'] = 'PASS';
wp_delete_post( $trash_template_id, true );

// Block Hooks defect-class falsification plus material positive control for DB source.
srwf_wu04_register_hook_probe();
$db_equiv_id = srwf_wu04_create_db_override( $serialization_variant, 'publish' );
$candidate = srwf_wu04_frontend_candidate();
srwf_wu04_assert( $candidate instanceof WP_Block_Template && 'custom' === $candidate->source, 'Published DB override was not selected.' );
srwf_wu04_assert( has_block( 'srwf-lab/wu04-hooked-probe', (string) $candidate->content ), 'Block Hooks did not transform returned DB content.' );
$d = srwf_wu04_exercise_fixture( $evidence, 'db_override_block_hooks_equivalent', \SRWF\HostCompanion\TemplateDiagnostics::CUSTOMIZED_DB_OVERRIDE, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::CUSTOMIZED_DB_OVERRIDE, $healthy_id, $db_equiv_id, 'یک نسخه سفارشی‌شده در پایگاه داده بر قالب مرجع مقدم است' );
$c = $d['resolution']['evidence']['source_comparison'];
srwf_wu04_assert( 'PASS' === $c['status'] && true === $c['content_matches_canonical'] && 'RAW_DB_WP_TEMPLATE_POST_CONTENT' === $c['basis'], 'Raw-equivalent DB override falsely mismatched under Block Hooks.' );
$evidence['runtime_contract']['provider_observation']['published_db_selected'] = 'PASS';
$evidence['runtime_contract']['raw_source_comparison']['db_block_hooks_equivalent'] = 'PASS';
wp_delete_post( $db_equiv_id, true );

$db_material_id = srwf_wu04_create_db_override( $material_drift, 'publish' );
$candidate = srwf_wu04_frontend_candidate();
srwf_wu04_assert( $candidate instanceof WP_Block_Template && has_block( 'srwf-lab/wu04-hooked-probe', (string) $candidate->content ), 'Block Hooks did not transform material DB candidate.' );
$d = srwf_wu04_exercise_fixture( $evidence, 'db_override_block_hooks_material', \SRWF\HostCompanion\TemplateDiagnostics::CUSTOMIZED_DB_OVERRIDE, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::CUSTOMIZED_DB_OVERRIDE, $healthy_id, $db_material_id, 'یک نسخه سفارشی‌شده در پایگاه داده بر قالب مرجع مقدم است' );
$c = $d['resolution']['evidence']['source_comparison'];
srwf_wu04_assert( 'PASS' === $c['status'] && false === $c['content_matches_canonical'], 'Material DB raw-source drift was suppressed.' );
$evidence['runtime_contract']['raw_source_comparison']['db_material_positive_control'] = 'PASS';
wp_delete_post( $db_material_id, true );
srwf_wu04_unregister_hook_probe();

$missing_page = srwf_wu04_create_page( 'SRWF WU-04 Missing Template', 'publish', 'SRWF_WU04_PAGE_CONTENT_MISSING_TEMPLATE' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $missing_page ), 'Could not configure missing-template page.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $missing_page );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign missing-template page.' );
$unregistered = unregister_block_template( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME );
srwf_wu04_assert( $unregistered instanceof WP_Block_Template && null === srwf_wu04_frontend_candidate(), 'Missing-template fixture did not remove active provider.' );
srwf_wu04_exercise_fixture( $evidence, 'missing_template', \SRWF\HostCompanion\TemplateDiagnostics::MISSING_TEMPLATE, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::MISSING_TEMPLATE, $missing_page, 0, 'قالب مرجع SRWF در حال حاضر resolve نمی‌شود' );
srwf_wu04_assert( null === srwf_wu04_frontend_candidate(), 'Diagnostics silently repaired missing provider.' );
srwf_wu04_assert( \SRWF\HostCompanion\TemplateRegistrar::register() instanceof WP_Block_Template, 'Canonical template could not be restored after fixture.' );

$unknown_page = srwf_wu04_create_page( 'SRWF WU-04 Unknown Candidate', 'publish', 'SRWF_WU04_PAGE_CONTENT_UNKNOWN' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $unknown_page ), 'Could not configure unknown page.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $unknown_page );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign unknown page.' );
add_filter( 'pre_get_block_templates', 'srwf_wu04_unknown_candidates', 10, 3 );
$d = srwf_wu04_exercise_fixture( $evidence, 'unknown', \SRWF\HostCompanion\TemplateDiagnostics::UNKNOWN, \SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID, \SRWF\HostCompanion\TemplateDiagnostics::UNKNOWN, $unknown_page, 0, 'منبع قالب با اطمینان قابل طبقه‌بندی نیست' );
srwf_wu04_assert( 'external-fixture' === $d['resolution']['evidence']['provider']['source'], 'UNKNOWN did not exercise repaired get_block_templates seam.' );
remove_filter( 'pre_get_block_templates', 'srwf_wu04_unknown_candidates', 10 );

$report = \SRWF\HostCompanion\TemplateDiagnostics::build_report( $canonical_diag );
srwf_wu04_assert( false !== strpos( $report, 'provider_observation=GET_BLOCK_TEMPLATES_SLUG_CANDIDATES' ), 'Report omits provider observation.' );
srwf_wu04_assert( false !== strpos( $report, 'source_comparison_status=NOT_PROVEN' ), 'Report omits canonical comparison limitation.' );
srwf_wu04_assert( false === strpos( $report, 'SRWF_WU04_PAGE_CONTENT_' ) && false === stripos( $report, 'nonce=' ) && false === stripos( $report, 'cookie=' ) && false === strpos( $report, 'runtime@example.invalid' ), 'Diagnostic report leaked protected payload.' );
$evidence['diagnostic_report'] = array( 'status' => 'PASS', 'privacy_safe_fields' => true, 'contains_page_content' => false, 'contains_nonce' => false, 'contains_cookie' => false, 'contains_user_email' => false );

$required_states = array( 'canonical', 'wrong_page_assignment', 'page_not_published', 'page_missing', 'page_trashed', 'page_type_invalid', 'db_draft_not_active', 'db_trash_not_active', 'db_override_block_hooks_equivalent', 'db_override_block_hooks_material', 'theme_override_block_hooks_equivalent', 'theme_override_block_hooks_material', 'missing_template', 'unknown' );
foreach ( $required_states as $name ) {
	srwf_wu04_assert( isset( $evidence['states'][ $name ] ) && 'PASS' === $evidence['states'][ $name ]['status'], 'Required fixture did not PASS: ' . $name );
	srwf_wu04_assert( 'PASS' === $evidence['states'][ $name ]['non_mutation'], 'Non-mutation proof missing: ' . $name );
}

$provider_contract = &$evidence['runtime_contract']['provider_observation'];
$raw_contract = &$evidence['runtime_contract']['raw_source_comparison'];
srwf_wu04_assert( 'PASS' === $provider_contract['draft_db_excluded'] && 'PASS' === $provider_contract['trash_db_excluded'] && 'PASS' === $provider_contract['published_db_selected'], 'Provider eligibility falsification incomplete.' );
$provider_contract['status'] = 'PASS';
srwf_wu04_assert( 'PASS' === $raw_contract['db_block_hooks_equivalent'] && 'PASS' === $raw_contract['db_material_positive_control'] && 'PASS' === $raw_contract['theme_block_hooks_equivalent'] && 'PASS' === $raw_contract['theme_material_positive_control'], 'Raw-source Block Hooks falsification incomplete.' );
$raw_contract['status'] = 'PASS';

$evidence['claims'] = array(
	'diagnostics_runtime_proven' => 'PASS',
	'frontend_provider_eligibility' => 'PASS',
	'raw_source_drift_comparison' => 'PASS',
	'block_hooks_source_boundary' => 'PASS',
	'material_drift_positive_controls' => 'PASS',
	'check_again_read_only' => 'PASS',
	'no_hidden_repair' => 'PASS',
	'page_validity_reused' => 'PASS',
	'normalized_raw_source_drift_comparison' => 'PASS',
	'privacy_safe_report' => 'PASS',
	'qualification_lab_wu04_slice' => 'PASS',
);
$evidence['capture_status'] = 'PASS';
$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu04_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Unable to write WU-04 evidence.' );
fwrite( STDOUT, "WU-04 diagnostics/drift repaired qualification assertions passed.\n" );
