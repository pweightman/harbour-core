<?php
/**
 * Advice tag topic pages.
 *
 * A tag becomes an indexable topic page only once it has enough linked articles
 * (the shared `harbour_tag_min_posts` threshold, default 3); thinner tags are
 * kept out of the index so they can't dilute the site with near-empty pages.
 * Substantial tags get a clean SEO title and a sensible meta description.
 *
 * Works with Rank Math (robots handled via its frontend filter; title and
 * description via its tag-taxonomy defaults set during the SEO migration) and
 * with our own SEO fallback when Rank Math is inactive.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the current tag archive substantial enough to index? A tag becomes a topic
 * page once it has at least the shared `harbour_tag_min_posts` linked articles
 * (default 3). The filter is the single source of truth shared with the theme.
 *
 * @return bool
 */
function harbour_tag_is_indexable(): bool {
	if ( ! is_tag() ) {
		return false;
	}
	$term = get_queried_object();
	$min  = max( 1, (int) apply_filters( 'harbour_tag_min_posts', 3 ) );
	return $term instanceof WP_Term && (int) $term->count >= $min;
}

/**
 * SEO title/description for the current tag archive (used by the fallback and
 * to seed Rank Math's tag defaults).
 *
 * @return array{title:string,desc:string}|null
 */
function harbour_tag_archive_seo(): ?array {
	if ( ! is_tag() ) {
		return null;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	$desc = trim( wp_strip_all_tags( term_description( $term ) ) );
	if ( '' === $desc ) {
		$desc = sprintf(
			/* translators: %s: tag name. */
			__( 'Practical tree care and firewood advice tagged “%s” from Harbour Tree Care, a Leicestershire family firm since 1977.', 'harbour-core' ),
			$term->name
		);
	}
	return array(
		'title' => sprintf(
			/* translators: %s: tag name. */
			__( '%s — tree care advice | Harbour Tree Care', 'harbour-core' ),
			ucfirst( $term->name )
		),
		'desc'  => $desc,
	);
}

/**
 * Rank Math: index a tag archive only when it clears the threshold.
 *
 * @param array $robots Robots directives.
 * @return array
 */
add_filter(
	'rank_math/frontend/robots',
	static function ( $robots ) {
		if ( ! harbour_rankmath_active() || ! is_tag() ) {
			return $robots;
		}
		$robots          = is_array( $robots ) ? $robots : array();
		$robots['index'] = harbour_tag_is_indexable() ? 'index' : 'noindex';
		return $robots;
	}
);

/**
 * Fallback (Rank Math inactive): noindex thin tag archives via core robots.
 *
 * @param array $robots Robots directives.
 * @return array
 */
add_filter(
	'wp_robots',
	static function ( $robots ) {
		if ( harbour_rankmath_active() || ! is_tag() ) {
			return $robots;
		}
		if ( ! harbour_tag_is_indexable() ) {
			$robots['noindex'] = true;
		}
		return $robots;
	},
	11
);
