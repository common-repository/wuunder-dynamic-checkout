<?php

/**
 * Fired during plugin activation
 *
 * @link       wuunder
 * @since      3.2.1
 *
 * @package    Wuunder
 * @subpackage Wuunder/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      3.2.1
 * @package    Wuunder
 * @subpackage Wuunder/includes
 * @author     CustommerConnections <Custommerconnections@wearewuunder.com>
 */
class Wuunder_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    3.2.1
	 */
	public static function activate() {
		update_option("wuunder_plugin_status", "disable");
		update_option("wuunder_plugin_environment", "playground");
		if(get_option('woocommerce_enable_shipping_calc') == 'yes'){
			update_option('woocommerce_enable_shipping_calc', 'no');
			update_option('wuunder_enable_shipping_calc_disabled', 'yes');
		}	
	}

}
