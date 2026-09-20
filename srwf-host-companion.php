<?php
/**
 * Plugin Name:       SRWF Host Companion
 * Plugin URI:        https://github.com/rezahh107/SRWF-Host-Companion
 * Description:       Project-specific WordPress host integration layer for SRWF.
 * Version:           0.0.0-dev
 * Requires at least: 6.7
 * Text Domain:       srwf-host-companion
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Repository-foundation bootstrap only.
 *
 * Runtime features are intentionally not loaded yet. The first functional
 * implementation must follow docs/architecture/MOTHER_ARCHITECTURE.md and
 * the bounded work sequence in docs/implementation/V0_IMPLEMENTATION_PLAN.md.
 */
