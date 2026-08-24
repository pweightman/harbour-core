<?php
/**
 * Template tags for public content: hero heading and the FAQ accordion.
 * The theme calls these; markup can be overridden by the theme if needed.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * The hero heading for a service/area, falling back to the title.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function harbour_hero_heading( ?int $post_id = null ): string {
	$post_id = $post_id ? $post_id : get_the_ID();
	$h       = get_post_meta( $post_id, '_harbour_hero_heading', true );
	return $h ? $h : get_the_title( $post_id );
}

/**
 * Return the FAQ pairs for a post.
 *
 * @param int|null $post_id Post ID.
 * @return array<int,array{q:string,a:string}>
 */
function harbour_get_faq( ?int $post_id = null ): array {
	$post_id = $post_id ? $post_id : get_the_ID();
	$faq     = get_post_meta( $post_id, '_harbour_faq', true );
	return is_array( $faq ) ? $faq : array();
}

/**
 * Render the FAQ accordion (native <details>), matching the prototype.
 *
 * @param int|null $post_id Post ID.
 */
function harbour_render_faq( ?int $post_id = null ): void {
	$faq = harbour_get_faq( $post_id );
	if ( ! $faq ) {
		return;
	}
	echo '<div class="faq">';
	$first = true;
	foreach ( $faq as $row ) {
		if ( empty( $row['q'] ) || empty( $row['a'] ) ) {
			continue;
		}
		printf(
			'<details%s><summary>%s</summary><p>%s</p></details>',
			$first ? ' open' : '',
			esc_html( $row['q'] ),
			nl2br( esc_html( $row['a'] ) )
		);
		$first = false;
	}
	echo '</div>';
}
