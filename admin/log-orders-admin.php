<?php
/**
 * Log-order admin: columns, status metabox, CSV export.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

const HARBOUR_ORDER_STATUSES = array(
	'new'       => 'New',
	'confirmed' => 'Confirmed',
	'delivered' => 'Delivered',
	'cancelled' => 'Cancelled',
);

add_filter(
	'manage_log_order_posts_columns',
	function ( $cols ) {
		$new              = array( 'cb' => $cols['cb'] ?? '' );
		$new['title']     = __( 'Order', 'harbour-core' );
		$new['lo_prod']   = __( 'Product', 'harbour-core' );
		$new['lo_pc']     = __( 'Postcode', 'harbour-core' );
		$new['lo_dist']   = __( 'Distance', 'harbour-core' );
		$new['lo_status'] = __( 'Status', 'harbour-core' );
		$new['date']      = $cols['date'] ?? __( 'Date', 'harbour-core' );
		return $new;
	}
);

add_action(
	'manage_log_order_posts_custom_column',
	function ( $col, $post_id ) {
		switch ( $col ) {
			case 'lo_prod':
				echo esc_html( get_post_meta( $post_id, '_harbour_product', true ) . ' ×' . get_post_meta( $post_id, '_harbour_qty', true ) );
				break;
			case 'lo_pc':
				echo esc_html( get_post_meta( $post_id, '_harbour_postcode', true ) ? get_post_meta( $post_id, '_harbour_postcode', true ) : '—' );
				break;
			case 'lo_dist':
				$d = get_post_meta( $post_id, '_harbour_distance', true );
				$b = get_post_meta( $post_id, '_harbour_delivery_band', true );
				echo '' === $d ? esc_html__( 'not checked', 'harbour-core' ) : esc_html( $d . ' mi · ' . $b );
				break;
			case 'lo_status':
				$s = get_post_meta( $post_id, '_harbour_status', true ) ? get_post_meta( $post_id, '_harbour_status', true ) : 'new';
				echo esc_html( HARBOUR_ORDER_STATUSES[ $s ] ?? $s );
				if ( get_post_meta( $post_id, '_harbour_mail_failed', true ) ) {
					echo ' <span title="' . esc_attr__( 'Notification email failed', 'harbour-core' ) . '" style="color:#b32d2e">⚠</span>';
				}
				break;
		}
	},
	10,
	2
);

add_action(
	'add_meta_boxes_log_order',
	function () {
		add_meta_box( 'harbour_order', __( 'Order', 'harbour-core' ), 'harbour_log_order_metabox', 'log_order', 'normal', 'high' );
	}
);

/**
 * Order detail + status metabox.
 *
 * @param WP_Post $post Order.
 */
function harbour_log_order_metabox( $post ): void {
	wp_nonce_field( 'harbour_order_meta', 'harbour_order_meta_nonce' );
	$g     = static function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, $k, true );
	};
	$total = $g( '_harbour_total' );
	$rows  = array(
		__( 'Product', 'harbour-core' )     => $g( '_harbour_product' ) . ' × ' . $g( '_harbour_qty' ),
		__( 'Unit price', 'harbour-core' )  => $g( '_harbour_unit_price' ),
		__( 'Est. total', 'harbour-core' )  => '' === $total ? __( 'to confirm', 'harbour-core' ) : '£' . number_format( (float) $total, 2 ),
		__( 'Delivery to', 'harbour-core' ) => $g( '_harbour_postcode' ) . ' (' . ( '' === $g( '_harbour_distance' ) ? __( 'not checked', 'harbour-core' ) : $g( '_harbour_distance' ) . ' mi, ' . $g( '_harbour_delivery_band' ) ) . ')',
		__( 'Window', 'harbour-core' )      => $g( '_harbour_slot' ) ? $g( '_harbour_slot' ) : '—',
		__( 'Name', 'harbour-core' )        => $g( '_harbour_name' ),
		__( 'Phone', 'harbour-core' )       => $g( '_harbour_phone' ),
		__( 'Email', 'harbour-core' )       => $g( '_harbour_email' ),
		__( 'Address', 'harbour-core' )     => $g( '_harbour_address' ),
		__( 'Access', 'harbour-core' )      => $g( '_harbour_access' ) ? $g( '_harbour_access' ) : '—',
	);
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $rows as $label => $value ) {
		echo '<tr><th style="width:130px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( (string) $value ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
	echo '<p><label for="harbour_order_status"><strong>' . esc_html__( 'Status', 'harbour-core' ) . '</strong></label><br><select id="harbour_order_status" name="harbour_order_status">';
	foreach ( HARBOUR_ORDER_STATUSES as $slug => $label ) {
		echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $g( '_harbour_status' ) ? $g( '_harbour_status' ) : 'new', $slug, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></p>';
	echo '<p><label for="harbour_order_date"><strong>' . esc_html__( 'Delivery date', 'harbour-core' ) . '</strong></label><br><input type="date" id="harbour_order_date" name="harbour_order_date" value="' . esc_attr( $g( '_harbour_delivery_date' ) ) . '"></p>';
	echo '<p><label for="harbour_order_notes"><strong>' . esc_html__( 'Notes', 'harbour-core' ) . '</strong></label><br><textarea id="harbour_order_notes" name="harbour_order_notes" rows="3" class="large-text">' . esc_textarea( $g( '_harbour_notes' ) ) . '</textarea></p>';
}

add_action(
	'save_post_log_order',
	function ( $post_id ) {
		if ( ! isset( $_POST['harbour_order_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_order_meta_nonce'] ) ), 'harbour_order_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( isset( $_POST['harbour_order_status'] ) ) {
			$st = sanitize_key( wp_unslash( $_POST['harbour_order_status'] ) );
			if ( isset( HARBOUR_ORDER_STATUSES[ $st ] ) ) {
				update_post_meta( $post_id, '_harbour_status', $st );
			}
		}
		if ( isset( $_POST['harbour_order_date'] ) ) {
			update_post_meta( $post_id, '_harbour_delivery_date', sanitize_text_field( wp_unslash( $_POST['harbour_order_date'] ) ) );
		}
		if ( isset( $_POST['harbour_order_notes'] ) ) {
			update_post_meta( $post_id, '_harbour_notes', sanitize_textarea_field( wp_unslash( $_POST['harbour_order_notes'] ) ) );
		}
	}
);

add_action(
	'manage_posts_extra_tablenav',
	function ( $which ) {
		$screen = get_current_screen();
		if ( 'top' !== $which || ! $screen || 'log_order' !== $screen->post_type ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=harbour_export_orders' ), 'harbour_export_orders' );
		echo '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Export CSV', 'harbour-core' ) . '</a>';
	}
);

add_action(
	'admin_post_harbour_export_orders',
	function () {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'harbour-core' ) );
		}
		check_admin_referer( 'harbour_export_orders' );
		$rows = get_posts(
			array(
				'post_type'      => 'log_order',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			)
		);
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=harbour-log-orders-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Date', 'Name', 'Phone', 'Email', 'Product', 'Qty', 'Total', 'Postcode', 'Distance', 'Band', 'Window', 'Status', 'Delivery date', 'Address', 'Access', 'Notes' ) );
		foreach ( $rows as $r ) {
			$m = static function ( $k ) use ( $r ) {
				return get_post_meta( $r->ID, $k, true );
			};
			fputcsv(
				$out,
				array(
					get_the_date( 'Y-m-d H:i', $r ),
					$m( '_harbour_name' ),
					$m( '_harbour_phone' ),
					$m( '_harbour_email' ),
					$m( '_harbour_product' ),
					$m( '_harbour_qty' ),
					$m( '_harbour_total' ),
					$m( '_harbour_postcode' ),
					$m( '_harbour_distance' ),
					$m( '_harbour_delivery_band' ),
					$m( '_harbour_slot' ),
					$m( '_harbour_status' ),
					$m( '_harbour_delivery_date' ),
					$m( '_harbour_address' ),
					$m( '_harbour_access' ),
					$m( '_harbour_notes' ),
				)
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- writing to the php://output stream.
		exit;
	}
);
