<?php
/**
 * Module 4 — Job gallery. Before/after pairs, filterable by service and area.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Query jobs.
 *
 * @param array $args service, area (term slugs), count, featured.
 * @return WP_Post[]
 */
function harbour_get_jobs( array $args = array() ): array {
	$args = wp_parse_args(
		$args,
		array(
			'service'  => '',
			'area'     => '',
			'count'    => 12,
			'featured' => false,
		)
	);
	$q    = array(
		'post_type'      => 'job',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['count'],
		'orderby'        => 'menu_order date',
		'order'          => 'DESC',
	);
	$tax  = array();
	if ( $args['service'] ) {
		$tax[] = array(
			'taxonomy' => 'service_type',
			'field'    => 'slug',
			'terms'    => $args['service'],
		);
	}
	if ( $args['area'] ) {
		$tax[] = array(
			'taxonomy' => 'service_area',
			'field'    => 'slug',
			'terms'    => $args['area'],
		);
	}
	if ( $tax ) {
		$tax['relation'] = 'AND';
		$q['tax_query']  = $tax; // phpcs:ignore
	}
	if ( $args['featured'] ) {
		$q['meta_key']   = '_harbour_featured'; // phpcs:ignore
		$q['meta_value'] = '1'; // phpcs:ignore
	}
	return get_posts( $q );
}

/**
 * Render one before/after job card. Emits an ImageObject pair.
 *
 * @param WP_Post $job Job post.
 * @return string
 */
function harbour_job_card( WP_Post $job ): string {
	$before = (int) get_post_meta( $job->ID, '_harbour_before', true );
	$after  = (int) get_post_meta( $job->ID, '_harbour_after', true );
	if ( ! $before && ! $after && ! has_post_thumbnail( $job ) ) {
		return '';
	}
	$img = static function ( $id, $label ) use ( $job ) {
		if ( ! $id ) {
			return '';
		}
		$alt = trim( $label . ' — ' . get_the_title( $job ) );
		return '<figure class="job-shot"><span class="job-tag">' . esc_html( $label ) . '</span>'
			. wp_get_attachment_image(
				$id,
				'medium_large',
				false,
				array(
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			)
			. '</figure>';
	};
	ob_start();
	echo '<article class="job-card reveal">';
	echo '<div class="job-pair">';
	// The $img closure returns wp_get_attachment_image() output plus esc_html'd labels.
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( $before && $after ) {
		echo $img( $before, __( 'Before', 'harbour-core' ) ) . $img( $after, __( 'After', 'harbour-core' ) );
	} elseif ( $after ) {
		echo $img( $after, __( 'After', 'harbour-core' ) );
	} elseif ( $before ) {
		echo $img( $before, __( 'Before', 'harbour-core' ) );
	} else {
		echo get_the_post_thumbnail( $job, 'medium_large', array( 'loading' => 'lazy' ) );
	}
	echo '</div>';
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div class="job-body"><h3>' . esc_html( get_the_title( $job ) ) . '</h3>';
	if ( $job->post_content ) {
		echo '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $job->post_content ), 26 ) ) . '</p>';
	}
	$terms = wp_get_post_terms( $job->ID, array( 'service_type', 'service_area' ), array( 'fields' => 'names' ) );
	if ( $terms && ! is_wp_error( $terms ) ) {
		echo '<p class="small muted mb-0">' . esc_html( implode( ' · ', $terms ) ) . '</p>';
	}
	echo '</div>';

	// ImageObject pair schema.
	$objs = array();
	foreach ( array( $before, $after ) as $id ) {
		if ( $id ) {
			$src = wp_get_attachment_image_url( $id, 'large' );
			if ( $src ) {
				$objs[] = array(
					'@context'   => 'https://schema.org',
					'@type'      => 'ImageObject',
					'contentUrl' => $src,
					'name'       => get_the_title( $job ),
				);
			}
		}
	}
	foreach ( $objs as $o ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $o, JSON_UNESCAPED_SLASHES ) . '</script>';
	}
	echo '</article>';
	return (string) ob_get_clean();
}

/**
 * Render a job grid.
 *
 * @param array $args See harbour_get_jobs().
 * @return string
 */
function harbour_jobs_grid( array $args = array() ): string {
	$jobs = harbour_get_jobs( $args );
	if ( ! $jobs ) {
		return '';
	}
	$out = '<div class="job-grid">';
	foreach ( $jobs as $job ) {
		$out .= harbour_job_card( $job );
	}
	$out .= '</div>';
	return $out;
}

/**
 * [harbour_gallery service="" area="" count="12"]
 *
 * @param array $atts Attributes.
 * @return string
 */
function harbour_gallery_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'service'  => '',
			'area'     => '',
			'count'    => 12,
			'featured' => '',
		),
		$atts,
		'harbour_gallery'
	);
	return harbour_jobs_grid(
		array(
			'service'  => sanitize_title( $atts['service'] ),
			'area'     => sanitize_title( $atts['area'] ),
			'count'    => (int) $atts['count'],
			'featured' => ! empty( $atts['featured'] ),
		)
	);
}
add_shortcode( 'harbour_gallery', 'harbour_gallery_shortcode' );

/**
 * Order the job archive by menu order (featured/curated first), then newest.
 *
 * @param WP_Query $q Main query.
 */
function harbour_job_archive_order( $q ): void {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'job' ) ) {
		$q->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'harbour_job_archive_order' );
