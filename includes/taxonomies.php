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

	register_taxonomy( 'service_type', array( 'service', 'area', 'job', 'testimonial' ), array(
		'labels'            => array(
			'name'          => 'Service types',
			'singular_name' => 'Service type',
			'menu_name'     => 'Service types',
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'service-type', 'with_front' => false ),
	) );

	register_taxonomy( 'service_area', array( 'service', 'area', 'job', 'testimonial' ), array(
		'labels'            => array(
			'name'          => 'Service areas',
			'singular_name' => 'Service area',
			'menu_name'     => 'Service areas',
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'service-area', 'with_front' => false ),
	) );
}
add_action( 'init', 'harbour_register_taxonomies' );
