<?php
/**
 * Module 2 — Firewood log ordering. Pay on delivery: no card handling, no PCI
 * scope. Orders are requests, not confirmed bookings, until the yard replies.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Firewood products from settings.
 *
 * @return array<int,array>
 */
function harbour_firewood_products(): array {
	$p = get_option( HARBOUR_OPTION, array() )['firewood']['products'] ?? array();
	return is_array( $p ) ? $p : array();
}

/**
 * Named delivery windows from settings.
 *
 * @return array<int,string>
 */
function harbour_delivery_slots(): array {
	$s = get_option( HARBOUR_OPTION, array() )['firewood']['slots'] ?? array();
	return is_array( $s ) ? $s : array();
}

/**
 * Render the log-order form. Called by the /order-logs/ page.
 */
function harbour_render_log_order_form(): void {
	$products = harbour_firewood_products();
	$slots    = harbour_delivery_slots();
	$result   = $GLOBALS['harbour_log_order_result'] ?? array();
	$errors   = $result['errors'] ?? array();
	$old      = $result['old'] ?? array();

	if ( ! $products ) {
		echo '<div class="form-card"><p>' . esc_html__( 'Firewood ordering will be available here shortly. In the meantime, please ring the yard to order logs.', 'harbour-core' ) . '</p><p><a class="btn btn-primary" href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact us', 'harbour-core' ) . '</a></p></div>';
		return;
	}

	$val = static function ( $k ) use ( $old ) {
		return isset( $old[ $k ] ) ? esc_attr( $old[ $k ] ) : '';
	};
	?>
	<form class="form-card" method="post" action="" novalidate>
		<?php if ( $errors ) : ?>
			<div class="form-errors" role="alert" tabindex="-1">
				<strong><?php esc_html_e( 'Please check the following:', 'harbour-core' ); ?></strong>
				<ul><?php foreach ( $errors as $f => $m ) : ?><li><a href="#lo-<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $m ); ?></a></li><?php endforeach; ?></ul>
			</div>
		<?php endif; ?>

		<div class="field">
			<label for="lo-product"><?php esc_html_e( 'Which logs?', 'harbour-core' ); ?></label>
			<select id="lo-product" name="harbour_product" required>
				<option value=""><?php esc_html_e( 'Choose a product…', 'harbour-core' ); ?></option>
				<?php foreach ( $products as $i => $p ) :
					$out = 'out' === ( $p['availability'] ?? 'in' );
					$label = $p['name'];
					if ( ! empty( $p['price'] ) ) { $label .= ' — ' . $p['price']; }
					if ( 'low' === ( $p['availability'] ?? '' ) ) { $label .= ' (low stock)'; }
					if ( $out ) { $label .= ' (out of stock)'; }
					?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php disabled( $out ); ?> <?php selected( (string) ( $old['product'] ?? '' ), (string) $i ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field-row">
			<div class="field">
				<label for="lo-qty"><?php esc_html_e( 'Quantity', 'harbour-core' ); ?></label>
				<input id="lo-qty" name="harbour_qty" type="number" min="1" value="<?php echo $val( 'qty' ) ?: '1'; ?>" required>
			</div>
			<div class="field">
				<label for="lo-postcode"><?php esc_html_e( 'Delivery postcode', 'harbour-core' ); ?></label>
				<input id="lo-postcode" name="harbour_postcode" type="text" autocomplete="postal-code" value="<?php echo $val( 'postcode' ); ?>" required>
			</div>
		</div>
		<?php if ( $slots ) : ?>
			<div class="field">
				<label for="lo-slot"><?php esc_html_e( 'Preferred delivery window', 'harbour-core' ); ?></label>
				<select id="lo-slot" name="harbour_slot">
					<option value=""><?php esc_html_e( 'No preference', 'harbour-core' ); ?></option>
					<?php foreach ( $slots as $slot ) : ?>
						<option <?php selected( $old['slot'] ?? '', $slot ); ?>><?php echo esc_html( $slot ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
		<div class="field-row">
			<div class="field">
				<label for="lo-name"><?php esc_html_e( 'Your name', 'harbour-core' ); ?></label>
				<input id="lo-name" name="harbour_name" type="text" autocomplete="name" value="<?php echo $val( 'name' ); ?>" required>
			</div>
			<div class="field">
				<label for="lo-phone"><?php esc_html_e( 'Phone', 'harbour-core' ); ?></label>
				<input id="lo-phone" name="harbour_phone" type="tel" autocomplete="tel" value="<?php echo $val( 'phone' ); ?>" required>
			</div>
		</div>
		<div class="field">
			<label for="lo-email"><?php esc_html_e( 'Email', 'harbour-core' ); ?></label>
			<input id="lo-email" name="harbour_email" type="email" autocomplete="email" value="<?php echo $val( 'email' ); ?>" required>
		</div>
		<div class="field">
			<label for="lo-address"><?php esc_html_e( 'Delivery address', 'harbour-core' ); ?></label>
			<textarea id="lo-address" name="harbour_address" required><?php echo esc_textarea( $old['address'] ?? '' ); ?></textarea>
		</div>
		<div class="field">
			<label for="lo-access"><?php esc_html_e( 'Access notes', 'harbour-core' ); ?> <span class="hint"><?php esc_html_e( 'optional', 'harbour-core' ); ?></span></label>
			<textarea id="lo-access" name="harbour_access" placeholder="<?php esc_attr_e( 'Gate round the side, please don\'t tip on the drive…', 'harbour-core' ); ?>"><?php echo esc_textarea( $old['access'] ?? '' ); ?></textarea>
		</div>
		<div class="consent-note" style="background:var(--paper);border:1px solid var(--stone);border-radius:var(--radius);padding:1rem;margin-bottom:var(--s-4)">
			<p class="small mb-0"><strong><?php esc_html_e( 'This is a request, not a confirmed order.', 'harbour-core' ); ?></strong> <?php esc_html_e( "We'll confirm availability, the total and a delivery day by phone or email before anything is delivered. Payment is on delivery — cash or card to the driver.", 'harbour-core' ); ?></p>
		</div>
		<label class="consent" style="margin-bottom:var(--s-5)">
			<input type="checkbox" name="harbour_consent" value="1" required <?php checked( ! empty( $old['consent'] ) ); ?>>
			<span><?php esc_html_e( "I understand this is a delivery request and that Harbour Tree Care will confirm it before delivering. I'm happy to be contacted about it.", 'harbour-core' ); ?></span>
		</label>
		<?php harbour_honeypot_fields(); ?>
		<input type="hidden" name="harbour_action" value="log_order">
		<?php wp_nonce_field( 'harbour_log_order', 'harbour_nonce' ); ?>
		<button class="btn btn-primary btn-lg" type="submit" style="width:100%"><?php esc_html_e( 'Request this delivery', 'harbour-core' ); ?></button>
	</form>
	<?php
}

/**
 * Intercept a self-posted log order.
 */
function harbour_maybe_process_log_order(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || 'log_order' !== ( $_POST['harbour_action'] ?? '' ) ) {
		return;
	}
	$result = harbour_process_log_order();
	if ( ! empty( $result['ok'] ) ) {
		wp_safe_redirect( home_url( '/thank-you/' ) );
		exit;
	}
	$GLOBALS['harbour_log_order_result'] = $result;
}
add_action( 'template_redirect', 'harbour_maybe_process_log_order' );

/**
 * Validate, price, store and notify a log order.
 *
 * @return array
 */
function harbour_process_log_order(): array {
	$products = harbour_firewood_products();
	$old = array(
		'product'  => sanitize_text_field( wp_unslash( $_POST['harbour_product'] ?? '' ) ),
		'qty'      => absint( wp_unslash( $_POST['harbour_qty'] ?? 0 ) ),
		'postcode' => sanitize_text_field( wp_unslash( $_POST['harbour_postcode'] ?? '' ) ),
		'slot'     => sanitize_text_field( wp_unslash( $_POST['harbour_slot'] ?? '' ) ),
		'name'     => sanitize_text_field( wp_unslash( $_POST['harbour_name'] ?? '' ) ),
		'phone'    => sanitize_text_field( wp_unslash( $_POST['harbour_phone'] ?? '' ) ),
		'email'    => sanitize_email( wp_unslash( $_POST['harbour_email'] ?? '' ) ),
		'address'  => sanitize_textarea_field( wp_unslash( $_POST['harbour_address'] ?? '' ) ),
		'access'   => sanitize_textarea_field( wp_unslash( $_POST['harbour_access'] ?? '' ) ),
		'consent'  => ! empty( $_POST['harbour_consent'] ),
	);
	$fail = static function ( $errors ) use ( $old ) {
		return array( 'ok' => false, 'errors' => $errors, 'old' => $old );
	};

	if ( ! isset( $_POST['harbour_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_nonce'] ) ), 'harbour_log_order' ) ) {
		return $fail( array( 'product' => __( 'Your session expired — please try again.', 'harbour-core' ) ) );
	}
	if ( ! harbour_passes_honeypot( wp_unslash( $_POST ) ) ) {
		return $fail( array( 'product' => __( 'Something looked off with that submission. Please try again.', 'harbour-core' ) ) );
	}
	if ( ! harbour_rate_ok( 'log_order' ) ) {
		return $fail( array( 'product' => __( 'Too many requests in a short time — please ring the yard instead.', 'harbour-core' ) ) );
	}

	$errors = array();
	$product = ( '' !== $old['product'] && isset( $products[ (int) $old['product'] ] ) ) ? $products[ (int) $old['product'] ] : null;
	if ( ! $product ) {
		$errors['product'] = __( 'Please choose a product.', 'harbour-core' );
	}
	if ( '' === $old['name'] ) { $errors['name'] = __( 'Please tell us your name.', 'harbour-core' ); }
	if ( '' === $old['phone'] ) { $errors['phone'] = __( 'Please give a phone number.', 'harbour-core' ); }
	if ( ! is_email( $old['email'] ) ) { $errors['email'] = __( 'Please enter a valid email.', 'harbour-core' ); }
	if ( '' === $old['address'] ) { $errors['address'] = __( 'Please give the delivery address.', 'harbour-core' ); }
	if ( ! harbour_is_valid_uk_postcode( $old['postcode'] ) ) { $errors['postcode'] = __( 'Please enter a valid UK postcode.', 'harbour-core' ); }
	if ( ! $old['consent'] ) { $errors['consent'] = __( 'Please tick the box so we can process your request.', 'harbour-core' ); }

	// Quantity / minimum order.
	if ( $product ) {
		$totals = harbour_order_total( harbour_parse_price( $product['price'] ?? '' ), $old['qty'], (int) ( $product['min_order'] ?? 1 ) );
		if ( ! $totals['valid'] ) {
			$errors['qty'] = 'min_order' === $totals['reason']
				? sprintf( __( 'The minimum order for %1$s is %2$d.', 'harbour-core' ), $product['name'], (int) ( $product['min_order'] ?? 1 ) )
				: __( 'Please enter a quantity of at least 1.', 'harbour-core' );
		}
	}

	if ( $errors ) {
		return $fail( $errors );
	}

	// Delivery radius. Outside → decline (no order created). Otherwise proceed.
	$delivery = harbour_delivery_status( $old['postcode'] );
	if ( 'outside' === $delivery['status'] ) {
		return $fail( array( 'postcode' => $delivery['message'] ) );
	}

	$totals = harbour_order_total( harbour_parse_price( $product['price'] ?? '' ), $old['qty'], (int) ( $product['min_order'] ?? 1 ) );
	$title  = sprintf( '%s — %s×%d — %s', $old['name'], $product['name'], $old['qty'], harbour_normalize_postcode( $old['postcode'] ) );
	$post_id = wp_insert_post( array( 'post_type' => 'log_order', 'post_status' => 'publish', 'post_title' => $title ), true );
	if ( is_wp_error( $post_id ) ) {
		return $fail( array( 'product' => __( 'Sorry — we could not save that. Please ring the yard.', 'harbour-core' ) ) );
	}

	$meta = array(
		'_harbour_product'   => $product['name'],
		'_harbour_qty'       => $old['qty'],
		'_harbour_unit_price'=> $product['price'] ?? '',
		'_harbour_total'     => null === $totals['total'] ? '' : $totals['total'],
		'_harbour_postcode'  => harbour_normalize_postcode( $old['postcode'] ),
		'_harbour_distance'  => $delivery['distance'],
		'_harbour_delivery_band' => $delivery['status'],
		'_harbour_slot'      => $old['slot'],
		'_harbour_name'      => $old['name'],
		'_harbour_phone'     => $old['phone'],
		'_harbour_email'     => $old['email'],
		'_harbour_address'   => $old['address'],
		'_harbour_access'    => $old['access'],
		'_harbour_status'    => 'new',
		'_harbour_consent_time' => current_time( 'mysql' ),
		'_harbour_consent_ip'   => harbour_client_ip(),
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $post_id, $k, $v );
	}

	harbour_notify_log_order( $post_id, $old, $product, $totals, $delivery );

	return array( 'ok' => true );
}

/**
 * Email the yard and acknowledge the customer.
 */
function harbour_notify_log_order( int $post_id, array $data, array $product, array $totals, array $delivery ): void {
	$recipients = harbour_setting( 'enquiries', 'notify_emails', harbour_setting( 'business', 'email', get_option( 'admin_email' ) ) );
	$to = array_filter( array_map( 'trim', explode( ',', (string) $recipients ) ) ) ?: array( get_option( 'admin_email' ) );

	$total_str = null === $totals['total'] ? __( 'to confirm', 'harbour-core' ) : '£' . number_format( (float) $totals['total'], 2 );
	$rows = array(
		__( 'Product', 'harbour-core' )  => $product['name'],
		__( 'Quantity', 'harbour-core' ) => $data['qty'],
		__( 'Est. total', 'harbour-core' ) => $total_str,
		__( 'Postcode', 'harbour-core' ) => $data['postcode'],
		__( 'Distance', 'harbour-core' ) => null === $delivery['distance'] ? __( 'not checked', 'harbour-core' ) : $delivery['distance'] . ' mi (' . $delivery['status'] . ')',
		__( 'Window', 'harbour-core' )   => $data['slot'] ?: __( 'no preference', 'harbour-core' ),
		__( 'Name', 'harbour-core' )     => $data['name'],
		__( 'Phone', 'harbour-core' )    => $data['phone'],
		__( 'Email', 'harbour-core' )    => $data['email'],
		__( 'Address', 'harbour-core' )  => $data['address'],
		__( 'Access', 'harbour-core' )   => $data['access'],
	);
	$body = '<p><strong>' . esc_html__( 'New firewood delivery request.', 'harbour-core' ) . '</strong></p><table cellpadding="6" style="border-collapse:collapse">';
	foreach ( $rows as $label => $value ) {
		$body .= '<tr><td style="border:1px solid #DEE4EC;font-weight:bold">' . esc_html( $label ) . '</td><td style="border:1px solid #DEE4EC">' . nl2br( esc_html( (string) $value ) ) . '</td></tr>';
	}
	$body .= '</table><p><a href="' . esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) . '">' . esc_html__( 'Open this order', 'harbour-core' ) . '</a></p>';

	if ( ! harbour_mail( $to, __( 'Firewood request — ', 'harbour-core' ) . $data['postcode'], $body, array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' ) ) ) {
		update_post_meta( $post_id, '_harbour_mail_failed', current_time( 'mysql' ) );
	}

	$ack = '<p>' . esc_html__( "Thanks — we've received your firewood delivery request. This is a request, not a confirmed order: we'll be in touch to confirm availability, the total and a delivery day. Payment is on delivery.", 'harbour-core' ) . '</p>';
	harbour_mail( $data['email'], __( "We've got your firewood request", 'harbour-core' ), $ack );
}
