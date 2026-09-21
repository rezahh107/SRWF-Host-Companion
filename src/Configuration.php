<?php

namespace SRWF\HostCompanion;

final class Configuration {
	const OPTION_NAME           = 'srwf_host_companion_config';
	const SCHEMA_VERSION        = 2;
	const LEGACY_SCHEMA_VERSION = 1;

	/**
	 * Return the normalized schema-v2 configuration without mutating storage.
	 *
	 * Valid schema-v1 state is normalized in memory so the Registration page is
	 * preserved and Inbox starts unconfigured. Malformed or unsupported stored
	 * data is treated as unconfigured state. Unknown keys are not promoted into
	 * canonical truth.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		$stored = get_option( self::OPTION_NAME, null );

		if ( ! is_array( $stored ) ) {
			return self::defaults();
		}

		$schema_version = $stored['schema_version'] ?? null;
		if ( self::LEGACY_SCHEMA_VERSION === $schema_version ) {
			return self::normalize_schema_v1( $stored );
		}

		if ( self::SCHEMA_VERSION === $schema_version ) {
			return self::normalize_schema_v2( $stored );
		}

		return self::defaults();
	}

	/**
	 * Return the canonical Registration page ID, or zero when unconfigured.
	 *
	 * @return int
	 */
	public static function get_registration_page_id() {
		$config = self::get();
		return $config['roles']['registration']['page_id'];
	}

	/**
	 * Return the canonical Inbox page ID, or zero when unconfigured.
	 *
	 * @return int
	 */
	public static function get_inbox_page_id() {
		$config = self::get();
		return $config['roles']['inbox']['page_id'];
	}

	/**
	 * Persist one canonical Registration page ID while preserving Inbox.
	 *
	 * Registration-only legacy/unconfigured installs retain schema v1 until the
	 * Inbox capability is explicitly persisted. Once schema v2 exists, this
	 * setter preserves Inbox and remains on schema v2.
	 *
	 * @param mixed $page_id Registration page ID.
	 * @return bool True when the intended canonical value persists and reads back.
	 */
	public static function set_registration_page_id( $page_id ) {
		if ( ! is_int( $page_id ) || $page_id < 0 ) {
			return false;
		}

		$stored = get_option( self::OPTION_NAME, null );
		if ( self::can_preserve_schema_v1( $stored ) ) {
			$expected_stored = self::build_schema_v1( $page_id );
			update_option( self::OPTION_NAME, $expected_stored, false );

			return get_option( self::OPTION_NAME, null ) === $expected_stored
				&& self::get_registration_page_id() === $page_id
				&& 0 === self::get_inbox_page_id();
		}

		return self::persist_schema_v2_role( 'registration', $page_id );
	}

	/**
	 * Persist one canonical Inbox page ID in schema v2 while preserving Registration.
	 *
	 * This explicit Inbox mutation is the schema-v1 → schema-v2 persistence
	 * boundary. Merely reading/rendering configuration never writes migration state.
	 *
	 * @param mixed $page_id Inbox page ID.
	 * @return bool True when the canonical value is persisted and reads back.
	 */
	public static function set_inbox_page_id( $page_id ) {
		return self::persist_schema_v2_role( 'inbox', $page_id );
	}

	/**
	 * @param mixed $stored Raw stored option.
	 * @return bool
	 */
	private static function can_preserve_schema_v1( $stored ) {
		if ( null === $stored || false === $stored ) {
			return true;
		}

		if ( ! is_array( $stored ) || self::LEGACY_SCHEMA_VERSION !== ( $stored['schema_version'] ?? null ) ) {
			return false;
		}

		$normalized = self::normalize_schema_v1( $stored );
		return 0 === $normalized['roles']['inbox']['page_id'];
	}

	/**
	 * @param string $role Supported role key.
	 * @param mixed  $page_id Page ID.
	 * @return bool
	 */
	private static function persist_schema_v2_role( $role, $page_id ) {
		if ( ! in_array( $role, array( 'registration', 'inbox' ), true ) || ! is_int( $page_id ) || $page_id < 0 ) {
			return false;
		}

		$current         = self::get();
		$registration_id = $current['roles']['registration']['page_id'];
		$inbox_id        = $current['roles']['inbox']['page_id'];

		if ( 'registration' === $role ) {
			$registration_id = $page_id;
		} else {
			$inbox_id = $page_id;
		}

		$expected = self::build( $registration_id, $inbox_id );
		update_option( self::OPTION_NAME, $expected, false );

		return get_option( self::OPTION_NAME, null ) === $expected && self::get() === $expected;
	}

	/**
	 * @param array<string,mixed> $stored Stored schema-v1 value.
	 * @return array<string,mixed>
	 */
	private static function normalize_schema_v1( $stored ) {
		$roles = $stored['roles'] ?? null;
		if ( ! is_array( $roles ) ) {
			return self::defaults();
		}

		$registration = $roles['registration'] ?? null;
		$page_id      = is_array( $registration ) ? ( $registration['page_id'] ?? null ) : null;
		if ( ! is_int( $page_id ) || $page_id < 0 ) {
			return self::defaults();
		}

		return self::build( $page_id, 0 );
	}

	/**
	 * @param array<string,mixed> $stored Stored schema-v2 value.
	 * @return array<string,mixed>
	 */
	private static function normalize_schema_v2( $stored ) {
		$roles = $stored['roles'] ?? null;
		if ( ! is_array( $roles ) ) {
			return self::defaults();
		}

		$registration    = $roles['registration'] ?? null;
		$inbox           = $roles['inbox'] ?? null;
		$registration_id = is_array( $registration ) ? ( $registration['page_id'] ?? null ) : null;
		$inbox_id        = is_array( $inbox ) ? ( $inbox['page_id'] ?? null ) : null;

		if (
			! is_int( $registration_id ) || $registration_id < 0
			|| ! is_int( $inbox_id ) || $inbox_id < 0
		) {
			return self::defaults();
		}

		return self::build( $registration_id, $inbox_id );
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function defaults() {
		return self::build( 0, 0 );
	}

	/**
	 * @param int $registration_page_id Registration page ID.
	 * @param int $inbox_page_id Inbox page ID.
	 * @return array<string,mixed>
	 */
	private static function build( $registration_page_id, $inbox_page_id ) {
		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'roles'          => array(
				'registration' => array(
					'page_id' => $registration_page_id,
				),
				'inbox' => array(
					'page_id' => $inbox_page_id,
				),
			),
		);
	}

	/**
	 * @param int $registration_page_id Registration page ID.
	 * @return array<string,mixed>
	 */
	private static function build_schema_v1( $registration_page_id ) {
		return array(
			'schema_version' => self::LEGACY_SCHEMA_VERSION,
			'roles'          => array(
				'registration' => array(
					'page_id' => $registration_page_id,
				),
			),
		);
	}
}
