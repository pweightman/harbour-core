<?php
/**
 * Uninstall handler.
 *
 * Deliberately conservative: removing the plugin must NOT destroy a business's
 * customer records. Enquiries, log orders, reviews and job entries are left in
 * place unless an explicit opt-in setting says otherwise (wired up with the
 * settings screen in a later phase).
 *
 * @package HarbourCore
 * @see PLUGIN-SPEC.md — Security requirements
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$harbour_settings = get_option( 'harbour_core_settings', array() );

if ( empty( $harbour_settings['delete_data_on_uninstall'] ) ) {
	// Default path: keep all content. Nothing to do.
	return;
}

// Explicit opt-in only. Destructive data removal is implemented alongside the
// settings screen so this stays a single, auditable place.
