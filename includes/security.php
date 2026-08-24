<?php
/**
 * Security helpers shared by the public forms: honeypot, submission timing,
 * per-IP rate limiting and client IP resolution.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output the hidden anti-spam fields: a honeypot and a render timestamp.
 * The honeypot must stay empty; the timestamp lets us reject instant submits.
 */
function harbour_honeypot_fields(): void {
	echo '<div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px" tabindex="-1">';
	echo '<label>Leave this field empty<input type="text" name="harbour_hp" value="" autocomplete="off" tabindex="-1"></label>';
	echo '</div>';
	echo '<input type="hidden" name="harbour_ts" value="' . esc_attr( (string) time() ) . '">';
}

/**
 * True if the submission passes the honeypot and timing checks.
 *
 * @param array $data      Request data ($_POST).
 * @param int   $min_secs  Minimum seconds between render and submit.
 * @return bool
 */
function harbour_passes_honeypot( array $data, int $min_secs = 3 ): bool {
	if ( ! empty( $data['harbour_hp'] ) ) {
		return false;
	}
	$ts = isset( $data['harbour_ts'] ) ? (int) $data['harbour_ts'] : 0;
	if ( $ts <= 0 || ( time() - $ts ) < $min_secs ) {
		return false;
	}
	return true;
}

/**
 * Resolve the client IP conservatively (REMOTE_ADDR only; proxies are not
 * trusted by default).
 *
 * @return string
 */
function harbour_client_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	$ip = filter_var( $ip, FILTER_VALIDATE_IP );
	return $ip ? $ip : '0.0.0.0';
}

/**
 * Enforce a per-IP rate limit using a transient.
 *
 * @param string $bucket Namespace, e.g. 'enquiry'.
 * @param int    $max    Max submissions per window.
 * @param int    $window Window length in seconds.
 * @return bool True if within the limit (allowed), false if exceeded.
 */
function harbour_rate_ok( string $bucket, int $max = 5, int $window = HOUR_IN_SECONDS ): bool {
	$key   = 'harbour_rl_' . $bucket . '_' . md5( harbour_client_ip() );
	$count = (int) get_transient( $key );
	if ( $count >= $max ) {
		return false;
	}
	set_transient( $key, $count + 1, $window );
	return true;
}
