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

if ( ! defined( 'SRWF_HOST_COMPANION_VERSION' ) ) {
	define( 'SRWF_HOST_COMPANION_VERSION', '0.0.0-dev' );
}

require_once __DIR__ . '/src/Configuration.php';
require_once __DIR__ . '/src/TemplateRegistrar.php';
require_once __DIR__ . '/src/PageTemplateAssignment.php';
require_once __DIR__ . '/src/TemplateDiagnostics.php';
require_once __DIR__ . '/src/AdminSettings.php';

\SRWF\HostCompanion\TemplateRegistrar::boot();
\SRWF\HostCompanion\AdminSettings::boot();
