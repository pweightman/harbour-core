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

	$name   = harbour_setting( 'business', 'name', get_bloginfo( 'name' ) );
	$phone  = harbour_setting( 'business', 'phone_yard', '' );
	$email  = harbour_setting( 'business', 'email', '' );
	$biz_id = home_url( '/#business' );

	$business = array(
		'@type'      => array( 'LocalBusiness', 'HomeAndConstructionBusiness' ),
		'@id'        => $biz_id,
		'name'       => $name,
		'url'        => home_url( '/' ),
		'areaServed' => array( 'Leicestershire', 'Warwickshire', 'Northamptonshire', 'Nottinghamshire', 'Derbyshire' ),
	);
	if ( $phone ) {
		$business['telephone'] = $phone;
	}
	if ( $email ) {
		$business['email'] = $email;
	}
	$address = array_filter(
		array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => trim( harbour_setting( 'business', 'addr_line1', '' ) . ' ' . harbour_setting( 'business', 'addr_line2', '' ) ),
			'addressLocality' => harbour_setting( 'business', 'addr_line2', '' ),
			'addressRegion'   => harbour_setting( 'business', 'addr_county', '' ),
			'postalCode'      => harbour_setting( 'business', 'addr_post', '' ),
			'addressCountry'  => 'GB',
		)
	);
	if ( count( $address ) > 1 ) {
		$business['address'] = $address;
	}
	$same_as = array_values(
		array_filter(
			array(
				harbour_setting( 'business', 'facebook', '' ),
				harbour_setting( 'business', 'instagram', '' ),
			)
		)
	);
	if ( $same_as ) {
		$business['sameAs'] = $same_as;
	}
	$logo = get_theme_mod( 'custom_logo' );
	if ( $logo ) {
		$src = wp_get_attachment_image_url( $logo, 'full' );
		if ( $src ) {
			$business['logo']  = $src;
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
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Home', 'harbour-core' ),
				'item'     => home_url( '/' ),
			),
		);
		$pos   = 2;

		// Insert the Advice level for blog posts.
		if ( is_singular( 'post' ) ) {
			$blog_id = (int) get_option( 'page_for_posts' );
			if ( $blog_id ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => get_the_title( $blog_id ),
					'item'     => get_permalink( $blog_id ),
				);
			}
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos,
				'name'     => wp_strip_all_tags( get_the_title( get_queried_object_id() ) ),
				'item'     => get_permalink( get_queried_object_id() ),
			);
		} elseif ( is_tag() ) {
			// Advice tag archive: Home > Advice > Tag.
			$blog_id = (int) get_option( 'page_for_posts' );
			if ( $blog_id ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => get_the_title( $blog_id ),
					'item'     => get_permalink( $blog_id ),
				);
			}
			$term    = get_queried_object();
			$link    = $term instanceof WP_Term ? get_term_link( $term ) : '';
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos,
				'name'     => $term instanceof WP_Term ? $term->name : wp_strip_all_tags( wp_get_document_title() ),
				'item'     => ( $link && ! is_wp_error( $link ) ) ? $link : home_url( '/' ),
			);
		} else {
			$title   = wp_get_document_title();
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos,
				'name'     => wp_strip_all_tags( $title ),
				'item'     => home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ),
			);
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	// Content-derived schema on singular service/area pages.
	if ( is_singular( array( 'service', 'area' ) ) ) {
		$pid = get_queried_object_id();

		$service = array(
			'@type'      => 'Service',
			'name'       => get_the_title( $pid ),
			'provider'   => array( '@id' => $biz_id ),
			'areaServed' => is_singular( 'area' ) ? get_the_title( $pid ) : array( 'Leicestershire', 'Warwickshire', 'Northamptonshire' ),
			'url'        => get_permalink( $pid ),
		);
		$graph[] = $service;

		$faq = get_post_meta( $pid, '_harbour_faq', true );
		if ( is_array( $faq ) && $faq ) {
			$items = array();
			foreach ( $faq as $row ) {
				if ( empty( $row['q'] ) || empty( $row['a'] ) ) {
					continue;
				}
				$items[] = array(
					'@type'          => 'Question',
					'name'           => $row['q'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $row['a'],
					),
				);
			}
			if ( $items ) {
				$graph[] = array(
					'@type'      => 'FAQPage',
					'mainEntity' => $items,
				);
			}
		}
	}

	// BlogPosting for single advice articles.
	if ( is_singular( 'post' ) ) {
		$pid     = get_queried_object_id();
		$post    = array(
			'@type'            => 'BlogPosting',
			'headline'         => wp_strip_all_tags( get_the_title( $pid ) ),
			'datePublished'    => get_the_date( 'c', $pid ),
			'dateModified'     => get_the_modified_date( 'c', $pid ),
			'author'           => array( '@id' => $biz_id ),
			'publisher'        => array( '@id' => $biz_id ),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $pid ),
			),
		);
		$excerpt = has_excerpt( $pid ) ? get_the_excerpt( $pid ) : '';
		if ( $excerpt ) {
			$post['description'] = wp_strip_all_tags( $excerpt );
		}
		$img = get_the_post_thumbnail_url( $pid, 'large' );
		if ( $img ) {
			$post['image'] = $img;
		}
		$graph[] = $post;
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
	if ( harbour_rankmath_active() ) {
		return $robots; // Rank Math owns robots (thank-you noindex migrated to rank_math_robots).
	}
	if ( is_page( 'thank-you' ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'harbour_robots' );
