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

/**
 * Parse a numeric price out of a free-text price string.
 *
 * @param string $price e.g. "£90", "1,200.50", "Price on request".
 * @return float|null Null when no number is present.
 */
function harbour_parse_price( string $price ): ?float {
	$clean = str_replace( ',', '', $price );
	if ( preg_match( '/\d+(\.\d+)?/', $clean, $m ) ) {
		return (float) $m[0];
	}
	return null;
}

/**
 * Work out an order line total, enforcing the minimum quantity.
 *
 * @param float|null $unit_price Per-unit price, or null if not numeric.
 * @param int        $qty        Requested quantity.
 * @param int        $min        Minimum order quantity.
 * @return array{valid:bool,qty:int,total:?float,reason:string}
 */
function harbour_order_total( ?float $unit_price, int $qty, int $min = 1 ): array {
	$min = max( 1, $min );
	if ( $qty < 1 ) {
		return array( 'valid' => false, 'qty' => $qty, 'total' => null, 'reason' => 'quantity' );
	}
	if ( $qty < $min ) {
		return array( 'valid' => false, 'qty' => $qty, 'total' => null, 'reason' => 'min_order' );
	}
	$total = ( null === $unit_price ) ? null : round( $unit_price * $qty, 2 );
	return array( 'valid' => true, 'qty' => $qty, 'total' => $total, 'reason' => '' );
}
