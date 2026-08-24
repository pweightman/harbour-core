<?php
/**
 * Custom post types.
 *
 * Public content types (service, area) carry the marketing pages; enquiry is a
 * private store of customer submissions — never public, never in search or the
 * sitemap. job / testimonial / log_order are registered by their own modules
 * in a later phase.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register post types.
 */
function harbour_register_post_types(): void {

	register_post_type(
		'service',
		array(
			'labels'        => harbour_pt_labels( 'Service', 'Services' ),
			'public'        => true,
			'menu_icon'     => 'dashicons-palmtree',
			'menu_position' => 21,
			'has_archive'   => 'tree-surgery-leicestershire',
			'rewrite'       => array(
				'slug'       => 'services',
				'with_front' => false,
			),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'area',
		array(
			'labels'        => harbour_pt_labels( 'Area', 'Areas' ),
			'public'        => true,
			'menu_icon'     => 'dashicons-location-alt',
			'menu_position' => 22,
			'has_archive'   => 'areas',
			'rewrite'       => array(
				'slug'       => 'areas',
				'with_front' => false,
			),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'enquiry',
		array(
			'labels'              => harbour_pt_labels( 'Enquiry', 'Enquiries' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'harbour',
			'menu_icon'           => 'dashicons-email',
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_rest'        => false,
		)
	);

	register_post_type(
		'job',
		array(
			'labels'        => harbour_pt_labels( 'Job', 'Our work' ),
			'public'        => true,
			'menu_icon'     => 'dashicons-camera',
			'menu_position' => 23,
			'has_archive'   => 'our-work',
			'rewrite'       => array(
				'slug'       => 'our-work',
				'with_front' => false,
			),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'testimonial',
		array(
			'labels'              => harbour_pt_labels( 'Review', 'Reviews' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'harbour',
			'menu_icon'           => 'dashicons-star-filled',
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'supports'            => array( 'title' ),
			'show_in_rest'        => false,
		)
	);

	register_post_type(
		'log_order',
		array(
			'labels'              => harbour_pt_labels( 'Log order', 'Log orders' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'harbour',
			'menu_icon'           => 'dashicons-cart',
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_rest'        => false,
		)
	);
}
add_action( 'init', 'harbour_register_post_types' );

/**
 * Standard label set for a post type.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array<string,string>
 */
function harbour_pt_labels( string $singular, string $plural ): array {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		'add_new_item'  => sprintf( 'Add new %s', strtolower( $singular ) ),
		'edit_item'     => sprintf( 'Edit %s', strtolower( $singular ) ),
		'new_item'      => sprintf( 'New %s', strtolower( $singular ) ),
		'view_item'     => sprintf( 'View %s', strtolower( $singular ) ),
		'search_items'  => sprintf( 'Search %s', strtolower( $plural ) ),
		'not_found'     => sprintf( 'No %s found', strtolower( $plural ) ),
		'all_items'     => $plural,
		'menu_name'     => $plural,
	);
}
