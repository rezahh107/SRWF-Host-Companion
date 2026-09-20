<?php
/**
 * WU-01 disposable runtime-fact probe.
 *
 * Run through WP-CLI eval-file with SRWF_WU01_PHASE=active or inactive.
 * This file observes WordPress behavior; it does not implement product runtime code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$phase         = getenv( 'SRWF_WU01_PHASE' ) ?: 'active';
$evidence_path = getenv( 'SRWF_WU01_EVIDENCE_PATH' );

if ( ! $evidence_path ) {
	fwrite( STDERR, "SRWF_WU01_EVIDENCE_PATH is required.\n" );
	exit( 2 );
}

$template_name  = 'srwf-host-companion//registration-full-width';
$template_slug  = 'registration-full-width';
$template_title = 'SRWF — Registration Full Width [WU-01 Probe]';
$page_marker    = 'SRWF_WU01_PAGE_CONTENT_MARKER';

/**
 * @param mixed $value Value to normalize.
 * @return mixed
 */
function srwf_wu01_normalize( $value ) {
	if ( is_wp_error( $value ) ) {
		return array(
			'is_wp_error' => true,
			'code'        => $value->get_error_code(),
			'message'     => $value->get_error_message(),
		);
	}

	return $value;
}

/**
 * @param string $path Evidence path.
 * @return array<string,mixed>
 */
function srwf_wu01_read_evidence( $path ) {
	if ( ! file_exists( $path ) ) {
		return array();
	}

	$decoded = json_decode( (string) file_get_contents( $path ), true );
	return is_array( $decoded ) ? $decoded : array();
}

/**
 * @param string              $path Evidence path.
 * @param array<string,mixed> $evidence Evidence.
 * @return void
 */
function srwf_wu01_write_evidence( $path, $evidence ) {
	$encoded = wp_json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $encoded || false === file_put_contents( $path, $encoded . "\n" ) ) {
		fwrite( STDERR, "Unable to write WU-01 evidence.\n" );
		exit( 3 );
	}
}

$evidence = srwf_wu01_read_evidence( $evidence_path );

if ( 'active' === $phase ) {
	$theme = wp_get_theme();
	$evidence = array(
		'schema'         => 'srwf-host-companion-wu01-runtime-facts-v1',
		'evidence_class' => 'DISPOSABLE_CI_RUNTIME_LAB',
		'observed_at_utc'=> gmdate( 'c' ),
		'environment'    => array(
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
			'theme_stylesheet'  => get_stylesheet(),
			'theme_name'        => $theme->get( 'Name' ),
			'theme_version'     => $theme->get( 'Version' ),
		),
		'template'       => array(
			'registration_name' => $template_name,
			'expected_slug'     => $template_slug,
			'expected_title'    => $template_title,
		),
		'claims'         => array(),
	);

	$registered = get_block_templates( array( 'post_type' => 'page' ), 'wp_template' );
	$matched    = null;
	foreach ( $registered as $candidate ) {
		if ( $template_slug === $candidate->slug && $template_title === $candidate->title ) {
			$matched = $candidate;
			break;
		}
	}

	$evidence['template']['registered_for_page'] = (bool) $matched;
	if ( $matched ) {
		$evidence['template']['registered_object'] = array(
			'id'          => $matched->id,
			'slug'        => $matched->slug,
			'title'       => $matched->title,
			'source'      => $matched->source,
			'origin'      => $matched->origin,
			'plugin'      => $matched->plugin,
			'post_types'  => isset( $matched->post_types ) ? $matched->post_types : null,
			'is_custom'   => $matched->is_custom,
		);
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'SRWF WU-01 Synthetic Runtime Probe',
			'post_content' => '<!-- wp:paragraph --><p>' . $page_marker . '</p><!-- /wp:paragraph -->',
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		$evidence['page_creation'] = srwf_wu01_normalize( $page_id );
		srwf_wu01_write_evidence( $evidence_path, $evidence );
		exit( 4 );
	}

	$page = get_post( $page_id );
	$page_templates = wp_get_theme()->get_page_templates( $page );
	$assignment_key = null;
	foreach ( $page_templates as $key => $title ) {
		if ( $template_title === $title ) {
			$assignment_key = (string) $key;
			break;
		}
	}

	$evidence['page'] = array(
		'id'                         => $page_id,
		'url'                        => get_permalink( $page_id ),
		'page_template_map_key'      => $assignment_key,
		'page_template_map_contains'=> null !== $assignment_key,
	);

	$update_result = null;
	if ( null !== $assignment_key ) {
		$update_result = wp_update_post(
			array(
				'ID'            => $page_id,
				'page_template' => $assignment_key,
			),
			true
		);
	}

	$stored_meta = get_post_meta( $page_id, '_wp_page_template', true );
	$page_slug   = get_page_template_slug( $page_id );
	$resolved_id = $stored_meta ? get_stylesheet() . '//' . $stored_meta : null;
	$resolved    = $resolved_id ? get_block_template( $resolved_id, 'wp_template' ) : null;

	$evidence['assignment'] = array(
		'api'                         => 'wp_update_post(page_template)',
		'input_value'                 => $assignment_key,
		'update_result'               => srwf_wu01_normalize( $update_result ),
		'raw_wp_page_template_meta'   => $stored_meta,
		'get_page_template_slug'      => $page_slug,
		'theme_qualified_lookup_id'   => $resolved_id,
		'resolved_template'           => $resolved ? array(
			'id'         => $resolved->id,
			'slug'       => $resolved->slug,
			'title'      => $resolved->title,
			'source'     => $resolved->source,
			'origin'     => $resolved->origin,
			'plugin'     => $resolved->plugin,
			'is_custom'  => $resolved->is_custom,
		) : null,
	);

	$evidence['claims']['registration_runtime_proven'] = (bool) $matched && null !== $assignment_key;
	$evidence['claims']['assignment_runtime_proven']   = ! is_wp_error( $update_result ) && (int) $update_result === $page_id && '' !== $stored_meta;
	$evidence['claims']['active_render_runtime_proven']= false;
	$evidence['claims']['deactivation_runtime_proven'] = false;

	srwf_wu01_write_evidence( $evidence_path, $evidence );
	fwrite( STDOUT, (string) $page_id . "\n" );
	exit( 0 );
}

if ( 'inactive' === $phase ) {
	$page_id = isset( $evidence['page']['id'] ) ? (int) $evidence['page']['id'] : 0;
	if ( ! $page_id || ! get_post( $page_id ) ) {
		fwrite( STDERR, "WU-01 synthetic page is unavailable for inactive phase.\n" );
		exit( 5 );
	}

	$stored_meta = get_post_meta( $page_id, '_wp_page_template', true );
	$page_slug   = get_page_template_slug( $page_id );
	$resolved_id = $stored_meta ? get_stylesheet() . '//' . $stored_meta : null;
	$resolved    = $resolved_id ? get_block_template( $resolved_id, 'wp_template' ) : null;

	$evidence['after_deactivation'] = array(
		'plugin_active'              => is_plugin_active( 'srwf-host-companion-wu01-probe/srwf-host-companion-wu01-probe.php' ),
		'raw_wp_page_template_meta' => $stored_meta,
		'get_page_template_slug'    => $page_slug,
		'theme_qualified_lookup_id' => $resolved_id,
		'resolved_template'         => $resolved ? array(
			'id'        => $resolved->id,
			'slug'      => $resolved->slug,
			'title'     => $resolved->title,
			'source'    => $resolved->source,
			'origin'    => $resolved->origin,
			'plugin'    => $resolved->plugin,
			'is_custom' => $resolved->is_custom,
		) : null,
	);

	srwf_wu01_write_evidence( $evidence_path, $evidence );
	exit( 0 );
}

fwrite( STDERR, "Unknown SRWF_WU01_PHASE.\n" );
exit( 6 );
