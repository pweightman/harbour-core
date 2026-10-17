<?php
/**
 * Shared taxonomies.
 *
 * service_type and service_area are shared across service/area now and extended
 * to job/testimonial by the gallery/reviews modules later, so a Hinckley
 * stump-grinding job can surface on both the stump-grinding and Hinckley pages.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register taxonomies.
 */
function harbour_register_taxonomies(): void {

	register_taxonomy(
		'service_type',
		array( 'service', 'area', 'job', 'testimonial' ),
		array(
			'labels'             => array(
				'name'          => 'Service types',
				'singular_name' => 'Service type',
				'menu_name'     => 'Service types',
			),
			// Admin-only taxonomy: it organises and filters content, it is not a
			// public landing page. Its thin /service-type/ archive was competing
			// with the real service pages for the same queries, so it is no longer
			// publicly queryable; old archive URLs are 301'd to the matching
			// service (see harbour_redirect_service_type_archives below). The admin
			// UI, the block-editor panel and the admin column all stay.
			'public'             => false,
			'publicly_queryable' => false,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'show_admin_column'  => true,
			'rewrite'            => false,
			'query_var'          => false,
		)
	);

	register_taxonomy(
		'service_area',
		array( 'service', 'area', 'job', 'testimonial' ),
		array(
			'labels'            => array(
				'name'          => 'Service areas',
				'singular_name' => 'Service area',
				'menu_name'     => 'Service areas',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'service-area',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'harbour_register_taxonomies' );

/**
 * 301 old /service-type/{slug}/ archive URLs to the matching service page.
 *
 * service_type is no longer publicly queryable (see above), so its rewrite rule
 * is gone and these URLs would otherwise 404. Redirecting them passes the
 * ranking signals the thin archive had collected to the real service page, and
 * consolidates duplicate "crown reduction leicester"-type impressions onto one
 * URL. Unknown slugs fall back to the services landing page.
 */
function harbour_redirect_service_type_archives(): void {
	$path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH )
		: '';
	if ( '' === $path || 0 !== strpos( $path, '/service-type/' ) ) {
		return;
	}
	$slug = trim( substr( $path, strlen( '/service-type/' ) ), '/' );
	$map  = array(
		'pruning-crown-reduction' => '/services/tree-pruning-crown-reduction/',
	);
	$target = isset( $map[ $slug ] ) ? $map[ $slug ] : '/tree-surgery-leicestershire/';
	wp_safe_redirect( home_url( $target ), 301 );
	exit;
}
add_action( 'template_redirect', 'harbour_redirect_service_type_archives', 1 );
