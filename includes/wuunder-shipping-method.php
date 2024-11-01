<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins') ) ) ) {

    function wuunder_shipping_method()
    {
        if ( !class_exists( 'WC_wuunder_shipping' ) ) {
            class WC_wuunder_shipping extends WC_Shipping_Method
            {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct( $instance_id = 1 ) {
                    $this->id = 'wuunder_shipping';
                    $this->instance_id = absint( $instance_id );
                    $this->method_title = __( 'Wuunder Shipping' );
                    $this->method_description = __( 'Method name that will be shown in checkout while no service has been selected.' );
                    // $this->enabled            = ( 'yes' === $this->get_option( 'enabled') ) ? $this->get_option( 'enabled') : 'no';
                    $this->enabled = 'yes';
                    $this->title = WuunderUtil::get_checkout_description();
                    // These are the options set by the user
                    $this->cost = $this->get_option( 'cost' );
                    $this->carriers = $this->get_option( 'select_carriers');
                    // $this->init();
                }

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {
                    
                    if (!isset($_POST['post_data'])) {
                        $price = 0;
                        $service_name = '';
                    } else {
                        $post_data_str = sanitize_text_field($_POST['post_data']);
                        parse_str($post_data_str, $post_data);
                        $cart_data = $this->_wuunder_get_cart_data_by_checkout_token($post_data['wuunder_checkout_token']);
                        if (empty($cart_data)) {
                            $price = 0;
                            $service_name = '';
                        } else {
                            $price = $cart_data['shipment_price'];
                            $service_name = " {$cart_data['name']}";
                        }
                    }
                    $rate = array(
                        'id'        => $this->id,
                        'label'     => (empty($service_name) ? $this->title : $service_name),
                        'cost'      => $price,
                        'calc_tax'  => 'per_item'
                    );

                    // Register the rate
                    $this->add_rate( $rate );
                }

                private function _wuunder_get_cart_data_by_checkout_token($checkout_token) {
                    if (empty($checkout_token)) {
                        return false;
                    }

                    $api_key = WuunderUtil::get_api_key();
                    $wuunder_api_base_url = WuunderUtil::get_api_base_url() . "checkout/";
                    $wuunder_api_url = "{$wuunder_api_base_url}api/v1/token/{$checkout_token}/service";

                    $response = wp_remote_post( $wuunder_api_url, array(
                        'method'      => 'GET',
                        'timeout'     => 45,
                        'blocking'    => true,
                        'headers'     => array(
                            'Authorization' => "Bearer " . $api_key
                        )
                        )
                    );

                    if ( is_wp_error( $response ) ) {
                        wc_add_notice(__('Could not validate chosen service'), 'error');
                    } else {
                        $data = json_decode($response['body'], true);
                        if ($data['success'] === true) {
                            return $data['data'];
                        }
                        return false;
                    }
                }

            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'wuunder_shipping_method' );

    function wuunder_shipping( $methods ) {
        $methods['wuunder_shipping'] = 'WC_wuunder_shipping';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'wuunder_shipping' );
}