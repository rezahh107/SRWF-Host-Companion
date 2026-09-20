<?php

namespace SRWF\HostCompanion;

final class Configuration {
	const OPTION_NAME    = 'srwf_host_companion_config';
	const SCHEMA_VERSION = 1;

	/**
	 * Return the normalized schema-v1 configuration.
	 *
	 * Malformed or unsupported stored data is treated as unconfigured state.
	 * Unknown keys are not promoted into canonical configuration truth.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		$stored = get_option( self::OPTION_NAME, null );

		if ( ! is_array( $stored ) || self::SCHEMA_VERSION !== ( $stored['schema_version'] ?? null ) ) {
			return self::defaults();
		}

		$roles = $stored['roles'] ?? null;
		if ( ! is_array( $roles ) ) {
			return self::defaults();
		}

		$registration = $roles['registration'] ?? null;
		$page_id      = is_array( $registration ) ? ( $registration['page_id'] ?? null ) : null;

		if ( ! is_int( $page_id ) || $page_id < 0 ) {
			return self::defaults();
		}

		return self::build( $page_id );
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
	 * Persist one canonical Registration page ID in schema v1.
	 *
	 * This is an internal storage primitive. Request authorization and target-page
	 * validation belong to the later Owner-facing mutation workflow.
	 *
	 * @param mixed $page_id Registration page ID.
	 * @return bool True when the canonical value is persisted and reads back.
	 */
	public static function set_registration_page_id( $page_id ) {
		if ( ! is_int( $page_id ) || $page_id < 0 ) {
			return false;
		}

		$expected = self::build( $page_id );
		update_option( self::OPTION_NAME, $expected, false );

		return self::get() === $expected;
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function defaults() {
		return self::build( 0 );
	}

	/**
	 * @param int $page_id Registration page ID.
	 * @return array<string,mixed>
	 */
	private static function build( $page_id ) {
		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'roles'          => array(
				'registration' => array(
					'page_id' => $page_id,
				),
			),
		);
	}
}
