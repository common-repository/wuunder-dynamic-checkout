<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       wuunder
 * @since      3.2.1
 *
 * @package    Wuunder
 * @subpackage Wuunder/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wuunder
 * @subpackage Wuunder/public
 * @author     CustommerConnections <Custommerconnections@wearewuunder.com>
 */
class Wuunder_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.2.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.2.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.2.1
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    3.2.1
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wuunder_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wuunder_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wuunder-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    3.2.1
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wuunder_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wuunder_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$wuunder_api_frontend_base_url = WuunderUtil::get_api_base_url(false, true);
		$webshop_id = WuunderUtil::get_webshop_id();
		wp_enqueue_script( "wuunder_external_js", $wuunder_api_frontend_base_url . 'checkout/js/' . $webshop_id . '?ts=' . time(), array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wuunder-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'wuunder_ajax',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

}
