<?php
/**
 * Module 3 — Reviews. Admin-managed testimonials, surfaced by a shortcode and
 * a render function, filterable by service or area.
 *
 * Schema rules: Review markup only on reviews that genuinely exist with a real
 * named reviewer. AggregateRating ONLY when a real rating and count are set in
 * settings — never typed-in or fabricated (Google penalises fake review markup).
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Query testimonials.
 *
 * @param array $args service (term slug), area (term slug), count, featured.
 * @return WP_Post[]
 */
function harbour_get_reviews( array $args = array() ): array {
	$args = wp_parse_args( $args, array( 'service' => '', 'area' => '', 'count' => 3, 'featured' => false ) );
	$q = array(
		'post_type'      => 'testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['count'],
		'orderby'        => 'menu_order date',
		'order'          => 'DESC',
	);
	$tax = array();
	if ( $args['service'] ) {
		$tax[] = array( 'taxonomy' => 'service_type', 'field' => 'slug', 'terms' => $args['service'] );
	}
	if ( $args['area'] ) {
		$tax[] = array( 'taxonomy' => 'service_area', 'field' => 'slug', 'terms' => $args['area'] );
	}
	if ( $tax ) {
		$tax['relation'] = 'AND';
		$q['tax_query']  = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	if ( $args['featured'] ) {
		$q['meta_key']   = '_harbour_featured'; // phpcs:ignore
		$q['meta_value'] = '1'; // phpcs:ignore
	}
	return get_posts( $q );
}

/**
 * Render a set of reviews as the prototype's .quotes grid. Emits Review schema.
 *
 * @param array $args See harbour_get_reviews().
 * @return string
 */
function harbour_reviews_render( array $args = array() ): string {
	$reviews = harbour_get_reviews( $args );
	if ( ! $reviews ) {
		return '';
	}
	ob_start();
	echo '<div class="quotes">';
	foreach ( $reviews as $r ) {
		$quote    = get_post_meta( $r->ID, '_harbour_quote', true );
		$reviewer = get_post_meta( $r->ID, '_harbour_reviewer', true );
		$town     = get_post_meta( $r->ID, '_harbour_town', true );
		$source   = get_post_meta( $r->ID, '_harbour_source', true );
		$rating   = (int) get_post_meta( $r->ID, '_harbour_rating', true );
		$date     = get_post_meta( $r->ID, '_harbour_review_date', true );
		if ( ! $quote ) {
			continue;
		}
		echo '<div class="quote reveal">';
		if ( $rating >= 1 && $rating <= 5 ) {
			echo '<div class="stars" aria-label="' . esc_attr( sprintf( __( '%d out of 5', 'harbour-core' ), $rating ) ) . '">';
			for ( $i = 0; $i < $rating; $i++ ) {
				echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l3 6.5 7 .9-5 4.8 1.3 7L12 17.8 5.7 21.2 7 14.2 2 9.4l7-.9z"/></svg>';
			}
			echo '</div>';
		}
		echo '<blockquote>&ldquo;' . esc_html( $quote ) . '&rdquo;</blockquote>';
		$attr = array_filter( array( $reviewer, $town, $source ) );
		echo '<footer>' . esc_html( implode( ' · ', $attr ) ) . '</footer>';

		// Review schema — only because this is a genuine, named review record.
		if ( $reviewer ) {
			$schema = array(
				'@context'    => 'https://schema.org',
				'@type'       => 'Review',
				'author'      => array( '@type' => 'Person', 'name' => $reviewer ),
				'reviewBody'  => $quote,
				'itemReviewed'=> array( '@type' => 'LocalBusiness', 'name' => harbour_setting( 'business', 'name', get_bloginfo( 'name' ) ), '@id' => home_url( '/#business' ) ),
			);
			if ( $rating >= 1 && $rating <= 5 ) {
				$schema['reviewRating'] = array( '@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5 );
			}
			if ( $date ) {
				$schema['datePublished'] = $date;
			}
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
		}
		echo '</div>';
	}
	echo '</div>';
	return (string) ob_get_clean();
}

/**
 * [harbour_reviews service="" area="" count="3" featured=""]
 *
 * @param array $atts Attributes.
 * @return string
 */
function harbour_reviews_shortcode( $atts ): string {
	$atts = shortcode_atts( array( 'service' => '', 'area' => '', 'count' => 3, 'featured' => '' ), $atts, 'harbour_reviews' );
	return harbour_reviews_render( array(
		'service'  => sanitize_title( $atts['service'] ),
		'area'     => sanitize_title( $atts['area'] ),
		'count'    => (int) $atts['count'],
		'featured' => ! empty( $atts['featured'] ),
	) );
}
add_shortcode( 'harbour_reviews', 'harbour_reviews_shortcode' );

/**
 * Emit AggregateRating in the head — ONLY from a real, admin-entered figure.
 */
function harbour_aggregate_rating_schema(): void {
	if ( is_admin() ) {
		return;
	}
	$value = harbour_setting( 'reviews', 'aggregate_value', '' );
	$count = harbour_setting( 'reviews', 'aggregate_count', '' );
	if ( '' === $value || '' === $count || (float) $value <= 0 || (int) $count <= 0 ) {
		return; // No fabricated ratings.
	}
	$data = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'AggregateRating',
		'itemReviewed'    => array( '@type' => 'LocalBusiness', '@id' => home_url( '/#business' ), 'name' => harbour_setting( 'business', 'name', get_bloginfo( 'name' ) ) ),
		'ratingValue'     => (float) $value,
		'reviewCount'     => (int) $count,
		'bestRating'      => 5,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'harbour_aggregate_rating_schema', 21 );
