<?php
/**
 * Module 1 — Quote enquiries. The site's primary conversion path.
 *
 * Self-posting form: processed on template_redirect before the page renders, so
 * validation errors appear inline (accessible error summary, focus moved to it),
 * and a successful submit redirects to /thank-you/. The enquiry record is always
 * created before mail is attempted, so a mail failure never loses the lead.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Validation helpers (pure — unit tested)
 * ---------------------------------------------------------------------- */

/* -------------------------------------------------------------------------
 * Front-end form
 * ---------------------------------------------------------------------- */

/**
 * Render the enquiry form. Called by the theme (replaces the placeholder part).
 *
 * @param array $ctx Optional context: 'result' with errors/old input.
 */
function harbour_render_enquiry_form( array $ctx = array() ): void {
	$result = $ctx['result'] ?? ( $GLOBALS['harbour_enquiry_result'] ?? array() );
	$errors = $result['errors'] ?? array();
	$old    = $result['old'] ?? array();

	$val    = static function ( string $key ) use ( $old ): string {
		return isset( $old[ $key ] ) ? esc_attr( $old[ $key ] ) : '';
	};
	$err_id = static function ( string $key ) use ( $errors ): string {
		return isset( $errors[ $key ] ) ? 'err-' . $key : '';
	};

	$services = array(
		'Pruning / crown reduction',
		'Felling / tree removal',
		'Stump grinding',
		'Site clearance',
		'Survey or report',
		'Firewood',
		'Storm damage — urgent',
		'Not sure — please advise',
	);
	?>
	<form class="form-card" method="post" action="" enctype="multipart/form-data" novalidate>
		<?php if ( $errors ) : ?>
			<div class="form-errors" role="alert" tabindex="-1" id="form-errors">
				<strong><?php echo esc_html( _n( 'Please fix this before sending:', 'Please fix these before sending:', count( $errors ), 'harbour-core' ) ); ?></strong>
				<ul>
					<?php foreach ( $errors as $field => $msg ) : ?>
						<li><a href="#<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $msg ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="field-row">
			<div class="field">
				<label for="name"><?php esc_html_e( 'Your name', 'harbour-core' ); ?></label>
				<input id="name" name="harbour_name" type="text" autocomplete="name" value="<?php echo esc_attr( $val( 'name' ) ); ?>" required <?php echo $err_id( 'name' ) ? 'aria-describedby="err-name" aria-invalid="true"' : ''; ?>>
				<?php
				if ( isset( $errors['name'] ) ) :
					?>
					<span class="field-error" id="err-name"><?php echo esc_html( $errors['name'] ); ?></span><?php endif; ?>
			</div>
			<div class="field">
				<label for="phone"><?php esc_html_e( 'Phone', 'harbour-core' ); ?></label>
				<input id="phone" name="harbour_phone" type="tel" autocomplete="tel" value="<?php echo esc_attr( $val( 'phone' ) ); ?>" required <?php echo $err_id( 'phone' ) ? 'aria-describedby="err-phone" aria-invalid="true"' : ''; ?>>
				<?php
				if ( isset( $errors['phone'] ) ) :
					?>
					<span class="field-error" id="err-phone"><?php echo esc_html( $errors['phone'] ); ?></span><?php endif; ?>
			</div>
		</div>
		<div class="field-row">
			<div class="field">
				<label for="email"><?php esc_html_e( 'Email', 'harbour-core' ); ?></label>
				<input id="email" name="harbour_email" type="email" autocomplete="email" value="<?php echo esc_attr( $val( 'email' ) ); ?>" required <?php echo $err_id( 'email' ) ? 'aria-describedby="err-email" aria-invalid="true"' : ''; ?>>
				<?php
				if ( isset( $errors['email'] ) ) :
					?>
					<span class="field-error" id="err-email"><?php echo esc_html( $errors['email'] ); ?></span><?php endif; ?>
			</div>
			<div class="field">
				<label for="postcode"><?php esc_html_e( 'Postcode', 'harbour-core' ); ?> <span class="hint"><?php esc_html_e( 'so we know the travel', 'harbour-core' ); ?></span></label>
				<input id="postcode" name="harbour_postcode" type="text" autocomplete="postal-code" value="<?php echo esc_attr( $val( 'postcode' ) ); ?>" required <?php echo $err_id( 'postcode' ) ? 'aria-describedby="err-postcode" aria-invalid="true"' : ''; ?>>
				<?php
				if ( isset( $errors['postcode'] ) ) :
					?>
					<span class="field-error" id="err-postcode"><?php echo esc_html( $errors['postcode'] ); ?></span><?php endif; ?>
			</div>
		</div>
		<div class="field">
			<label for="service"><?php esc_html_e( 'What do you need?', 'harbour-core' ); ?></label>
			<select id="service" name="harbour_service" required <?php echo $err_id( 'service' ) ? 'aria-describedby="err-service" aria-invalid="true"' : ''; ?>>
				<option value=""><?php esc_html_e( 'Choose one…', 'harbour-core' ); ?></option>
				<?php foreach ( $services as $svc ) : ?>
					<option <?php selected( $old['service'] ?? '', $svc ); ?>><?php echo esc_html( $svc ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
			if ( isset( $errors['service'] ) ) :
				?>
				<span class="field-error" id="err-service"><?php echo esc_html( $errors['service'] ); ?></span><?php endif; ?>
		</div>
		<div class="field">
			<label for="msg"><?php esc_html_e( 'Anything else we should know?', 'harbour-core' ); ?></label>
			<textarea id="msg" name="harbour_message" placeholder="<?php esc_attr_e( 'Roughly how big, how close to the house, is there side access…', 'harbour-core' ); ?>"><?php echo esc_textarea( $old['message'] ?? '' ); ?></textarea>
		</div>
		<div class="field">
			<label for="photos"><?php esc_html_e( 'Photos', 'harbour-core' ); ?> <span class="hint"><?php esc_html_e( 'optional, JPG/PNG/WebP, up to 5', 'harbour-core' ); ?></span></label>
			<div class="dropzone"><b><?php esc_html_e( 'Add photos', 'harbour-core' ); ?></b> — <?php esc_html_e( 'drag them here or tap to browse', 'harbour-core' ); ?><input id="photos" name="harbour_photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></div>
			<?php
			if ( isset( $errors['photos'] ) ) :
				?>
				<span class="field-error"><?php echo esc_html( $errors['photos'] ); ?></span><?php endif; ?>
		</div>
		<label class="consent" style="margin-bottom:var(--s-5)">
			<input type="checkbox" name="harbour_consent" value="1" required <?php checked( ! empty( $old['consent'] ) ); ?>>
			<span><?php esc_html_e( "I'm happy for Harbour Tree Care to contact me about this enquiry. We don't share details with anyone else.", 'harbour-core' ); ?></span>
		</label>
		<?php
		if ( isset( $errors['consent'] ) ) :
			?>
			<span class="field-error" id="err-consent"><?php echo esc_html( $errors['consent'] ); ?></span><?php endif; ?>

		<?php harbour_honeypot_fields(); ?>
		<input type="hidden" name="harbour_action" value="enquiry">
		<?php wp_nonce_field( 'harbour_enquiry', 'harbour_nonce' ); ?>
		<button class="btn btn-primary btn-lg" type="submit" style="width:100%"><?php esc_html_e( 'Send my enquiry', 'harbour-core' ); ?></button>
		<p class="small muted center" style="margin:var(--s-4) 0 0"><?php esc_html_e( 'We usually reply the same working day.', 'harbour-core' ); ?></p>
	</form>
	<?php
}

/* -------------------------------------------------------------------------
 * Submission handling
 * ---------------------------------------------------------------------- */

/**
 * Intercept a self-posted enquiry before the page renders.
 */
function harbour_maybe_process_enquiry(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	if ( 'enquiry' !== ( $_POST['harbour_action'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- routing only; nonce verified in processor.
		return;
	}
	$result = harbour_process_enquiry();
	if ( ! empty( $result['ok'] ) ) {
		wp_safe_redirect( home_url( '/thank-you/' ) );
		exit;
	}
	$GLOBALS['harbour_enquiry_result'] = $result;
}
add_action( 'template_redirect', 'harbour_maybe_process_enquiry' );

/**
 * Validate, store and notify. Returns ['ok'=>bool, 'errors'=>[], 'old'=>[]].
 *
 * @return array
 */
function harbour_process_enquiry(): array {
	$fail = static function ( array $errors, array $old ): array {
		return array(
			'ok'     => false,
			'errors' => $errors,
			'old'    => $old,
		);
	};

	// Verify nonce before touching any submitted data.
	if ( ! isset( $_POST['harbour_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_nonce'] ) ), 'harbour_enquiry' ) ) {
		return $fail( array( 'name' => __( 'Your session expired — please try again.', 'harbour-core' ) ), array() );
	}

	$old = array(
		'name'     => sanitize_text_field( wp_unslash( $_POST['harbour_name'] ?? '' ) ),
		'phone'    => sanitize_text_field( wp_unslash( $_POST['harbour_phone'] ?? '' ) ),
		'email'    => sanitize_email( wp_unslash( $_POST['harbour_email'] ?? '' ) ),
		'postcode' => sanitize_text_field( wp_unslash( $_POST['harbour_postcode'] ?? '' ) ),
		'service'  => sanitize_text_field( wp_unslash( $_POST['harbour_service'] ?? '' ) ),
		'message'  => sanitize_textarea_field( wp_unslash( $_POST['harbour_message'] ?? '' ) ),
		'consent'  => ! empty( $_POST['harbour_consent'] ),
	);

	// Honeypot + timing.
	if ( ! harbour_passes_honeypot( wp_unslash( $_POST ) ) ) {
		return $fail( array( 'name' => __( 'Something looked off with that submission. Please try again.', 'harbour-core' ) ), $old );
	}
	// Rate limit.
	if ( ! harbour_rate_ok( 'enquiry' ) ) {
		return $fail( array( 'name' => __( "You've sent several enquiries in a short time. Please ring us on the number above instead.", 'harbour-core' ) ), $old );
	}

	// Field validation.
	$errors = array();
	if ( '' === $old['name'] ) {
		$errors['name'] = __( 'Please tell us your name.', 'harbour-core' );
	}
	if ( '' === $old['phone'] ) {
		$errors['phone'] = __( 'Please give us a phone number.', 'harbour-core' );
	}
	if ( '' === $old['email'] || ! is_email( $old['email'] ) ) {
		$errors['email'] = __( 'Please enter a valid email address.', 'harbour-core' );
	}
	if ( '' === $old['postcode'] || ! harbour_is_valid_uk_postcode( $old['postcode'] ) ) {
		$errors['postcode'] = __( 'Please enter a valid UK postcode.', 'harbour-core' );
	}
	if ( '' === $old['service'] ) {
		$errors['service'] = __( 'Please choose what you need.', 'harbour-core' );
	}
	if ( ! $old['consent'] ) {
		$errors['consent'] = __( 'Please tick the box so we can reply.', 'harbour-core' );
	}

	// Uploads (validated even if fields failed, to surface all errors at once).
	$upload = harbour_handle_enquiry_uploads();
	if ( ! empty( $upload['error'] ) ) {
		$errors['photos'] = $upload['error'];
	}

	if ( $errors ) {
		return $fail( $errors, $old );
	}

	// Create the record first — it must survive a mail failure.
	$town    = harbour_normalize_postcode( $old['postcode'] );
	$title   = sprintf( '%s — %s — %s', $old['name'], $town, wp_date( 'j M Y' ) );
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'enquiry',
			'post_status' => 'publish',
			'post_title'  => $title,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $fail( array( 'name' => __( 'Sorry — we could not save that. Please ring the yard.', 'harbour-core' ) ), $old );
	}

	update_post_meta( $post_id, '_harbour_name', $old['name'] );
	update_post_meta( $post_id, '_harbour_phone', $old['phone'] );
	update_post_meta( $post_id, '_harbour_email', $old['email'] );
	update_post_meta( $post_id, '_harbour_postcode', harbour_normalize_postcode( $old['postcode'] ) );
	update_post_meta( $post_id, '_harbour_service', $old['service'] );
	update_post_meta( $post_id, '_harbour_message', $old['message'] );
	update_post_meta( $post_id, '_harbour_status', 'new' );
	update_post_meta( $post_id, '_harbour_source', 'website' );
	// Consent record.
	update_post_meta( $post_id, '_harbour_consent_time', current_time( 'mysql' ) );
	update_post_meta( $post_id, '_harbour_consent_ip', harbour_client_ip() );
	update_post_meta( $post_id, '_harbour_consent_wording', "I'm happy for Harbour Tree Care to contact me about this enquiry. We don't share details with anyone else." );

	// Attach uploaded photos.
	foreach ( $upload['attachment_ids'] as $att_id ) {
		wp_update_post(
			array(
				'ID'          => $att_id,
				'post_parent' => $post_id,
			)
		);
		update_post_meta( $att_id, '_harbour_enquiry_photo', 1 );
	}

	harbour_notify_enquiry( $post_id, $old, $upload['attachment_ids'] );

	return array( 'ok' => true );
}

/**
 * Handle photo uploads: whitelist by content, cap count/size, re-encode to
 * strip EXIF (GPS), and create attachments.
 *
 * @return array ['attachment_ids'=>int[], 'error'=>string]
 */
function harbour_handle_enquiry_uploads(): array {
	$out = array(
		'attachment_ids' => array(),
		'error'          => '',
	);

	// Nonce is verified in harbour_process_enquiry() before this is called.
	if ( empty( $_FILES['harbour_photos'] ) || empty( $_FILES['harbour_photos']['name'][0] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $out;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$files   = $_FILES['harbour_photos']; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by caller.
	$count   = count( array_filter( (array) $files['name'] ) );
	$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
	$max     = 10 * MB_IN_BYTES;

	if ( $count > 5 ) {
		$out['error'] = __( 'Please attach no more than 5 photos.', 'harbour-core' );
		return $out;
	}

	for ( $i = 0; $i < $count; $i++ ) {
		if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] ) {
			continue;
		}
		if ( (int) $files['size'][ $i ] > $max ) {
			$out['error'] = __( 'Each photo must be 10MB or smaller.', 'harbour-core' );
			return $out;
		}
		// Verify MIME by content, not extension.
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime  = finfo_file( $finfo, $files['tmp_name'][ $i ] );
		// finfo is an object since PHP 8.1 (freed by GC); finfo_close() is deprecated in 8.5.
		if ( ! in_array( $mime, $allowed, true ) ) {
			$out['error'] = __( 'Photos must be JPG, PNG or WebP. (iPhone HEIC photos are not supported — set the camera to “Most Compatible”, or send a screenshot.)', 'harbour-core' );
			return $out;
		}

		$single                   = array(
			'name'     => $files['name'][ $i ],
			'type'     => $mime,
			'tmp_name' => $files['tmp_name'][ $i ],
			'error'    => $files['error'][ $i ],
			'size'     => $files['size'][ $i ],
		);
		$_FILES['harbour_single'] = $single;

		$att_id = media_handle_upload(
			'harbour_single',
			0,
			array(),
			array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg' => 'image/jpeg',
					'png'      => 'image/png',
					'webp'     => 'image/webp',
				),
			)
		);
		unset( $_FILES['harbour_single'] );

		if ( is_wp_error( $att_id ) ) {
			$out['error'] = __( 'One of the photos could not be processed. Please try a different file.', 'harbour-core' );
			return $out;
		}

		harbour_strip_exif( $att_id );
		$out['attachment_ids'][] = $att_id;
	}

	return $out;
}

/**
 * Re-encode an image attachment in place to drop EXIF/GPS metadata.
 *
 * @param int $attachment_id Attachment ID.
 */
function harbour_strip_exif( int $attachment_id ): void {
	$path = get_attached_file( $attachment_id );
	if ( ! $path || ! file_exists( $path ) ) {
		return;
	}
	$editor = wp_get_image_editor( $path );
	if ( is_wp_error( $editor ) ) {
		return;
	}
	// Saving via the editor re-encodes and does not carry EXIF across.
	$saved = $editor->save( $path );
	if ( ! is_wp_error( $saved ) ) {
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $path ) );
	}
}

/**
 * Email the yard and acknowledge the customer.
 *
 * @param int   $post_id Enquiry ID.
 * @param array $data    Sanitised fields.
 * @param int[] $atts    Attachment IDs.
 */
function harbour_notify_enquiry( int $post_id, array $data, array $atts ): void {
	$recipients = harbour_setting( 'enquiries', 'notify_emails', harbour_setting( 'business', 'email', get_option( 'admin_email' ) ) );
	$to         = array_filter( array_map( 'trim', explode( ',', (string) $recipients ) ) );
	if ( empty( $to ) ) {
		$to = array( get_option( 'admin_email' ) );
	}

	$tel_href = 'tel:' . preg_replace( '/\s+/', '', $data['phone'] );
	$edit     = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	$rows     = array(
		__( 'Name', 'harbour-core' )     => esc_html( $data['name'] ),
		__( 'Phone', 'harbour-core' )    => '<a href="' . esc_url( $tel_href ) . '">' . esc_html( $data['phone'] ) . '</a>',
		__( 'Email', 'harbour-core' )    => '<a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a>',
		__( 'Postcode', 'harbour-core' ) => esc_html( $data['postcode'] ),
		__( 'Service', 'harbour-core' )  => esc_html( $data['service'] ),
		__( 'Message', 'harbour-core' )  => nl2br( esc_html( $data['message'] ) ),
	);
	$body     = '<p><strong>' . esc_html__( 'New quote enquiry from the website.', 'harbour-core' ) . '</strong></p><table cellpadding="6" style="border-collapse:collapse">';
	foreach ( $rows as $label => $value ) {
		$body .= '<tr><td style="border:1px solid #DEE4EC;font-weight:bold">' . esc_html( $label ) . '</td><td style="border:1px solid #DEE4EC">' . $value . '</td></tr>';
	}
	$body .= '</table>';

	// Embed the uploaded photos inline, so whoever quotes has everything in the
	// email itself and never needs to log in. We embed the resized 'large'
	// version to keep the message a sensible size.
	$inline = array();
	foreach ( $atts as $i => $att_id ) {
		$path = harbour_email_image_path( (int) $att_id );
		if ( $path ) {
			$cid      = 'harbourphoto' . $i;
			$inline[] = array(
				'path' => $path,
				'cid'  => $cid,
				'name' => basename( $path ),
			);
		}
	}
	if ( $inline ) {
		$body .= '<p style="font-weight:bold;margin-top:20px">' . esc_html( sprintf( /* translators: %d: number of photos. */ _n( '%d photo attached:', '%d photos attached:', count( $inline ), 'harbour-core' ), count( $inline ) ) ) . '</p>';
		foreach ( $inline as $img ) {
			$body .= '<div style="margin:0 0 12px"><img src="cid:' . esc_attr( $img['cid'] ) . '" alt="" style="max-width:520px;width:100%;height:auto;border:1px solid #DEE4EC;border-radius:6px"></div>';
		}
	}

	$body .= '<p style="margin-top:16px"><a href="' . esc_url( $edit ) . '">' . esc_html__( 'Open this enquiry in admin (optional)', 'harbour-core' ) . '</a></p>';

	// Make the images available to phpmailer_init for inline embedding, scoped
	// to this one send.
	$GLOBALS['harbour_inline_images'] = $inline;
	add_action( 'phpmailer_init', 'harbour_embed_inline_images' );

	$reply = array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' );
	$sent  = harbour_mail( $to, __( 'New enquiry — ', 'harbour-core' ) . $data['postcode'], $body, $reply );

	// Clear so later mails (e.g. the customer acknowledgement) don't embed them.
	$GLOBALS['harbour_inline_images'] = array();

	if ( ! $sent ) {
		update_post_meta( $post_id, '_harbour_mail_failed', current_time( 'mysql' ) );
		error_log( 'Harbour: enquiry notification email failed for #' . $post_id ); // phpcs:ignore
	}

	// Customer acknowledgement.
	$ack_subject = harbour_setting( 'enquiries', 'ack_subject', __( 'Thanks — we have your enquiry', 'harbour-core' ) );
	$ack_body    = harbour_setting( 'enquiries', 'ack_body', __( "Thanks for getting in touch. We've received your enquiry and will usually reply the same working day. If it's urgent, please call the yard.", 'harbour-core' ) );
	harbour_mail( $data['email'], $ack_subject, '<p>' . nl2br( esc_html( $ack_body ) ) . '</p>' );
}

/**
 * Best file path to embed in an email for an image attachment: the resized
 * 'large' version if it exists (keeps the email small), else the full file.
 *
 * @param int $att_id Attachment ID.
 * @return string Absolute path, or '' if unavailable.
 */
function harbour_email_image_path( int $att_id ): string {
	$full = get_attached_file( $att_id );
	if ( ! $full || ! file_exists( $full ) ) {
		return '';
	}
	$meta = wp_get_attachment_metadata( $att_id );
	if ( is_array( $meta ) && ! empty( $meta['sizes']['large']['file'] ) ) {
		$large = trailingslashit( dirname( $full ) ) . $meta['sizes']['large']['file'];
		if ( file_exists( $large ) ) {
			return $large;
		}
	}
	return $full;
}

/**
 * Embed the queued images into the outgoing message as inline (CID) parts.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer instance (by reference).
 */
function harbour_embed_inline_images( $phpmailer ): void {
	$images = $GLOBALS['harbour_inline_images'] ?? array();
	if ( empty( $images ) || ! is_array( $images ) ) {
		return;
	}
	foreach ( $images as $img ) {
		if ( ! empty( $img['path'] ) && file_exists( $img['path'] ) ) {
			try {
				$phpmailer->addEmbeddedImage( $img['path'], $img['cid'], (string) $img['name'] );
			} catch ( \Exception $e ) {
				continue;
			}
		}
	}
}
