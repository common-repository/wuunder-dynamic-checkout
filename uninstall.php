<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       wuunder
 * @since      3.2.1
 *
 * @package    Wuunder
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = array(
    'wuunder_api_environment',
    'wuunder_setup_success',
    'wuunder_webshop_id',
    'wuunder_api_key',
    'wuunder_test_webshop_id',
    'wuunder_test_api_key',
    'wuunder_plugin_status',
    'wuunder_btn_css',
    'wuunder_checkout_description',
    'wuunder_btn_text',
    'wuunder_enable_shipping_calc_disabled',
    'wuunder_plugin_status',
    'wuunder_plugin_environment'
);

foreach ( $options as $option ) {
    if ( get_option($option ) ) {
        delete_option( $option );
    }
}

global $wpdb;
$results = $wpdb->get_results( "SELECT webhook_id, name FROM {$wpdb->prefix}wc_webhooks" );
foreach($results as $result)
{
    if(strpos($result->name, 'wuunder') !== false)
    {
        $wh = new WC_Webhook();
        $wh->set_id($result->webhook_id);
        $wh->delete();
    }
}