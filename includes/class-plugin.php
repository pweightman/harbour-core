<?php
/**
 * Bootstrapper: loads the plugin's modules in dependency order.
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

final class Harbour_Core {

	/**
	 * Require every module. Settings first — other modules read settings.
	 */
	public static function boot(): void {
		$inc = HARBOUR_CORE_PATH . 'includes/';

		require_once $inc . 'validation.php';
		require_once $inc . 'settings.php';
		require_once $inc . 'security.php';
		require_once $inc . 'mail.php';
		require_once $inc . 'post-types.php';
		require_once $inc . 'taxonomies.php';
		require_once $inc . 'meta.php';
		require_once $inc . 'schema.php';
		require_once $inc . 'content-tags.php';
		require_once $inc . 'modules/enquiries.php';

		if ( is_admin() ) {
			require_once HARBOUR_CORE_PATH . 'admin/enquiries-admin.php';
			require_once HARBOUR_CORE_PATH . 'admin/content-meta.php';
		}
	}

	/**
	 * On activation: register rewrite-bearing types, then flush.
	 */
	public static function activate(): void {
		self::boot();
		harbour_register_post_types();
		harbour_register_taxonomies();
		flush_rewrite_rules();
	}

	/**
	 * On deactivation: flush so CPT rewrite rules are dropped.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
