<?php

namespace SRWF\HostCompanion;

final class TemplateDiagnostics {
	const PAGE_UNCONFIGURED = 'PAGE_UNCONFIGURED';
	const PAGE_VALID        = 'PAGE_VALID';
	const PAGE_NOT_PUBLISHED = 'PAGE_NOT_PUBLISHED';
	const PAGE_MISSING      = 'PAGE_MISSING';
	const PAGE_TRASHED      = 'PAGE_TRASHED';
	const PAGE_TYPE_INVALID = 'PAGE_TYPE_INVALID';

	const ASSIGNMENT_EXPECTED = 'ASSIGNMENT_EXPECTED';
	const WRONG_PAGE_ASSIGNMENT = 'WRONG_PAGE_ASSIGNMENT';
	const ASSIGNMENT_UNAVAILABLE = 'ASSIGNMENT_UNAVAILABLE';

	const CANONICAL              = 'CANONICAL';
	const CUSTOMIZED_DB_OVERRIDE = 'CUSTOMIZED_DB_OVERRIDE';
	const THEME_OVERRIDE         = 'THEME_OVERRIDE';
	const MISSING_TEMPLATE       = 'MISSING_TEMPLATE';
	const UNKNOWN                = 'UNKNOWN';

	/**
	 * Inspect current Registration host state without mutation.
	 *
	 * @return array<string,mixed>
	 */
	public static function inspect() {
		$page_id = Configuration::get_registration_page_id();
		$page    = 0 === $page_id ? self::unconfigured_page() : self::classify_page( $page_id );
		$assignment = self::inspect_assignment( $page_id, $page );
		$resolution = self::inspect_resolution();

		$primary_state = self::primary_state( $page, $assignment, $resolution );
		$status        = self::overall_status( $primary_state );

		return array(
			'status'           => $status,
			'primary_state'    => $primary_state,
			'page'             => $page,
			'assignment'       => $assignment,
			'resolution'       => $resolution,
			'recommended_action' => self::recommended_action( $page, $assignment, $resolution ),
		);
	}

	/**
	 * Reuse one page-validity model for mutation and diagnostics.
	 *
	 * @param int $page_id Candidate object ID.
	 * @return array<string,mixed>
	 */
	public static function classify_page( $page_id ) {
		if ( ! is_int( $page_id ) || $page_id <= 0 ) {
			return array(
				'code'        => self::PAGE_MISSING,
				'page_id'     => max( 0, (int) $page_id ),
				'post_status' => '',
				'post_type'   => '',
			);
		}

		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post ) {
			return array(
				'code'        => self::PAGE_MISSING,
				'page_id'     => $page_id,
				'post_status' => '',
				'post_type'   => '',
			);
		}

		if ( 'page' !== $post->post_type ) {
			return array(
				'code'        => self::PAGE_TYPE_INVALID,
				'page_id'     => $page_id,
				'post_status' => (string) $post->post_status,
				'post_type'   => (string) $post->post_type,
			);
		}

		if ( 'trash' === $post->post_status ) {
			return array(
				'code'        => self::PAGE_TRASHED,
				'page_id'     => $page_id,
				'post_status' => 'trash',
				'post_type'   => 'page',
			);
		}

		return array(
			'code'        => 'publish' === $post->post_status ? self::PAGE_VALID : self::PAGE_NOT_PUBLISHED,
			'page_id'     => $page_id,
			'post_status' => (string) $post->post_status,
			'post_type'   => 'page',
		);
	}

	/**
	 * Normalize block markup through WordPress's parser/serializer.
	 *
	 * @param string $content Block template content.
	 * @return string
	 */
	public static function normalize_content( $content ) {
		if ( ! is_string( $content ) ) {
			return '';
		}

		return serialize_blocks( parse_blocks( $content ) );
	}

	/**
	 * Build a stable technical fingerprint from normalized block markup.
	 *
	 * @param string $content Block template content.
	 * @return string
	 */
	public static function fingerprint_content( $content ) {
		return hash( 'sha256', self::normalize_content( $content ) );
	}

	/**
	 * Privacy-minimized support report. No page content, user data, nonces or form data.
	 *
	 * @param array<string,mixed> $diagnostics Diagnostics returned by inspect().
	 * @return string
	 */
	public static function build_report( $diagnostics ) {
		$theme      = wp_get_theme();
		$page       = isset( $diagnostics['page'] ) && is_array( $diagnostics['page'] ) ? $diagnostics['page'] : array();
		$assignment = isset( $diagnostics['assignment'] ) && is_array( $diagnostics['assignment'] ) ? $diagnostics['assignment'] : array();
		$resolution = isset( $diagnostics['resolution'] ) && is_array( $diagnostics['resolution'] ) ? $diagnostics['resolution'] : array();
		$evidence   = isset( $resolution['evidence'] ) && is_array( $resolution['evidence'] ) ? $resolution['evidence'] : array();

		$lines = array(
			__( 'SRWF Host Companion diagnostic report', 'srwf-host-companion' ),
			'plugin_version=' . ( defined( 'SRWF_HOST_COMPANION_VERSION' ) ? SRWF_HOST_COMPANION_VERSION : 'unknown' ),
			'wordpress_version=' . get_bloginfo( 'version' ),
			'php_version=' . PHP_VERSION,
			'theme=' . get_stylesheet(),
			'theme_version=' . $theme->get( 'Version' ),
			'overall_status=' . (string) ( $diagnostics['status'] ?? self::UNKNOWN ),
			'diagnostic_state=' . (string) ( $diagnostics['primary_state'] ?? self::UNKNOWN ),
			'page_id=' . (string) ( $page['page_id'] ?? 0 ),
			'page_state=' . (string) ( $page['code'] ?? self::UNKNOWN ),
			'page_status=' . (string) ( $page['post_status'] ?? '' ),
			'expected_template_slug=' . TemplateRegistrar::TEMPLATE_SLUG,
			'actual_assignment=' . (string) ( $assignment['actual_slug'] ?? '' ),
			'assignment_state=' . (string) ( $assignment['state'] ?? self::ASSIGNMENT_UNAVAILABLE ),
			'resolution_state=' . (string) ( $resolution['state'] ?? self::UNKNOWN ),
			'resolved_id=' . (string) ( $evidence['id'] ?? '' ),
			'resolved_source=' . (string) ( $evidence['source'] ?? '' ),
			'resolved_origin=' . (string) ( $evidence['origin'] ?? '' ),
			'resolved_plugin=' . (string) ( $evidence['plugin'] ?? '' ),
			'resolved_wp_id=' . (string) ( $evidence['wp_id'] ?? '' ),
			'canonical_fingerprint=' . (string) ( $evidence['canonical_fingerprint'] ?? '' ),
			'resolved_fingerprint=' . (string) ( $evidence['resolved_fingerprint'] ?? '' ),
			'content_matches_canonical=' . self::bool_text( $evidence['content_matches_canonical'] ?? null ),
			'recommended_action=' . (string) ( $diagnostics['recommended_action'] ?? 'CHECK_AGAIN' ),
		);

		return implode( "\n", $lines );
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function unconfigured_page() {
		return array(
			'code'        => self::PAGE_UNCONFIGURED,
			'page_id'     => 0,
			'post_status' => '',
			'post_type'   => '',
		);
	}

	/**
	 * @param int                 $page_id Selected Registration page ID.
	 * @param array<string,mixed> $page Page classification.
	 * @return array<string,mixed>
	 */
	private static function inspect_assignment( $page_id, $page ) {
		$page_code = (string) ( $page['code'] ?? self::PAGE_MISSING );
		if ( in_array( $page_code, array( self::PAGE_UNCONFIGURED, self::PAGE_MISSING, self::PAGE_TRASHED, self::PAGE_TYPE_INVALID ), true ) ) {
			return array(
				'state'         => self::ASSIGNMENT_UNAVAILABLE,
				'expected_slug' => TemplateRegistrar::TEMPLATE_SLUG,
				'actual_slug'   => '',
			);
		}

		$actual = PageTemplateAssignment::read( $page_id );
		$actual = false === $actual ? '' : (string) $actual;

		return array(
			'state'         => TemplateRegistrar::TEMPLATE_SLUG === $actual ? self::ASSIGNMENT_EXPECTED : self::WRONG_PAGE_ASSIGNMENT,
			'expected_slug' => TemplateRegistrar::TEMPLATE_SLUG,
			'actual_slug'   => $actual,
		);
	}

	/**
	 * Inspect the provider WordPress resolves for the canonical slug.
	 *
	 * WordPress 7.1.1 resolves DB templates before theme files and theme files
	 * before a registered plugin template. We classify only the provenance that
	 * the resolved WP_Block_Template object actually exposes.
	 *
	 * @return array<string,mixed>
	 */
	private static function inspect_resolution() {
		$resolved_id = get_stylesheet() . '//' . TemplateRegistrar::TEMPLATE_SLUG;
		$resolved    = get_block_template( $resolved_id, 'wp_template' );
		$canonical   = TemplateRegistrar::get_canonical_content();

		if ( ! $resolved instanceof \WP_Block_Template ) {
			return array(
				'state'    => self::MISSING_TEMPLATE,
				'evidence' => array(
					'lookup_id' => $resolved_id,
					'found'     => false,
				),
			);
		}

		$evidence = array(
			'lookup_id'             => $resolved_id,
			'found'                 => true,
			'id'                    => (string) $resolved->id,
			'slug'                  => (string) $resolved->slug,
			'source'                => (string) $resolved->source,
			'origin'                => isset( $resolved->origin ) ? (string) $resolved->origin : '',
			'plugin'                => isset( $resolved->plugin ) ? (string) $resolved->plugin : '',
			'wp_id'                 => isset( $resolved->wp_id ) ? (int) $resolved->wp_id : 0,
			'has_theme_file'        => isset( $resolved->has_theme_file ) ? (bool) $resolved->has_theme_file : false,
			'is_custom'             => isset( $resolved->is_custom ) ? (bool) $resolved->is_custom : null,
			'canonical_fingerprint' => false === $canonical ? '' : self::fingerprint_content( $canonical ),
			'resolved_fingerprint'  => self::fingerprint_content( (string) $resolved->content ),
		);

		$evidence['content_matches_canonical'] = '' !== $evidence['canonical_fingerprint'] && hash_equals( $evidence['canonical_fingerprint'], $evidence['resolved_fingerprint'] );

		if ( 'custom' === $resolved->source && ! empty( $resolved->wp_id ) ) {
			return array( 'state' => self::CUSTOMIZED_DB_OVERRIDE, 'evidence' => $evidence );
		}

		if ( 'theme' === $resolved->source && ! empty( $resolved->has_theme_file ) ) {
			return array( 'state' => self::THEME_OVERRIDE, 'evidence' => $evidence );
		}

		if (
			'plugin' === $resolved->source &&
			'srwf-host-companion' === (string) $resolved->plugin &&
			'plugin' === (string) $resolved->origin &&
			true === $evidence['content_matches_canonical']
		) {
			return array( 'state' => self::CANONICAL, 'evidence' => $evidence );
		}

		return array( 'state' => self::UNKNOWN, 'evidence' => $evidence );
	}

	/**
	 * @param array<string,mixed> $page Page evidence.
	 * @param array<string,mixed> $assignment Assignment evidence.
	 * @param array<string,mixed> $resolution Resolution evidence.
	 * @return string
	 */
	private static function primary_state( $page, $assignment, $resolution ) {
		$page_code = (string) ( $page['code'] ?? self::PAGE_MISSING );
		if ( self::PAGE_UNCONFIGURED === $page_code ) {
			return 'NEEDS_SETUP';
		}
		if ( in_array( $page_code, array( self::PAGE_MISSING, self::PAGE_TRASHED, self::PAGE_TYPE_INVALID ), true ) ) {
			return 'PAGE_INVALID';
		}
		$resolution_state = (string) ( $resolution['state'] ?? self::UNKNOWN );
		if ( self::CANONICAL !== $resolution_state ) {
			return $resolution_state;
		}
		if ( self::WRONG_PAGE_ASSIGNMENT === ( $assignment['state'] ?? '' ) ) {
			return self::WRONG_PAGE_ASSIGNMENT;
		}
		if ( self::PAGE_NOT_PUBLISHED === $page_code ) {
			return self::PAGE_NOT_PUBLISHED;
		}

		return self::CANONICAL;
	}

	/**
	 * @param string $primary_state Primary diagnostic state.
	 * @return string
	 */
	private static function overall_status( $primary_state ) {
		if ( self::CANONICAL === $primary_state ) {
			return 'READY';
		}
		if ( self::UNKNOWN === $primary_state ) {
			return 'UNKNOWN';
		}
		if ( 'NEEDS_SETUP' === $primary_state ) {
			return 'NEEDS_SETUP';
		}
		return 'ATTENTION_REQUIRED';
	}

	/**
	 * @param array<string,mixed> $page Page evidence.
	 * @param array<string,mixed> $assignment Assignment evidence.
	 * @param array<string,mixed> $resolution Resolution evidence.
	 * @return string
	 */
	private static function recommended_action( $page, $assignment, $resolution ) {
		$page_code = (string) ( $page['code'] ?? self::PAGE_MISSING );
		if ( in_array( $page_code, array( self::PAGE_UNCONFIGURED, self::PAGE_MISSING, self::PAGE_TRASHED, self::PAGE_TYPE_INVALID ), true ) ) {
			return 'SELECT_PAGE';
		}
		$resolution_state = (string) ( $resolution['state'] ?? self::UNKNOWN );
		if ( in_array( $resolution_state, array( self::CUSTOMIZED_DB_OVERRIDE, self::THEME_OVERRIDE ), true ) ) {
			return 'REVIEW_OVERRIDE';
		}
		if ( self::MISSING_TEMPLATE === $resolution_state ) {
			return 'CHECK_PLUGIN_TEMPLATE';
		}
		if ( self::UNKNOWN === $resolution_state ) {
			return 'CHECK_AGAIN';
		}
		if ( self::WRONG_PAGE_ASSIGNMENT === ( $assignment['state'] ?? '' ) ) {
			return 'APPLY_TEMPLATE';
		}
		if ( self::PAGE_NOT_PUBLISHED === $page_code ) {
			return 'REVIEW_PUBLICATION';
		}

		return 'NONE';
	}

	/**
	 * @param mixed $value Boolean-like value.
	 * @return string
	 */
	private static function bool_text( $value ) {
		if ( true === $value ) {
			return 'true';
		}
		if ( false === $value ) {
			return 'false';
		}
		return 'unknown';
	}
}
