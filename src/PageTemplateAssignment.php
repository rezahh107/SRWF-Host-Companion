<?php

namespace SRWF\HostCompanion;

final class PageTemplateAssignment {
	/**
	 * Assign the canonical Registration template to one WordPress page.
	 *
	 * This adapter deliberately does not change configuration and does not touch
	 * any previously configured Registration page.
	 *
	 * @param mixed $page_id Target page ID.
	 * @return array<string,mixed>
	 */
	public static function assign( $page_id ) {
		if ( ! is_int( $page_id ) || $page_id <= 0 ) {
			return self::result( false, 'invalid_page_id', 0, '' );
		}

		$post = get_post( $page_id );

		if ( ! $post instanceof \WP_Post ) {
			return self::result( false, 'target_not_found', $page_id, '' );
		}

		if ( 'page' !== $post->post_type ) {
			return self::result( false, 'target_not_page', $page_id, get_page_template_slug( $page_id ) );
		}

		$updated = wp_update_post(
			array(
				'ID'            => $page_id,
				'page_template' => TemplateRegistrar::TEMPLATE_SLUG,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return self::result(
				false,
				'assignment_failed',
				$page_id,
				get_page_template_slug( $page_id ),
				$updated->get_error_code()
			);
		}

		$actual = get_page_template_slug( $page_id );

		if ( TemplateRegistrar::TEMPLATE_SLUG !== $actual ) {
			return self::result( false, 'readback_mismatch', $page_id, $actual );
		}

		return self::result( true, 'assigned', $page_id, $actual );
	}

	/**
	 * Read the currently persisted page-template slug without mutation.
	 *
	 * @param mixed $page_id Target page ID.
	 * @return string|false
	 */
	public static function read( $page_id ) {
		if ( ! is_int( $page_id ) || $page_id <= 0 ) {
			return false;
		}

		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return false;
		}

		return get_page_template_slug( $page_id );
	}

	/**
	 * @param bool   $success Result state.
	 * @param string $code Result code.
	 * @param int    $page_id Target page ID.
	 * @param mixed  $actual Actual readback value.
	 * @param string $error_code Optional WordPress error code.
	 * @return array<string,mixed>
	 */
	private static function result( $success, $code, $page_id, $actual, $error_code = '' ) {
		return array(
			'success'       => $success,
			'code'          => $code,
			'page_id'       => $page_id,
			'expected_slug' => TemplateRegistrar::TEMPLATE_SLUG,
			'actual_slug'   => $actual,
			'error_code'    => $error_code,
		);
	}
}
