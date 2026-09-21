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

function srwf_wu03_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-03 assertion failed: {$message}\n" );
		exit( 10 );
	}
}

function srwf_wu03_wp_die_handler( $message, $title = '', $args = array() ) {
	unset( $title, $args );
	throw new RuntimeException( wp_strip_all_tags( (string) $message ) );
}

function srwf_wu03_wp_die_handler_filter() {
	return 'srwf_wu03_wp_die_handler';
}

function srwf_wu03_create_page( $title, $status, $content = '' ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => $status,
			'post_title'   => $title,
			'post_content' => $content,
		),
		true
	);

	srwf_wu03_assert( ! is_wp_error( $id ) && $id > 0, 'Synthetic page could not be created: ' . $title );
	return (int) $id;
}

function srwf_wu03_process( $request ) {
	return \SRWF\HostCompanion\AdminSettings::process_save_apply( $request );
}

function srwf_wu03_process_inbox( $request ) {
	return \SRWF\HostCompanion\AdminSettings::process_inbox_save_apply( $request );
}

function srwf_wu03_content_hash( $page_id ) {
	return hash( 'sha256', (string) get_post_field( 'post_content', $page_id ) );
}

srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\Configuration', false ), 'Configuration class was not loaded.' );
srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\TemplateRegistrar', false ), 'TemplateRegistrar class was not loaded.' );
srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\PageTemplateAssignment', false ), 'PageTemplateAssignment class was not loaded.' );
srwf_wu03_assert( class_exists( 'SRWF\\HostCompanion\\AdminSettings', false ), 'AdminSettings class was not loaded.' );

$admin = get_user_by( 'login', 'runtime_admin' );
srwf_wu03_assert( $admin instanceof WP_User, 'Runtime administrator is unavailable.' );
wp_set_current_user( $admin->ID );
srwf_wu03_assert( current_user_can( 'manage_options' ), 'Runtime administrator lacks manage_options.' );

$page_a     = srwf_wu03_create_page( 'SRWF WU-03 Registration A', 'publish', 'REGISTRATION_A_CONTENT' );
$page_b     = srwf_wu03_create_page( 'SRWF WU-03 Registration B', 'publish', 'REGISTRATION_B_CONTENT' );
$inbox_a    = srwf_wu03_create_page( 'SRWF WU-03 Inbox A', 'publish', 'INBOX_A_CONTENT' );
$inbox_b    = srwf_wu03_create_page( 'SRWF WU-03 Inbox B', 'publish', 'INBOX_B_CONTENT' );
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
srwf_wu03_assert( has_action( 'admin_post_srwf_host_companion_save_apply', array( 'SRWF\\HostCompanion\\AdminSettings', 'handle_save_apply' ) ), 'Registration admin-post action is not registered.' );
srwf_wu03_assert( has_action( 'admin_post_srwf_host_companion_inbox_save_apply', array( 'SRWF\\HostCompanion\\AdminSettings', 'handle_inbox_save_apply' ) ), 'Inbox admin-post action is not registered.' );
$links = apply_filters( 'plugin_action_links_srwf-host-companion/srwf-host-companion.php', array() );
srwf_wu03_assert( isset( $links[0] ) && false !== strpos( $links[0], 'page=srwf-host' ), 'Plugin-row Settings action link is unavailable.' );

delete_option( \SRWF\HostCompanion\Configuration::OPTION_NAME );
update_post_meta( $page_a, '_wp_page_template', 'legacy-render-sentinel' );
$sentinel_before = get_page_template_slug( $page_a );
$_GET = array();
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$first_run_html = (string) ob_get_clean();
srwf_wu03_assert( false !== strpos( $first_run_html, 'راه‌اندازی اولیه' ), 'First-run orientation is missing.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'ذخیره و اعمال قالب تمام‌عرض' ), 'Registration explicit action is missing.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'srwf_registration_page_id' ), 'Registration selector is missing.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'srwf_inbox_page_id' ), 'Inbox selector is missing.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'صفحه اینباکس' ), 'Inbox concept is not independently labelled.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'Gravity Flow' ), 'Inbox ownership guidance is missing.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'فقط صفحه ثبت‌نام را پوشش می‌دهد' ), 'Registration-only diagnostics boundary is not explicit.' );
srwf_wu03_assert( false !== strpos( $first_run_html, 'Gravity Forms' ), 'First-run Registration ownership guidance is missing.' );
srwf_wu03_assert( null === get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ), 'Rendering created configuration state.' );
srwf_wu03_assert( $sentinel_before === get_page_template_slug( $page_a ), 'Rendering changed a page template assignment.' );

srwf_wu03_assert( \SRWF\HostCompanion\Configuration::set_registration_page_id( $page_a ), 'Could not seed canonical Registration configuration for render test.' );
update_post_meta( $page_a, '_wp_page_template', 'legacy-old-template' );
$config_before_render   = \SRWF\HostCompanion\Configuration::get();
$stored_before_render   = get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null );
$template_before_render = get_page_template_slug( $page_a );
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$configured_html = (string) ob_get_clean();
srwf_wu03_assert( false !== strpos( $configured_html, 'صفحه ثبت‌نام' ), 'Configured settings screen did not render Registration selector.' );
srwf_wu03_assert( false !== strpos( $configured_html, 'صفحه اینباکس' ), 'Configured settings screen did not render Inbox selector.' );
srwf_wu03_assert( $config_before_render === \SRWF\HostCompanion\Configuration::get(), 'Rendering changed normalized configuration.' );
srwf_wu03_assert( $stored_before_render === get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ), 'Rendering migrated or rewrote persistent configuration.' );
srwf_wu03_assert( $template_before_render === get_page_template_slug( $page_a ), 'Rendering changed configured page template.' );

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
$denied_inbox = srwf_wu03_process_inbox(
	array(
		\SRWF\HostCompanion\AdminSettings::INBOX_NONCE_NAME     => 'irrelevant',
		\SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $inbox_a,
	)
);
srwf_wu03_assert( 'insufficient_manage_options' === $denied_inbox['code'], 'Inbox mutation did not fail closed without manage_options.' );

wp_set_current_user( $admin->ID );
$admin_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$base_request = array(
	\SRWF\HostCompanion\AdminSettings::NONCE_NAME => $admin_nonce,
);

$config_before_invalid = \SRWF\HostCompanion\Configuration::get();
$invalid = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => '12oops' ) );
srwf_wu03_assert( 'invalid_page_id' === $invalid['code'], 'Malformed Registration page ID was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Malformed Registration page ID corrupted canonical configuration.' );

$missing = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $missing_id ) );
srwf_wu03_assert( 'page_missing' === $missing['code'], 'Missing Registration target was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Missing Registration target corrupted canonical configuration.' );

$trashed = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $trash_id ) );
srwf_wu03_assert( 'page_trashed' === $trashed['code'], 'Trashed Registration target was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Trashed Registration target corrupted canonical configuration.' );

$wrong_type = srwf_wu03_process( $base_request + array( \SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $post_id ) );
srwf_wu03_assert( 'page_type_invalid' === $wrong_type['code'], 'Wrong Registration post type was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Wrong Registration post type corrupted canonical configuration.' );

$page_b_before_nonce = get_page_template_slug( $page_b );
$nonce_failure = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => 'invalid-nonce',
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $page_b,
	)
);
srwf_wu03_assert( 'nonce_invalid' === $nonce_failure['code'], 'Invalid Registration nonce was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Registration nonce failure changed canonical configuration.' );
srwf_wu03_assert( $page_b_before_nonce === get_page_template_slug( $page_b ), 'Registration nonce failure changed target template.' );

$inbox_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::INBOX_NONCE_ACTION );
$inbox_base_request = array(
	\SRWF\HostCompanion\AdminSettings::INBOX_NONCE_NAME => $inbox_nonce,
);
$inbox_invalid = srwf_wu03_process_inbox( $inbox_base_request + array( \SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => '12oops' ) );
srwf_wu03_assert( 'invalid_page_id' === $inbox_invalid['code'], 'Malformed Inbox page ID was not rejected.' );
$inbox_missing = srwf_wu03_process_inbox( $inbox_base_request + array( \SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $missing_id ) );
srwf_wu03_assert( 'page_missing' === $inbox_missing['code'], 'Missing Inbox target was not rejected.' );
$inbox_trashed = srwf_wu03_process_inbox( $inbox_base_request + array( \SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $trash_id ) );
srwf_wu03_assert( 'page_trashed' === $inbox_trashed['code'], 'Trashed Inbox target was not rejected.' );
$inbox_wrong_type = srwf_wu03_process_inbox( $inbox_base_request + array( \SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $post_id ) );
srwf_wu03_assert( 'page_type_invalid' === $inbox_wrong_type['code'], 'Wrong Inbox post type was not rejected.' );
$inbox_nonce_failure = srwf_wu03_process_inbox(
	array(
		\SRWF\HostCompanion\AdminSettings::INBOX_NONCE_NAME     => 'invalid-nonce',
		\SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $inbox_a,
	)
);
srwf_wu03_assert( 'nonce_invalid' === $inbox_nonce_failure['code'], 'Invalid Inbox nonce was not rejected.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Failed Inbox requests changed canonical configuration.' );
srwf_wu03_assert( '' === get_page_template_slug( $inbox_a ), 'Failed Inbox requests changed target template.' );

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
srwf_wu03_assert( ! current_user_can( 'edit_post', $page_b ), 'Settings-only user unexpectedly has Registration target edit capability.' );
srwf_wu03_assert( ! current_user_can( 'edit_post', $inbox_a ), 'Settings-only user unexpectedly has Inbox target edit capability.' );
$limited_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$edit_denied = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => $limited_nonce,
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $page_b,
	)
);
srwf_wu03_assert( 'target_edit_denied' === $edit_denied['code'], 'Registration target edit capability denial did not block mutation.' );
$limited_inbox_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::INBOX_NONCE_ACTION );
$inbox_edit_denied = srwf_wu03_process_inbox(
	array(
		\SRWF\HostCompanion\AdminSettings::INBOX_NONCE_NAME     => $limited_inbox_nonce,
		\SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $inbox_a,
	)
);
srwf_wu03_assert( 'target_edit_denied' === $inbox_edit_denied['code'], 'Inbox target edit capability denial did not block mutation.' );
srwf_wu03_assert( $config_before_invalid === \SRWF\HostCompanion\Configuration::get(), 'Target edit denial changed canonical configuration.' );
srwf_wu03_assert( $page_b_before_nonce === get_page_template_slug( $page_b ), 'Registration target edit denial changed target template.' );
srwf_wu03_assert( '' === get_page_template_slug( $inbox_a ), 'Inbox target edit denial changed target template.' );

wp_set_current_user( $admin->ID );
$admin_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$draft_result = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => $admin_nonce,
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $draft_id,
	)
);
srwf_wu03_assert( true === $draft_result['success'], 'Authorized draft-page Registration apply did not succeed.' );
srwf_wu03_assert( 'registration' === $draft_result['role'], 'Registration result lost role identity.' );
srwf_wu03_assert( 'success_non_published' === $draft_result['code'], 'Draft-page apply did not retain non-published result semantics.' );
srwf_wu03_assert( 'PAGE_NOT_PUBLISHED' === $draft_result['page_state'], 'Draft-page result lost PAGE_NOT_PUBLISHED classification.' );
srwf_wu03_assert( true === $draft_result['configuration_saved'] && true === $draft_result['template_applied'], 'Draft-page result did not truthfully report save/apply state.' );
srwf_wu03_assert( $draft_id === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Draft page did not persist as canonical Registration page.' );
srwf_wu03_assert( 0 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Registration apply changed unconfigured Inbox role.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $draft_id ), 'Draft page template assignment did not read back.' );
srwf_wu03_assert( 'legacy-old-template' === get_page_template_slug( $page_a ), 'Changing Registration page silently rewrote the previous page.' );

$admin_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$published_result = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => $admin_nonce,
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $page_b,
	)
);
srwf_wu03_assert( true === $published_result['success'], 'Published-page Registration apply did not succeed.' );
srwf_wu03_assert( 'success' === $published_result['code'], 'Published-page Registration apply returned an unexpected result code.' );
srwf_wu03_assert( 'PAGE_VALID' === $published_result['page_state'], 'Published-page result lost PAGE_VALID classification.' );
srwf_wu03_assert( true === $published_result['configuration_saved'], 'Published-page result did not report configuration saved.' );
srwf_wu03_assert( true === $published_result['template_applied'], 'Published-page result did not report template applied.' );
srwf_wu03_assert( $page_b === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Published page did not persist as canonical Registration page.' );
srwf_wu03_assert( 0 === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Registration apply changed unconfigured Inbox role.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === get_page_template_slug( $page_b ), 'Published page did not receive canonical template.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $page_b ), 'Published page assignment readback failed.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === get_page_template_slug( $draft_id ), 'Previous draft page was silently rewritten when Registration selection changed again.' );

$registration_id_before_inbox         = \SRWF\HostCompanion\Configuration::get_registration_page_id();
$registration_template_before_inbox   = get_page_template_slug( $page_b );
$registration_content_before_inbox    = srwf_wu03_content_hash( $page_b );
$inbox_a_content_before               = srwf_wu03_content_hash( $inbox_a );
$stored_before_first_inbox_persistence = get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null );
srwf_wu03_assert( 1 === ( $stored_before_first_inbox_persistence['schema_version'] ?? 0 ), 'Registration-only state was expected to remain schema v1 before Inbox adoption.' );

$inbox_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::INBOX_NONCE_ACTION );
$inbox_first_result = srwf_wu03_process_inbox(
	array(
		\SRWF\HostCompanion\AdminSettings::INBOX_NONCE_NAME     => $inbox_nonce,
		\SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $inbox_a,
	)
);
srwf_wu03_assert( true === $inbox_first_result['success'], 'Authorized Inbox apply did not succeed.' );
srwf_wu03_assert( 'inbox' === $inbox_first_result['role'], 'Inbox result lost role identity.' );
srwf_wu03_assert( 'success' === $inbox_first_result['code'], 'Inbox apply returned an unexpected result code.' );
srwf_wu03_assert( true === $inbox_first_result['configuration_saved'] && true === $inbox_first_result['template_applied'], 'Inbox result did not truthfully report save/apply state.' );
srwf_wu03_assert( $inbox_a === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Inbox page did not persist as canonical Inbox page.' );
srwf_wu03_assert( $registration_id_before_inbox === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Inbox apply changed Registration configuration.' );
srwf_wu03_assert( $registration_template_before_inbox === get_page_template_slug( $page_b ), 'Inbox apply changed Registration page assignment.' );
srwf_wu03_assert( $registration_content_before_inbox === srwf_wu03_content_hash( $page_b ), 'Inbox apply changed Registration page content.' );
srwf_wu03_assert( $inbox_a_content_before === srwf_wu03_content_hash( $inbox_a ), 'Inbox apply changed Inbox page content.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $inbox_a ), 'Inbox assignment did not read back canonical existing template slug.' );
$stored_after_first_inbox = get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null );
srwf_wu03_assert( 2 === ( $stored_after_first_inbox['schema_version'] ?? 0 ), 'First explicit Inbox persistence did not upgrade stored schema to v2.' );
srwf_wu03_assert( $registration_id_before_inbox === $stored_after_first_inbox['roles']['registration']['page_id'], 'Schema-v2 upgrade lost Registration.' );
srwf_wu03_assert( $inbox_a === $stored_after_first_inbox['roles']['inbox']['page_id'], 'Schema-v2 upgrade did not persist Inbox.' );

update_post_meta( $inbox_a, '_wp_page_template', 'inbox-previous-page-sentinel' );
$inbox_a_content_before_change = srwf_wu03_content_hash( $inbox_a );
$inbox_b_content_before        = srwf_wu03_content_hash( $inbox_b );
$inbox_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::INBOX_NONCE_ACTION );
$inbox_second_result = srwf_wu03_process_inbox(
	array(
		\SRWF\HostCompanion\AdminSettings::INBOX_NONCE_NAME     => $inbox_nonce,
		\SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $inbox_b,
	)
);
srwf_wu03_assert( true === $inbox_second_result['success'], 'Changing Inbox page did not succeed.' );
srwf_wu03_assert( $inbox_a === $inbox_second_result['previous_page_id'], 'Inbox result did not truthfully report previous Inbox page.' );
srwf_wu03_assert( $inbox_b === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'New Inbox page did not become canonical config truth.' );
srwf_wu03_assert( 'inbox-previous-page-sentinel' === get_page_template_slug( $inbox_a ), 'Changing Inbox silently rewrote the previous Inbox page.' );
srwf_wu03_assert( $inbox_a_content_before_change === srwf_wu03_content_hash( $inbox_a ), 'Changing Inbox altered previous Inbox page content.' );
srwf_wu03_assert( $inbox_b_content_before === srwf_wu03_content_hash( $inbox_b ), 'Changing Inbox altered new Inbox page content.' );
srwf_wu03_assert( \SRWF\HostCompanion\TemplateRegistrar::TEMPLATE_SLUG === \SRWF\HostCompanion\PageTemplateAssignment::read( $inbox_b ), 'New Inbox page assignment readback failed.' );
srwf_wu03_assert( $registration_id_before_inbox === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Changing Inbox changed Registration configuration.' );
srwf_wu03_assert( $registration_template_before_inbox === get_page_template_slug( $page_b ), 'Changing Inbox changed Registration assignment.' );

$inbox_id_before_registration       = \SRWF\HostCompanion\Configuration::get_inbox_page_id();
$inbox_template_before_registration = get_page_template_slug( $inbox_b );
$inbox_content_before_registration  = srwf_wu03_content_hash( $inbox_b );
$admin_nonce = wp_create_nonce( \SRWF\HostCompanion\AdminSettings::NONCE_ACTION );
$registration_after_inbox_result = srwf_wu03_process(
	array(
		\SRWF\HostCompanion\AdminSettings::NONCE_NAME     => $admin_nonce,
		\SRWF\HostCompanion\AdminSettings::FIELD_PAGE_ID => (string) $page_a,
	)
);
srwf_wu03_assert( true === $registration_after_inbox_result['success'], 'Registration apply after Inbox configuration failed.' );
srwf_wu03_assert( $inbox_id_before_registration === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Registration apply changed Inbox configuration.' );
srwf_wu03_assert( $inbox_template_before_registration === get_page_template_slug( $inbox_b ), 'Registration apply changed Inbox assignment.' );
srwf_wu03_assert( $inbox_content_before_registration === srwf_wu03_content_hash( $inbox_b ), 'Registration apply changed Inbox page content.' );

$_GET = array( 'srwf_result' => 'success' );
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$spoofed_success_html = (string) ob_get_clean();
srwf_wu03_assert( false === strpos( $spoofed_success_html, 'فقط موفقیت همین عملیات' ), 'Unsigned result query produced a false success notice.' );

$_GET = array(
	'srwf_result' => 'success',
	\SRWF\HostCompanion\AdminSettings::RESULT_NONCE_NAME => wp_create_nonce( 'srwf_host_companion_result_success' ),
);
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$success_html = (string) ob_get_clean();
srwf_wu03_assert( false !== strpos( $success_html, 'فقط موفقیت همین عملیات' ), 'Successful Registration Owner-facing result overstates or omits the bounded claim.' );

$_GET = array(
	'srwf_result' => 'success',
	'srwf_role'   => 'inbox',
	\SRWF\HostCompanion\AdminSettings::RESULT_NONCE_NAME => wp_create_nonce( 'srwf_host_companion_result_inbox_success' ),
);
ob_start();
\SRWF\HostCompanion\AdminSettings::render_page();
$inbox_success_html = (string) ob_get_clean();
srwf_wu03_assert( false !== strpos( $inbox_success_html, 'فقط موفقیت همین عملیات' ), 'Successful Inbox Owner-facing result overstates or omits the bounded claim.' );

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
	'page_states'             => $states,
	'published_result'        => $published_result,
	'draft_result'            => $draft_result,
	'inbox_first_result'      => $inbox_first_result,
	'inbox_second_result'     => $inbox_second_result,
	'registration_after_inbox'=> $registration_after_inbox_result,
	'claims'                  => array(
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
		'independent_inbox_selector_present' => true,
		'inbox_security_guards_proven'       => true,
		'inbox_schema_v2_upgrade_proven'     => true,
		'inbox_assignment_readback_proven'   => true,
		'inbox_page_content_unchanged'       => true,
		'inbox_previous_page_not_rewritten'  => true,
		'inbox_preserves_registration'       => true,
		'registration_preserves_inbox'       => true,
		'browser_e2e_proven'                 => false,
		'full_width_geometry_proven'         => false,
		'diagnostics_drift_proven'           => false,
		'production_qualification_proven'    => false,
	),
);

$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu03_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Unable to write WU-03 evidence.' );

fwrite( STDOUT, "WU-03 Owner settings integration assertions passed.\n" );
