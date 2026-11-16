<?php
/**
 * Module — Mailing-list sign-up band.
 *
 * A compact "Join our mailing list" band the theme renders above the footer on
 * every page (harbour_render_signup_band()). It collects an email address and
 * subscribes it to the Postbag list "Tree care tips & news" via the shared
 * harbour_subscribe_marketing() path, which — connected to Corantr — sends the
 * double opt-in confirmation. The visitor's act of entering their address into a
 * clearly-labelled sign-up band, plus that confirmation email, is the PECR
 * affirmative opt-in; there is no pre-ticked box.
 *
 * Self-posting like the enquiry form: processed on template_redirect. A valid
 * submission redirects back to the originating page with ?harbour_subscribed=1
 * (Post/Redirect/Get, so a refresh does not re-submit) and the band shows a
 * "check your inbox" confirmation. A validation error re-renders the page with
 * the message inline on the band.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Should the sign-up band be shown on the current request?
 *
 * Shown on every front-end page (to all visitors, logged in or out) except the
 * search-results and 404 templates, which already present a prominent search
 * form as their main content — stacking the band under it would give two
 * competing boxes above the footer. Filterable so individual templates can
 * opt in or out.
 *
 * @return bool
 */
function harbour_show_signup_band(): bool {
	$show = ! ( is_search() || is_404() );
	return (bool) apply_filters( 'harbour_show_signup_band', $show );
}

/**
 * Render the mailing-list band. Called by the theme footer.
 *
 * Safe to call unconditionally: returns without output when the band is
 * filtered off for this request.
 */
function harbour_render_signup_band(): void {
	if ( ! harbour_show_signup_band() ) {
		return;
	}

	$done   = isset( $_GET['harbour_subscribed'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag, no action taken.
	$result = $GLOBALS['harbour_signup_result'] ?? array();
	$error  = $result['error'] ?? '';
	$email  = $result['email'] ?? '';
	?>
	<section class="signup-band" id="mailing-list" aria-labelledby="signup-band-title">
		<div class="wrap">
			<div class="signup-band-inner">
				<div class="signup-band-copy">
					<h2 id="signup-band-title"><?php esc_html_e( 'Join our mailing list', 'harbour-core' ); ?></h2>
					<p><?php esc_html_e( 'Seasonal tree-care tips and the occasional offer from Harbour Tree Care, straight to your inbox. No spam, and you can unsubscribe any time.', 'harbour-core' ); ?></p>
				</div>

				<?php if ( $done ) : ?>
					<div class="signup-band-done" role="status">
						<strong><?php esc_html_e( 'Almost there — please check your inbox.', 'harbour-core' ); ?></strong>
						<span><?php esc_html_e( 'We\'ve sent you an email to confirm your subscription. Click the link in it and you\'re on the list.', 'harbour-core' ); ?></span>
					</div>
				<?php else : ?>
					<form class="signup-band-form" method="post" action="#mailing-list" novalidate>
						<?php if ( '' !== $error ) : ?>
							<p class="signup-band-error" role="alert"><?php echo esc_html( $error ); ?></p>
						<?php endif; ?>
						<div class="signup-field">
							<label class="sr-only" for="signup-email"><?php esc_html_e( 'Your email address', 'harbour-core' ); ?></label>
							<input
								id="signup-email"
								name="harbour_signup_email"
								type="email"
								inputmode="email"
								autocomplete="email"
								required
								placeholder="<?php esc_attr_e( 'you@example.com', 'harbour-core' ); ?>"
								value="<?php echo esc_attr( $email ); ?>"
								<?php echo '' !== $error ? 'aria-describedby="signup-band-error" aria-invalid="true"' : ''; ?>>
							<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Subscribe', 'harbour-core' ); ?></button>
						</div>
						<p class="signup-band-note">
							<?php
							printf(
								/* translators: %s: link to the privacy policy. */
								esc_html__( 'We\'ll only email you tree-care tips and offers. See our %s.', 'harbour-core' ),
								'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'privacy policy', 'harbour-core' ) . '</a>'
							);
							?>
						</p>
						<?php harbour_honeypot_fields(); ?>
						<input type="hidden" name="harbour_action" value="signup">
						<?php wp_nonce_field( 'harbour_signup', 'harbour_signup_nonce' ); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/* -------------------------------------------------------------------------
 * Submission handling
 * ---------------------------------------------------------------------- */

/**
 * Intercept a self-posted mailing-list sign-up before the page renders.
 */
function harbour_maybe_process_signup(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	if ( 'signup' !== ( $_POST['harbour_action'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- routing only; nonce verified in processor.
		return;
	}
	$result = harbour_process_signup();
	if ( ! empty( $result['ok'] ) ) {
		$back = wp_get_referer();
		if ( ! $back ) {
			$back = home_url( '/' );
		}
		// Strip any stale flag, then add our own and anchor to the band.
		$back = remove_query_arg( 'harbour_subscribed', $back );
		wp_safe_redirect( add_query_arg( 'harbour_subscribed', '1', $back ) . '#mailing-list' );
		exit;
	}
	$GLOBALS['harbour_signup_result'] = $result;
}
add_action( 'template_redirect', 'harbour_maybe_process_signup' );

/**
 * Validate and subscribe. Returns ['ok'=>bool, 'error'=>string, 'email'=>string].
 *
 * @return array
 */
function harbour_process_signup(): array {
	$fail = static function ( string $error, string $email ): array {
		return array(
			'ok'    => false,
			'error' => $error,
			'email' => $email,
		);
	};

	// Verify nonce before touching any submitted data.
	if ( ! isset( $_POST['harbour_signup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['harbour_signup_nonce'] ) ), 'harbour_signup' ) ) {
		return $fail( __( 'Your session expired — please try again.', 'harbour-core' ), '' );
	}

	$email = sanitize_email( wp_unslash( $_POST['harbour_signup_email'] ?? '' ) );

	// Honeypot + timing. Silently absorbed as a success so bots learn nothing.
	if ( ! harbour_passes_honeypot( wp_unslash( $_POST ) ) ) {
		return array( 'ok' => true );
	}

	// Rate limit.
	if ( ! harbour_rate_ok( 'signup' ) ) {
		return $fail( __( "You've tried a few times just now — please wait a moment and try again.", 'harbour-core' ), $email );
	}

	// Validation.
	if ( '' === $email || ! is_email( $email ) ) {
		return $fail( __( 'Please enter a valid email address.', 'harbour-core' ), $email );
	}

	// Collapse duplicate submissions of the same address within a short window
	// (double taps, browser POST retries, CDN replays) — treated as a success so
	// the visitor still sees the confirmation, without a second subscribe call.
	if ( ! harbour_is_fresh_submission( 'signup', $email ) ) {
		return array( 'ok' => true );
	}

	harbour_subscribe_marketing( $email, '', 'harbour-signup-band', 'harbour-signup-band-v1' );

	return array( 'ok' => true );
}
