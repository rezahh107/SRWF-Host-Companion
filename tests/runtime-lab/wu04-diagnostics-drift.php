<?php
/**
 * WU-04 exact-target disposable diagnostics/drift qualification probe.
 *
 * Run through WP-CLI eval-file with the product plugin active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$evidence_path = getenv( 'SRWF_WU04_EVIDENCE_PATH' );
$tested_sha    = getenv( 'SRWF_TESTED_COMMIT_SHA' );

if ( ! $evidence_path || ! $tested_sha ) {
	fwrite( STDERR, "SRWF_WU04_EVIDENCE_PATH and SRWF_TESTED_COMMIT_SHA are required.\n" );
	exit( 2 );
}

function srwf_wu04_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-04 assertion failed: {$message}\n" );
		exit( 10 );
	}
}

function srwf_wu04_create_page( $title, $status, $marker ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => $status,
			'post_title'   => $title,
			'post_content' => '<!-- wp:paragraph --><p>' . $marker . '</p><!-- /wp:paragraph -->',
		),
		true
	);
	srwf_wu04_assert( ! is_wp_error( $id ) && $id > 0, 'Synthetic page could not be created: ' . $title );
	return (int) $id;
}

function srwf_wu04_snapshot( $object_id = 0, $template_post_id = 0, $theme_file = '' ) {
	$post = $object_id > 0 ? get_post( $object_id ) : null;
	$template_post = $template_post_id > 0 ? get_post( $template_post_id ) : null;
	$snapshot = array(
		'configuration_raw' => get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ),
		'object'            => null,
		'template_post'     => null,
		'theme_file'        => null,
	);
	if ( $post instanceof WP_Post ) {
		$snapshot['object'] = array(
			'ID'                 => (int) $post->ID,
			'post_type'          => (string) $post->post_type,
			'post_status'        => (string) $post->post_status,
			'post_content'       => (string) $post->post_content,
			'page_template_meta' => (string) get_post_meta( $post->ID, '_wp_page_template', true ),
			'page_template_slug' => 'page' === $post->post_type ? (string) get_page_template_slug( $post->ID ) : '',
		);
	}
	if ( $template_post instanceof WP_Post ) {
		$terms = wp_get_object_terms( $template_post->ID, 'wp_theme', array( 'fields' => 'names' ) );
		$snapshot['template_post'] = array(
			'ID'           => (int) $template_post->ID,
			'post_name'    => (string) $template_post->post_name,
			'post_status'  => (string) $template_post->post_status,
			'post_content' => (string) $template_post->post_content,
			'origin'       => (string) get_post_meta( $template_post->ID, 'origin', true ),
			'wp_theme'     => is_wp_error( $terms ) ? array() : array_values( $terms ),
		);
	}
	if ( '' !== $theme_file ) {
		clearstatcache( true, $theme_file );
		$snapshot['theme_file'] = array(
			'exists' => file_exists( $theme_file ),
			'hash'   => file_exists( $theme_file ) ? hash_file( 'sha256', $theme_file ) : '',
		);
	}
	return $snapshot;
}

function srwf_wu04_render_settings( $check_again = false ) {
	$_GET = array( 'page' => \SRWF\HostCompanion\AdminSettings::PAGE_SLUG );
	if ( $check_again ) {
		$_GET['srwf_check'] = '1';
	}
	ob_start();
	\SRWF\HostCompanion\AdminSettings::render_page();
	$html = (string) ob_get_clean();
	$_GET = array();
	return $html;
}

function srwf_wu04_exercise_fixture( &$evidence, $name, $expected_primary, $expected_page, $expected_resolution, $object_id, $template_post_id, $theme_file, $owner_fragment, $check_again = true ) {
	$before = srwf_wu04_snapshot( $object_id, $template_post_id, $theme_file );
	$diagnostics = \SRWF\HostCompanion\TemplateDiagnostics::inspect();
	srwf_wu04_assert( $expected_primary === $diagnostics['primary_state'], $name . ': primary state mismatch.' );
	if ( '' !== $expected_page ) {
		srwf_wu04_assert( $expected_page === $diagnostics['page']['code'], $name . ': page state mismatch.' );
	}
	if ( '' !== $expected_resolution ) {
		srwf_wu04_assert( $expected_resolution === $diagnostics['resolution']['state'], $name . ': resolution state mismatch.' );
	}
	$html = srwf_wu04_render_settings( $check_again );
	srwf_wu04_assert( false !== strpos( $html, $owner_fragment ), $name . ': Owner-facing explanation missing.' );
	srwf_wu04_assert( false !== strpos( $html, 'بررسی دوباره' ), $name . ': Check Again action is missing.' );
	srwf_wu04_assert( false !== strpos( $html, 'جزئیات فنی' ), $name . ': progressive technical details are missing.' );
	$after = srwf_wu04_snapshot( $object_id, $template_post_id, $theme_file );
	srwf_wu04_assert( $before === $after, $name . ': diagnostic inspection/render mutated persistent sentinel state.' );
	$resolution_evidence = is_array( $diagnostics['resolution']['evidence'] ?? null ) ? $diagnostics['resolution']['evidence'] : array();
	$evidence['states'][ $name ] = array(
		'status'                => 'PASS',
		'expected_primary'      => $expected_primary,
		'actual_primary'        => $diagnostics['primary_state'],
		'expected_page_state'   => $expected_page,
		'actual_page_state'     => $diagnostics['page']['code'],
		'expected_resolution'   => $expected_resolution,
		'actual_resolution'     => $diagnostics['resolution']['state'],
		'assignment_state'      => $diagnostics['assignment']['state'],
		'recommended_action'    => $diagnostics['recommended_action'],
		'resolution_evidence'   => array(
			'id'                        => $resolution_evidence['id'] ?? '',
			'source'                    => $resolution_evidence['source'] ?? '',
			'origin'                    => $resolution_evidence['origin'] ?? '',
			'plugin'                    => $resolution_evidence['plugin'] ?? '',
			'wp_id'                     => $resolution_evidence['wp_id'] ?? 0,
			'has_theme_file'            => $resolution_evidence['has_theme_file'] ?? false,
			'canonical_fingerprint'     => $resolution_evidence['canonical_fingerprint'] ?? '',
			'resolved_fingerprint'      => $resolution_evidence['resolved_fingerprint'] ?? '',
			'content_matches_canonical' => $resolution_evidence['content_matches_canonical'] ?? null,
		),
		'non_mutation'          => 'PASS',
		'check_again_exercised' => $check_again,
	);
	return $diagnostics;
}

function srwf_wu04_create_db_override( $content ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'wp_template',
			'post_status'  => 'publish',
			'post_name'    => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG,
			'post_title'   => 'SRWF WU-04 DB Override',
			'post_content' => $content,
		),
		true
	);
	srwf_wu04_assert( ! is_wp_error( $id ) && $id > 0, 'Database override wp_template could not be created.' );
	$id = (int) $id;
	$terms = wp_set_object_terms( $id, get_stylesheet(), 'wp_theme', false );
	srwf_wu04_assert( ! is_wp_error( $terms ), 'Database override wp_theme term could not be assigned.' );
	update_post_meta( $id, 'origin', 'plugin' );
	clean_post_cache( $id );
	return $id;
}

function srwf_wu04_unknown_resolver( $template, $id, $type ) {
	$expected_id = get_stylesheet() . '//' . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG;
	if ( $expected_id !== $id || 'wp_template' !== $type ) {
		return $template;
	}
	$object = new WP_Block_Template();
	$object->id = $expected_id;
	$object->theme = get_stylesheet();
	$object->slug = \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG;
	$object->type = 'wp_template';
	$object->title = 'SRWF WU-04 Unknown Resolver';
	$object->content = (string) \SRWF\HostCompanion\TemplateRegistrar::get_canonical_content();
	$object->source = 'external-fixture';
	$object->origin = null;
	$object->plugin = null;
	$object->wp_id = null;
	$object->has_theme_file = false;
	$object->is_custom = true;
	$object->post_types = array( 'page' );
	return $object;
}

srwf_wu04_assert( class_exists( 'SRWF\\HostCompanion\\TemplateDiagnostics', false ), 'TemplateDiagnostics class was not loaded.' );
srwf_wu04_assert( class_exists( 'SRWF\\HostCompanion\\AdminSettings', false ), 'AdminSettings class was not loaded.' );
srwf_wu04_assert( function_exists( 'register_block_template' ) && function_exists( 'unregister_block_template' ), 'WordPress template registration APIs are unavailable.' );

$admin = get_user_by( 'login', 'runtime_admin' );
srwf_wu04_assert( $admin instanceof WP_User, 'Runtime administrator is unavailable.' );
wp_set_current_user( $admin->ID );
srwf_wu04_assert( current_user_can( 'manage_options' ), 'Runtime administrator lacks manage_options.' );

$canonical = \SRWF\HostCompanion\TemplateRegistrar::get_canonical_content();
srwf_wu04_assert( is_string( $canonical ) && '' !== $canonical, 'Canonical template content is unavailable.' );
$serialization_variant = str_replace( '<!-- wp:post-content /-->', '<!-- wp:post-content/-->', $canonical );
srwf_wu04_assert( $serialization_variant !== $canonical, 'Normalization fixture did not change raw serialization.' );
srwf_wu04_assert(
	\SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $serialization_variant ) === \SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $canonical ),
	'parse_blocks()/serialize_blocks() did not normalize harmless block-comment serialization trivia.'
);
$material_drift = $canonical . "\n<!-- wp:paragraph --><p>SRWF_WU04_TEMPLATE_DRIFT_MARKER</p><!-- /wp:paragraph -->\n";
srwf_wu04_assert(
	\SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $material_drift ) !== \SRWF\HostCompanion\TemplateDiagnostics::fingerprint_content( $canonical ),
	'Normalized fingerprint erased a material block difference.'
);

$theme = wp_get_theme();
$evidence = array(
	'schema'           => 'srwf-host-companion-wu04-diagnostics-drift-v1',
	'evidence_class'   => 'DISPOSABLE_CI_WORDPRESS_INTEGRATION',
	'tested_commit_sha'=> $tested_sha,
	'observed_at_utc'  => gmdate( 'c' ),
	'environment'      => array(
		'wordpress_version' => get_bloginfo( 'version' ),
		'php_version'       => PHP_VERSION,
		'theme_stylesheet'  => get_stylesheet(),
		'theme_name'        => $theme->get( 'Name' ),
		'theme_version'     => $theme->get( 'Version' ),
	),
	'runtime_contract' => array(
		'resolution_precedence' => 'database > theme file > registered plugin template',
		'canonical_provider'     => array(),
		'normalization'          => array(
			'mechanism'                     => 'serialize_blocks(parse_blocks(content))',
			'serialization_trivia_ignored'  => true,
			'material_block_drift_detected' => true,
			'status'                        => 'PASS',
		),
	),
	'states'             => array(),
	'unsupported_states' => array(),
	'claim_ceiling'      => array(
		'wu05_full_width_geometry'         => 'NOT_PROVEN',
		'wu06_browser_admin_qualification' => 'NOT_RUN',
		'wu07_browser_e2e_comprehension'   => 'NOT_RUN',
		'production_qualification'         => 'NOT_PROVEN',
	),
);

$healthy_id = srwf_wu04_create_page( 'SRWF WU-04 Canonical', 'publish', 'SRWF_WU04_PAGE_CONTENT_CANONICAL' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $healthy_id ), 'Could not configure canonical fixture.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $healthy_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign canonical template fixture.' );
$canonical_diag = srwf_wu04_exercise_fixture(
	$evidence,
	'canonical',
	\SRWF\HostCompanion\TemplateDiagnostics::CANONICAL,
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	\SRWF\HostCompanion\TemplateDiagnostics::CANONICAL,
	$healthy_id,
	0,
	'',
	'اتصال قالب مطابق انتظار است',
	true
);
$canonical_runtime = $canonical_diag['resolution']['evidence'];
srwf_wu04_assert( 'plugin' === $canonical_runtime['source'], 'Canonical runtime source is not plugin.' );
srwf_wu04_assert( 'plugin' === $canonical_runtime['origin'], 'Canonical runtime origin is not plugin.' );
srwf_wu04_assert( 'srwf-host-companion' === $canonical_runtime['plugin'], 'Canonical runtime plugin provenance mismatch.' );
srwf_wu04_assert( true === $canonical_runtime['content_matches_canonical'], 'Canonical resolved content fingerprint mismatch.' );
$evidence['runtime_contract']['canonical_provider'] = array(
	'id'     => $canonical_runtime['id'],
	'source' => $canonical_runtime['source'],
	'origin' => $canonical_runtime['origin'],
	'plugin' => $canonical_runtime['plugin'],
	'status' => 'PASS',
);

update_post_meta( $healthy_id, '_wp_page_template', 'legacy-wrong-template' );
srwf_wu04_exercise_fixture(
	$evidence,
	'wrong_page_assignment',
	\SRWF\HostCompanion\TemplateDiagnostics::WRONG_PAGE_ASSIGNMENT,
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	\SRWF\HostCompanion\TemplateDiagnostics::CANONICAL,
	$healthy_id,
	0,
	'',
	'قالب مورد انتظار به صفحه ثبت‌نام متصل نیست'
);

$draft_id = srwf_wu04_create_page( 'SRWF WU-04 Draft', 'draft', 'SRWF_WU04_PAGE_CONTENT_DRAFT' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $draft_id ), 'Could not configure draft fixture.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $draft_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign canonical template to draft fixture.' );
srwf_wu04_exercise_fixture(
	$evidence,
	'page_not_published',
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_NOT_PUBLISHED,
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_NOT_PUBLISHED,
	\SRWF\HostCompanion\TemplateDiagnostics::CANONICAL,
	$draft_id,
	0,
	'',
	'صفحه معتبر است اما منتشرشده نیست'
);

$deleted_id = srwf_wu04_create_page( 'SRWF WU-04 Deleted', 'publish', 'SRWF_WU04_PAGE_CONTENT_DELETED' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $deleted_id ), 'Could not configure missing fixture.' );
wp_delete_post( $deleted_id, true );
srwf_wu04_exercise_fixture(
	$evidence,
	'page_missing',
	'PAGE_INVALID',
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_MISSING,
	\SRWF\HostCompanion\TemplateDiagnostics::CANONICAL,
	$deleted_id,
	0,
	'',
	'صفحه ثبت‌نام نیاز به اصلاح دارد'
);

$trash_id = srwf_wu04_create_page( 'SRWF WU-04 Trash', 'draft', 'SRWF_WU04_PAGE_CONTENT_TRASH' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $trash_id ), 'Could not configure trash fixture.' );
wp_trash_post( $trash_id );
srwf_wu04_exercise_fixture(
	$evidence,
	'page_trashed',
	'PAGE_INVALID',
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_TRASHED,
	\SRWF\HostCompanion\TemplateDiagnostics::CANONICAL,
	$trash_id,
	0,
	'',
	'صفحه ثبت‌نام نیاز به اصلاح دارد'
);

$post_id = wp_insert_post(
	array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'SRWF WU-04 Wrong Type',
		'post_content' => 'SRWF_WU04_WRONG_TYPE_CONTENT',
	),
	true
);
srwf_wu04_assert( ! is_wp_error( $post_id ) && $post_id > 0, 'Wrong-type fixture could not be created.' );
$post_id = (int) $post_id;
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $post_id ), 'Could not configure wrong-type fixture.' );
srwf_wu04_exercise_fixture(
	$evidence,
	'page_type_invalid',
	'PAGE_INVALID',
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_TYPE_INVALID,
	\SRWF\HostCompanion\TemplateDiagnostics::CANONICAL,
	$post_id,
	0,
	'',
	'صفحه ثبت‌نام نیاز به اصلاح دارد'
);

$db_page_id = srwf_wu04_create_page( 'SRWF WU-04 DB Override Page', 'publish', 'SRWF_WU04_PAGE_CONTENT_DB_OVERRIDE' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $db_page_id ), 'Could not configure DB override fixture.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $db_page_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign DB override fixture page.' );
$db_template_id = srwf_wu04_create_db_override( $material_drift );
$db_resolved = get_block_template( get_stylesheet() . '//' . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG, 'wp_template' );
srwf_wu04_assert( $db_resolved instanceof WP_Block_Template && 'custom' === $db_resolved->source && $db_template_id === (int) $db_resolved->wp_id, 'WordPress did not resolve the DB override fixture as expected.' );
$db_diag = srwf_wu04_exercise_fixture(
	$evidence,
	'customized_db_override',
	\SRWF\HostCompanion\TemplateDiagnostics::CUSTOMIZED_DB_OVERRIDE,
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	\SRWF\HostCompanion\TemplateDiagnostics::CUSTOMIZED_DB_OVERRIDE,
	$db_page_id,
	$db_template_id,
	'',
	'یک نسخه سفارشی‌شده در پایگاه داده بر قالب مرجع مقدم است'
);
srwf_wu04_assert( 'custom' === $db_diag['resolution']['evidence']['source'], 'DB override diagnostic source mismatch.' );
srwf_wu04_assert( 'plugin' === $db_diag['resolution']['evidence']['origin'], 'DB override origin did not preserve plugin provenance.' );
srwf_wu04_assert( false === $db_diag['resolution']['evidence']['content_matches_canonical'], 'Material DB override was normalized into a false canonical match.' );
wp_delete_post( $db_template_id, true );
clean_post_cache( $db_template_id );

$theme_page_id = srwf_wu04_create_page( 'SRWF WU-04 Theme Override Page', 'publish', 'SRWF_WU04_PAGE_CONTENT_THEME_OVERRIDE' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $theme_page_id ), 'Could not configure theme override fixture.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $theme_page_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign theme override fixture page.' );
$folders = get_block_theme_folders( get_stylesheet() );
$theme_file = trailingslashit( get_stylesheet_directory() ) . trailingslashit( $folders['wp_template'] ) . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG . '.html';
srwf_wu04_assert( ! file_exists( $theme_file ), 'Qualified TT25 unexpectedly already contains the canonical SRWF template slug.' );
srwf_wu04_assert( false !== file_put_contents( $theme_file, $material_drift ), 'Theme override fixture file could not be created.' );
clearstatcache( true, $theme_file );
$theme_resolved = get_block_template( get_stylesheet() . '//' . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG, 'wp_template' );
srwf_wu04_assert( $theme_resolved instanceof WP_Block_Template && 'theme' === $theme_resolved->source, 'WordPress did not resolve the theme-file override fixture as expected.' );
$theme_diag = srwf_wu04_exercise_fixture(
	$evidence,
	'theme_override',
	\SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE,
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	\SRWF\HostCompanion\TemplateDiagnostics::THEME_OVERRIDE,
	$theme_page_id,
	0,
	$theme_file,
	'پوسته فعال نسخه‌ای با همین نام قالب دارد'
);
srwf_wu04_assert( false === $theme_diag['resolution']['evidence']['content_matches_canonical'], 'Material theme override was normalized into a false canonical match.' );
unlink( $theme_file );
clearstatcache( true, $theme_file );

$missing_template_page = srwf_wu04_create_page( 'SRWF WU-04 Missing Template Page', 'publish', 'SRWF_WU04_PAGE_CONTENT_MISSING_TEMPLATE' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $missing_template_page ), 'Could not configure missing-template fixture.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $missing_template_page );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign missing-template fixture page.' );
$unregistered = unregister_block_template( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME );
srwf_wu04_assert( $unregistered instanceof WP_Block_Template, 'Canonical template could not be unregistered for missing-template fixture.' );
srwf_wu04_assert( null === get_block_template( get_stylesheet() . '//' . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG, 'wp_template' ), 'Missing-template fixture still resolved before diagnostics.' );
srwf_wu04_exercise_fixture(
	$evidence,
	'missing_template',
	\SRWF\HostCompanion\TemplateDiagnostics::MISSING_TEMPLATE,
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	\SRWF\HostCompanion\TemplateDiagnostics::MISSING_TEMPLATE,
	$missing_template_page,
	0,
	'',
	'قالب مرجع SRWF در حال حاضر resolve نمی‌شود'
);
srwf_wu04_assert( null === get_block_template( get_stylesheet() . '//' . \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG, 'wp_template' ), 'Diagnostics silently re-registered/repaired the missing template.' );
$registered_again = \SRWF\HostCompanion\TemplateRegistrar::register();
srwf_wu04_assert( $registered_again instanceof WP_Block_Template, 'Canonical template could not be restored after disposable missing-template fixture.' );

$unknown_page_id = srwf_wu04_create_page( 'SRWF WU-04 Unknown Resolver Page', 'publish', 'SRWF_WU04_PAGE_CONTENT_UNKNOWN' );
srwf_wu04_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $unknown_page_id ), 'Could not configure unknown fixture.' );
$assign = \SRWF\HostCompanion\PageTemplateAssignment::assign( $unknown_page_id );
srwf_wu04_assert( ! empty( $assign['success'] ), 'Could not assign unknown fixture page.' );
add_filter( 'pre_get_block_template', 'srwf_wu04_unknown_resolver', 10, 3 );
$unknown_diag = srwf_wu04_exercise_fixture(
	$evidence,
	'unknown',
	\SRWF\HostCompanion\TemplateDiagnostics::UNKNOWN,
	\SRWF\HostCompanion\TemplateDiagnostics::PAGE_VALID,
	\SRWF\HostCompanion\TemplateDiagnostics::UNKNOWN,
	$unknown_page_id,
	0,
	'',
	'منبع قالب با اطمینان قابل طبقه‌بندی نیست'
);
srwf_wu04_assert( 'external-fixture' === $unknown_diag['resolution']['evidence']['source'], 'Unknown fixture provenance was not preserved as evidence.' );
remove_filter( 'pre_get_block_template', 'srwf_wu04_unknown_resolver', 10 );

$report = \SRWF\HostCompanion\TemplateDiagnostics::build_report( $canonical_diag );
srwf_wu04_assert( false !== strpos( $report, 'wordpress_version=' ), 'Diagnostic report omits WordPress version.' );
srwf_wu04_assert( false !== strpos( $report, 'expected_template_slug=registration-full-width' ), 'Diagnostic report omits expected template slug.' );
srwf_wu04_assert( false === strpos( $report, 'SRWF_WU04_PAGE_CONTENT_' ), 'Diagnostic report leaked synthetic page content.' );
srwf_wu04_assert( false === stripos( $report, 'nonce=' ), 'Diagnostic report contains nonce data.' );
srwf_wu04_assert( false === stripos( $report, 'cookie=' ), 'Diagnostic report contains cookie data.' );
srwf_wu04_assert( false === strpos( $report, 'runtime@example.invalid' ), 'Diagnostic report leaked administrator email.' );
$evidence['diagnostic_report'] = array(
	'status'                => 'PASS',
	'privacy_safe_fields'   => true,
	'contains_page_content' => false,
	'contains_nonce'        => false,
	'contains_cookie'       => false,
	'contains_user_email'   => false,
);

$required_states = array(
	'canonical',
	'wrong_page_assignment',
	'page_not_published',
	'page_missing',
	'page_trashed',
	'page_type_invalid',
	'customized_db_override',
	'theme_override',
	'missing_template',
	'unknown',
);
foreach ( $required_states as $state_name ) {
	srwf_wu04_assert( isset( $evidence['states'][ $state_name ] ) && 'PASS' === $evidence['states'][ $state_name ]['status'], 'Required WU-04 fixture did not PASS: ' . $state_name );
	srwf_wu04_assert( 'PASS' === $evidence['states'][ $state_name ]['non_mutation'], 'Non-mutation proof missing: ' . $state_name );
}

$evidence['claims'] = array(
	'diagnostics_runtime_proven'  => 'PASS',
	'check_again_read_only'        => 'PASS',
	'no_hidden_repair'             => 'PASS',
	'page_validity_reused'         => 'PASS',
	'normalized_drift_comparison'  => 'PASS',
	'privacy_safe_report'          => 'PASS',
	'qualification_lab_wu04_slice' => 'PASS',
);
$evidence['capture_status'] = 'PASS';

$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu04_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Unable to write WU-04 evidence.' );

fwrite( STDOUT, "WU-04 diagnostics/drift qualification assertions passed.\n" );
