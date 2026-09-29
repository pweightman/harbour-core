<?php
/**
 * Per-page SEO output: overrides the document <title> and prints a meta
 * description from the _harbour_seo_* fields, falling back to the excerpt.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Override the document title on singular content that has an SEO title.
 *
 * @param string $title Default title.
 * @return string
 */
function harbour_seo_document_title( $title ) {
	if ( harbour_rankmath_active() ) {
		return $title; // Rank Math owns the title.
	}
	if ( is_singular() ) {
		$seo = get_post_meta( get_queried_object_id(), '_harbour_seo_title', true );
		if ( $seo ) {
			return $seo;
		}
	}
	$arch = harbour_archive_seo();
	if ( $arch && $arch['title'] ) {
		return $arch['title'];
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'harbour_seo_document_title', 20 );

/**
 * Print a meta description in the head.
 */
function harbour_seo_meta_description(): void {
	if ( harbour_rankmath_active() ) {
		return; // Rank Math owns the meta description and Open Graph.
	}
	$desc = '';
	if ( is_singular() ) {
		$id   = get_queried_object_id();
		$desc = get_post_meta( $id, '_harbour_seo_desc', true );
		if ( ! $desc ) {
			$desc = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
		}
	} else {
		$arch = harbour_archive_seo();
		$desc = $arch ? $arch['desc'] : '';
	}
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'harbour_seo_meta_description', 2 );

/**
 * Emit a canonical for the blog index and CPT archives, which WordPress core
 * does not canonicalise (it only handles singular content via rel_canonical).
 */
function harbour_seo_canonical(): void {
	if ( harbour_rankmath_active() ) {
		return; // Rank Math owns canonical.
	}
	$url = '';
	if ( is_home() && ! is_front_page() ) {
		$blog_id = (int) get_option( 'page_for_posts' );
		$url     = $blog_id ? get_permalink( $blog_id ) : home_url( '/' );
	} elseif ( is_post_type_archive( 'service' ) ) {
		$url = get_post_type_archive_link( 'service' );
	} elseif ( is_post_type_archive( 'area' ) ) {
		$url = get_post_type_archive_link( 'area' );
	}
	if ( $url ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'harbour_seo_canonical', 2 );

/**
 * SEO title/description values for the service archive (context-free).
 *
 * @return array{title:string,desc:string}
 */
function harbour_service_archive_seo(): array {
	return array(
		'title' => 'Tree Surgery Services in Leicestershire | Harbour Tree Care',
		'desc'  => 'Pruning, felling, stump grinding, site clearance, surveys and seasoned firewood across Leicestershire. Free site visits from a family firm since 1977.',
	);
}

/**
 * SEO title/description values for the area archive (context-free).
 *
 * @return array{title:string,desc:string}
 */
function harbour_area_archive_seo(): array {
	return array(
		'title' => 'Areas We Cover | Harbour Tree Care, Leicestershire',
		'desc'  => 'Tree surgeons covering Lutterworth, Hinckley, Leicester, Rugby and Market Harborough, plus wider Leicestershire and the East Midlands. Free quotes.',
	);
}

/**
 * SEO title/description values for the advice (blog) index (context-free).
 *
 * @return array{title:string,desc:string}
 */
function harbour_blog_index_seo(): array {
	return array(
		'title' => 'Tree Care & Firewood Advice | Harbour Tree Care',
		'desc'  => 'Straight answers on tree care, pruning, felling, stumps and firewood from a Leicestershire family firm, since 1977.',
	);
}

/**
 * SEO title/description for the current archive / blog index.
 *
 * @return array{title:string,desc:string}|null
 */
function harbour_archive_seo(): ?array {
	if ( is_post_type_archive( 'service' ) ) {
		return harbour_service_archive_seo();
	}
	if ( is_post_type_archive( 'area' ) ) {
		return harbour_area_archive_seo();
	}
	if ( is_home() && ! is_front_page() ) {
		return harbour_blog_index_seo();
	}
	return null;
}
