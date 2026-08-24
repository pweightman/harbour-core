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
	unset( $text );
}
add_action( 'init', 'harbour_register_meta' );
