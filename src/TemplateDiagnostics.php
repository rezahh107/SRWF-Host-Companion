<?php

namespace SRWF\HostCompanion;

final class TemplateDiagnostics {
	const PAGE_UNCONFIGURED  = 'PAGE_UNCONFIGURED';
	const PAGE_VALID         = 'PAGE_VALID';
	const PAGE_NOT_PUBLISHED = 'PAGE_NOT_PUBLISHED';
	const PAGE_MISSING       = 'PAGE_MISSING';
	const PAGE_TRASHED       = 'PAGE_TRASHED';
	const PAGE_TYPE_INVALID  = 'PAGE_TYPE_INVALID';

	const ASSIGNMENT_EXPECTED    = 'ASSIGNMENT_EXPECTED';
	const WRONG_PAGE_ASSIGNMENT  = 'WRONG_PAGE_ASSIGNMENT';
	const ASSIGNMENT_UNAVAILABLE = 'ASSIGNMENT_UNAVAILABLE';

	const CANONICAL              = 'CANONICAL';
	const CUSTOMIZED_DB_OVERRIDE = 'CUSTOMIZED_DB_OVERRIDE';
	const THEME_OVERRIDE         = 'THEME_OVERRIDE';
	const MISSING_TEMPLATE       = 'MISSING_TEMPLATE';
	const UNKNOWN                = 'UNKNOWN';

	public static function inspect() {
		$page_id    = Configuration::get_registration_page_id();
		$page       = 0 === $page_id ? self::unconfigured_page() : self::classify_page( $page_id );
		$assignment = self::inspect_assignment( $page_id, $page );
		$resolution = self::with_legacy_resolution_aliases( self::inspect_resolution() );
		$primary_state = self::primary_state( $page, $assignment, $resolution );
		return array(
			'status'             => self::overall_status( $primary_state ),
			'primary_state'      => $primary_state,
			'page'               => $page,
			'assignment'         => $assignment,
			'resolution'         => $resolution,
			'recommended_action' => self::recommended_action( $page, $assignment, $resolution ),
		);
	}

	public static function classify_page( $page_id ) {
		if ( ! is_int( $page_id ) || $page_id <= 0 ) {
			return array( 'code' => self::PAGE_MISSING, 'page_id' => max( 0, (int) $page_id ), 'post_status' => '', 'post_type' => '' );
		}
		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post ) {
			return array( 'code' => self::PAGE_MISSING, 'page_id' => $page_id, 'post_status' => '', 'post_type' => '' );
		}
		if ( 'page' !== $post->post_type ) {
			return array( 'code' => self::PAGE_TYPE_INVALID, 'page_id' => $page_id, 'post_status' => (string) $post->post_status, 'post_type' => (string) $post->post_type );
		}
		if ( 'trash' === $post->post_status ) {
			return array( 'code' => self::PAGE_TRASHED, 'page_id' => $page_id, 'post_status' => 'trash', 'post_type' => 'page' );
		}
		return array(
			'code'        => 'publish' === $post->post_status ? self::PAGE_VALID : self::PAGE_NOT_PUBLISHED,
			'page_id'     => $page_id,
			'post_status' => (string) $post->post_status,
			'post_type'   => 'page',
		);
	}

	public static function normalize_content( $content ) {
		return is_string( $content ) ? serialize_blocks( parse_blocks( $content ) ) : '';
	}

	public static function fingerprint_content( $content ) {
		return hash( 'sha256', self::normalize_content( $content ) );
	}

	public static function build_report( $diagnostics ) {
		$theme       = wp_get_theme();
		$page        = is_array( $diagnostics['page'] ?? null ) ? $diagnostics['page'] : array();
		$assignment  = is_array( $diagnostics['assignment'] ?? null ) ? $diagnostics['assignment'] : array();
		$resolution  = is_array( $diagnostics['resolution'] ?? null ) ? $diagnostics['resolution'] : array();
		$evidence    = is_array( $resolution['evidence'] ?? null ) ? $resolution['evidence'] : array();
		$provider    = is_array( $evidence['provider'] ?? null ) ? $evidence['provider'] : array();
		$comparison  = is_array( $evidence['source_comparison'] ?? null ) ? $evidence['source_comparison'] : array();
		$transformed = is_array( $evidence['transformed_resolved'] ?? null ) ? $evidence['transformed_resolved'] : array();
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
			'provider_observation=' . (string) ( $provider['observation'] ?? 'NOT_AVAILABLE' ),
			'provider_evidence_status=' . (string) ( $provider['evidence_status'] ?? 'NOT_PROVEN' ),
			'provider_id=' . (string) ( $provider['id'] ?? '' ),
			'provider_source=' . (string) ( $provider['source'] ?? '' ),
			'provider_origin=' . (string) ( $provider['origin'] ?? '' ),
			'provider_plugin=' . (string) ( $provider['plugin'] ?? '' ),
			'provider_wp_id=' . (string) ( $provider['wp_id'] ?? '' ),
			'provider_template_status=' . (string) ( $provider['template_status'] ?? '' ),
			'source_comparison_status=' . (string) ( $comparison['status'] ?? 'NOT_PROVEN' ),
			'source_comparison_basis=' . (string) ( $comparison['basis'] ?? 'NOT_AVAILABLE' ),
			'source_comparison_reason=' . (string) ( $comparison['reason'] ?? '' ),
			'canonical_fingerprint=' . (string) ( $comparison['canonical_fingerprint'] ?? '' ),
			'raw_source_fingerprint=' . (string) ( $comparison['source_fingerprint'] ?? '' ),
			'content_matches_canonical=' . self::bool_text( $comparison['content_matches_canonical'] ?? null ),
			'transformed_resolved_status=' . (string) ( $transformed['status'] ?? 'NOT_PROVEN' ),
			'transformed_resolved_basis=' . (string) ( $transformed['basis'] ?? 'NOT_AVAILABLE' ),
			'transformed_resolution_transform=' . (string) ( $transformed['transformation_status'] ?? 'NOT_PROVEN' ),
			'transformed_resolved_fingerprint=' . (string) ( $transformed['fingerprint'] ?? '' ),
			'recommended_action=' . (string) ( $diagnostics['recommended_action'] ?? 'CHECK_AGAIN' ),
		);
		return implode( "\n", $lines );
	}

	private static function unconfigured_page() {
		return array( 'code' => self::PAGE_UNCONFIGURED, 'page_id' => 0, 'post_status' => '', 'post_type' => '' );
	}

	private static function inspect_assignment( $page_id, $page ) {
		$page_code = (string) ( $page['code'] ?? self::PAGE_MISSING );
		if ( in_array( $page_code, array( self::PAGE_UNCONFIGURED, self::PAGE_MISSING, self::PAGE_TRASHED, self::PAGE_TYPE_INVALID ), true ) ) {
			return array( 'state' => self::ASSIGNMENT_UNAVAILABLE, 'expected_slug' => TemplateRegistrar::TEMPLATE_SLUG, 'actual_slug' => '' );
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
	 * Observe the same published candidate model used by frontend resolution.
	 * Returned template content is post-Block-Hooks evidence, not raw-source truth.
	 */
	private static function inspect_resolution() {
		$lookup_id = get_stylesheet() . '//' . TemplateRegistrar::TEMPLATE_SLUG;
		$observed  = self::observe_frontend_candidate();
		if ( 'MISSING' === $observed['status'] ) {
			return array(
				'state'    => self::MISSING_TEMPLATE,
				'evidence' => array(
					'lookup_id' => $lookup_id,
					'provider' => $observed['provider'],
					'source_comparison' => self::not_proven_comparison( 'NO_ACTIVE_PROVIDER' ),
					'transformed_resolved' => self::not_proven_transformed( 'NO_ACTIVE_PROVIDER' ),
				),
			);
		}
		if ( 'PASS' !== $observed['status'] || ! $observed['template'] instanceof \WP_Block_Template ) {
			return array(
				'state'    => self::UNKNOWN,
				'evidence' => array(
					'lookup_id' => $lookup_id,
					'provider' => $observed['provider'],
					'source_comparison' => self::not_proven_comparison( 'ACTIVE_PROVIDER_NOT_PROVEN' ),
					'transformed_resolved' => self::not_proven_transformed( 'ACTIVE_PROVIDER_NOT_PROVEN' ),
				),
			);
		}

		$provider = $observed['template'];
		$evidence = array(
			'lookup_id' => $lookup_id,
			'provider' => $observed['provider'],
			'source_comparison' => self::source_comparison( $provider ),
			'transformed_resolved' => array(
				'status' => 'PASS',
				'basis' => 'GET_BLOCK_TEMPLATES_RETURNED_CONTENT',
				'transformation_status' => self::core_transformation_status( $provider ),
				'fingerprint' => self::fingerprint_content( (string) $provider->content ),
			),
		);
		if ( 'custom' === (string) $provider->source && ! empty( $provider->wp_id ) ) {
			return array( 'state' => self::CUSTOMIZED_DB_OVERRIDE, 'evidence' => $evidence );
		}
		if ( 'theme' === (string) $provider->source && ! empty( $provider->has_theme_file ) ) {
			return array( 'state' => self::THEME_OVERRIDE, 'evidence' => $evidence );
		}
		if ( 'plugin' === (string) $provider->source && 'plugin' === (string) ( $provider->origin ?? '' ) && 'srwf-host-companion' === (string) ( $provider->plugin ?? '' ) ) {
			return array( 'state' => self::CANONICAL, 'evidence' => $evidence );
		}
		return array( 'state' => self::UNKNOWN, 'evidence' => $evidence );
	}

	private static function observe_frontend_candidate() {
		$templates = get_block_templates( array( 'slug__in' => array( TemplateRegistrar::TEMPLATE_SLUG ) ), 'wp_template' );
		if ( ! is_array( $templates ) ) {
			return array(
				'status' => 'NOT_PROVEN', 'template' => null,
				'provider' => array( 'observation' => 'GET_BLOCK_TEMPLATES_SLUG_CANDIDATES', 'evidence_status' => 'NOT_PROVEN', 'reason' => 'NON_ARRAY_CANDIDATE_RESULT', 'found' => false ),
			);
		}
		$matches = array();
		foreach ( $templates as $template ) {
			if ( $template instanceof \WP_Block_Template && TemplateRegistrar::TEMPLATE_SLUG === (string) $template->slug ) {
				$matches[] = $template;
			}
		}
		if ( empty( $matches ) ) {
			return array(
				'status' => 'MISSING', 'template' => null,
				'provider' => array( 'observation' => 'GET_BLOCK_TEMPLATES_SLUG_CANDIDATES', 'evidence_status' => 'PASS', 'reason' => '', 'found' => false ),
			);
		}
		if ( 1 !== count( $matches ) ) {
			return array(
				'status' => 'NOT_PROVEN', 'template' => null,
				'provider' => array( 'observation' => 'GET_BLOCK_TEMPLATES_SLUG_CANDIDATES', 'evidence_status' => 'NOT_PROVEN', 'reason' => 'MULTIPLE_EXACT_SLUG_CANDIDATES', 'found' => true, 'candidate_count' => count( $matches ) ),
			);
		}
		$template = $matches[0];
		return array(
			'status' => 'PASS',
			'template' => $template,
			'provider' => array(
				'observation' => 'GET_BLOCK_TEMPLATES_SLUG_CANDIDATES',
				'evidence_status' => 'PASS',
				'reason' => '',
				'found' => true,
				'id' => (string) $template->id,
				'slug' => (string) $template->slug,
				'source' => (string) $template->source,
				'origin' => isset( $template->origin ) ? (string) $template->origin : '',
				'plugin' => isset( $template->plugin ) ? (string) $template->plugin : '',
				'wp_id' => isset( $template->wp_id ) ? (int) $template->wp_id : 0,
				'template_status' => isset( $template->status ) ? (string) $template->status : '',
				'has_theme_file' => isset( $template->has_theme_file ) ? (bool) $template->has_theme_file : false,
				'is_custom' => isset( $template->is_custom ) ? (bool) $template->is_custom : null,
			),
		);
	}

	private static function source_comparison( $provider ) {
		$canonical = TemplateRegistrar::get_canonical_content();
		if ( ! is_string( $canonical ) || '' === $canonical ) {
			return self::not_proven_comparison( 'CANONICAL_SOURCE_UNAVAILABLE' );
		}
		if ( 'custom' === (string) $provider->source && ! empty( $provider->wp_id ) ) {
			$raw = self::database_raw_source( $provider );
		} elseif ( 'theme' === (string) $provider->source && ! empty( $provider->has_theme_file ) ) {
			$raw = self::theme_raw_source( (string) $provider->slug );
		} elseif ( 'plugin' === (string) $provider->source && 'plugin' === (string) ( $provider->origin ?? '' ) && 'srwf-host-companion' === (string) ( $provider->plugin ?? '' ) ) {
			return self::not_proven_comparison( 'REGISTERED_PLUGIN_POST_RESOLUTION_CONTENT_TRANSFORMED_BY_BLOCK_HOOKS', 'REGISTERED_PLUGIN_PROVIDER' );
		} else {
			return self::not_proven_comparison( 'RAW_SOURCE_BOUNDARY_NOT_ESTABLISHED' );
		}
		if ( 'PASS' !== $raw['status'] || ! isset( $raw['content'] ) || ! is_string( $raw['content'] ) ) {
			return self::not_proven_comparison( (string) ( $raw['reason'] ?? 'RAW_SOURCE_UNAVAILABLE' ), (string) ( $raw['basis'] ?? 'NOT_AVAILABLE' ), (string) ( $raw['source_identity'] ?? '' ) );
		}
		$canonical_fingerprint = self::fingerprint_content( $canonical );
		$source_fingerprint = self::fingerprint_content( $raw['content'] );
		return array(
			'status' => 'PASS', 'basis' => (string) $raw['basis'], 'reason' => '', 'source_identity' => (string) $raw['source_identity'],
			'canonical_fingerprint' => $canonical_fingerprint, 'source_fingerprint' => $source_fingerprint,
			'content_matches_canonical' => hash_equals( $canonical_fingerprint, $source_fingerprint ),
		);
	}

	private static function database_raw_source( $provider ) {
		$wp_id = isset( $provider->wp_id ) ? (int) $provider->wp_id : 0;
		if ( $wp_id <= 0 ) {
			return array( 'status' => 'NOT_PROVEN', 'basis' => 'RAW_DB_WP_TEMPLATE_POST_CONTENT', 'reason' => 'PROVIDER_WP_ID_UNAVAILABLE' );
		}
		$post = get_post( $wp_id, OBJECT, 'raw' );
		if ( ! $post instanceof \WP_Post ) {
			return array( 'status' => 'NOT_PROVEN', 'basis' => 'RAW_DB_WP_TEMPLATE_POST_CONTENT', 'reason' => 'PROVIDER_POST_UNAVAILABLE' );
		}
		if ( 'wp_template' !== $post->post_type || 'publish' !== $post->post_status || TemplateRegistrar::TEMPLATE_SLUG !== $post->post_name ) {
			return array( 'status' => 'NOT_PROVEN', 'basis' => 'RAW_DB_WP_TEMPLATE_POST_CONTENT', 'reason' => 'PROVIDER_POST_IDENTITY_MISMATCH' );
		}
		$themes = wp_get_object_terms( $wp_id, 'wp_theme', array( 'fields' => 'names' ) );
		if ( is_wp_error( $themes ) || ! in_array( get_stylesheet(), $themes, true ) ) {
			return array( 'status' => 'NOT_PROVEN', 'basis' => 'RAW_DB_WP_TEMPLATE_POST_CONTENT', 'reason' => 'PROVIDER_THEME_TERM_NOT_PROVEN' );
		}
		return array( 'status' => 'PASS', 'basis' => 'RAW_DB_WP_TEMPLATE_POST_CONTENT', 'reason' => '', 'source_identity' => 'wp_template:' . $wp_id, 'content' => (string) $post->post_content );
	}

	private static function theme_raw_source( $slug ) {
		$stylesheet = get_stylesheet();
		$template = get_template();
		$themes = array( $stylesheet );
		if ( $template !== $stylesheet ) {
			$themes[] = $template;
		}
		foreach ( $themes as $theme_slug ) {
			$theme = wp_get_theme( $theme_slug );
			if ( ! $theme->exists() ) {
				continue;
			}
			$folders = get_block_theme_folders( $theme_slug );
			if ( ! isset( $folders['wp_template'] ) || ! is_string( $folders['wp_template'] ) ) {
				continue;
			}
			$directory = trailingslashit( $theme->get_stylesheet_directory() ) . trailingslashit( $folders['wp_template'] );
			$path = $directory . $slug . '.html';
			$root = realpath( $directory );
			$real_path = realpath( $path );
			if ( false === $root || false === $real_path || ! str_starts_with( wp_normalize_path( $real_path ), trailingslashit( wp_normalize_path( $root ) ) ) || ! is_readable( $real_path ) ) {
				continue;
			}
			$content = file_get_contents( $real_path );
			if ( false === $content ) {
				return array( 'status' => 'NOT_PROVEN', 'basis' => 'RAW_THEME_FILE_CONTENT', 'reason' => 'THEME_FILE_READ_FAILED' );
			}
			return array( 'status' => 'PASS', 'basis' => 'RAW_THEME_FILE_CONTENT', 'reason' => '', 'source_identity' => $theme_slug . ':' . $folders['wp_template'] . '/' . $slug . '.html', 'content' => (string) $content );
		}
		return array( 'status' => 'NOT_PROVEN', 'basis' => 'RAW_THEME_FILE_CONTENT', 'reason' => 'THEME_FILE_SOURCE_NOT_ESTABLISHED' );
	}

	private static function not_proven_comparison( $reason, $basis = 'NOT_AVAILABLE', $source_identity = '' ) {
		return array( 'status' => 'NOT_PROVEN', 'basis' => (string) $basis, 'reason' => (string) $reason, 'source_identity' => (string) $source_identity, 'canonical_fingerprint' => '', 'source_fingerprint' => '', 'content_matches_canonical' => null );
	}

	private static function not_proven_transformed( $reason ) {
		return array( 'status' => 'NOT_PROVEN', 'basis' => 'GET_BLOCK_TEMPLATES_RETURNED_CONTENT', 'transformation_status' => 'NOT_PROVEN', 'reason' => (string) $reason, 'fingerprint' => '' );
	}

	/**
	 * Preserve the reviewed WU-04 UI/report field shape while sourcing truth from
	 * the repaired nested provider/raw-source/transformed evidence model.
	 *
	 * @param array<string,mixed> $resolution Resolution result.
	 * @return array<string,mixed>
	 */
	private static function with_legacy_resolution_aliases( $resolution ) {
		if ( ! isset( $resolution['evidence'] ) || ! is_array( $resolution['evidence'] ) ) {
			return $resolution;
		}

		$evidence    = $resolution['evidence'];
		$provider    = is_array( $evidence['provider'] ?? null ) ? $evidence['provider'] : array();
		$comparison  = is_array( $evidence['source_comparison'] ?? null ) ? $evidence['source_comparison'] : array();
		$transformed = is_array( $evidence['transformed_resolved'] ?? null ) ? $evidence['transformed_resolved'] : array();

		$evidence['id']                        = (string) ( $provider['id'] ?? '' );
		$evidence['source']                    = (string) ( $provider['source'] ?? '' );
		$evidence['origin']                    = (string) ( $provider['origin'] ?? '' );
		$evidence['plugin']                    = (string) ( $provider['plugin'] ?? '' );
		$evidence['wp_id']                     = (int) ( $provider['wp_id'] ?? 0 );
		$evidence['has_theme_file']            = (bool) ( $provider['has_theme_file'] ?? false );
		$evidence['canonical_fingerprint']     = (string) ( $comparison['canonical_fingerprint'] ?? '' );
		$evidence['resolved_fingerprint']      = (string) ( $transformed['fingerprint'] ?? '' );
		$evidence['content_matches_canonical'] = $comparison['content_matches_canonical'] ?? null;

		if ( 'PASS' === ( $comparison['status'] ?? '' ) ) {
			$evidence['content_comparison'] = 'NORMALIZED_RAW_SOURCE_COMPARISON';
		} elseif ( 'plugin' === ( $provider['source'] ?? '' ) && 'plugin' === ( $provider['origin'] ?? '' ) && 'srwf-host-companion' === ( $provider['plugin'] ?? '' ) ) {
			$evidence['content_comparison'] = 'NATIVE_PLUGIN_RESOLUTION_NOT_DIRECTLY_COMPARABLE';
		} else {
			$evidence['content_comparison'] = 'NOT_PROVEN';
		}

		$resolution['evidence'] = $evidence;
		return $resolution;
	}

	private static function core_transformation_status( $provider ) {
		return in_array( (string) $provider->source, array( 'custom', 'theme', 'plugin' ), true ) ? 'CORE_BLOCK_HOOKS_APPLIED' : 'NOT_PROVEN';
	}

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
