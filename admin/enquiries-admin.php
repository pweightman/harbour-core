<?php
/**
 * Enquiry admin: list columns, triage metabox (status, notes, details, photos)
 * and CSV export. Everything is capability-checked and nonce-protected.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

const HARBOUR_STATUSES = array(
	'new'    => 'New',
	'quoted' => 'Quoted',
	'won'    => 'Won',
	'lost'   => 'Lost',
);

/* ------------------------- List table columns ------------------------- */

add_filter( 'manage_enquiry_posts_columns', function ( $cols ) {
	$new = array( 'cb' => $cols['cb'] ?? '' );
	$new['title']    = __( 'Enquiry', 'harbour-core' );
	$new['h_phone']  = __( 'Phone', 'harbour-core' );
	$new['h_service']= __( 'Service', 'harbour-core' );
	$new['h_pc']     = __( 'Postcode', 'harbour-core' );
	$new['h_status'] = __( 'Status', 'harbour-core' );
	$new['date']     = $cols['date'] ?? __( 'Date', 'harbour-core' );
	return $new;
} );

add_action( 'manage_enquiry_posts_custom_column', function ( $col, $post_id ) {
	switch ( $col ) {
		case 'h_phone':
			$phone = get_post_meta( $post_id, '_harbour_phone', true );
			echo $phone ? '<a href="tel:' . esc_attr( preg_replace( '/\s+/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>' : '—';
			break;
		case 'h_service':
			echo esc_html( get_post_meta( $post_id, '_harbour_service', true ) ?: '—' );
			break;
		case 'h_pc':
			echo esc_html( get_post_meta( $post_id, '_harbour_postcode', true ) ?: '—' );
			break;
		case 'h_status':
			$status = get_post_meta( $post_id, '_harbour_status', true ) ?: 'new';
			$failed = get_post_meta( $post_id, '_harbour_mail_failed', true );
			echo '<span class="harbour-status harbour-status-' . esc_attr( $status ) . '">' . esc_html( HARBOUR_STATUSES[ $status ] ?? $status ) . '</span>';
			if ( $failed ) {
				echo ' <span title="' . esc_attr__( 'Notification email failed — follow up manually', 'harbour-core' ) . '" style="color:#b32d2e">⚠</span>';
			}
			break;
	}
}, 10, 2 );

add_action( 'admin_head-edit.php', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'enquiry' !== $screen->post_type ) {
		return;
	}
	echo '<style>.harbour-status{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
	.harbour-status-new{background:#e6f0fb;color:#0f4c81}
	.harbour-status-quoted{background:#fff4d6;color:#8a6100}
	.harbour-status-won{background:#e3f5e3;color:#1e6b1e}
	.harbour-status-lost{background:#f1f1f1;color:#666}</style>';
} );

/* --------------------------- Triage metabox --------------------------- */

add_action( 'add_meta_boxes_enquiry', function () {
	add_meta_box( 'harbour_enquiry_triage', __( 'Enquiry', 'harbour-core' ), 'harbour_enquiry_metabox', 'enquiry', 'normal', 'high' );
} );

/**
 * Render the enquiry detail + status + notes metabox.
 *
 * @param WP_Post $post Enquiry post.
 */
function harbour_enquiry_metabox( $post ): void {
	wp_nonce_field( 'harbour_enquiry_meta', 'harbour_enquiry_meta_nonce' );
	$get = static function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, $k, true );
	};
	$status = $get( '_harbour_status' ) ?: 'new';
	$fields = array(
		__( 'Name', 'harbour-core' )     => $get( '_harbour_name' ),
		__( 'Phone', 'harbour-core' )    => $get( '_harbour_phone' ),
		__( 'Email', 'harbour-core' )    => $get( '_harbour_email' ),
		__( 'Postcode', 'harbour-core' ) => $get( '_harbour_postcode' ),
		__( 'Service', 'harbour-core' )  => $get( '_harbour_service' ),
	);
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $fields as $label => $value ) {
		echo '<tr><th style="width:120px">' . esc_html( $label ) . '</th><td>';
		if ( __( 'Phone', 'harbour-core' ) === $label && $value ) {
			echo '<a href="tel:' . esc_attr( preg_replace( '/\s+/', '', $value ) ) . '">' . esc_html( $value ) . '</a>';
		} elseif ( __( 'Email', 'harbour-core' ) === $label && $value ) {
			echo '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
		} else {
			echo esc_html( $value ?: '—' );
		}
		echo '</td></tr>';
	}
	echo '<tr><th>' . esc_html__( 'Message', 'harbour-core' ) . '</th><td>' . nl2br( esc_html( $get( '_harbour_message' ) ?: '—' ) ) . '</td></tr>';
	echo '</tbody></table>';

	// Photos.
	$photos = get_posts( array(
		'post_type'      => 'attachment',
		'post_parent'    => $post->ID,
		'posts_per_page' => 20,
		'meta_key'       => '_harbour_enquiry_photo',
	) );
	if ( $photos ) {
		echo '<p><strong>' . esc_html__( 'Photos', 'harbour-core' ) . '</strong></p><div style="display:flex;flex-wrap:wrap;gap:8px">';
		foreach ( $photos as $photo ) {
			$thumb = wp_get_attachment_image( $photo->ID, array( 140, 140 ), false, array( 'style' => 'border-radius:6px;object-fit:cover' ) );
			$full  = wp_get_attachment_url( $photo->ID );
			echo '<a href="' . esc_url( $full ) . '" target="_blank" rel="noopener">' . $thumb . '</a>';
		}
		echo '</div>';
	}

	// Status + notes.
	echo '<p><label for="harbour_status"><strong>' . esc_html__( 'Status', 'harbour-core' ) . '</strong></label><br>';
	echo '<select id="harbour_status" name="harbour_status">';
	foreach ( HARBOUR_STATUSES as $slug => $label ) {
		echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $status, $slug, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></p>';
	echo '<p><label for="harbour_notes"><strong>' . esc_html__( 'Internal notes', 'harbour-core' ) . '</strong></label><br>';
	echo '<textarea id="harbour_notes" name="harbour_notes" rows="4" class="large-text">' . esc_textarea( $get( '_harbour_notes' ) ) . '</textarea></p>';

	$consent_time = $get( '_harbour_consent_time' );
	if ( $consent_time ) {
		echo '<p class="description">' . esc_html( sprintf( __( 'Consent given %1$s from IP %2$s.', 'harbour-core' ), $consent_time, $get( '_harbour_consent_ip' ) ) ) . '</p>';
	}
}

add_action( 'save_post_enquiry', function ( $post_id ) {
	if ( ! isset( $_POST['harbour_enquiry_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_enquiry_meta_nonce'] ) ), 'harbour_enquiry_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['harbour_status'] ) ) {
		$status = sanitize_key( wp_unslash( $_POST['harbour_status'] ) );
		if ( isset( HARBOUR_STATUSES[ $status ] ) ) {
			update_post_meta( $post_id, '_harbour_status', $status );
		}
	}
	if ( isset( $_POST['harbour_notes'] ) ) {
		update_post_meta( $post_id, '_harbour_notes', sanitize_textarea_field( wp_unslash( $_POST['harbour_notes'] ) ) );
	}
} );

/* ------------------------------ CSV export ---------------------------- */

add_action( 'manage_posts_extra_tablenav', function ( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'enquiry' !== $screen->post_type ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=harbour_export_enquiries' ), 'harbour_export_enquiries' );
	echo '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Export CSV', 'harbour-core' ) . '</a>';
} );

add_action( 'admin_post_harbour_export_enquiries', function () {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'harbour-core' ) );
	}
	check_admin_referer( 'harbour_export_enquiries' );

	$rows = get_posts( array(
		'post_type'      => 'enquiry',
		'post_status'    => 'any',
		'posts_per_page' => -1,
	) );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=harbour-enquiries-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Date', 'Name', 'Phone', 'Email', 'Postcode', 'Service', 'Status', 'Message', 'Notes' ) );
	foreach ( $rows as $r ) {
		fputcsv( $out, array(
			get_the_date( 'Y-m-d H:i', $r ),
			get_post_meta( $r->ID, '_harbour_name', true ),
			get_post_meta( $r->ID, '_harbour_phone', true ),
			get_post_meta( $r->ID, '_harbour_email', true ),
			get_post_meta( $r->ID, '_harbour_postcode', true ),
			get_post_meta( $r->ID, '_harbour_service', true ),
			get_post_meta( $r->ID, '_harbour_status', true ),
			get_post_meta( $r->ID, '_harbour_message', true ),
			get_post_meta( $r->ID, '_harbour_notes', true ),
		) );
	}
	fclose( $out );
	exit;
} );
