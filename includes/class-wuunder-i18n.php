<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       wuunder
 * @since      3.2.1
 *
 * @package    Wuunder
 * @subpackage Wuunder/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      3.2.1
 * @package    Wuunder
 * @subpackage Wuunder/includes
 * @author     CustommerConnections <Custommerconnections@wearewuunder.com>
 */
class Wuunder_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    3.2.1
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wuunder',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
