<?php
/**
 * Reviews admin: the review fields metabox and a standing notice about the
 * schema rules (so a future editor doesn't fabricate review markup).
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_testimonial', function () {
	add_meta_box( 'harbour_review', __( 'Review details', 'harbour-core' ), 'harbour_review_metabox', 'testimonial', 'normal', 'high' );
} );

/**
 * Review fields metabox.
 *
 * @param WP_Post $post Testimonial.
 */
function harbour_review_metabox( $post ): void {
	wp_nonce_field( 'harbour_review_meta', 'harbour_review_meta_nonce' );
	$g = static function ( $k ) use ( $post ) { return get_post_meta( $post->ID, $k, true ); };
	?>
	<style>.harbour-rev label{display:block;font-weight:600;margin:12px 0 4px}.harbour-rev input[type=text],.harbour-rev textarea,.harbour-rev select{width:100%}</style>
	<div class="harbour-rev">
		<label for="hr_quote"><?php esc_html_e( 'Quote', 'harbour-core' ); ?></label>
		<textarea id="hr_quote" name="harbour_quote" rows="3"><?php echo esc_textarea( $g( '_harbour_quote' ) ); ?></textarea>
		<label for="hr_reviewer"><?php esc_html_e( 'Reviewer first name', 'harbour-core' ); ?></label>
		<input type="text" id="hr_reviewer" name="harbour_reviewer" value="<?php echo esc_attr( $g( '_harbour_reviewer' ) ); ?>">
		<label for="hr_town"><?php esc_html_e( 'Town', 'harbour-core' ); ?></label>
		<input type="text" id="hr_town" name="harbour_town" value="<?php echo esc_attr( $g( '_harbour_town' ) ); ?>">
		<label for="hr_source"><?php esc_html_e( 'Source', 'harbour-core' ); ?></label>
		<select id="hr_source" name="harbour_source">
			<?php foreach ( array( 'Google', 'Facebook', 'Direct' ) as $src ) : ?>
				<option <?php selected( $g( '_harbour_source' ), $src ); ?>><?php echo esc_html( $src ); ?></option>
			<?php endforeach; ?>
		</select>
		<label for="hr_rating"><?php esc_html_e( 'Rating (1–5)', 'harbour-core' ); ?></label>
		<select id="hr_rating" name="harbour_rating">
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<option value="<?php echo esc_attr( $i ); ?>" <?php selected( (int) $g( '_harbour_rating' ), $i ); ?>><?php echo esc_html( $i ); ?></option>
			<?php endfor; ?>
		</select>
		<label for="hr_date"><?php esc_html_e( 'Date', 'harbour-core' ); ?></label>
		<input type="date" id="hr_date" name="harbour_review_date" value="<?php echo esc_attr( $g( '_harbour_review_date' ) ); ?>">
		<label style="margin-top:14px"><input type="checkbox" name="harbour_featured" value="1" <?php checked( $g( '_harbour_featured' ), '1' ); ?>> <?php esc_html_e( 'Featured', 'harbour-core' ); ?></label>
		<p class="description" style="margin-top:8px"><?php esc_html_e( 'Assign Service type and Service area terms (right-hand panels) so this review can be shown on the matching service and area pages.', 'harbour-core' ); ?></p>
	</div>
	<?php
}

add_action( 'save_post_testimonial', function ( $post_id ) {
	if ( ! isset( $_POST['harbour_review_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_review_meta_nonce'] ) ), 'harbour_review_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_harbour_quote', sanitize_textarea_field( wp_unslash( $_POST['harbour_quote'] ?? '' ) ) );
	update_post_meta( $post_id, '_harbour_reviewer', sanitize_text_field( wp_unslash( $_POST['harbour_reviewer'] ?? '' ) ) );
	update_post_meta( $post_id, '_harbour_town', sanitize_text_field( wp_unslash( $_POST['harbour_town'] ?? '' ) ) );
	update_post_meta( $post_id, '_harbour_source', sanitize_text_field( wp_unslash( $_POST['harbour_source'] ?? '' ) ) );
	$rating = absint( wp_unslash( $_POST['harbour_rating'] ?? 0 ) );
	update_post_meta( $post_id, '_harbour_rating', min( 5, max( 1, $rating ) ) );
	update_post_meta( $post_id, '_harbour_review_date', sanitize_text_field( wp_unslash( $_POST['harbour_review_date'] ?? '' ) ) );
	update_post_meta( $post_id, '_harbour_featured', empty( $_POST['harbour_featured'] ) ? '' : '1' );
	// Keep an admin-friendly title.
	$name = sanitize_text_field( wp_unslash( $_POST['harbour_reviewer'] ?? '' ) );
	if ( $name ) {
		remove_action( 'save_post_testimonial', __FUNCTION__ );
		wp_update_post( array( 'ID' => $post_id, 'post_title' => $name . ' — ' . sanitize_text_field( wp_unslash( $_POST['harbour_town'] ?? '' ) ) ) );
		add_action( 'save_post_testimonial', __FUNCTION__ );
	}
} );

/**
 * Standing notice on the reviews screens about honest schema.
 */
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'testimonial' !== $screen->post_type ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Reviews must be genuine.', 'harbour-core' ) . '</strong> ' . esc_html__( 'Only add real reviews from real, named customers. The site marks up reviews for Google using this data — fabricated reviews or ratings earn a manual penalty that takes months to recover from. Never add a review the business wrote about itself.', 'harbour-core' ) . '</p></div>';
} );
