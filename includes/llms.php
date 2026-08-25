<?php
/**
 * llms.txt — a plain-markdown guide for AI assistants / LLMs, served at
 * /llms.txt. Generated from business settings and published content, so it
 * stays in sync (held/draft pages are excluded automatically).
 *
 * @see https://llmstxt.org/
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the /llms.txt rewrite + query var.
 */
function harbour_llms_rewrite(): void {
	add_rewrite_rule( '^llms\.txt$', 'index.php?harbour_llms=1', 'top' );
}
add_action( 'init', 'harbour_llms_rewrite' );

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'harbour_llms';
		return $vars;
	}
);

/**
 * Flush rewrite rules once after the plugin version changes, so new rules
 * (like /llms.txt) take effect on a self-update without manual intervention.
 */
function harbour_maybe_flush_rewrites(): void {
	if ( get_option( 'harbour_rewrites_version' ) !== HARBOUR_CORE_VERSION ) {
		harbour_llms_rewrite();
		flush_rewrite_rules();
		update_option( 'harbour_rewrites_version', HARBOUR_CORE_VERSION );
	}
}
add_action( 'admin_init', 'harbour_maybe_flush_rewrites' );

/**
 * Output the llms.txt body when requested.
 */
function harbour_render_llms(): void {
	if ( ! get_query_var( 'harbour_llms' ) ) {
		return;
	}

	$name  = harbour_setting( 'business', 'name', get_bloginfo( 'name' ) );
	$home  = home_url( '/' );
	$phone = harbour_setting( 'business', 'phone_yard', '' );
	$mob   = harbour_setting( 'business', 'phone_mobile', '' );
	$email = harbour_setting( 'business', 'email', '' );
	$est   = harbour_setting( 'business', 'established', '1977' );
	$addr  = trim(
		implode(
			', ',
			array_filter(
				array(
					harbour_setting( 'business', 'addr_line1', '' ),
					harbour_setting( 'business', 'addr_line2', '' ),
					harbour_setting( 'business', 'addr_county', '' ),
					harbour_setting( 'business', 'addr_post', '' ),
				)
			)
		)
	);
	$hours = trim( wp_strip_all_tags( str_replace( array( '<br />', '<br>' ), ' / ', harbour_setting( 'business', 'hours', '' ) ) ) );

	$lines   = array();
	$lines[] = '# ' . $name;
	$lines[] = '';
	$lines[] = sprintf( '> Family-run tree surgeons and arborists in Leicestershire since %s. Pruning, felling, stump grinding, site clearance, tree surveys and seasoned firewood across Leicestershire, Warwickshire, Northamptonshire, Nottinghamshire and Derbyshire. Free site visits and fixed written quotes.', $est );
	$lines[] = '';
	$contact = array();
	if ( $phone ) {
		$contact[] = 'Yard ' . $phone; }
	if ( $mob ) {
		$contact[] = 'Mobile ' . $mob; }
	if ( $email ) {
		$contact[] = $email; }
	if ( $contact ) {
		$lines[] = 'Contact: ' . implode( ' · ', $contact ) . '.'; }
	if ( $addr ) {
		$lines[] = 'Based at: ' . $addr . '.'; }
	if ( $hours ) {
		$lines[] = 'Opening hours: ' . $hours . '.'; }
	$lines[] = '';

	$section = static function ( $title, $posts ) use ( &$lines ) {
		if ( ! $posts ) {
			return;
		}
		$lines[] = '## ' . $title;
		foreach ( $posts as $post ) {
			$excerpt = has_excerpt( $post ) ? html_entity_decode( wp_strip_all_tags( get_the_excerpt( $post ) ), ENT_QUOTES, 'UTF-8' ) : '';
			$line    = '- [' . html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) . '](' . get_permalink( $post ) . ')';
			if ( $excerpt ) {
				$line .= ': ' . $excerpt;
			}
			$lines[] = $line;
		}
		$lines[] = '';
	};

	$section(
		'Services',
		get_posts(
			array(
				'post_type'   => 'service',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'menu_order title',
				'order'       => 'ASC',
			)
		)
	);
	$section(
		'Areas covered',
		get_posts(
			array(
				'post_type'   => 'area',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'menu_order title',
				'order'       => 'ASC',
			)
		)
	);

	// Key pages (published only).
	$key = array();
	foreach ( array( 'about', 'contact', 'order-logs', 'tree-surgery-prices', 'emergency-tree-surgeon-leicestershire' ) as $slug ) {
		$pages = get_posts(
			array(
				'post_type'   => 'page',
				'name'        => $slug,
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);
		if ( $pages ) {
			$key[] = $pages[0];
		}
	}
	$section( 'Key pages', $key );

	$lines[] = '## More';
	$lines[] = '- [Full site map](' . home_url( '/wp-sitemap.xml' ) . ')';
	$lines[] = '';

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo implode( "\n", $lines ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text output, values are from trusted settings/titles.
	exit;
}
add_action( 'template_redirect', 'harbour_render_llms', 0 );

// Don't let WordPress canonical-redirect /llms.txt to a trailing slash.
add_filter(
	'redirect_canonical',
	function ( $redirect_url ) {
		return get_query_var( 'harbour_llms' ) ? false : $redirect_url;
	}
);
