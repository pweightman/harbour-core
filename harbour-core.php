<?php
/**
 * Plugin Name:       Harbour Core
 * Plugin URI:        https://github.com/pweightman/harbour-core
 * Description:       Post types, enquiries, firewood orders, reviews, job gallery and schema for Harbour Tree Care. Survives any theme change.
 * Version:           0.1.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Patrick Weightman
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       harbour-core
 *
 * @package HarbourCore
 */

defined( 'ABSPATH' ) || exit;

define( 'HARBOUR_CORE_VERSION', '0.1.0' );
define( 'HARBOUR_CORE_FILE', __FILE__ );
define( 'HARBOUR_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'HARBOUR_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once HARBOUR_CORE_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Wire the plugin up to its GitHub releases.
 */
function harbour_core_updates(): void {
	$updater = PucFactory::buildUpdateChecker(
		'https://github.com/pweightman/harbour-core/',
		HARBOUR_CORE_FILE,
		'harbour-core'
	);
	$updater->getVcsApi()->enableReleaseAssets( '/harbour-core\.zip$/i' );
}
add_action( 'init', 'harbour_core_updates' );

require_once HARBOUR_CORE_PATH . 'includes/class-plugin.php';

register_activation_hook( HARBOUR_CORE_FILE, array( 'Harbour_Core', 'activate' ) );
register_deactivation_hook( HARBOUR_CORE_FILE, array( 'Harbour_Core', 'deactivate' ) );

Harbour_Core::boot();
