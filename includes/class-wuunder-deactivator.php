<?php

/**
 * Fired during plugin deactivation
 *
 * @link       wuunder
 * @since      3.2.1
 *
 * @package    Wuunder
 * @subpackage Wuunder/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      3.2.1
 * @package    Wuunder
 * @subpackage Wuunder/includes
 * @author     CustommerConnections <Custommerconnections@wearewuunder.com>
 */
class Wuunder_Deactivator
{

	/**
	 * Send deactivation request
	 *
	 * @since    3.2.1
	 */
	public static function deactivate()
	{
		$wuunder_api_url = WuunderUtil::get_api_base_url() . "integrations/woocommerce/deactivate/";
		$api_key = WuunderUtil::get_api_key();
		$webshop_id = WuunderUtil::get_webshop_id();
		$body = array(
			"webshop_id" => $webshop_id
		);

		$response = wp_remote_post($wuunder_api_url, array(
			'method'      => 'POST',
			'data_format' => 'body',
			'headers'     => array(
				'Authorization' => "Bearer " . $api_key,
				'Content-Type' => "application/json"
			),
			'body'       => json_encode($body),
		));

		delete_wuunder_webhooks();

		try {
			$data = json_decode($response['body'], true);
			return $data['success'] === true;
		} catch (Exception $e) {
			return false;
		}

		if (get_option('wuunder_enable_shipping_calc_disabled') == 'yes') {
			update_option('woocommerce_enable_shipping_calc', 'yes');
			delete_option('wuunder_enable_shipping_calc_disabled');
		}
	}
}
