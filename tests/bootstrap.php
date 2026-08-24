<?php
/**
 * Standalone unit-test bootstrap: loads the plugin's hookless pure-logic files
 * without a full WordPress bootstrap. Only functions with no WP dependency are
 * exercised here (postcode + honeypot). Integration behaviour is covered by the
 * manual acceptance tests documented in the build notes.
 *
 * @package HarbourCore
 */

define( 'ABSPATH', __DIR__ . '/' );
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

require dirname( __DIR__ ) . '/includes/validation.php';
require dirname( __DIR__ ) . '/includes/security.php';
