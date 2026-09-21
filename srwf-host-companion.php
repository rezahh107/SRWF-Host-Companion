<?php
/**
 * Plugin Name:       SRWF Host Companion
 * Plugin URI:        https://github.com/rezahh107/SRWF-Host-Companion
 * Description:       Project-specific WordPress host integration layer for SRWF.
 * Version:           0.1.0
 * Author:            Reza Hashemi Hosseini
 * Requires at least: 7.1
 * Requires PHP:      8.3
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       srwf-host-companion
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SRWF_HOST_COMPANION_VERSION' ) ) {
	define( 'SRWF_HOST_COMPANION_VERSION', '0.1.0' );
}

require_once __DIR__ . '/src/Configuration.php';
require_once __DIR__ . '/src/TemplateRegistrar.php';
require_once __DIR__ . '/src/PageTemplateAssignment.php';
require_once __DIR__ . '/src/TemplateDiagnostics.php';
require_once __DIR__ . '/src/AdminSettings.php';

\SRWF\HostCompanion\TemplateRegistrar::boot();
\SRWF\HostCompanion\AdminSettings::boot();
