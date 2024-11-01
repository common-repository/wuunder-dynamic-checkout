<?php

/**
 * @link              Wuunder
 * @since             3.2.1
 * @package           Wuunder
 *
 * @wordpress-plugin
 * Plugin Name:       Wuunder Dynamic Checkout
 * Plugin URI:        https://wearewuunder.com/eenvoudig-koppelen/webshopmodule/koppelen-woocommerce/
 * Description:       Wuunder Shipping Module with support for dynamic rates
 * Version:           3.2.1
 * Author:            Wuunder
 * Author URI:        https://wearewuunder.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       https://wearewuunder.com
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WUUNDER_VERSION', '3.2.1' );
define( 'WUUNDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action('init', 'wuunder_init');

function wuunder_init() {
    if (!class_exists('WooCommerce')) {
        return;
	}
	
	add_filter( 'woocommerce_get_settings_pages', 'add_wuunder_settings' );
}

require_once plugin_dir_path( __FILE__ ) . 'includes/util.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/checkout.php';
include_once( plugin_dir_path( __FILE__ ) . 'includes/wuunder-shipping-method.php' );
include_once( plugin_dir_path( __FILE__ ) . 'includes/wuunder-backup-shipping-method.php' );
include_once( plugin_dir_path( __FILE__ ) . 'includes/wuunder-setup.php' );

function add_wuunder_settings(array $settings) {
	include_once( plugin_dir_path( __FILE__ ) . 'includes/wuunder-settings.php' );
	$settings[] = new WC_Wuunder_Connector_Settings();
	return $settings;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wuunder-activator.php
 */
function activate_wuunder() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wuunder-activator.php';
	Wuunder_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wuunder-deactivator.php
 */
function deactivate_wuunder() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wuunder-deactivator.php';
	Wuunder_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wuunder' );
register_deactivation_hook( __FILE__, 'deactivate_wuunder' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wuunder.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    3.2.1
 */
function run_wuunder() {

	$plugin = new Wuunder();
	$plugin->run();

}
run_wuunder();
