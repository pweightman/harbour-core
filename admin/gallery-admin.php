<?php
/**
 * Job gallery admin: two image pickers (before / after) and a featured flag.
 * The edit screen is deliberately just two pickers and a title.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'add_meta_boxes_job',
	function () {
		add_meta_box( 'harbour_job', __( 'Before & after', 'harbour-core' ), 'harbour_job_metabox', 'job', 'normal', 'high' );
	}
);

/**
 * Enqueue the media picker on the job edit screen.
 *
 * @param string $hook Admin page hook.
 */
function harbour_gallery_admin_assets( $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'job' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'harbour-gallery-admin', HARBOUR_CORE_URL . 'admin/assets/gallery-admin.js', array( 'jquery' ), HARBOUR_CORE_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'harbour_gallery_admin_assets' );

/**
 * Before/after picker metabox.
 *
 * @param WP_Post $post Job.
 */
function harbour_job_metabox( $post ): void {
	wp_nonce_field( 'harbour_job_meta', 'harbour_job_meta_nonce' );
	$before = (int) get_post_meta( $post->ID, '_harbour_before', true );
	$after  = (int) get_post_meta( $post->ID, '_harbour_after', true );
	$field  = static function ( $slot, $id ) {
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		echo '<div class="harbour-pick" style="display:inline-block;vertical-align:top;width:48%;min-width:220px;margin-right:1%">';
		echo '<p><strong>' . esc_html( ucfirst( $slot ) ) . '</strong></p>';
		echo '<div class="harbour-pick-preview" style="border:1px dashed #c3c4c7;border-radius:6px;min-height:120px;display:flex;align-items:center;justify-content:center;background:#f6f7f7;overflow:hidden">';
		echo $src ? '<img src="' . esc_url( $src ) . '" style="max-width:100%;height:auto;display:block">' : '<span style="color:#787c82">' . esc_html__( 'No image', 'harbour-core' ) . '</span>';
		echo '</div>';
		echo '<input type="hidden" class="harbour-pick-input" name="harbour_' . esc_attr( $slot ) . '" value="' . esc_attr( $id ) . '">';
		echo '<p><button type="button" class="button harbour-pick-choose" data-slot="' . esc_attr( $slot ) . '">' . esc_html__( 'Choose image', 'harbour-core' ) . '</button> ';
		echo '<button type="button" class="button-link harbour-pick-clear" style="color:#b32d2e">' . esc_html__( 'Remove', 'harbour-core' ) . '</button></p>';
		echo '</div>';
	};
	echo '<div class="harbour-pickers">';
	$field( 'before', $before );
	$field( 'after', $after );
	echo '</div>';
	echo '<p style="margin-top:10px"><label><input type="checkbox" name="harbour_featured" value="1" ' . checked( get_post_meta( $post->ID, '_harbour_featured', true ), '1', false ) . '> ' . esc_html__( 'Featured (shown first)', 'harbour-core' ) . '</label></p>';
	echo '<p class="description">' . esc_html__( 'Add a short description in the main editor, and assign Service type / Service area terms so this job surfaces on the matching pages.', 'harbour-core' ) . '</p>';
}

add_action(
	'save_post_job',
	function ( $post_id ) {
		if ( ! isset( $_POST['harbour_job_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_job_meta_nonce'] ) ), 'harbour_job_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_harbour_before', absint( wp_unslash( $_POST['harbour_before'] ?? 0 ) ) );
		update_post_meta( $post_id, '_harbour_after', absint( wp_unslash( $_POST['harbour_after'] ?? 0 ) ) );
		update_post_meta( $post_id, '_harbour_featured', empty( $_POST['harbour_featured'] ) ? '' : '1' );
	}
);
