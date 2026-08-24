<?php
/**
 * Registered post meta for enquiries (and FAQ fields reused by templates).
 *
 * Enquiry meta is single, sanitised, and NOT exposed in REST — it is customer
 * personal data.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register enquiry meta keys.
 */
function harbour_register_meta(): void {
	$text = array(
		'type'         => 'string',
		'single'       => true,
		'show_in_rest' => false,
		'auth_callback'=> '__return_false', // Never editable via REST.
	);

	foreach ( array(
		'_harbour_name', '_harbour_phone', '_harbour_email', '_harbour_postcode',
		'_harbour_service', '_harbour_timing', '_harbour_message', '_harbour_status',
		'_harbour_notes', '_harbour_consent_wording', '_harbour_consent_ip',
		'_harbour_consent_time', '_harbour_source',
	) as $key ) {
		register_post_meta( 'enquiry', $key, array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function () {
				return current_user_can( 'edit_others_posts' );
			},
		) );
	}

	// Hero heading + FAQ for public content types.
	foreach ( array( 'service', 'area' ) as $pt ) {
		register_post_meta( $pt, '_harbour_hero_heading', array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function () { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( $pt, '_harbour_faq', array(
			'type'          => 'array',
			'single'        => true,
			'show_in_rest'  => false,
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
	}

	// Review (testimonial) fields.
	foreach ( array( '_harbour_quote', '_harbour_reviewer', '_harbour_town', '_harbour_source', '_harbour_rating', '_harbour_review_date' ) as $key ) {
		register_post_meta( 'testimonial', $key, array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => false,
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
	}
	register_post_meta( 'testimonial', '_harbour_featured', array( 'type' => 'boolean', 'single' => true, 'show_in_rest' => false, 'auth_callback' => function () { return current_user_can( 'edit_posts' ); } ) );

	// Job (gallery) fields.
	foreach ( array( '_harbour_before', '_harbour_after' ) as $key ) {
		register_post_meta( 'job', $key, array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true, 'auth_callback' => function () { return current_user_can( 'edit_posts' ); } ) );
	}
	register_post_meta( 'job', '_harbour_featured', array( 'type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'auth_callback' => function () { return current_user_can( 'edit_posts' ); } ) );

	unset( $text );
}
add_action( 'init', 'harbour_register_meta' );
