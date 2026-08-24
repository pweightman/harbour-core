<?php
/**
 * Settings — one top-level "Harbour" menu, one option array, one sanitise pass.
 *
 * Every business fact the templates print lives here, so the NAP is identical
 * everywhere. Tabs save independently by merging over the stored option.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

const HARBOUR_OPTION = 'harbour_core_settings';

/**
 * Read one setting, e.g. harbour_setting( 'business', 'phone_yard' ).
 *
 * @param string $group   Group key (business|enquiries|firewood).
 * @param string $key     Field key.
 * @param mixed  $default Default if unset.
 * @return mixed
 */
function harbour_setting( string $group, string $key, $default = '' ) {
	$all = get_option( HARBOUR_OPTION, array() );
	return $all[ $group ][ $key ] ?? $default;
}

/**
 * Register the option and its sanitise callback.
 */
function harbour_register_settings(): void {
	register_setting( 'harbour_settings_group', HARBOUR_OPTION, array(
		'type'              => 'array',
		'sanitize_callback' => 'harbour_sanitize_settings',
		'default'           => array(),
	) );
}
add_action( 'admin_init', 'harbour_register_settings' );

/**
 * Merge the submitted tab over the stored option and sanitise every field.
 *
 * @param mixed $input Raw submitted array.
 * @return array
 */
function harbour_sanitize_settings( $input ): array {
	$existing = get_option( HARBOUR_OPTION, array() );
	$existing = is_array( $existing ) ? $existing : array();
	$input    = is_array( $input ) ? $input : array();

	// Business.
	if ( isset( $input['business'] ) && is_array( $input['business'] ) ) {
		$b = $input['business'];
		$existing['business'] = array(
			'name'         => sanitize_text_field( $b['name'] ?? '' ),
			'established'  => sanitize_text_field( $b['established'] ?? '' ),
			'company_no'   => sanitize_text_field( $b['company_no'] ?? '' ),
			'phone_yard'   => sanitize_text_field( $b['phone_yard'] ?? '' ),
			'phone_mobile' => sanitize_text_field( $b['phone_mobile'] ?? '' ),
			'email'        => sanitize_email( $b['email'] ?? '' ),
			'addr_line1'   => sanitize_text_field( $b['addr_line1'] ?? '' ),
			'addr_line2'   => sanitize_text_field( $b['addr_line2'] ?? '' ),
			'addr_county'  => sanitize_text_field( $b['addr_county'] ?? '' ),
			'addr_post'    => sanitize_text_field( $b['addr_post'] ?? '' ),
			'yard_postcode'=> strtoupper( sanitize_text_field( $b['yard_postcode'] ?? '' ) ),
			'hours'        => nl2br( esc_html( trim( wp_unslash( $b['hours'] ?? '' ) ) ), false ),
			'facebook'     => esc_url_raw( $b['facebook'] ?? '' ),
			'instagram'    => esc_url_raw( $b['instagram'] ?? '' ),
		);
	}

	// Enquiries.
	if ( isset( $input['enquiries'] ) && is_array( $input['enquiries'] ) ) {
		$e = $input['enquiries'];
		$recipients = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', $e['notify_emails'] ?? '' ) ) ) );
		$existing['enquiries'] = array(
			'notify_emails'      => implode( ', ', $recipients ),
			'ack_subject'        => sanitize_text_field( $e['ack_subject'] ?? '' ),
			'ack_body'           => sanitize_textarea_field( $e['ack_body'] ?? '' ),
			'retention_months'   => max( 1, absint( $e['retention_months'] ?? 24 ) ),
		);
	}

	// Firewood.
	if ( isset( $input['firewood'] ) && is_array( $input['firewood'] ) ) {
		$f = $input['firewood'];
		$products = array();
		if ( ! empty( $f['products'] ) && is_array( $f['products'] ) ) {
			foreach ( $f['products'] as $row ) {
				$name = sanitize_text_field( $row['name'] ?? '' );
				if ( '' === $name ) {
					continue;
				}
				$products[] = array(
					'name'        => $name,
					'description' => sanitize_text_field( $row['description'] ?? '' ),
					'price'       => sanitize_text_field( $row['price'] ?? '' ),
					'availability'=> in_array( $row['availability'] ?? '', array( 'in', 'low', 'out' ), true ) ? $row['availability'] : 'in',
					'min_order'   => max( 1, absint( $row['min_order'] ?? 1 ) ),
				);
			}
		}
		$slots = array_filter( array_map( 'sanitize_text_field', array_map( 'trim', explode( "\n", $f['slots'] ?? '' ) ) ) );
		$existing['firewood'] = array(
			'products'     => $products,
			'radius_inner' => max( 0, (float) ( $f['radius_inner'] ?? 12 ) ),
			'radius_outer' => max( 0, (float) ( $f['radius_outer'] ?? 20 ) ),
			'slots'        => array_values( $slots ),
			'vat_display'  => ! empty( $f['vat_display'] ) ? 1 : 0,
		);
	}

	return $existing;
}

/**
 * Register the Harbour menu and settings submenu.
 */
function harbour_admin_menu(): void {
	add_menu_page(
		'Harbour',
		'Harbour',
		'edit_posts',
		'harbour',
		'harbour_dashboard_page',
		'dashicons-palmtree',
		24
	);
	add_submenu_page(
		'harbour',
		'Harbour settings',
		'Settings',
		'manage_options',
		'harbour-settings',
		'harbour_settings_page'
	);
}
add_action( 'admin_menu', 'harbour_admin_menu' );

/**
 * Dashboard landing page.
 */
function harbour_dashboard_page(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$enquiries = wp_count_posts( 'enquiry' );
	$new = isset( $enquiries->publish ) ? (int) $enquiries->publish : 0;
	echo '<div class="wrap"><h1>Harbour</h1>';
	echo '<p>Enquiries, orders, reviews and business settings for Harbour Tree Care.</p>';
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'edit.php?post_type=enquiry' ) ) . '">View enquiries</a> ';
	echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=harbour-settings' ) ) . '">Settings</a></p>';
	echo '</div>';
}

/**
 * Tabbed settings page.
 */
function harbour_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs = array(
		'business'  => 'Business',
		'enquiries' => 'Enquiries',
		'firewood'  => 'Firewood',
		'integrations' => 'Integrations',
	);
	$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'business';
	if ( ! isset( $tabs[ $active ] ) ) {
		$active = 'business';
	}
	?>
	<div class="wrap">
		<h1>Harbour settings</h1>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=harbour-settings&tab=' . $slug ) ); ?>" class="nav-tab <?php echo $active === $slug ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</h2>
		<form action="options.php" method="post">
			<?php settings_fields( 'harbour_settings_group' ); ?>
			<?php
			switch ( $active ) {
				case 'business':
					harbour_settings_tab_business();
					break;
				case 'enquiries':
					harbour_settings_tab_enquiries();
					break;
				case 'firewood':
					harbour_settings_tab_firewood();
					break;
				case 'integrations':
					echo '<p>Transactional email (SMTP / Resend) credentials are defined as constants in <code>wp-config.php</code>, never stored in the database. See the plugin README.</p>';
					break;
			}
			if ( in_array( $active, array( 'business', 'enquiries', 'firewood' ), true ) ) {
				submit_button();
			}
			?>
		</form>
	</div>
	<?php
}

/**
 * Render a single text field bound to the option array.
 */
function harbour_field( string $group, string $key, string $label, string $type = 'text', string $hint = '' ): void {
	$value = harbour_setting( $group, $key );
	$name  = HARBOUR_OPTION . '[' . $group . '][' . $key . ']';
	$id    = 'harbour-' . $group . '-' . $key;
	echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
	if ( 'textarea' === $type ) {
		echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4" class="large-text">' . esc_textarea( html_entity_decode( str_replace( '<br />', "\n", (string) $value ) ) ) . '</textarea>';
	} else {
		echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text">';
	}
	if ( $hint ) {
		echo '<p class="description">' . esc_html( $hint ) . '</p>';
	}
	echo '</td></tr>';
}

/**
 * Business tab fields.
 */
function harbour_settings_tab_business(): void {
	echo '<table class="form-table" role="presentation">';
	harbour_field( 'business', 'name', 'Trading name' );
	harbour_field( 'business', 'established', 'Established (year)' );
	harbour_field( 'business', 'company_no', 'Company number' );
	harbour_field( 'business', 'phone_yard', 'Phone — yard' );
	harbour_field( 'business', 'phone_mobile', 'Phone — mobile' );
	harbour_field( 'business', 'email', 'Email', 'email' );
	harbour_field( 'business', 'addr_line1', 'Address line 1' );
	harbour_field( 'business', 'addr_line2', 'Address line 2' );
	harbour_field( 'business', 'addr_county', 'County' );
	harbour_field( 'business', 'addr_post', 'Postcode (display)' );
	harbour_field( 'business', 'yard_postcode', 'Yard postcode (for delivery radius)', 'text', 'Used to measure firewood delivery distance. e.g. LE17 5NJ' );
	harbour_field( 'business', 'hours', 'Opening hours', 'textarea', 'One line per row; shown with line breaks.' );
	harbour_field( 'business', 'facebook', 'Facebook URL', 'url' );
	harbour_field( 'business', 'instagram', 'Instagram URL', 'url' );
	echo '</table>';
}

/**
 * Enquiries tab fields.
 */
function harbour_settings_tab_enquiries(): void {
	echo '<table class="form-table" role="presentation">';
	harbour_field( 'enquiries', 'notify_emails', 'Notification recipients', 'text', 'Comma-separated. Where new enquiries are emailed.' );
	harbour_field( 'enquiries', 'ack_subject', 'Autoresponder subject' );
	harbour_field( 'enquiries', 'ack_body', 'Autoresponder message', 'textarea', 'Sent to the customer to acknowledge their enquiry.' );
	harbour_field( 'enquiries', 'retention_months', 'Retention (months)', 'number', 'How long enquiries are kept before scheduled cleanup.' );
	echo '</table>';
}

/**
 * Firewood tab fields: products, delivery radius, slots, VAT display.
 */
function harbour_settings_tab_firewood(): void {
	$fw       = get_option( HARBOUR_OPTION, array() )['firewood'] ?? array();
	$products = $fw['products'] ?? array();
	$slots    = isset( $fw['slots'] ) ? implode( "\n", (array) $fw['slots'] ) : '';
	$avail    = array( 'in' => 'In stock', 'low' => 'Low', 'out' => 'Out of stock' );

	echo '<h2>' . esc_html__( 'Products', 'harbour-core' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Up to 6 firewood products. Leave the name blank to remove a row. Prices show as entered — include units (e.g. "£90").', 'harbour-core' ) . '</p>';
	for ( $i = 0; $i < 6; $i++ ) {
		$p = $products[ $i ] ?? array();
		$n = HARBOUR_OPTION . '[firewood][products][' . $i . ']';
		echo '<div style="margin:0 0 14px;padding:10px;border:1px solid #dcdcde;border-radius:4px;max-width:760px">';
		echo '<input type="text" name="' . esc_attr( $n ) . '[name]" value="' . esc_attr( $p['name'] ?? '' ) . '" placeholder="' . esc_attr__( 'Product name (e.g. Full load)', 'harbour-core' ) . '" class="regular-text" style="margin-right:8px">';
		echo '<input type="text" name="' . esc_attr( $n ) . '[price]" value="' . esc_attr( $p['price'] ?? '' ) . '" placeholder="' . esc_attr__( 'Price', 'harbour-core' ) . '" style="width:90px;margin-right:8px">';
		echo '<select name="' . esc_attr( $n ) . '[availability]" style="margin-right:8px">';
		foreach ( $avail as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $p['availability'] ?? 'in', $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<label>' . esc_html__( 'Min qty', 'harbour-core' ) . ' <input type="number" min="1" name="' . esc_attr( $n ) . '[min_order]" value="' . esc_attr( $p['min_order'] ?? 1 ) . '" style="width:70px"></label>';
		echo '<br><input type="text" name="' . esc_attr( $n ) . '[description]" value="' . esc_attr( $p['description'] ?? '' ) . '" placeholder="' . esc_attr__( 'Description (volume, species, seasoning)', 'harbour-core' ) . '" class="large-text" style="margin-top:8px">';
		echo '</div>';
	}

	echo '<h2>' . esc_html__( 'Delivery', 'harbour-core' ) . '</h2>';
	echo '<table class="form-table" role="presentation">';
	printf(
		'<tr><th><label for="ri">%s</label></th><td><input type="number" step="0.5" id="ri" name="%s[firewood][radius_inner]" value="%s" style="width:90px"> %s</td></tr>',
		esc_html__( 'Inner radius (miles)', 'harbour-core' ),
		esc_attr( HARBOUR_OPTION ),
		esc_attr( $fw['radius_inner'] ?? 12 ),
		esc_html__( 'within this, we deliver', 'harbour-core' )
	);
	printf(
		'<tr><th><label for="ro">%s</label></th><td><input type="number" step="0.5" id="ro" name="%s[firewood][radius_outer]" value="%s" style="width:90px"> %s</td></tr>',
		esc_html__( 'Outer radius (miles)', 'harbour-core' ),
		esc_attr( HARBOUR_OPTION ),
		esc_attr( $fw['radius_outer'] ?? 20 ),
		esc_html__( 'between inner and outer, we confirm; beyond, we decline', 'harbour-core' )
	);
	printf(
		'<tr><th><label for="slots">%s</label></th><td><textarea id="slots" name="%s[firewood][slots]" rows="4" class="large-text">%s</textarea><p class="description">%s</p></td></tr>',
		esc_html__( 'Delivery windows', 'harbour-core' ),
		esc_attr( HARBOUR_OPTION ),
		esc_textarea( $slots ),
		esc_html__( 'One per line, e.g. "Weekday morning". These are named slots, not a live calendar.', 'harbour-core' )
	);
	printf(
		'<tr><th>%s</th><td><label><input type="checkbox" name="%s[firewood][vat_display]" value="1" %s> %s</label></td></tr>',
		esc_html__( 'VAT', 'harbour-core' ),
		esc_attr( HARBOUR_OPTION ),
		checked( ! empty( $fw['vat_display'] ), true, false ),
		esc_html__( 'Note that prices include VAT', 'harbour-core' )
	);
	echo '</table>';
}
