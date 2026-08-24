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
foreach ( array( 'HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400, 'MINUTE_IN_SECONDS' => 60 ) as $k => $v ) {
	if ( ! defined( $k ) ) { define( $k, $v ); }
}

require dirname( __DIR__ ) . '/includes/validation.php';
require dirname( __DIR__ ) . '/includes/security.php';

// geo.php's pure maths (haversine, classify) — the API functions aren't called here.
require dirname( __DIR__ ) . '/includes/geo.php';
