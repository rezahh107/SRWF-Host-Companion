<?php
/**
 * Focused WU-03 Inbox missing-nonce regression assertion.
 *
 * Runs in the same exact-target WordPress lab immediately after the main WU-03
 * probe and appends the bounded result to the same machine-readable evidence.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$evidence_path = getenv( 'SRWF_WU03_EVIDENCE_PATH' );
if ( ! $evidence_path || ! file_exists( $evidence_path ) ) {
	fwrite( STDERR, "WU-03 evidence is required before Inbox missing-nonce qualification.\n" );
	exit( 2 );
}

function srwf_wu03_inbox_nonce_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "WU-03 Inbox missing-nonce assertion failed: {$message}\n" );
		exit( 10 );
	}
}

$admin = get_user_by( 'login', 'runtime_admin' );
srwf_wu03_inbox_nonce_assert( $admin instanceof WP_User, 'Runtime administrator is unavailable.' );
wp_set_current_user( $admin->ID );
srwf_wu03_inbox_nonce_assert( current_user_can( 'manage_options' ), 'Runtime administrator lacks manage_options.' );

$registration_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'SRWF WU-03 Missing Nonce Registration Sentinel',
		'post_content' => 'WU03_MISSING_NONCE_REGISTRATION_SENTINEL',
	),
	true
);
$inbox_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'SRWF WU-03 Missing Nonce Inbox Target',
		'post_content' => 'WU03_MISSING_NONCE_INBOX_SENTINEL',
	),
	true
);
srwf_wu03_inbox_nonce_assert( ! is_wp_error( $registration_id ) && $registration_id > 0, 'Registration sentinel page could not be created.' );
srwf_wu03_inbox_nonce_assert( ! is_wp_error( $inbox_id ) && $inbox_id > 0, 'Inbox target page could not be created.' );
$registration_id = (int) $registration_id;
$inbox_id        = (int) $inbox_id;

delete_option( \SRWF\HostCompanion\Configuration::OPTION_NAME );
srwf_wu03_inbox_nonce_assert(
	\SRWF\HostCompanion\Configuration::set_registration_page_id( $registration_id ),
	'Could not seed Registration-only schema-v1 state.'
);

$config_before            = get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null );
$registration_before      = \SRWF\HostCompanion\Configuration::get_registration_page_id();
$inbox_before             = \SRWF\HostCompanion\Configuration::get_inbox_page_id();
$registration_template    = get_page_template_slug( $registration_id );
$inbox_template_before    = get_page_template_slug( $inbox_id );
$inbox_content_hash_before = hash( 'sha256', (string) get_post_field( 'post_content', $inbox_id ) );

$result = \SRWF\HostCompanion\AdminSettings::process_inbox_save_apply(
	array(
		\SRWF\HostCompanion\AdminSettings::INBOX_FIELD_PAGE_ID => (string) $inbox_id,
	)
);

srwf_wu03_inbox_nonce_assert( 'nonce_invalid' === $result['code'], 'Missing Inbox nonce was not rejected.' );
srwf_wu03_inbox_nonce_assert( 'inbox' === $result['role'], 'Missing-nonce result lost Inbox role identity.' );
srwf_wu03_inbox_nonce_assert( false === $result['success'], 'Missing Inbox nonce reported success.' );
srwf_wu03_inbox_nonce_assert( false === $result['configuration_saved'], 'Missing Inbox nonce reported configuration persistence.' );
srwf_wu03_inbox_nonce_assert( false === $result['template_applied'], 'Missing Inbox nonce reported template assignment.' );
srwf_wu03_inbox_nonce_assert( $config_before === get_option( \SRWF\HostCompanion\Configuration::OPTION_NAME, null ), 'Missing Inbox nonce changed raw configuration.' );
srwf_wu03_inbox_nonce_assert( $registration_before === \SRWF\HostCompanion\Configuration::get_registration_page_id(), 'Missing Inbox nonce changed Registration configuration.' );
srwf_wu03_inbox_nonce_assert( $inbox_before === \SRWF\HostCompanion\Configuration::get_inbox_page_id(), 'Missing Inbox nonce changed Inbox configuration.' );
srwf_wu03_inbox_nonce_assert( $registration_template === get_page_template_slug( $registration_id ), 'Missing Inbox nonce changed Registration assignment.' );
srwf_wu03_inbox_nonce_assert( $inbox_template_before === get_page_template_slug( $inbox_id ), 'Missing Inbox nonce changed Inbox target assignment.' );
srwf_wu03_inbox_nonce_assert( $inbox_content_hash_before === hash( 'sha256', (string) get_post_field( 'post_content', $inbox_id ) ), 'Missing Inbox nonce changed Inbox page content.' );

$evidence = json_decode( (string) file_get_contents( $evidence_path ), true );
srwf_wu03_inbox_nonce_assert( is_array( $evidence ), 'Existing WU-03 evidence is malformed.' );
$evidence['inbox_missing_nonce_result'] = $result;
$evidence['claims']['inbox_missing_nonce_fails_closed'] = true;
$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
srwf_wu03_inbox_nonce_assert( false !== $encoded && false !== file_put_contents( $evidence_path, $encoded . "\n" ), 'Could not append Inbox missing-nonce evidence.' );

fwrite( STDOUT, "WU-03 Inbox missing-nonce assertions passed.\n" );
