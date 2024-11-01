<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins') ) ) ) {

    function wuunder_backup_shipping_method()
    {
        if ( !class_exists( 'WC_wuunder_backup_shipping' ) ) {
            class WC_wuunder_backup_shipping extends WC_Shipping_Method
            {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct( $instance_id = 1 ) {
                    $this->id = 'wuunder_backup_shipping';
                    $this->instance_id = absint( $instance_id );
                    $this->method_title = __( 'Wuunder Shipping' );
                    $this->method_description = __( 'Method name that will be shown in checkout while no service has been selected.' );
                    // $this->enabled            = ( 'yes' === $this->get_option( 'enabled') ) ? $this->get_option( 'enabled') : 'no';
                    $this->enabled = 'yes';
                    $this->title = WuunderUtil::get_checkout_description();
                    // These are the options set by the user
                    $this->cost = $this->get_option( 'cost' );
                    // $this->carriers = $this->get_option( 'select_carriers');
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
                    $rate = array(
                        'id'        => $this->id,
                        'label'     => $this->method_title,
                        'cost'      => floatval(str_replace(",", ".", get_option( 'wuunder_custom_backup_price' ))),
                        'calc_tax'  => 'per_item'
                    );

                    // Register the rate
                    $this->add_rate( $rate );
                }

            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'wuunder_backup_shipping_method' );

    function wuunder_backup_shipping( $methods ) {
        $methods['wuunder_backup_shipping'] = 'WC_wuunder_backup_shipping';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'wuunder_backup_shipping' );
}