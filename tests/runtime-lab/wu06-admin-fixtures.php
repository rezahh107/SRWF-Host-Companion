<?php
/**
 * WU-06 exact-target disposable admin/browser fixture controller.
 *
 * Run through WP-CLI eval-file with the product plugin active. This script owns
 * synthetic qualification state only; product runtime behavior stays in src/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$command          = (string) getenv( 'SRWF_WU06_COMMAND' );
$state            = (string) getenv( 'SRWF_WU06_STATE' );
$fixture_path     = (string) getenv( 'SRWF_WU06_FIXTURE_PATH' );
$output_path      = (string) getenv( 'SRWF_WU06_OUTPUT_PATH' );
$tested_sha       = (string) getenv( 'SRWF_TESTED_COMMIT_SHA' );
$settings_pass    = (string) getenv( 'SRWF_WU06_SETTINGS_PASSWORD' );
$subscriber_pass  = (string) getenv( 'SRWF_WU06_SUBSCRIBER_PASSWORD' );

if ( '' === $command || '' === $fixture_path || '' === $tested_sha ) {
	fwrite( STDERR, "WU-06 command, fixture path, and tested SHA are required.\n" );
	exit( 2 );
}

function srwf_wu06_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-06 fixture assertion failed: {$message}\n" );
		exit( 10 );
	}
}

function srwf_wu06_write_json( $path, $value ) {
	$encoded = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	srwf_wu06_assert( false !== $encoded && false !== file_put_contents( $path, $encoded . "\n" ), 'Unable to write JSON output: ' . $path );
}

function srwf_wu06_read_fixture( $path ) {
	$raw = file_get_contents( $path );
	srwf_wu06_assert( false !== $raw, 'Fixture manifest is unavailable.' );
	$data = json_decode( $raw, true );
	srwf_wu06_assert( is_array( $data ), 'Fixture manifest is malformed.' );
	return $data;
}

function srwf_wu06_create_page( $title, $status, $content ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => $status,
			'post_title'   => $title,
			'post_content' => $content,
		),
		true
	);
	srwf_wu06_assert( ! is_wp_error( $id ) && $id > 0, 'Synthetic page could not be created: ' . $title );
	return (int) $id;
}

function srwf_wu06_theme_template_path() {
	$folders = get_block_theme_folders( get_stylesheet() );
	srwf_wu06_assert( is_array( $folders ) && ! empty( $folders['wp_template'] ), 'Block theme template folder is unavailable.' );
	return trailingslashit( wp_get_theme()->get_stylesheet_directory() )
		. trailingslashit( $folders['wp_template'] )
		. \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG
		. '.html';
}

function srwf_wu06_delete_db_overrides() {
	$statuses = array_keys( get_post_stati() );
	$ids      = get_posts(
		array(
			'post_type'        => 'wp_template',
			'post_status'      => $statuses,
			'name'             => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG,
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}
}

function srwf_wu06_reset_registered_template() {
	if ( function_exists( 'unregister_block_template' ) ) {
		unregister_block_template( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME );
	}
	$registered = \SRWF\HostCompanion\TemplateRegistrar::register();
	srwf_wu06_assert( $registered instanceof WP_Block_Template, 'Canonical product template could not be re-registered for fixture reset.' );
}

function srwf_wu06_set_default_template( $page_id ) {
	if ( 'theme_override' === (string) getenv( 'SRWF_WU06_STATE' ) ) {
		return;
	}

	$updated = wp_update_post(
		array(
			'ID'            => (int) $page_id,
			'page_template' => 'default',
		),
		true
	);
	srwf_wu06_assert( ! is_wp_error( $updated ), 'Could not restore default page template.' );
}

function srwf_wu06_assign_canonical( $page_id ) {
	$result = \SRWF\HostCompanion\PageTemplateAssignment::assign( (int) $page_id );
	srwf_wu06_assert( ! empty( $result['success'] ), 'Canonical page-template assignment failed.' );
	srwf_wu06_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( (int) $page_id ), 'Canonical assignment readback failed.' );
}

function srwf_wu06_create_db_override( $content ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'wp_template',
			'post_status'  => 'publish',
			'post_name'    => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG,
			'post_title'   => 'SRWF WU-06 DB Override',
			'post_content' => $content,
		),
		true
	);
	srwf_wu06_assert( ! is_wp_error( $id ) && $id > 0, 'Database override could not be created.' );
	$id    = (int) $id;
	$terms = wp_set_object_terms( $id, get_stylesheet(), 'wp_theme', false );
	srwf_wu06_assert( ! is_wp_error( $terms ), 'Database override theme term could not be assigned.' );
	update_post_meta( $id, 'origin', 'plugin' );
	clean_post_cache( $id );
	return $id;
}

function srwf_wu06_cleanup( $fixture ) {
	update_option( 'srwf_wu06_fixture_mode', '', false );
	if ( function_exists( 'srwf_wu06_unknown_candidates' ) ) {
		remove_filter( 'pre_get_block_templates', 'srwf_wu06_unknown_candidates', 10 );
	}

	srwf_wu06_delete_db_overrides();
	$theme_file = srwf_wu06_theme_template_path();
	if ( file_exists( $theme_file ) ) {
		srwf_wu06_assert( unlink( $theme_file ), 'Could not remove previous theme override fixture.' );
		clearstatcache( true, $theme_file );
	}

	srwf_wu06_reset_registered_template();

	foreach ( array( 'healthy', 'draft', 'apply_target' ) as $key ) {
		if ( ! empty( $fixture['pages'][ $key ] ) && get_post( (int) $fixture['pages'][ $key ] ) instanceof WP_Post ) {
			srwf_wu06_set_default_template( (int) $fixture['pages'][ $key ] );
		}
	}
	delete_option( \SRWF\HostCompanion\Configuration::OPTION_NAME );
}

function srwf_wu06_snapshot( $fixture ) {
	$config_raw = get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null );
	$config     = \SRWF\HostCompanion\Configuration::get();
	$page_id    = (int) ( $config['roles']['registration']['page_id'] ?? 0 );
	$post       = $page_id > 0 ? get_post( $page_id ) : null;
	$page       = null;
	if ( $post instanceof WP_Post ) {
		$page = array(
			'ID'                     => (int) $post->ID,
			'post_type'              => (string) $post->post_type,
			'post_status'            => (string) $post->post_status,
			'post_content_sha256'    => hash( 'sha256', (string) $post->post_content ),
			'page_template_meta'     => (string) get_post_meta( $post->ID, '_wp_page_template', true ),
			'page_template_readback' => 'page' === $post->post_type ? (string) \SRWF\HostCompanion\PageTemplateAssignment::read( (int) $post->ID ) : '',
		);
	}

	$tracked_pages = array();
	foreach ( array( 'healthy', 'draft', 'trashed', 'apply_target' ) as $key ) {
		$tracked_id = (int) ( $fixture['pages'][ $key ] ?? 0 );
		$tracked    = $tracked_id > 0 ? get_post( $tracked_id ) : null;
		if ( $tracked instanceof WP_Post ) {
			$tracked_pages[ $key ] = array(
				'ID'                     => (int) $tracked->ID,
				'post_type'              => (string) $tracked->post_type,
				'post_status'            => (string) $tracked->post_status,
				'post_content_sha256'    => hash( 'sha256', (string) $tracked->post_content ),
				'page_template_meta'     => (string) get_post_meta( $tracked->ID, '_wp_page_template', true ),
				'page_template_readback' => 'page' === $tracked->post_type ? (string) \SRWF\HostCompanion\PageTemplateAssignment::read( (int) $tracked->ID ) : '',
			);
		}
	}

	$db_templates = get_posts(
		array(
			'post_type'        => 'wp_template',
			'post_status'      => array_keys( get_post_stati() ),
			'name'             => \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG,
			'numberposts'      => -1,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'suppress_filters' => true,
		)
	);
	$db_sources = array();
	foreach ( $db_templates as $template ) {
		$terms = wp_get_object_terms( $template->ID, 'wp_theme', array( 'fields' => 'names' ) );
		$db_sources[] = array(
			'ID'                  => (int) $template->ID,
			'post_status'         => (string) $template->post_status,
			'post_content_sha256' => hash( 'sha256', (string) $template->post_content ),
			'origin'              => (string) get_post_meta( $template->ID, 'origin', true ),
			'wp_theme'            => is_wp_error( $terms ) ? array() : array_values( $terms ),
		);
	}

	$theme_file = srwf_wu06_theme_template_path();
	clearstatcache( true, $theme_file );

	return array(
		'configuration_raw'         => $config_raw,
		'configuration_normalized'  => $config,
		'registration_page_id'      => $page_id,
		'selected_object'           => $page,
		'tracked_pages'             => $tracked_pages,
		'database_override_sources' => $db_sources,
		'theme_file_source'         => array(
			'path_relative_to_theme' => str_replace( trailingslashit( wp_get_theme()->get_stylesheet_directory() ), '', $theme_file ),
			'exists'                 => file_exists( $theme_file ),
			'sha256'                 => file_exists( $theme_file ) ? hash_file( 'sha256', $theme_file ) : '',
		),
		'fixture_mode'              => (string) get_option( 'srwf_wu06_fixture_mode', '' ),
	);
}

function srwf_wu06_expected( $state ) {
	$map = array(
		'first_run'           => array( 'primary' => 'NEEDS_SETUP', 'page' => 'PAGE_UNCONFIGURED', 'resolution' => 'CANONICAL', 'action' => 'SELECT_PAGE' ),
		'valid_published'     => array( 'primary' => 'CANONICAL', 'page' => 'PAGE_VALID', 'resolution' => 'CANONICAL', 'action' => 'NONE' ),
		'valid_non_published' => array( 'primary' => 'PAGE_NOT_PUBLISHED', 'page' => 'PAGE_NOT_PUBLISHED', 'resolution' => 'CANONICAL', 'action' => 'REVIEW_PUBLICATION' ),
		'missing_configured'  => array( 'primary' => 'PAGE_INVALID', 'page' => 'PAGE_MISSING', 'resolution' => 'CANONICAL', 'action' => 'SELECT_PAGE' ),
		'trashed_page'        => array( 'primary' => 'PAGE_INVALID', 'page' => 'PAGE_TRASHED', 'resolution' => 'CANONICAL', 'action' => 'SELECT_PAGE' ),
		'wrong_post_type'     => array( 'primary' => 'PAGE_INVALID', 'page' => 'PAGE_TYPE_INVALID', 'resolution' => 'CANONICAL', 'action' => 'SELECT_PAGE' ),
		'wrong_assignment'    => array( 'primary' => 'WRONG_PAGE_ASSIGNMENT', 'page' => 'PAGE_VALID', 'resolution' => 'CANONICAL', 'action' => 'APPLY_TEMPLATE' ),
		'canonical_active'    => array( 'primary' => 'CANONICAL', 'page' => 'PAGE_VALID', 'resolution' => 'CANONICAL', 'action' => 'NONE' ),
		'database_override'   => array( 'primary' => 'CUSTOMIZED_DB_OVERRIDE', 'page' => 'PAGE_VALID', 'resolution' => 'CUSTOMIZED_DB_OVERRIDE', 'action' => 'REVIEW_OVERRIDE' ),
		'theme_override'      => array( 'primary' => 'THEME_OVERRIDE', 'page' => 'PAGE_VALID', 'resolution' => 'THEME_OVERRIDE', 'action' => 'REVIEW_OVERRIDE' ),
		'missing_template'    => array( 'primary' => 'MISSING_TEMPLATE', 'page' => 'PAGE_VALID', 'resolution' => 'MISSING_TEMPLATE', 'action' => 'CHECK_PLUGIN_TEMPLATE' ),
		'unknown'             => array( 'primary' => 'UNKNOWN', 'page' => 'PAGE_VALID', 'resolution' => 'UNKNOWN', 'action' => 'CHECK_AGAIN' ),
	);
	srwf_wu06_assert( isset( $map[ $state ] ), 'Unsupported WU-06 state: ' . $state );
	return $map[ $state ];
}

srwf_wu06_assert( class_exists( 'SRWF\\HostCompanion\\AdminSettings', false ), 'Product AdminSettings is not loaded.' );
srwf_wu06_assert( class_exists( 'SRWF\\HostCompanion\\TemplateDiagnostics', false ), 'Product TemplateDiagnostics is not loaded.' );

$admin = get_user_by( 'login', 'runtime_admin' );
srwf_wu06_assert( $admin instanceof WP_User, 'Runtime administrator is unavailable.' );
wp_set_current_user( $admin->ID );

if ( 'bootstrap' === $command ) {
	srwf_wu06_assert( '' !== $settings_pass && '' !== $subscriber_pass, 'Synthetic user passwords are required for bootstrap.' );

	$private_markers = implode(
		"\n",
		array(
			'<!-- wp:paragraph --><p>SRWF_WU06_STUDENT_VALUE_SECRET</p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p>https://uploads.example.invalid/SRWF_WU06_UPLOAD_URL_SECRET.pdf</p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p>SRWF_WU06_TOKEN_SECRET</p><!-- /wp:paragraph -->',
		)
	);

	$healthy_id = srwf_wu06_create_page( 'SRWF WU-06 Registration Healthy', 'publish', $private_markers );
	$draft_id   = srwf_wu06_create_page( 'SRWF WU-06 Registration Draft', 'draft', '<!-- wp:paragraph --><p>WU06 draft fixture</p><!-- /wp:paragraph -->' );
	$trash_id   = srwf_wu06_create_page( 'SRWF WU-06 Registration Trashed', 'draft', '<!-- wp:paragraph --><p>WU06 trash fixture</p><!-- /wp:paragraph -->' );
	$apply_id   = srwf_wu06_create_page( 'ZZZ SRWF WU-06 Apply Target', 'publish', '<!-- wp:paragraph --><p>WU06 apply fixture</p><!-- /wp:paragraph -->' );
	wp_trash_post( $trash_id );

	$deleted_id = srwf_wu06_create_page( 'SRWF WU-06 Deleted Registration', 'publish', '<!-- wp:paragraph --><p>WU06 deleted fixture</p><!-- /wp:paragraph -->' );
	wp_delete_post( $deleted_id, true );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => 'SRWF WU-06 Wrong Post Type',
			'post_content' => 'WU06 wrong type fixture',
		),
		true
	);
	srwf_wu06_assert( ! is_wp_error( $post_id ) && $post_id > 0, 'Wrong-post-type fixture could not be created.' );

	add_role(
		'srwf_wu06_settings_only',
		'SRWF WU-06 Settings Only',
		array(
			'read'           => true,
			'manage_options' => true,
		)
	);
	$settings_user_id = wp_create_user( 'wu06_settings_only', $settings_pass, 'wu06-settings-only@example.invalid' );
	srwf_wu06_assert( ! is_wp_error( $settings_user_id ), 'Settings-only user could not be created.' );
	( new WP_User( (int) $settings_user_id ) )->set_role( 'srwf_wu06_settings_only' );

	$subscriber_id = wp_create_user( 'wu06_subscriber', $subscriber_pass, 'wu06-subscriber@example.invalid' );
	srwf_wu06_assert( ! is_wp_error( $subscriber_id ), 'Subscriber user could not be created.' );
	( new WP_User( (int) $subscriber_id ) )->set_role( 'subscriber' );

	$theme = wp_get_theme();
	$fixture = array(
		'schema'            => 'srwf-host-companion-wu06-fixture-v1',
		'tested_commit_sha' => $tested_sha,
		'environment'       => array(
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
			'theme_stylesheet'  => get_stylesheet(),
			'theme_name'        => $theme->get( 'Name' ),
			'theme_version'     => $theme->get( 'Version' ),
			'locale'            => get_locale(),
		),
		'users' => array(
			'admin'         => 'runtime_admin',
			'settings_only' => 'wu06_settings_only',
			'subscriber'    => 'wu06_subscriber',
		),
		'pages' => array(
			'healthy'      => $healthy_id,
			'draft'        => $draft_id,
			'trashed'      => $trash_id,
			'deleted'      => $deleted_id,
			'wrong_type'   => (int) $post_id,
			'apply_target' => $apply_id,
		),
		'privacy_markers' => array(
			'SRWF_WU06_STUDENT_VALUE_SECRET',
			'https://uploads.example.invalid/SRWF_WU06_UPLOAD_URL_SECRET.pdf',
			'SRWF_WU06_TOKEN_SECRET',
			'wu06-settings-only@example.invalid',
			'wu06-subscriber@example.invalid',
		),
	);
	srwf_wu06_write_json( $fixture_path, $fixture );
	fwrite( STDOUT, "WU-06 synthetic fixture bootstrap PASS.\n" );
	exit( 0 );
}

$fixture = srwf_wu06_read_fixture( $fixture_path );

if ( 'snapshot' === $command ) {
	srwf_wu06_assert( '' !== $output_path, 'Snapshot output path is required.' );
	srwf_wu06_write_json( $output_path, srwf_wu06_snapshot( $fixture ) );
	fwrite( STDOUT, "WU-06 sentinel snapshot written.\n" );
	exit( 0 );
}

srwf_wu06_assert( 'prepare' === $command, 'Unsupported WU-06 command.' );
srwf_wu06_assert( '' !== $state && '' !== $output_path, 'Prepare state and output path are required.' );

srwf_wu06_cleanup( $fixture );
$pages          = $fixture['pages'];
$canonical      = \SRWF\HostCompanion\TemplateRegistrar::get_canonical_content();
srwf_wu06_assert( is_string( $canonical ) && '' !== $canonical, 'Canonical template source is unavailable.' );
$material_drift = $canonical . "\n<!-- wp:paragraph --><p>SRWF_WU06_MATERIAL_DRIFT</p><!-- /wp:paragraph -->\n";

switch ( $state ) {
	case 'first_run':
		delete_option( \SRWF\HostCompanion\Configuration::OPTION_NAME );
		break;
	case 'valid_published':
	case 'canonical_active':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['healthy'] ), 'Could not configure healthy page.' );
		srwf_wu06_assign_canonical( (int) $pages['healthy'] );
		break;
	case 'valid_non_published':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['draft'] ), 'Could not configure draft page.' );
		srwf_wu06_assign_canonical( (int) $pages['draft'] );
		break;
	case 'missing_configured':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['deleted'] ), 'Could not configure missing page ID.' );
		break;
	case 'trashed_page':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['trashed'] ), 'Could not configure trashed page.' );
		break;
	case 'wrong_post_type':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['wrong_type'] ), 'Could not configure wrong post type.' );
		break;
	case 'wrong_assignment':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['healthy'] ), 'Could not configure wrong-assignment page.' );
		srwf_wu06_set_default_template( (int) $pages['healthy'] );
		break;
	case 'database_override':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['healthy'] ), 'Could not configure DB-override page.' );
		srwf_wu06_assign_canonical( (int) $pages['healthy'] );
		srwf_wu06_create_db_override( $material_drift );
		break;
	case 'theme_override':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['healthy'] ), 'Could not configure theme-override page.' );
		$theme_file = srwf_wu06_theme_template_path();
		srwf_wu06_assert( is_dir( dirname( $theme_file ) ) && is_writable( dirname( $theme_file ) ), 'Qualified theme template directory is not writable.' );
		srwf_wu06_assert( false !== file_put_contents( $theme_file, $material_drift ), 'Could not create theme override fixture.' );
		clearstatcache( true, $theme_file );
		srwf_wu06_assign_canonical( (int) $pages['healthy'] );
		break;
	case 'missing_template':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['healthy'] ), 'Could not configure missing-template page.' );
		srwf_wu06_assign_canonical( (int) $pages['healthy'] );
		update_option( 'srwf_wu06_fixture_mode', 'missing_template', false );
		$unregistered = unregister_block_template( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_NAME );
		srwf_wu06_assert( $unregistered instanceof WP_Block_Template, 'Missing-template fixture could not unregister product template.' );
		break;
	case 'unknown':
		srwf_wu06_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( (int) $pages['healthy'] ), 'Could not configure UNKNOWN page.' );
		srwf_wu06_assign_canonical( (int) $pages['healthy'] );
		update_option( 'srwf_wu06_fixture_mode', 'unknown', false );
		add_filter( 'pre_get_block_templates', 'srwf_wu06_unknown_candidates', 10, 3 );
		break;
	default:
		srwf_wu06_assert( false, 'Unsupported prepare state: ' . $state );
}

$expected    = srwf_wu06_expected( $state );
$diagnostics = \SRWF\HostCompanion\TemplateDiagnostics::inspect();
srwf_wu06_assert( $expected['primary'] === $diagnostics['primary_state'], $state . ': primary state mismatch.' );
srwf_wu06_assert( $expected['page'] === $diagnostics['page']['code'], $state . ': page state mismatch.' );
srwf_wu06_assert( $expected['resolution'] === $diagnostics['resolution']['state'], $state . ': resolution state mismatch.' );
srwf_wu06_assert( $expected['action'] === $diagnostics['recommended_action'], $state . ': recommended action mismatch.' );

$result = array(
	'state'    => $state,
	'status'   => 'PASS',
	'expected' => $expected,
	'actual'   => array(
		'primary_state'      => $diagnostics['primary_state'],
		'page_state'         => $diagnostics['page']['code'],
		'assignment_state'   => $diagnostics['assignment']['state'],
		'resolution_state'   => $diagnostics['resolution']['state'],
		'recommended_action' => $diagnostics['recommended_action'],
	),
	'sentinel' => srwf_wu06_snapshot( $fixture ),
);
srwf_wu06_write_json( $output_path, $result );
fwrite( STDOUT, "WU-06 state prepared: {$state}\n" );
