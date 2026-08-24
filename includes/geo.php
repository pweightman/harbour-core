<?php
/**
 * Postcode geocoding + delivery-radius logic for firewood orders.
 *
 * Uses postcodes.io (free, UK-only, no key). Coordinates are cached for 30 days
 * because they don't move. The pure maths (haversine, band classification) is
 * split out so it can be unit tested without the network.
 *
 * If the API is unreachable we NEVER lose an order: the status returns 'unknown'
 * and the order is accepted and flagged for a manual distance check.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Great-circle distance in miles between two lat/lng points.
 *
 * @param float $lat1 Latitude 1.
 * @param float $lon1 Longitude 1.
 * @param float $lat2 Latitude 2.
 * @param float $lon2 Longitude 2.
 * @return float Miles.
 */
function harbour_haversine_miles( float $lat1, float $lon1, float $lat2, float $lon2 ): float {
	$earth = 3958.7613; // Earth radius in miles.
	$dlat  = deg2rad( $lat2 - $lat1 );
	$dlon  = deg2rad( $lon2 - $lon1 );
	$a     = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlon / 2 ) ** 2;
	$c     = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
	return $earth * $c;
}

/**
 * Classify a distance against the inner/outer delivery bands.
 *
 * @param float $miles Distance.
 * @param float $inner Inner radius (deliver).
 * @param float $outer Outer radius (may deliver).
 * @return string 'inside' | 'outer' | 'outside'.
 */
function harbour_classify_distance( float $miles, float $inner, float $outer ): string {
	if ( $miles <= $inner ) {
		return 'inside';
	}
	if ( $miles <= $outer ) {
		return 'outer';
	}
	return 'outside';
}

/**
 * Coordinates for a UK postcode, cached 30 days. Null on invalid/failed lookup.
 *
 * @param string $postcode Postcode.
 * @return array{lat:float,lng:float}|null
 */
function harbour_postcode_coords( string $postcode ) {
	$pc = strtoupper( preg_replace( '/\s+/', '', $postcode ) );
	if ( '' === $pc ) {
		return null;
	}
	$key    = 'harbour_geo_' . md5( $pc );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	if ( 'MISS' === $cached ) {
		return null; // Recently failed; don't hammer the API.
	}

	$resp = wp_remote_get( 'https://api.postcodes.io/postcodes/' . rawurlencode( $pc ), array( 'timeout' => 5 ) );
	if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
		set_transient( $key, 'MISS', HOUR_IN_SECONDS );
		return null;
	}
	$data = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( empty( $data['result']['latitude'] ) || ! isset( $data['result']['longitude'] ) ) {
		set_transient( $key, 'MISS', DAY_IN_SECONDS );
		return null;
	}
	$coords = array(
		'lat' => (float) $data['result']['latitude'],
		'lng' => (float) $data['result']['longitude'],
	);
	set_transient( $key, $coords, 30 * DAY_IN_SECONDS );
	return $coords;
}

/**
 * Delivery status for a customer postcode.
 *
 * @param string $postcode Customer postcode.
 * @return array{status:string,distance:?float,message:string}
 *         status: inside|outer|outside|unknown
 */
function harbour_delivery_status( string $postcode ): array {
	$inner = (float) harbour_setting( 'firewood', 'radius_inner', 12 );
	$outer = (float) harbour_setting( 'firewood', 'radius_outer', 20 );
	$yard  = harbour_setting( 'business', 'yard_postcode', 'LE17 5NJ' );
	$phone = harbour_setting( 'business', 'phone_yard', '' );

	$yard_c = harbour_postcode_coords( $yard );
	$cust_c = harbour_postcode_coords( $postcode );

	if ( ! $yard_c || ! $cust_c ) {
		return array(
			'status'   => 'unknown',
			'distance' => null,
			'message'  => __( "We couldn't check the distance automatically, but that's fine — we'll confirm delivery when we get your request.", 'harbour-core' ),
		);
	}

	$miles  = harbour_haversine_miles( $yard_c['lat'], $yard_c['lng'], $cust_c['lat'], $cust_c['lng'] );
	$status = harbour_classify_distance( $miles, $inner, $outer );

	$messages = array(
		'inside'  => __( "You're within our delivery area.", 'harbour-core' ),
		'outer'   => __( "You're just outside our usual area — we may still be able to deliver, and we'll confirm when we get your request.", 'harbour-core' ),
		'outside' => sprintf(
			/* translators: %s: phone number. */
			__( "That's outside our firewood delivery area, sorry. For collection or a special arrangement, give the yard a ring on %s.", 'harbour-core' ),
			$phone
		),
	);

	return array(
		'status'   => $status,
		'distance' => round( $miles, 1 ),
		'message'  => $messages[ $status ],
	);
}
