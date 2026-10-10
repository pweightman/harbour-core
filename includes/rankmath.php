<?php
/**
 * Rank Math compatibility.
 *
 * When Rank Math is active it owns the <title>, meta description, canonical,
 * robots and Open Graph / Twitter output; our own SEO output (seo.php) and our
 * robots filter stand down (see harbour_rankmath_active() guards there). We keep
 * OUR JSON-LD @graph as the single source of structured data and disable Rank
 * Math's. Our _harbour_seo_* fields are migrated into Rank Math's, once.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is Rank Math active?
 *
 * @return bool
 */
function harbour_rankmath_active(): bool {
	return class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' );
}

/**
 * Post types whose SEO fields we manage / migrate.
 *
 * @return string[]
 */
function harbour_seo_post_types(): array {
	return array( 'page', 'service', 'area', 'job' );
}

/**
 * Disable Rank Math's own JSON-LD — our @graph is the single source.
 */
add_filter(
	'rank_math/json_ld',
	function ( $data ) {
		return harbour_rankmath_active() ? array() : $data;
	},
	99
);

/**
 * Current migration schema version. Bump this to make the one-shot migration
 * re-run once on the next admin load (it only ever *fills* empty Rank Math
 * fields, so re-running is safe and catches content added since last time).
 */
const HARBOUR_SEO_MIGRATION_VERSION = 2;

/* -------------------------------------------------------------------------
 * Runtime description fallback.
 *
 * Rank Math leaves the meta description empty when neither a per-page value
 * nor a homepage/archive default is set — most visibly on the front page,
 * which otherwise ships with no <meta name="description"> at all. Rather than
 * depend on the one-shot migration having run (there's no WP-CLI on the live
 * host), fill any empty description at render time from our own stored SEO
 * fields. Self-healing: works the moment the plugin updates.
 * ---------------------------------------------------------------------- */

/**
 * Our stored SEO description for the current request, if any.
 *
 * @return string
 */
function harbour_current_seo_desc(): string {
	if ( is_front_page() ) {
		$front = (int) get_option( 'page_on_front' );
		if ( $front ) {
			return (string) get_post_meta( $front, '_harbour_seo_desc', true );
		}
	}
	if ( is_home() && function_exists( 'harbour_blog_index_seo' ) ) {
		$seo = harbour_blog_index_seo();
		return (string) ( $seo['desc'] ?? '' );
	}
	if ( is_singular( harbour_seo_post_types() ) ) {
		return (string) get_post_meta( get_queried_object_id(), '_harbour_seo_desc', true );
	}
	return '';
}

/**
 * Supply a meta description to Rank Math when it would otherwise emit none.
 *
 * @param string $desc Rank Math's computed description.
 * @return string
 */
add_filter(
	'rank_math/frontend/description',
	function ( $desc ) {
		if ( is_string( $desc ) && '' !== trim( $desc ) ) {
			return $desc;
		}
		$ours = harbour_current_seo_desc();
		return '' !== $ours ? $ours : $desc;
	}
);

/* -------------------------------------------------------------------------
 * One-off migration: _harbour_seo_* -> rank_math_*
 * ---------------------------------------------------------------------- */

/**
 * Copy our SEO fields into Rank Math's, only where Rank Math's are empty.
 *
 * @param bool $dry_run When true, report only; write nothing.
 * @return array{titles:int,descs:int,focus:int,posts:int,rows:array}
 */
function harbour_seo_migrate( bool $dry_run = false ): array {
	$out = array(
		'titles' => 0,
		'descs'  => 0,
		'focus'  => 0,
		'posts'  => 0,
		'rows'   => array(),
	);

	$posts = get_posts(
		array(
			'post_type'      => harbour_seo_post_types(),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $id ) {
		$t = (string) get_post_meta( $id, '_harbour_seo_title', true );
		$d = (string) get_post_meta( $id, '_harbour_seo_desc', true );
		if ( '' === $t && '' === $d ) {
			continue;
		}
		$changed = false;

		if ( '' !== $t && '' === (string) get_post_meta( $id, 'rank_math_title', true ) ) {
			if ( ! $dry_run ) {
				update_post_meta( $id, 'rank_math_title', $t );
			}
			++$out['titles'];
			$changed = true;

			// Focus keyword from the title's leading phrase.
			if ( '' === (string) get_post_meta( $id, 'rank_math_focus_keyword', true ) ) {
				$lead = strtolower( trim( preg_split( '/[|,]/', $t )[0] ) );
				if ( $lead && ! $dry_run ) {
					update_post_meta( $id, 'rank_math_focus_keyword', $lead );
				}
				if ( $lead ) {
					++$out['focus'];
				}
			}
		}

		if ( '' !== $d && '' === (string) get_post_meta( $id, 'rank_math_description', true ) ) {
			if ( ! $dry_run ) {
				update_post_meta( $id, 'rank_math_description', $d );
			}
			++$out['descs'];
			$changed = true;
		}

		if ( $changed ) {
			++$out['posts'];
			$out['rows'][] = get_the_title( $id ) . ' (#' . $id . ')';
		}
	}

	// Hand the service / area archive SEO to Rank Math's archive title options.
	$titles = get_option( 'rank-math-options-titles', array() );
	if ( ! is_array( $titles ) ) {
		$titles = array();
	}
	$default_arch   = '%title% %page% %sep% %sitename%';
	$archives       = array(
		'service' => harbour_service_archive_seo(),
		'area'    => harbour_area_archive_seo(),
	);
	$titles_changed = false;
	foreach ( $archives as $pt => $seo ) {
		$tk = 'pt_' . $pt . '_archive_title';
		$dk = 'pt_' . $pt . '_archive_description';
		if ( empty( $titles[ $tk ] ) || $default_arch === $titles[ $tk ] ) {
			$titles[ $tk ]  = $seo['title'];
			$titles_changed = true;
		}
		if ( empty( $titles[ $dk ] ) ) {
			$titles[ $dk ]  = $seo['desc'];
			$titles_changed = true;
		}
	}
	// Advice tag archives: a clean default title + description (editable per tag
	// in Rank Math). Whether each tag is actually indexed is decided at runtime
	// by the linked-article threshold in tags.php.
	$tag_default_title = '%term% %sep% %sitename%';
	if ( empty( $titles['tax_post_tag_title'] ) || $tag_default_title === $titles['tax_post_tag_title'] ) {
		$titles['tax_post_tag_title'] = '%term% — tree care advice %sep% %sitename%';
		$titles_changed               = true;
	}
	if ( empty( $titles['tax_post_tag_description'] ) ) {
		$titles['tax_post_tag_description'] = 'Practical tree care and firewood advice tagged %term% from Harbour Tree Care, a Leicestershire family firm since 1977.';
		$titles_changed                     = true;
	}
	if ( $titles_changed ) {
		if ( ! $dry_run ) {
			update_option( 'rank-math-options-titles', $titles );
		}
		$out['rows'][] = 'Service / area / tag archive titles';
	}

	// Hand the advice (blog) index SEO to the posts page's Rank Math meta.
	$blog_id = (int) get_option( 'page_for_posts' );
	if ( $blog_id ) {
		$bseo = harbour_blog_index_seo();
		if ( '' === (string) get_post_meta( $blog_id, 'rank_math_title', true ) ) {
			if ( ! $dry_run ) {
				update_post_meta( $blog_id, 'rank_math_title', $bseo['title'] );
			}
			++$out['titles'];
		}
		if ( '' === (string) get_post_meta( $blog_id, 'rank_math_description', true ) ) {
			if ( ! $dry_run ) {
				update_post_meta( $blog_id, 'rank_math_description', $bseo['desc'] );
			}
			++$out['descs'];
		}
	}

	// Preserve the /thank-you/ noindex under Rank Math's robots.
	$ty = get_posts(
		array(
			'post_type'   => 'page',
			'name'        => 'thank-you',
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	if ( $ty && ! $dry_run ) {
		$robots = get_post_meta( $ty[0], 'rank_math_robots', true );
		if ( ! is_array( $robots ) || ! in_array( 'noindex', $robots, true ) ) {
			update_post_meta( $ty[0], 'rank_math_robots', array( 'noindex', 'nofollow' ) );
		}
	}

	return $out;
}

/**
 * Run the migration once, when Rank Math is active.
 */
function harbour_rankmath_maybe_migrate(): void {
	if ( ! harbour_rankmath_active() ) {
		return;
	}
	// Re-run once whenever the migration schema version advances. It only ever
	// fills empty Rank Math fields, so re-running catches content (e.g. the
	// front-page description) added since the last run on hosts with no CLI.
	if ( (int) get_option( 'harbour_rankmath_migrated' ) >= HARBOUR_SEO_MIGRATION_VERSION ) {
		return;
	}
	$report = harbour_seo_migrate( false );
	update_option( 'harbour_rankmath_migrated', HARBOUR_SEO_MIGRATION_VERSION );
	update_option( 'harbour_rankmath_migrated_count', (int) $report['posts'] );
}
add_action( 'admin_init', 'harbour_rankmath_maybe_migrate' );

/**
 * One-time admin notice with the migration count.
 */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$count = get_option( 'harbour_rankmath_migrated_count', false );
		if ( false === $count || get_option( 'harbour_rankmath_notice_dismissed' ) ) {
			return;
		}
		echo '<div class="notice notice-success is-dismissible"><p>'
		. esc_html(
			sprintf(
			/* translators: %d: number of pages migrated. */
				_n( 'Harbour: SEO handed over to Rank Math — %d page migrated.', 'Harbour: SEO handed over to Rank Math — %d pages migrated.', (int) $count, 'harbour-core' ),
				(int) $count
			)
		)
		. '</p></div>';
		update_option( 'harbour_rankmath_notice_dismissed', 1 );
	}
);

/* -------------------------------------------------------------------------
 * Core sitemap fallback (only when Rank Math is NOT active — Rank Math
 * replaces the WP core sitemap with its own).
 * ---------------------------------------------------------------------- */

/**
 * Drop the service_type / service_area taxonomies from the core sitemap.
 *
 * @param array $taxonomies Taxonomy objects keyed by name.
 * @return array
 */
add_filter(
	'wp_sitemaps_taxonomies',
	function ( $taxonomies ) {
		if ( harbour_rankmath_active() ) {
			return $taxonomies;
		}
		unset( $taxonomies['service_type'], $taxonomies['service_area'] );
		return $taxonomies;
	}
);

/**
 * Keep noindexed pages (e.g. /thank-you/) out of the core sitemap.
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type.
 * @return array
 */
add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args, $post_type ) {
		if ( harbour_rankmath_active() || 'page' !== $post_type ) {
			return $args;
		}
		$ty = get_page_by_path( 'thank-you' );
		if ( $ty ) {
			$args['post__not_in'] = array_merge( $args['post__not_in'] ?? array(), array( $ty->ID ) );
		}
		return $args;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * WP-CLI: wp harbour seo-migrate [--dry-run]
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'harbour seo-migrate',
		function ( $args, $assoc ) {
			$dry = isset( $assoc['dry-run'] );
			$r   = harbour_seo_migrate( $dry );
			WP_CLI::log( ( $dry ? '[dry run] ' : '' ) . sprintf( '%d pages: %d titles, %d descriptions, %d focus keywords.', $r['posts'], $r['titles'], $r['descs'], $r['focus'] ) );
			foreach ( $r['rows'] as $row ) {
				WP_CLI::log( '  - ' . $row );
			}
			WP_CLI::success( $dry ? 'Dry run complete (nothing written).' : 'Migration complete.' );
		}
	);
}
