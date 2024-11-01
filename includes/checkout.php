<?php
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

add_action('woocommerce_package_rates', 'wpwc_wuunder_filter_out_other_shipping_methods');
add_action('woocommerce_review_order_before_payment', 'wpwc_wuunder_add_link_shipping_rates');
add_action('woocommerce_review_order_before_payment', 'wpwc_wuunder_add_precondition_select_shipping_rates_text');
add_action('woocommerce_checkout_process', 'wpwc_wuunder_validate_checkout');
add_action('wp_ajax_wuunder_request_checkout_token', 'wuunder_request_checkout_token');
add_action('wp_ajax_nopriv_wuunder_request_checkout_token', 'wuunder_request_checkout_token');
add_action('woocommerce_after_order_notes', 'wpwc_wuunder_add_checkout_token_field');
add_action('woocommerce_checkout_update_order_meta', 'wpwc_wuunder_update_checkout_token');
add_action('woocommerce_checkout_update_order_review', 'wpwc_wuunder_checkout_update_refresh_shipping_methods', 10, 1);
add_action('woocommerce_checkout_update_order_review', 'wpwc_wuunder_save_all_customer_details');
add_action('woocommerce_after_shipping_rate', 'wpwc_wuunder_add_shipping_text', 20, 2);


add_filter('woocommerce_billing_fields', 'wuunder_checkout_fields_modify_checkout_fields', 2);
add_filter('woocommerce_shipping_fields', 'wuunder_checkout_fields_modify_checkout_fields', 2);



function wuunder_checkout_fields_modify_checkout_fields($fields)
{
    $force_update_fields = explode(",", get_option('wuunder_checkout_force_update_fields', ""));

    foreach ($force_update_fields as $field) {
        if (isset($fields[$field])) {
            $fields[$field]['class'][] = 'update_totals_on_change';
        }
    }

    return $fields;
}

/**
 * Set customer first- and lastname for when the user is not logged in
 */
function wpwc_wuunder_save_all_customer_details($post_data)
{
    global $woocommerce;
    global $order;

    $post = array();
    $vars = explode('&', $post_data);
    foreach ($vars as $k => $value) {
        $v = explode('=', urldecode($value));
        $post[$v[0]] = $v[1];
    }

    //update customer props because we use them when fetching cart data for checkout token request
    WC()->customer->set_props(
        array(
            'billing_first_name' => $post['billing_first_name'],
            'billing_last_name' => $post['billing_last_name'],
            'shipping_first_name' => !empty($post['shipping_first_name']) ? $post['shipping_first_name'] : $post['billing_first_name'],
            'shipping_last_name' => !empty($post['shipping_last_name']) ? $post['shipping_last_name'] : $post['billing_last_name'],
            'shipping_company' => !empty($post['shipping_company']) ? $post['shipping_company'] : $post['billing_company']
        )
    );

    WC()->session->set('wuunder_checkout_post_data', $post);
}

function wpwc_wuunder_add_shipping_text($method)
{
    if ($method->id === "wuunder_shipping") {

        if (!isset($_POST['post_data'])) {
            return;
        }
        $post_data_str = sanitize_text_field($_POST['post_data']);
        parse_str($post_data_str, $post_data);
        $api_key = WuunderUtil::get_api_key();
        $wuunder_api_base_url = WuunderUtil::get_api_base_url() . "checkout/";
        $wuunder_api_url = "{$wuunder_api_base_url}api/v1/token/{$post_data['wuunder_checkout_token']}/service";

        $response = wp_remote_post(
            $wuunder_api_url,
            array(
                'method'      => 'GET',
                'timeout'     => 45,
                'blocking'    => true,
                'headers'     => array(
                    'Authorization' => "Bearer " . $api_key
                )
            )
        );

        if (is_wp_error($response)) {
            wc_add_notice(__('Please choose a shipping service.') . ' (Error-code: WCE-107)', 'error');
        } else {
            if ($response['response']['code'] === 404) {
                // commented: in some cases session remembers checkout token longer than cart session on wuunderconnect.
                // echo '<p style="color:red">Could not find services, please contact the webshop owner (Error-code: WCE-108)</p>';
                return;
            }
            $data = json_decode($response['body'], true);
            if ($data['success'] === true) {
                echo WuunderUtil::esc_html($data['data']['additional_info']);
            }
            return;
        }
    }
}

// Field added for the checkout_token, so that it can be read by php in post values
function wpwc_wuunder_add_checkout_token_field($checkout)
{
    woocommerce_form_field('wuunder_checkout_token', array(
        'type' => 'text',
        'class' => array(
            'wuunder-hidden-checkout-field form-row-wide'
        ),
        'autocomplete' => "new-password"
    ), "");
}

function wuunder_request_checkout_token()
{
    $api_key = WuunderUtil::get_api_key();
    $webshop_id = WuunderUtil::get_webshop_id();
    $wuunder_api_base_url = WuunderUtil::get_api_base_url() . "checkout/";
    $wuunder_api_base_url_frontend = WuunderUtil::get_api_base_url(false, true) . "checkout/";

    $wuunder_api_url = "{$wuunder_api_base_url}api/v1/token/{$webshop_id}";
    $wuunder_frame_url = "{$wuunder_api_base_url_frontend}frame/";

    if (empty($api_key) || empty($webshop_id)) {
        wc_add_notice(__('Something went wrong, please contact the webshop') . ' (Error-code: WCE-100)', 'error');
    }

    $pre_draft = wpwc_wuunder_make_draft($webshop_id);

    $response = wp_remote_post(
        $wuunder_api_url,
        array(
            'method'      => 'POST',
            'timeout'     => 15,
            'blocking'    => true,
            'headers'     => array(
                'Authorization' => "Bearer " . $api_key,
                'Content-Type' => "application/json"
            ),
            'body'        => $pre_draft,
        )
    );

    if (is_wp_error($response)) {
        wc_add_notice(__('Something went wrong, please contact the webshop' . ' (Error-code: WCE-101)'), 'error');
    } else {
        $data = json_decode($response['body'], true);
        if ($data['success'] === true) {
            $return = array(
                "success" => true,
                "token" => $data['token'],
                "frame_url" => $wuunder_frame_url . $data['token']
            );
        } else {
            wc_add_notice(__('Something went wrong, please contact the webshop') . ' (Error-code: ' . $data['code'] . ')', 'error');
            $return = array(
                "success" => false
            );
        }

        echo json_encode($return);
    }

    die();
}


function wpwc_wuunder_make_draft($webshop_id, $jsonEncode = true)
{
    $bh_packages =  WC()->cart->get_shipping_packages();
    // https://github.com/woocommerce/woocommerce/blob/master/includes/class-wc-customer.php
    $data['shipping'] = $bh_packages;
    $data['cart'] = WC()->cart;

    $data['customer'] = [
        "shipping" => [
            "firstname" => WC()->customer->get_shipping_first_name(),
            "lastname" => WC()->customer->get_shipping_last_name(),
            "address" =>  WC()->customer->get_shipping_address(),
            "address2" => WC()->customer->get_shipping_address_2(),
            "city" => WC()->customer->get_shipping_city(),
            "postcode" => WC()->customer->get_shipping_postcode(),
            "country" => WC()->customer->get_shipping_country(),
            "company" => WC()->customer->get_shipping_company()
        ],
        "billing" => [
            "firstname" => WC()->customer->get_billing_first_name(),
            "lastname" => WC()->customer->get_billing_last_name(),
            "address" =>  WC()->customer->get_billing_address(),
            "address2" => WC()->customer->get_billing_address_2(),
            "city" => WC()->customer->get_billing_city(),
            "postcode" => WC()->customer->get_billing_postcode(),
            "country" => WC()->customer->get_billing_country(),
            "company" => WC()->customer->get_billing_company()
        ],
    ];

    $post_data_raw = WC()->session->get('wuunder_checkout_post_data');

    //  https://woocommerce.com/document/checkout-field-editor/?_gac=1.183442004.1649325823.Cj0KCQjwl7qSBhD-ARIsACvV1X2G1iRUu2EcwQRUuQfJIAr_MGu3-e4vu4ZCywYoSu1oRu10XKBql7QaAkLzEALw_wcB#add-custom-fields-to-webhooks-api
    if (class_exists('WC_Checkout_Field_Editor')) {
        $fieldgroups = array('billing' => 'billing', 'shipping' => 'shipping', 'additional' => 'additional');
        foreach ($fieldgroups as $fieldgroup => $payload_group) {
            $fakeorder = new WC_Order();
            foreach (wc_get_custom_checkout_fields($fakeorder, array($fieldgroup)) as $field_name => $field_options) {
                if ($field_name == 'billing_housenumber' || $field_name == 'shipping_housenumber' || $field_name == 'billing_housenr' || $field_name == 'shipping_housenr') {
                    if (isset($post_data_raw[$field_name])) {
                        $data['customer'][$fieldgroup]['housenumber'] = $post_data_raw[$field_name];
                    }
                }
            }
        }
        if (empty($data['customer']['shipping']['housenumber'])) {
            if (isset($data['customer']['billing']['housenumber'])) {
                $data['customer']['shipping']['housenumber'] = $data['customer']['billing']['housenumber'];
            }
        }
    }

    $return['webshop'] = $webshop_id;
    $return['cart_session'] = $data;

    $return['cart_session_checksum'] = crc32(strtolower(json_encode($data)));

    $return['cart_session']['post_data_raw'] = $post_data_raw;

    if (!$jsonEncode) {
        return $return;
    }

    return json_encode($return);
}

function wpwc_wuunder_checkout_update_refresh_shipping_methods($post_data)
{
    /**
     * Disables cache of shipping rates in checkout, needed to show changes in shipping costs
     */
    $packages = WC()->cart->get_shipping_packages();
    foreach ($packages as $package_key => $package) {
        WC()->session->set('shipping_for_package_' . $package_key, false);
    }
}

function wpwc_wuunder_invisible_methods($output_data)
{
    unset($output_data['wuunder_shipping']);
    unset($output_data['wuunder_backup_shipping']);
    return $output_data;
}

/**
 * Filter out all other shipping methods except the wuunder shipping method.
 */
function wpwc_wuunder_filter_out_other_shipping_methods($data)
{
    if (WuunderUtil::get_status_plugin() && WuunderUtil::get_status_checkout()) {
        $wuunder_status = wpwc_check_wuunder_status();
        if ($wuunder_status) {
            $output_data = array_filter($data, function ($k) {
                return ($k == 'wuunder_shipping');
            }, ARRAY_FILTER_USE_KEY);
        } else if (get_option('wuunder_use_custom_backup') === "yes") {
            $output_data = array_filter($data, function ($k) {
                return ($k == 'wuunder_backup_shipping');
            }, ARRAY_FILTER_USE_KEY);
        } else {
            $output_data = wpwc_wuunder_invisible_methods($data);
        }
    } else {
        $output_data = wpwc_wuunder_invisible_methods($data);
    }

    if (count($output_data) === 0) {
        wc_add_notice(__('Could not load Wuunder shipping method, please contact webshop owner') . ' (Error-code: WCE-102)', 'error');
    }

    return $output_data;
}

function wpwc_wuunder_add_link_shipping_rates()
{
    if (WuunderUtil::get_status_plugin() && WuunderUtil::get_status_checkout() && wpwc_check_wuunder_status()) {

        $btn_settings = WuunderUtil::get_checkout_button_settings();
        $invalidResponseErrorMessage = __("Something went wrong. Please contact webshop owner");
        $btn_settings['btn_text'] = __($btn_settings['btn_text']);

        $output = "
                <div class=\"wuunder-container\">
                    <button id=\"openIframe\" onclick=\"event.preventDefault()\" style=\"{$btn_settings['btn_css']}\">{$btn_settings['btn_text']}</button><br/><br/>
                    <div id=\"wuunderWrapper\">
                        <div id=\"wuunderBackdrop\"></div>
                        <div id=\"wuunderIframeContainer\">
                            <iframe id=\"wuunderIframe\"></iframe>
                        </div>
                    </div>
                <div id=\"wuunderCheckoutLoader\" class=\"wuunder-loader\"></div>
                <script>
                    onWuunderButtonLoad();
                    const invalidResponseErrorMessage = '{$invalidResponseErrorMessage}';
                </script>";
        echo WuunderUtil::esc_html($output);
    }
}

// Copied wc_add_notice styling. There doesn't seem to be a way to just get the text
// generated by the wc_add_notice function. Therefore, copy notice and hide it. It
// will be shown when user tries to open shipping rates without filled in address info.
function wpwc_wuunder_add_precondition_select_shipping_rates_text()
{
    if (WuunderUtil::get_status_plugin() && WuunderUtil::get_status_checkout() && wpwc_check_wuunder_status()) {
        $output = '<div class="woocommerce-notices-wrapper" id="wuunder_add_precondition_select_shipping_rates_text" hidden>';
        $output .= '<ul class="woocommerce-error" role="alert">';
        $output .= '<li>';
        $output .= 'Wuunder Shipping Rate cannot be selected yet. Please fill in address information (street name / house number, zipcode and city)! (Error-code: WCE-106)';
        $output .= '</li>';
        $output .= '</ul>';
        $output .= '</div>';
        echo WuunderUtil::esc_html($output);
    }
}

function wpwc_wuunder_validate_cart_session($checkout_token)
{
    $api_key = WuunderUtil::get_api_key();
    $webshop_id = WuunderUtil::get_webshop_id();
    $wuunder_api_base_url = WuunderUtil::get_api_base_url() . "checkout/";

    $cart_session = wpwc_wuunder_make_draft($webshop_id, false);
    $cart_session_checksum = $cart_session['cart_session_checksum'];

    $wuunder_api_url = "{$wuunder_api_base_url}api/v1/validateCartSessionByToken/{$checkout_token}/{$cart_session_checksum}";

    $response = wp_remote_post(
        $wuunder_api_url,
        array(
            'method'      => 'GET',
            'timeout'     => 45,
            'blocking'    => true,
            'headers'     => array(
                'Authorization' => "Bearer " . $api_key
            ),
        )
    );

    if (is_wp_error($response)) {
        return false;
    } else {
        // var_dump($response['body']); exit;
        $data = json_decode($response['body'], true);
        if ($data['success'] === true) {
            if ($data['service_selected'] == false) {
                wc_add_notice(__('No service selected, please select a service before completing the order'), 'error');
                return false;
            }
            return true;
        }
        return false;
    }
}


/**
 * Validate checkout data when trying to submit
 */
function wpwc_wuunder_validate_checkout()
{
    $errors = false;
    $checkout_token = sanitize_text_field($_POST['wuunder_checkout_token']);
    if (!WuunderUtil::get_status_checkout()) {
        return;
    }
    if (!wpwc_check_wuunder_status()) {
        return;
    }
    if ('wuunder_shipping' !== sanitize_text_field($_POST['shipping_method'][0])) {
        wc_add_notice(__('Unavailable shipping method. Please choose again.') . ' (Error-code: WCE-103)', 'error');
        $errors = true;
    } else {
        if (empty($checkout_token)) {
            wc_add_notice(__('Please choose a shipping service.') . ' (Error-code: WCE-104)', 'error');
            $errors = true;
        }
        if (!wpwc_wuunder_validate_cart_session($checkout_token)) {
            wc_add_notice(__('Please choose a shipping service.') . ' (Error-code: WCE-105)', 'error');
            $errors = true;
        }
    }
    if (!$errors) {
        // wuunder_mark_cart_session_as_order_placed($checkout_token);
    }
}

add_action('woocommerce_checkout_order_processed', 'action_checkout_order_processed', 10, 1);
function action_checkout_order_processed($order_id)
{
    // get an instance of the order object
    $order = wc_get_order($order_id);
    $checkout_token = null;

    foreach ($order->get_data()['meta_data'] as $meta_data) {
        if ($meta_data->key === 'wuunder_checkout_token') {
            $checkout_token = $meta_data->value;
        }
    }
    wpwc_wuunder_mark_cart_session_as_order_placed($checkout_token, $order_id);
    $additional_data = wpwc_wuunder_get_service_additional_data($checkout_token);
    if (!empty($additional_data) && isset($additional_data['parcelshop'])) {
        $parcelshop_data = $additional_data['parcelshop'];

        $old_shipping_address = $order->get_address("shipping");
        $order->set_shipping_address_1("{$parcelshop_data['address']['street_name']} {$parcelshop_data['address']['house_number']}");
        $order->set_shipping_city($parcelshop_data['address']['city']);
        $order->set_shipping_postcode($parcelshop_data['address']['zip_code']);
        $order->set_shipping_country($parcelshop_data['address']['alpha2']);
        $order->update_meta_data("wuunder_original_shipping_address", json_encode($old_shipping_address));
        $order->save();
    }
}

function wpwc_wuunder_get_service_additional_data($checkout_token)
{
    $api_key = WuunderUtil::get_api_key();
    $wuunder_api_base_url = WuunderUtil::get_api_base_url() . "checkout/";
    $wuunder_api_url = "{$wuunder_api_base_url}api/v1/token/{$checkout_token}/service";

    $response = wp_remote_post(
        $wuunder_api_url,
        array(
            'method'      => 'GET',
            'timeout'     => 45,
            'blocking'    => true,
            'headers'     => array(
                'Authorization' => "Bearer " . $api_key
            )
        )
    );

    if (is_wp_error($response)) {
        wc_add_notice(__('Could not validate chosen service'), 'error');
        return null;
    } else {
        $data = json_decode($response['body'], true);
        if ($data['success'] === true) {
            return $data['data']['additional_data'];
        }
        return null;
    }
}

function wpwc_wuunder_mark_cart_session_as_order_placed($checkout_token, $order_id)
{
    $api_key = WuunderUtil::get_api_key();
    $wuunder_api_base_url = WuunderUtil::get_api_base_url() . "checkout/";

    $wuunder_api_url = "{$wuunder_api_base_url}api/v1/cartsession/{$checkout_token}/placed/{$order_id}";

    $response = wp_remote_post(
        $wuunder_api_url,
        array(
            'method'      => 'GET',
            'timeout'     => 45,
            'blocking'    => true,
            'headers'     => array(
                'Authorization' => "Bearer " . $api_key
            )
        )
    );

    if (is_wp_error($response)) {
        return false;
    } else {
        $data = json_decode($response['body'], true);
        if ($data['success'] === true) {
            return true;
        }
        return false;
    }
}

function wpwc_wuunder_update_checkout_token($order_id)
{
    if (!empty($_POST['wuunder_checkout_token'])) {
        update_post_meta($order_id, 'wuunder_checkout_token', sanitize_text_field($_POST['wuunder_checkout_token']));
    }
}

function wpwc_check_wuunder_status()
{
    $wuunder_api_base_url = WuunderUtil::get_api_base_url();
    $wuunder_webshop_id = WuunderUtil::get_webshop_id();
    $webshop_base_url = urlencode(home_url());
    $wuunder_api_url = "{$wuunder_api_base_url}api/v1/integrations/plugin?baseurl={$webshop_base_url}";
    $wuunder_api_key = WuunderUtil::get_api_key();

    $response = wp_remote_post($wuunder_api_url, array(
        'method'      => 'GET',
        'timeout'     => 15,
        'blocking'    => true,
        'headers'     => array(
            'Content-Type' => "application/json"
        )
    ));



    if (is_wp_error($response)) {
        return false;
    }

    try {
        $data = json_decode($response['body'], true);
        return $data['success'] === true;
    } catch (Exception $e) {
        return false;
    }
}
