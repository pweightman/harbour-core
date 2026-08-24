<?php
/**
 * Metaboxes for service/area pages: an optional hero heading and a repeatable
 * FAQ (up to 8 pairs). FAQs feed both the on-page accordion and FAQPage schema.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

const HARBOUR_FAQ_ROWS = 8;

add_action( 'add_meta_boxes', function () {
	foreach ( array( 'service', 'area' ) as $pt ) {
		add_meta_box( 'harbour_hero', __( 'Hero heading', 'harbour-core' ), 'harbour_hero_metabox', $pt, 'normal', 'high' );
		add_meta_box( 'harbour_faq', __( 'FAQ', 'harbour-core' ), 'harbour_faq_metabox', $pt, 'normal', 'default' );
	}
} );

/**
 * Hero-heading metabox.
 *
 * @param WP_Post $post Post.
 */
function harbour_hero_metabox( $post ): void {
	wp_nonce_field( 'harbour_content_meta', 'harbour_content_meta_nonce' );
	$val = get_post_meta( $post->ID, '_harbour_hero_heading', true );
	echo '<p><label for="harbour_hero_heading">' . esc_html__( 'The headline shown in the page hero. Leave blank to use the title.', 'harbour-core' ) . '</label></p>';
	echo '<input type="text" id="harbour_hero_heading" name="harbour_hero_heading" value="' . esc_attr( $val ) . '" class="widefat">';
	echo '<p class="description">' . esc_html__( 'The page excerpt is used as the hero standfirst below this.', 'harbour-core' ) . '</p>';
}

/**
 * FAQ metabox.
 *
 * @param WP_Post $post Post.
 */
function harbour_faq_metabox( $post ): void {
	$faq = get_post_meta( $post->ID, '_harbour_faq', true );
	$faq = is_array( $faq ) ? array_values( $faq ) : array();
	echo '<p class="description">' . esc_html__( 'Questions with answers. Empty rows are ignored. These also generate FAQ structured data for Google.', 'harbour-core' ) . '</p>';
	for ( $i = 0; $i < HARBOUR_FAQ_ROWS; $i++ ) {
		$q = $faq[ $i ]['q'] ?? '';
		$a = $faq[ $i ]['a'] ?? '';
		echo '<div style="margin:0 0 14px;padding:10px;border:1px solid #dcdcde;border-radius:4px">';
		echo '<input type="text" name="harbour_faq[' . $i . '][q]" value="' . esc_attr( $q ) . '" class="widefat" placeholder="' . esc_attr__( 'Question', 'harbour-core' ) . '" style="margin-bottom:6px">';
		echo '<textarea name="harbour_faq[' . $i . '][a]" class="widefat" rows="3" placeholder="' . esc_attr__( 'Answer', 'harbour-core' ) . '">' . esc_textarea( $a ) . '</textarea>';
		echo '</div>';
	}
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['harbour_content_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_content_meta_nonce'] ) ), 'harbour_content_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['harbour_hero_heading'] ) ) {
		update_post_meta( $post_id, '_harbour_hero_heading', sanitize_text_field( wp_unslash( $_POST['harbour_hero_heading'] ) ) );
	}

	if ( isset( $_POST['harbour_faq'] ) && is_array( $_POST['harbour_faq'] ) ) {
		$clean = array();
		foreach ( wp_unslash( $_POST['harbour_faq'] ) as $row ) {
			$q = sanitize_text_field( $row['q'] ?? '' );
			$a = sanitize_textarea_field( $row['a'] ?? '' );
			if ( '' !== $q && '' !== $a ) {
				$clean[] = array( 'q' => $q, 'a' => $a );
			}
		}
		update_post_meta( $post_id, '_harbour_faq', $clean );
	}
} );
