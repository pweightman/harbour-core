<?php
/**
 * JSON-LD schema, derived from business settings so the NAP is identical
 * everywhere. Lives in the plugin because it is content, not presentation.
 *
 * No fabricated ratings: AggregateRating is emitted only when a real rating and
 * count exist in settings (added with the reviews module once the Google
 * Business Profile is claimed).
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output the @graph in the document head.
 */
function harbour_output_schema(): void {
	if ( is_admin() ) {
		return;
	}

	$name  = harbour_setting( 'business', 'name', get_bloginfo( 'name' ) );
	$phone = harbour_setting( 'business', 'phone_yard', '' );
	$email = harbour_setting( 'business', 'email', '' );
	$biz_id = home_url( '/#business' );

	$business = array(
		'@type'    => array( 'LocalBusiness', 'HomeAndConstructionBusiness' ),
		'@id'      => $biz_id,
		'name'     => $name,
		'url'      => home_url( '/' ),
		'areaServed' => array( 'Leicestershire', 'Warwickshire', 'Northamptonshire', 'Nottinghamshire', 'Derbyshire' ),
	);
	if ( $phone ) {
		$business['telephone'] = $phone;
	}
	if ( $email ) {
		$business['email'] = $email;
	}
	$address = array_filter( array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => trim( harbour_setting( 'business', 'addr_line1', '' ) . ' ' . harbour_setting( 'business', 'addr_line2', '' ) ),
		'addressLocality' => harbour_setting( 'business', 'addr_line2', '' ),
		'addressRegion'   => harbour_setting( 'business', 'addr_county', '' ),
		'postalCode'      => harbour_setting( 'business', 'addr_post', '' ),
		'addressCountry'  => 'GB',
	) );
	if ( count( $address ) > 1 ) {
		$business['address'] = $address;
	}
	$same_as = array_values( array_filter( array(
		harbour_setting( 'business', 'facebook', '' ),
		harbour_setting( 'business', 'instagram', '' ),
	) ) );
	if ( $same_as ) {
		$business['sameAs'] = $same_as;
	}
	$logo = get_theme_mod( 'custom_logo' );
	if ( $logo ) {
		$src = wp_get_attachment_image_url( $logo, 'full' );
		if ( $src ) {
			$business['logo'] = $src;
			$business['image'] = $src;
		}
	}

	$website = array(
		'@type'     => 'WebSite',
		'@id'       => home_url( '/#website' ),
		'url'       => home_url( '/' ),
		'name'      => $name,
		'publisher' => array( '@id' => $biz_id ),
	);

	$graph = array( $business, $website );

	// Breadcrumbs (Home → current) on non-front pages.
	if ( ! is_front_page() ) {
		$items = array(
			array( '@type' => 'ListItem', 'position' => 1, 'name' => __( 'Home', 'harbour-core' ), 'item' => home_url( '/' ) ),
		);
		$title = wp_get_document_title();
		$items[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => wp_strip_all_tags( $title ), 'item' => home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ) );
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $items );
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'harbour_output_schema', 20 );

/**
 * Keep the thank-you page and any private content out of the index.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function harbour_robots( array $robots ): array {
	if ( is_page( 'thank-you' ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'harbour_robots' );
