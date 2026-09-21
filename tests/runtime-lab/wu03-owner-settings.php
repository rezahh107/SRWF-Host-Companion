<?php
/**
 * WU-03 exact-target disposable integration probe.
 *
 * Run through WP-CLI eval-file with the product plugin active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$evidence_path = getenv( 'SRWF_WU03_EVIDENCE_PATH' );

if ( ! $evidence_path ) {
	fwrite( STDERR, "SRWF_WU03_EVIDENCE_PATH is required.\n" );
	exit( 2 );
}

/**
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function srwf_wu03_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-03 assertion failed: {$message}\n" );
		exit( 10 );
	}
}

/**
 * @param string $message Message.
 * @param string $title Title.
 * @param array  $args Args.
 * @throws RuntimeException Always.
 * @return void
 */
function srwf_wu03_wp_die_handler( $message, $title = '', $args = array() ) {
	unset( $title, $args );
	throw new RuntimeException( wp_strip_all_tags( (string) $message ) );
}

/**
 * @return string
 */
function srwf_wu03_wp_die_handler_filter() {
	return 'srwf_wu03_wp_die_handler';
}

/**
 * @param string $title Synthetic title.
 * @param string $status Post status.
 * @return int
 */
function srwf_wu03_create_page( $title, $status ) {
	$id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => $status,
			'post_title'  => $title,
		),
		true
	);

	srwf_wu03_assert( ! is_wp_error( $id ) && $id > 0, 'Synthetic page could not be created: ' . $title );
	return (int) $id;
}

/**
 * @param array<string,mixed> $request Request.
 * @return array<string,mixed>
 */
function srwf_wu03_process( $request ) {
	return \SRWF\HostCompanion\AdminSettings::process_save_apply( $request );
}

srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\Configuration', false ), 'Configuration class was not loaded.' );
srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\TemplateRegistrar', false ), 'TemplateRegistrar class was not loaded.' );
srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\PageTemplateAssignment', false ), 'PageTemplateAssignment class was not loaded.' );
srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\AdminSettings', false ), 'AdminSettings class was not loaded.' );

$admin = get_user_by( 'login', 'runtime_admin' );
srwf_wu03_assert( $admin instanceof WP_User, 'Runtime administrator is unavailable.' );
wp_set_current_user( $admin->ID );
srwf_wu03_assert( current_user_can( 'manage_options' ), 'Runtime administrator lacks manage_options.' );

$page_a     = srwf_wu03_create_page( 'SRWF WU-03 Registration A', 'publish' );
$page_b     = srwf_wu03_create_page( 'SRWF WU-03 Registration B', 'publish' );
$draft_id   = srwf_wu03_create_page( 'SRWF WU-03 Draft Registration', 'draft' );
$private_id = srwf_wu03_create_page( 'SRWF WU-03 Private Registration', 'private' );
$trash_id   = srwf_wu03_create_page( 'SRWF WU-03 Trashed Registration', 'draft' );
wp_trash_post( $trash_id );

$post_id = wp_insert_post(
	array(
		'post_type'   => 'post',
		'post_status' => 'publish',
		'post_title'  => 'SRWF WU-03 Wrong Post Type',
	),
	true
);
srwf_wu03_assert( ! is_wp_error( $post_id ) && $post_id > 0, 'Synthetic non-page object could not be created.' );
$post_id = (int) $post_id;

$missing_id = 999999999;

$states = array(
	'published'  => \SRWF\HostCompanion\AdminSettings::classify_page( $page_a ),
	'draft'      => \SRWF\HostCompanion\AdminSettings::classify_page( $draft_id ),
	'private'    => \SRWF\HostCompanion\AdminSettings::classify_page( $private_id ),
	'trashed'    => \SRWF\HostCompanion\AdminSettings::classify_page( $trash_id ),
	'wrong_type' => \SRWF\HostCompanion\AdminSettings::classify_page( $post_id ),
	'missing'    => \SRWF\HostCompanion\AdminSettings::classify_page( $missing_id ),
);
srwf_wu03_assert( 'PAGE_VALID' === $states['published']['code'], 'Published page was not PAGE_VALID.' );
srwf_wu03_assert( 'PAGE_NOT_PUBLISHED' === $states['draft']['code'], 'Draft page was not PAGE_NOT_PUBLISHED.' );
srwf_wu03_assert( 'PAGE_NOT_PUBLISHED' === $states['private']['code'], 'Private page was not PAGE_NOT_PUBLISHED.' );
srwf_wu03_assert( 'PAGE_TRASHED' === $states['trashed']['code'], 'Trashed page was not PAGE_TRASHED.' );
srwf_wu03_assert( 'PAGE_TYPE_INVALID' === $states['wrong_type']['code'], 'Wrong post type was not PAGE_TYPE_INVALID.' );
srwf_wu03_assert( 'PAGE_MISSING' === $states['missing']['code'], 'Missing object was not PAGE_MISSING.' );

srwf_wu03_assert( has_action( 'admin_menu', array( 'SRWF\\HostCompanion\\AdminSettings', 'register_menu' ) ), 'Settings menu hook is not registered.' );
srwf_wu03_assert( has_action( 'admin_post_srwf_host_companion_save_apply', array( 'SRWF\\HostCompanion\\AdminSettings', 'handle_save_apply' ) ), 'Explicit admin-post action is not registered.' );
$links = apply_filters( 'plugin_action_links_srwf-host-companion/srwf-host-companion.php', array() );
srwf_wu03_assert( isset( $links[0] ) && false !== strpos( $links[0], 'page=srwf-host' ), 'Plugin-row Settings action link is unavailable.' );

// First-run render is informative and side-effect-free.
delete_option( \SRWF\HostCompanion\Configuration::OPTION_NAME );
update_post_meta( $page_a, '_wp_page_template', 'legacy-render-sentinel' );
$sentinel_before = get_page_template_slug( $page_a );
$_GET = array();
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$first_run_html = (string) ob_get_clean();
srwf_wu03_assert( false !== strpos( $first_run_html, 'راه‌اندازی اولیه' ), 'First-run orientation is missing.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'ذخیره و اعمال قالب تمام‌عرض' ), 'Primary explicit action is missing.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'Gravity Forms' ), 'First-run ownership guidance is missing.' );
srwf_wu03_assert( null === get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ), 'Rendering created configuration state.' );
srwf_wu03_assert( $sentinel_before === get_page_template_slug( $page_a ), 'Rendering changed a page template assignment.' );

// Rendering an existing configuration is also side-effect-free.
srwf_wu03_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $page_a ), 'Could not seed canonical configuration for render test.' );
update_post_meta( $page_a, '_wp_page_template', 'legacy-old-template' );
$config_before_render   = \SRWF\HostCompanion\Configuration::get();
$template_before_render = get_page_template_slug( $page_a );
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$configured_html = (string) ob_get_clean();
srwf_wu03_assert( false !== strpos( $configured_html, 'صفحه ثبت‌نام' ), 'Configured settings screen did not render selector.' );
srwf_wu03_assert( $config_before_render === \SRWF\HostCompanion\Configuration::get(), 'Rendering changed stored configuration.' );
srwf_wu03_assert( $template_before_render === get_page_template_slug( $page_a ), 'Rendering changed configured page template.' );

// Users without manage_options cannot render the screen.
$subscriber_id = wp_create_user( 'srwf_wu03_subscriber', wp_generate_password( 32, true, true ), 'srwf-wu03-subscriber@example.invalid' );
srwf_wu03_assert( ! is_wp_error( $subscriber_id ), 'Subscriber user could not be created.' );
$subscriber = new WP_User( (int) $subscriber_id );
$subscriber->set_role( 'subscriber' );
wp_set_current_user( (int) $subscriber_id );
add_filter( 'wp_die_handler', 'srwf_wu03_wp_die_handler_filter' );
$denied_render = false;
try {
	\SRWF\HostCompanion\AdminSettings::render_page();
} catch ( RuntimeException $exception ) {
	$denied_render = false !== strpos( $exception->getMessage(), 'اجازه دسترسی' );
}
remove_filter( 'wp_die_handler', 'srwf_wu03_wp_die_handler_filter' );
srwf_wu03_assert( $denied_render, 'User without manage_options could render the settings screen.' );

wp_set_current_user( $admin->ID );
$admin_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$base_request = array(
	\SRWF\HostCompanion\AdminSettings::NONCE_NAME => $admin_nonce,
);

// Malformed, missing, trashed and wrong-type targets fail closed without corrupting canonical configuration.
$config_before_invalid = \SRWF\HostCompanion\Configuration::get();
$invalid = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => '12oops' ) );
srwf_wu03_assert( 'invalid_page_id' === $invalid['code'], 'Malformed page ID was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Malformed page ID corrupted canonical configuration.' );

$missing = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $missing_id ) );
srwf_wu03_assert( 'page_missing' === $missing['code'], 'Missing target was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Missing target corrupted canonical configuration.' );

$trashed = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $trash_id ) );
srwf_wu03_assert( 'page_trashed' === $trashed['code'], 'Trashed target was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Trashed target corrupted canonical configuration.' );

$wrong_type = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $post_id ) );
srwf_wu03_assert( 'page_type_invalid' === $wrong_type['code'], 'Wrong post type was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Wrong post type corrupted canonical configuration.' );

// Nonce failure prevents all mutation.
$page_b_before_nonce = get_page_template_slug( $page_b );
$nonce_failure = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => 'invalid-nonce',
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $page_b,
	)
);
srwf_wu03_assert( 'nonce_invalid' === $nonce_failure['code'], 'Invalid nonce was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Nonce failure changed canonical configuration.' );
srwf_wu03_assert( $page_b_before_nonce === get_page_template_slug( $page_b ), 'Nonce failure changed target template.' );

// manage_options alone is insufficient when the user cannot edit the selected page.
add_role(
	'srwf_wu03_settings_only',
	'SRWF WU-03 Settings Only',
	array(
		'read'           => true,
		'manage_options' => true,
	)
);
$limited_user_id = wp_create_user( 'srwf_wu03_settings_only', wp_generate_password( 32, true, true ), 'srwf-wu03-settings-only@example.invalid' );
srwf_wu03_assert( ! is_wp_error( $limited_user_id ), 'Settings-only user could not be created.' );
$limited_user = new WP_User( (int) $limited_user_id );
$limited_user->set_role( 'srwf_wu03_settings_only' );
wp_set_current_user( (int) $limited_user_id );
srwf_wu03_assert( current_user_can( 'manage_options' ), 'Settings-only user lacks manage_options.' );
srwf_wu03_assert( ! current_user_can( 'edit_post', $page_b ), 'Settings-only user unexpectedly has target edit capability.' );
$limited_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$edit_denied = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => $limited_nonce,
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $page_b,
	)
);
srwf_wu03_assert( 'target_edit_denied' === $edit_denied['code'], 'Target edit capability denial did not block mutation.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Target edit denial changed canonical configuration.' );
srwf_wu03_assert( $page_b_before_nonce === get_page_template_slug( $page_b ), 'Target edit denial changed target template.' );

// Draft/private pages are valid pages but are classified as non-published; an authorized explicit apply is allowed.
wp_set_current_user( $admin->ID );
$admin_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$draft_result = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => $admin_nonce,
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $draft_id,
	)
);
srwf_wu03_assert( true === $draft_result['success'], 'Authorized draft-page apply did not succeed.' );
srwf_wu03_assert( 'success_non_published' === $draft_result['code'], 'Draft-page apply did not retain non-published result semantics.' );
srwf_wu03_assert( 'PAGE_NOT_PUBLISHED' === $draft_result['page_state'], 'Draft-page result lost PAGE_NOT_PUBLISHED classification.' );
srwf_wu03_assert( true === $draft_result['configuration_saved'] && true === $draft_result['template_applied'], 'Draft-page result did not truthfully report save/apply state.' );
srwf_wu03_assert( $draft_id === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Draft page did not persist as canonical Registration page.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $draft_id ), 'Draft page template assignment did not read back.' );
srwf_wu03_assert( 'legacy-old-template' === get_page_template_slug( $page_a ), 'Changing Registration page silently rewrote the previous page.' );

// A published-page apply persists, assigns, reads back, and reports only the bounded operation as successful.
$admin_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$published_result = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => $admin_nonce,
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $page_b,
	)
);
srwf_wu03_assert( true === $published_result['success'], 'Published-page apply did not succeed.' );
srwf_wu03_assert( 'success' === $published_result['code'], 'Published-page apply returned an unexpected result code.' );
srwf_wu03_assert( 'PAGE_VALID' === $published_result['page_state'], 'Published-page result lost PAGE_VALID classification.' );
srwf_wu03_assert( true === $published_result['configuration_saved'], 'Published-page result did not report configuration saved.' );
srwf_wu03_assert( true === $published_result['template_applied'], 'Published-page result did not report template applied.' );
srwf_wu03_assert( $page_b === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Published page did not persist as canonical Registration page.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === get_page_template_slug( $page_b ), 'Published page did not receive canonical template.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $page_b ), 'Published page assignment readback failed.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === get_page_template_slug( $draft_id ), 'Previous draft page was silently rewritten when selection changed again.' );

$_GET = array( 'srwf_result' => 'success' );
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$success_html = (string) ob_get_clean();
srwf_wu03_assert( false !== strpos( $success_html, 'فقط موفقیت همین عملیات' ), 'Successful Owner-facing result overstates or omits the bounded claim.' );

$theme = wp_get_theme();
$evidence = array(
	'schema'          => 'srwf-host-companion-wu03-owner-settings-v1',
	'evidence_class'  => 'DISPOSABLE_CI_WORDPRESS_INTEGRATION',
	'observed_at_utc' => gmdate( 'c' ),
	'environment'     => array(
		'wordpress_version' => get_bloginfo( 'version' ),
		'php_version'       => PHP_VERSION,
		'theme_stylesheet'  => get_stylesheet(),
		'theme_name'        => $theme->get( 'Name' ),
		'theme_version'     => $theme->get( 'Version' ),
	),
	'page_states'      => $states,
	'published_result' => $published_result,
	'draft_result'     => $draft_result,
	'claims'           => array(
		'plugin_load'                        => true,
		'settings_surface_registered'        => true,
		'unauthorized_render_blocked'        => true,
		'render_side_effect_free'            => true,
		'valid_save_persists_configuration'  => true,
		'valid_apply_assigns_and_reads_back' => true,
		'previous_page_not_rewritten'        => true,
		'invalid_target_fails_closed'        => true,
		'wrong_post_type_fails_closed'       => true,
		'non_published_distinguished'        => true,
		'nonce_failure_fails_closed'         => true,
		'target_edit_capability_enforced'    => true,
		'truthful_success_result'            => true,
		'browser_e2e_proven'                 => false,
		'full_width_geometry_proven'         => false,
		'diagnostics_drift_proven'           => false,
		'production_qualification_proven'    => false,
	),
);

$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu03_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Unable to write WU-03 evidence.' );

fwrite( STDOUT, "WU-03 Owner settings integration assertions passed.\n" );
