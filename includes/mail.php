<?php
/**
 * Templated transactional email. HTML with a plain-text-friendly layout.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Send an HTML email wrapped in a minimal branded template.
 *
 * @param string|array $to          Recipient(s).
 * @param string       $subject     Subject.
 * @param string       $body        HTML body (inner content).
 * @param array        $headers     Extra headers.
 * @param array        $attachments Absolute file paths to attach.
 * @return bool
 */
function harbour_mail( $to, string $subject, string $body, array $headers = array(), array $attachments = array() ): bool {
	$name  = harbour_setting( 'business', 'name', 'Harbour Tree Care' );
	$html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#12203A;line-height:1.6">';
	$html .= '<h2 style="font-size:18px;margin:0 0 12px">' . esc_html( $name ) . '</h2>';
	$html .= $body;
	$html .= '<hr style="border:0;border-top:1px solid #DEE4EC;margin:20px 0">';
	$html .= '<p style="font-size:12px;color:#5a6b82;margin:0">' . esc_html( $name );
	$post  = harbour_setting( 'business', 'addr_post', '' );
	if ( $post ) {
		$html .= ' · ' . esc_html( harbour_setting( 'business', 'addr_line1', '' ) . ', ' . $post );
	}
	$html .= '</p></div>';

	$headers = array_merge( array( 'Content-Type: text/html; charset=UTF-8' ), $headers );

	return wp_mail( $to, $subject, $html, $headers, $attachments );
}
