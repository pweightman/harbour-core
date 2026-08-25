<?php
/**
 * One-click setup for no-SSH installs. Adds "Harbour → Setup" with buttons to
 * build the navigation menus and seed starter settings. Idempotent; safe to
 * re-run. All actions are capability-checked and nonce-protected.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'harbour', __( 'Setup', 'harbour-core' ), __( 'Setup', 'harbour-core' ), 'manage_options', 'harbour-setup', 'harbour_setup_page' );
	},
	20
);

/**
 * Render + handle the setup page.
 */
function harbour_setup_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$done = '';
	if ( isset( $_POST['harbour_setup_action'] ) && check_admin_referer( 'harbour_setup' ) ) {
		$action = sanitize_key( wp_unslash( $_POST['harbour_setup_action'] ) );
		if ( 'menus' === $action ) {
			harbour_setup_build_menus();
			$done = __( 'Navigation menus built and assigned.', 'harbour-core' );
		} elseif ( 'settings' === $action ) {
			harbour_setup_seed_settings();
			$done = __( 'Starter settings loaded. Now confirm every value on the Settings screen.', 'harbour-core' );
		} elseif ( 'frontpage' === $action ) {
			$home = get_posts(
				array(
					'post_type'   => 'page',
					'name'        => 'home',
					'numberposts' => 1,
					'post_status' => 'any',
				)
			);
			if ( $home ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $home[0]->ID );
				$done = __( 'Front page set to “Home”.', 'harbour-core' );
			} else {
				$done = __( 'No page with the slug “home” was found — import content first.', 'harbour-core' );
			}
		}
		flush_rewrite_rules();
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'Harbour setup', 'harbour-core' ) . '</h1>';
	if ( $done ) {
		echo '<div class="notice notice-success"><p>' . esc_html( $done ) . '</p></div>';
	}
	echo '<p>' . esc_html__( 'One-click helpers for setting the site up after installing the theme and plugin. Each is safe to run more than once.', 'harbour-core' ) . '</p>';

	$button = static function ( $action, $label, $desc ) {
		echo '<form method="post" style="margin:0 0 20px;padding:14px 16px;border:1px solid #dcdcde;border-radius:6px;max-width:640px">';
		wp_nonce_field( 'harbour_setup' );
		echo '<input type="hidden" name="harbour_setup_action" value="' . esc_attr( $action ) . '">';
		echo '<p style="margin:0 0 10px">' . esc_html( $desc ) . '</p>';
		echo '<button class="button button-primary">' . esc_html( $label ) . '</button>';
		echo '</form>';
	};
	$button( 'menus', __( 'Build navigation menus', 'harbour-core' ), __( 'Create the primary and footer menus (Services and Areas with their sub-items) and assign them to their locations. Rebuilds them if they already exist.', 'harbour-core' ) );
	$button( 'settings', __( 'Load starter settings', 'harbour-core' ), __( 'Fill the Business, Enquiries and Firewood settings with starter values. Confirm and correct them all afterwards — especially firewood prices.', 'harbour-core' ) );
	$button( 'frontpage', __( 'Set the front page', 'harbour-core' ), __( 'Set the site to show the “Home” page as the front page (import content first).', 'harbour-core' ) );
	echo '</div>';
}

/**
 * Build the primary + footer menus using the site's own home_url().
 */
function harbour_setup_build_menus(): void {
	$b    = untrailingslashit( home_url() );
	$make = static function ( $name, $location, $items ) {
		$existing = wp_get_nav_menu_object( $name );
		if ( $existing ) {
			wp_delete_nav_menu( $existing->term_id );
		}
		$menu_id = wp_create_nav_menu( $name );
		$parents = array();
		foreach ( $items as $key => $it ) {
			$args = array(
				'menu-item-title'  => $it['title'],
				'menu-item-url'    => $it['url'],
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			);
			if ( ! empty( $it['desc'] ) ) {
				$args['menu-item-description'] = $it['desc'];
			}
			if ( ! empty( $it['parent'] ) && isset( $parents[ $it['parent'] ] ) ) {
				$args['menu-item-parent-id'] = $parents[ $it['parent'] ];
			}
			$parents[ $key ] = wp_update_nav_menu_item( $menu_id, 0, $args );
		}
		$locations              = get_theme_mod( 'nav_menu_locations', array() );
		$locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	};

	$make(
		'Primary',
		'primary',
		array(
			'services' => array(
				'title' => 'Services',
				'url'   => "$b/tree-surgery-leicestershire/",
			),
			array(
				'title'  => 'Pruning & crown reduction',
				'url'    => "$b/services/tree-pruning-crown-reduction/",
				'desc'   => 'Reshape, thin and lift safely',
				'parent' => 'services',
			),
			array(
				'title'  => 'Tree felling & removal',
				'url'    => "$b/services/tree-felling-removal/",
				'desc'   => 'Dismantled section by section',
				'parent' => 'services',
			),
			array(
				'title'  => 'Stump grinding',
				'url'    => "$b/services/stump-grinding/",
				'desc'   => 'Ground out below the surface',
				'parent' => 'services',
			),
			array(
				'title'  => 'Site clearance',
				'url'    => "$b/services/site-clearance/",
				'desc'   => 'Plots cleared for development',
				'parent' => 'services',
			),
			array(
				'title'  => 'Tree surveys & reports',
				'url'    => "$b/services/tree-surveys-reports/",
				'desc'   => 'For planning and safety',
				'parent' => 'services',
			),
			array(
				'title'  => 'Seasoned firewood',
				'url'    => "$b/services/seasoned-firewood/",
				'desc'   => 'Ready to burn, locally delivered',
				'parent' => 'services',
			),
			'areas'    => array(
				'title' => 'Areas',
				'url'   => "$b/areas/",
			),
			array(
				'title'  => 'Hinckley',
				'url'    => "$b/areas/tree-surgeons-hinckley/",
				'parent' => 'areas',
			),
			array(
				'title'  => 'Lutterworth',
				'url'    => "$b/areas/tree-surgeons-lutterworth/",
				'parent' => 'areas',
			),
			array(
				'title'  => 'Leicester',
				'url'    => "$b/areas/tree-surgeons-leicester/",
				'parent' => 'areas',
			),
			array(
				'title'  => 'Rugby',
				'url'    => "$b/areas/tree-surgeons-rugby/",
				'parent' => 'areas',
			),
			array(
				'title'  => 'Market Harborough',
				'url'    => "$b/areas/tree-surgeons-market-harborough/",
				'parent' => 'areas',
			),
			array(
				'title'  => 'All areas covered',
				'url'    => "$b/areas/",
				'parent' => 'areas',
			),
			array(
				'title' => 'About',
				'url'   => "$b/about/",
			),
			array(
				'title' => 'Contact',
				'url'   => "$b/contact/",
			),
		)
	);
	$make(
		'Footer Services',
		'footer_services',
		array(
			array(
				'title' => 'Pruning & crown reduction',
				'url'   => "$b/services/tree-pruning-crown-reduction/",
			),
			array(
				'title' => 'Felling & removal',
				'url'   => "$b/services/tree-felling-removal/",
			),
			array(
				'title' => 'Stump grinding',
				'url'   => "$b/services/stump-grinding/",
			),
			array(
				'title' => 'Site clearance',
				'url'   => "$b/services/site-clearance/",
			),
			array(
				'title' => 'Surveys & reports',
				'url'   => "$b/services/tree-surveys-reports/",
			),
			array(
				'title' => 'Seasoned firewood',
				'url'   => "$b/services/seasoned-firewood/",
			),
		)
	);
	$make(
		'Footer Areas',
		'footer_areas',
		array(
			array(
				'title' => 'Hinckley',
				'url'   => "$b/areas/tree-surgeons-hinckley/",
			),
			array(
				'title' => 'Lutterworth',
				'url'   => "$b/areas/tree-surgeons-lutterworth/",
			),
			array(
				'title' => 'Leicester',
				'url'   => "$b/areas/tree-surgeons-leicester/",
			),
			array(
				'title' => 'Rugby',
				'url'   => "$b/areas/tree-surgeons-rugby/",
			),
			array(
				'title' => 'Market Harborough',
				'url'   => "$b/areas/tree-surgeons-market-harborough/",
			),
			array(
				'title' => 'See all areas',
				'url'   => "$b/areas/",
			),
		)
	);
}

/**
 * Seed starter settings (placeholders — must be confirmed afterwards).
 */
function harbour_setup_seed_settings(): void {
	$s              = get_option( HARBOUR_OPTION, array() );
	$s['business']  = wp_parse_args(
		$s['business'] ?? array(),
		array(
			'name'          => 'Harbour Tree Care',
			'established'   => '1977',
			'company_no'    => '08834201',
			'phone_yard'    => '01455 230643',
			'phone_mobile'  => '07815 835588',
			'email'         => 'info@harbourtreecare.co.uk',
			'addr_line1'    => 'Ashby Magna',
			'addr_line2'    => 'Lutterworth',
			'addr_county'   => 'Leicestershire',
			'addr_post'     => 'LE17 5NJ',
			'yard_postcode' => 'LE17 5NJ',
			'hours'         => 'Mon–Fri 7.30am–5.30pm<br />Sat 8am–1pm',
			'facebook'      => 'https://www.facebook.com/harbourtreecare',
			'instagram'     => 'https://www.instagram.com/raharbourtree',
		)
	);
	$s['enquiries'] = wp_parse_args(
		$s['enquiries'] ?? array(),
		array(
			'notify_emails'    => 'info@harbourtreecare.co.uk',
			'ack_subject'      => "Thanks — we've got your enquiry",
			'ack_body'         => "Thanks for getting in touch with Harbour Tree Care. We've received your enquiry and will usually reply the same working day. If it's urgent, please call the yard on 01455 230643.",
			'retention_months' => 24,
		)
	);
	if ( empty( $s['firewood'] ) ) {
		$s['firewood'] = array(
			'products'     => array(
				array(
					'name'         => 'Bulk bag',
					'description'  => 'Approx. 1 cubic metre of seasoned hardwood. Volume and price to confirm.',
					'price'        => '',
					'availability' => 'in',
					'min_order'    => 1,
				),
				array(
					'name'         => 'Half load',
					'description'  => 'Half a tipper load, seasoned hardwood. Volume and price to confirm.',
					'price'        => '',
					'availability' => 'in',
					'min_order'    => 1,
				),
				array(
					'name'         => 'Full load',
					'description'  => 'Full tipper load, seasoned hardwood. Volume and price to confirm.',
					'price'        => '',
					'availability' => 'in',
					'min_order'    => 1,
				),
			),
			'radius_inner' => 12,
			'radius_outer' => 20,
			'slots'        => array( 'Weekday morning', 'Weekday afternoon', 'Saturday morning' ),
			'vat_display'  => 0,
		);
	}
	update_option( HARBOUR_OPTION, $s );
}
