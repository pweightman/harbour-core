<?php
/**
 * Pure validation/normalisation helpers. No WordPress dependency, no hooks —
 * kept hookless so they can be unit tested in isolation.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalise a UK postcode: uppercase, single space before the inward code.
 *
 * @param string $postcode Raw input.
 * @return string
 */
function harbour_normalize_postcode( string $postcode ): string {
	$pc = strtoupper( preg_replace( '/\s+/', '', $postcode ) );
	if ( strlen( $pc ) > 3 ) {
		$pc = substr( $pc, 0, -3 ) . ' ' . substr( $pc, -3 );
	}
	return trim( $pc );
}

/**
 * Validate a UK postcode format (not existence).
 *
 * @param string $postcode Raw or normalised.
 * @return bool
 */
function harbour_is_valid_uk_postcode( string $postcode ): bool {
	$pc = harbour_normalize_postcode( $postcode );
	return (bool) preg_match( '/^[A-Z]{1,2}\d[A-Z\d]?\s\d[A-Z]{2}$/', $pc );
}
