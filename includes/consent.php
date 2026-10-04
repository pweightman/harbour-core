<?php
/**
 * Cookie consent + Google Consent Mode v2.
 *
 * Analytics on this site is loaded by Site Kit (gtag.js). UK law (PECR / UK
 * GDPR) requires consent before analytics cookies are set, so we:
 *
 *   1. Set Consent Mode defaults to "denied" as early as possible in <head>,
 *      before Site Kit's gtag config runs, so GA4 starts in consent mode and
 *      stores nothing until the visitor agrees.
 *   2. Show a lightweight banner. On "Accept" we push a Consent Mode update to
 *      the same dataLayer gtag uses, so GA4 begins measuring; on "Reject" the
 *      defaults stand and nothing is stored.
 *
 * The choice is remembered in localStorage and re-applied on the next page
 * load before gtag runs. If the WP Consent API is present its signal is set
 * too, so any consent-aware plugin stays in sync.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the consent banner enabled? Defaults to on.
 *
 * @return bool
 */
function harbour_consent_enabled(): bool {
	return (bool) harbour_setting( 'privacy', 'consent_enabled', '1' );
}

/**
 * Consent categories granted when the visitor accepts.
 *
 * @return array<string,string>
 */
function harbour_consent_granted(): array {
	return array(
		'ad_storage'         => 'granted',
		'ad_user_data'       => 'granted',
		'ad_personalization' => 'granted',
		'analytics_storage'  => 'granted',
	);
}

/**
 * Print the Consent Mode defaults as early as possible in the head.
 */
function harbour_consent_defaults(): void {
	if ( ! harbour_consent_enabled() ) {
		return;
	}
	$defaults = array(
		'ad_storage'            => 'denied',
		'ad_user_data'          => 'denied',
		'ad_personalization'    => 'denied',
		'analytics_storage'     => 'denied',
		'functionality_storage' => 'granted',
		'security_storage'      => 'granted',
		'wait_for_update'       => 500,
	);
	$granted  = harbour_consent_granted();
	?>
<script<?php echo harbour_consent_nonce_attr(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string. ?>>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', <?php echo wp_json_encode( $defaults ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe in a script context. ?>);
try{if(window.localStorage&&localStorage.getItem('harbour_consent')==='granted'){gtag('consent','update',<?php echo wp_json_encode( $granted ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe in a script context. ?>);}}catch(e){}
</script>
	<?php
}
add_action( 'wp_head', 'harbour_consent_defaults', 0 );

/**
 * CSP nonce attribute for inline scripts, if a nonce is in play. Empty string
 * otherwise. Keeps the inline consent scripts compatible with a CSP.
 *
 * @return string
 */
function harbour_consent_nonce_attr(): string {
	$nonce = apply_filters( 'harbour_csp_nonce', '' );
	return $nonce ? ' nonce="' . esc_attr( $nonce ) . '"' : '';
}

/**
 * Print the banner markup, styles and behaviour in the footer.
 */
function harbour_consent_banner(): void {
	if ( ! harbour_consent_enabled() ) {
		return;
	}
	$privacy = get_privacy_policy_url();
	$granted = wp_json_encode( harbour_consent_granted() );
	?>
<style>
.harbour-consent{position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;max-width:560px;margin:0 auto;background:#fff;color:#1a1a2e;border:1px solid #d7deea;border-radius:14px;box-shadow:0 10px 40px rgba(15,19,111,.18);padding:20px 22px;font:400 15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.harbour-consent[hidden]{display:none}
.harbour-consent h2{margin:0 0 6px;font-size:16px;font-weight:700;color:#0F136F}
.harbour-consent p{margin:0 0 14px}
.harbour-consent a{color:#2E6FC2}
.harbour-consent__actions{display:flex;flex-wrap:wrap;gap:10px}
.harbour-consent__btn{appearance:none;border:0;border-radius:9999px;padding:11px 20px;font-size:15px;font-weight:600;cursor:pointer;line-height:1}
.harbour-consent__btn--accept{background:#2E6FC2;color:#fff}
.harbour-consent__btn--accept:hover{background:#0F136F}
.harbour-consent__btn--reject{background:#eef1f7;color:#0F136F}
.harbour-consent__btn--reject:hover{background:#e2e7f1}
.harbour-consent__btn:focus-visible{outline:3px solid #6FA6E0;outline-offset:2px}
@media (prefers-color-scheme:dark){.harbour-consent{background:#15172b;color:#e8eaf3;border-color:#2a2d4a}.harbour-consent h2{color:#9db8e6}.harbour-consent__btn--reject{background:#23263f;color:#cdd6ea}}
</style>
<div class="harbour-consent" id="harbour-consent" role="dialog" aria-live="polite" aria-label="Cookie consent" hidden>
	<h2>Cookies</h2>
	<p>We'd like to use Google Analytics to see how people use the site, which sets cookies. Nothing is set until you choose.
	<?php
	if ( $privacy ) :
		?>
		See our <a href="<?php echo esc_url( $privacy ); ?>">privacy policy</a>.<?php endif; ?></p>
	<div class="harbour-consent__actions">
		<button type="button" class="harbour-consent__btn harbour-consent__btn--accept" data-harbour-consent="accept">Accept</button>
		<button type="button" class="harbour-consent__btn harbour-consent__btn--reject" data-harbour-consent="reject">Reject</button>
	</div>
</div>
<script<?php echo harbour_consent_nonce_attr(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string. ?>>
(function(){
	var KEY='harbour_consent';
	var el=document.getElementById('harbour-consent');
	if(!el){return;}
	function stored(){try{return window.localStorage?localStorage.getItem(KEY):null;}catch(e){return null;}}
	function save(v){try{if(window.localStorage){localStorage.setItem(KEY,v);}}catch(e){}}
	function wpConsent(allow){if(typeof window.wp_set_consent==='function'){var v=allow?'allow':'deny';window.wp_set_consent('statistics',v);window.wp_set_consent('marketing',v);}}
	function show(){el.hidden=false;}
	function hide(){el.hidden=true;}
	function accept(){if(typeof gtag==='function'){gtag('consent','update',<?php echo $granted; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe in a script context. ?>);}save('granted');wpConsent(true);hide();}
	function reject(){save('denied');wpConsent(false);hide();}
	el.addEventListener('click',function(e){var b=e.target.closest('[data-harbour-consent]');if(!b){return;}if(b.getAttribute('data-harbour-consent')==='accept'){accept();}else{reject();}});
	// Let any "Cookie settings" link re-open the banner.
	document.addEventListener('click',function(e){var t=e.target.closest('.harbour-cookie-settings,a[href="#cookie-settings"]');if(t){e.preventDefault();show();}});
	window.harbourManageCookies=show;
	if(!stored()){show();}
})();
</script>
	<?php
}
add_action( 'wp_footer', 'harbour_consent_banner' );
