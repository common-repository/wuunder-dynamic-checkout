<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_Wuunder_Connector_Settings')) :

    /**
     * Adds Settings Interface to WooCommerce Settings Tabs
     *
     * @class 		WC_Wuunder_Connector_Settings
     * @version		3.2.1
     * @author 		Vendidero
     */
    class WC_Wuunder_Connector_Settings extends \WC_Settings_Page
    {

        public $premium_sections = array();

        /**
         * Adds Hooks to output and save settings
         */
        public function __construct()
        {
            $this->id    = 'wuunder_connector';
            $this->label = __('Wuunder Shipment', 'woocommerce-wuunder');

            add_filter('woocommerce_settings_tabs_array', array(&$this, 'wcwp_add_settings_tab'), 50);
            add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
            add_action('woocommerce_settings_' . $this->id, array($this, 'wcwp_toggle_save'));
            add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
        }

        public function wcwp_add_settings_tab($settings_tabs)
        {
            $settings_tabs['wuunder_connector'] = __('Wuunder Connector', 'wuunder_connector');
            return $settings_tabs;
        }

        public function wcwp_toggle_save()
        {

            if (empty(get_option('wuunder_setup_success'))) {
                $GLOBALS['hide_save_button'] = true;
            } else {
                $GLOBALS['hide_save_button'] = false;
            }
        }

        public function wcwp_get_plugin_environment()
        {
            $settings =
                array(
                    'section_title'     => array(
                        'name'      => __('Environment', 'wuunder_connector'),
                        'type'      => 'title',
                        'id'        => 'wuunder_section_title',
                        'desc' => 'Setup the enviroment.'
                    ),
                    'api_environment'        => array(
                        // 'name'      => __( 'Environment', 'wuunder_connector' ),
                        'type'      => 'radio',
                        // 'desc'      => __( 'Yes = Test / staging, No = Live / production', 'wuunder_connector' ),
                        'options'   => array(
                            //uncomment row below for testing purposes
                            // 'localhost'   => __( 'Local', 'wuunder_connector' ),
                            'playground'   => __('Playground', 'wuunder_connector'),
                            'production' => __('Production', 'wuunder_connector')
                        ),
                        'id'        => 'wuunder_api_environment',
                        'default'   => 'playground'
                    ),
                    'section_end'           => array(
                        'type'      => 'sectionend',
                        'id'        => 'wuunder_section_end'
                    ),
                );
            return apply_filters('wuunder_settings', $settings);
        }

        public function wcwp_get_checkout_status()
        {
            $settings =
                array(
                    'section_title'     => array(
                        'name'      => __('Status checkout', 'wuunder_connector'),
                        'type'      => 'title',
                        'id'        => 'wuunder_section_title',
                        'desc' => 'After changing this setting to disabled, check your shipping settings.'
                    ),
                    'status'        => array(
                        // 'name'      => __( 'Status', 'wuunder_connector' ),
                        'type'      => 'radio',
                        'options'   => array(
                            'disable' => __('disable', 'wuunder_connector'),
                            'enable'   => __('enable', 'wuunder_connector')
                        ),
                        'id'        => 'wuunder_checkout_status',
                        'default' => 'disable'
                    ),
                    'section_end'           => array(
                        'type'      => 'sectionend',
                        'id'        => 'wuunder_section_end'
                    ),
                );
            return apply_filters('wuunder_settings', $settings);
        }

        public function wcwp_get_plugin_status()
        {
            $settings =
                array(
                    'section_title'     => array(
                        'name'      => __('Status plugin', 'wuunder_connector'),
                        'type'      => 'title',
                        'id'        => 'wuunder_section_title',
                        'desc' => 'When disabled the Wuunder plugin will not work.'
                    ),
                    'status'        => array(
                        // 'name'      => __( 'Status', 'wuunder_connector' ),
                        'type'      => 'radio',
                        'options'   => array(
                            'disable' => __('disable', 'wuunder_connector'),
                            'enable'   => __('enable', 'wuunder_connector')
                        ),
                        'id'        => 'wuunder_plugin_status',
                    ),
                    'section_end'           => array(
                        'type'      => 'sectionend',
                        'id'        => 'wuunder_section_end'
                    ),
                );
            return apply_filters('wuunder_settings', $settings);
        }

        public function wcwp_get_settings()
        {
            $settings =
                array(
                    'section_title'     => array(
                        'name'      => __('Production settings', 'wuunder_connector'),
                        'type'      => 'title',
                        'id'        => 'wuunder_section_title'
                    ),
                    'webshop'               => array(
                        'name'      => __('Webshop Key', 'wuunder_connector'),
                        'type'      => 'text',
                        'id'        => 'wuunder_webshop_id'
                    ),
                    'api'               => array(
                        'name'      => __('API Key', 'wuunder_connector'),
                        'type'      => 'text',
                        'id'        => 'wuunder_api_key'
                    ),
                    'section_end'           => array(
                        'type'      => 'sectionend',
                        'id'        => 'wuunder_section_end'
                    ),
                );
            return apply_filters('wuunder_settings', $settings);
        }

        public function wcwp_get_test_settings()
        {
            $settings =
                array(
                    'section_title'     => array(
                        'name'      => __('Test settings', 'wuunder_connector'),
                        'type'      => 'title',
                        'id'        => 'wuunder_section_title'
                    ),
                    'test_webshop'               => array(
                        'name'      => __('Webshop key', 'wuunder_connector'),
                        'type'      => 'text',
                        'id'        => 'wuunder_test_webshop_id'
                    ),
                    'test_api'          => array(
                        'name'      => __('API Key', 'wuunder_connector'),
                        'type'      => 'text',
                        'id'        => 'wuunder_test_api_key'
                    ),
                    'section_end'           => array(
                        'type'      => 'sectionend',
                        'id'        => 'wuunder_section_end'
                    ),
                );
            return apply_filters('wuunder_settings', $settings);
        }

        public function wcwp_get_wuunder_shipping_settings()
        {
            $settings =
                array(
                    'section_title'     => array(
                        'name'      => __('Settings for webshop checkout', 'wuunder_connector'),
                        'type'      => 'title',
                        'id'        => 'wuunder_section_title'
                    ),
                    'checkout_description'               => array(
                        'name'      => __('Checkout shipping description', 'wuunder_connector'),
                        'type'      => 'text',
                        'id'        => 'wuunder_checkout_description',
                        'default'   => __('Choose a shipping method'),
                        'desc'      => __('Method name that will be shown in checkout while no service has been selected.')
                    ),
                    'wuunder_btn_text'               => array(
                        'name'      => __('Checkout button text', 'wuunder'),
                        'type'      => 'text',
                        'id'        => 'wuunder_btn_text',
                        'default'   => __('Choose a Wuunder Shipping Rate!'),
                        'desc'      => __('Text for the shipping rates button in the checkout')
                    ),
                    'wuunder_btn_css'               => array(
                        'name'      => __('Checkout button css styling', 'wuunder'),
                        'type'      => 'textarea',
                        'id'        => 'wuunder_btn_css',
                        'default'   => 'background-color: #94d600;
border: none;
color: black;
padding: 15px 32px;
border-radius: 12px;
text-align: center;
text-decoration: none;
display: inline-block;
font-size: 16px;',
                        'desc'      => __('Edit or add CSS style settings for checkout button.'),
                        'css'       => 'height: 190px;'
                    ),
                    'wuunder_use_custom_backup'               => array(
                        'name'      => __('Use wuunder custom backup method', 'wuunder'),
                        'type'      => 'checkbox',
                        'id'        => 'wuunder_use_custom_backup',
                        'default'   => false,
                        'desc'      => __('When Wuunder services are down, use custom backup method. If disabled woocommerce zones will be used')
                    ),
                    'wuunder_custom_backup_price'               => array(
                        'name'      => __('Use wuunder custom backup method', 'wuunder'),
                        'type'      => 'text',
                        'id'        => 'wuunder_custom_backup_price',
                        'default'   => 5,
                        'desc'      => __('Cost of the custom wuunder backup method')
                    ),
                    'wuunder_checkout_force_update_fields'               => array(
                        'name'      => __('Fields to force save on change', 'wuunder'),
                        'type'      => 'text',
                        'id'        => 'wuunder_checkout_force_update_fields',
                        'default'   => "",
                        'desc'      => __('Comma seperated, no spaces. Can be used in combination with checkout field editor.')
                    ),
                    'section_end'           => array(
                        'type'      => 'sectionend',
                        'id'        => 'wuunder_section_end'
                    ),
                );
            return apply_filters('wuunder_settings', $settings);
        }



        public function output()
        {
            if (isset($_POST['wcwp_error'])) {
                echo '<div id="message" class="error inline"><p style="font-weight: bold;">' . $_POST['wcwp_error'] . '</p></div>';
            }
            echo '<div style="background-color:#fff; border:1px solid #CCCCCC; margin-bottom:10px; padding:10px;">
            <img src="' . WUUNDER_PLUGIN_URL . 'assets/images/wuunder_logo.png" style="float:left; display:inline-block; padding:20px 30px 20px 20px; width:80px;">
            <h4>' . __('Hello, We Are Wuunder!', 'wuunder_connector') . '</h4>
            <p>' . __('Thank you for installing our WooCommerce plug-in. With our platform, your shipping process will become as easy and convenient as possible.', 'wuunder_connector') . '</p>
            <p>' . __('Visit our', 'wuunder_connector') . ' <a href="http://www.wearewuunder.com/" target="_blank">website</a> ' . __('for more information. If you have any questions, please contact us via', 'wuunder_connector') . ' <a href="mailto:info@WeAreWuunder.com" target="_blank">info@wearewuunder.com</a>.</p><p>Plugin version: ' . WUUNDER_VERSION . '</p></div>';

            global $wp;
            $current_url = home_url(add_query_arg($wp->query_vars, $wp->request));
            $querySymbol = str_contains($current_url, '?') ? '&' : '?';

            if (empty(get_option('wuunder_setup_success'))) {
                echo '<div style="background-color:#fff; border:1px solid #CCCCCC; margin-bottom:10px; padding:10px;">';
                echo '<div class="row" style="overflow:hidden; width: 100%;">';
                echo "<p>Before you're able to use our plugin, you'll first need to setup your connection to Mywuunder</p>";
                echo '<a href="' . $current_url . $querySymbol . 'wuunder_setup=true&staging=true" class="button-primary">Setup Staging</a><br/><br/>';
                echo '<a href="' . $current_url . $querySymbol . 'wuunder_setup=true" class="button-primary">Setup Production</a>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="row" style="overflow:hidden; width: 100%;">';
                echo '<div class="wuunder-settings" style="width: 30%;float: left;">';
                WC_Admin_Settings::output_fields($this->wcwp_get_plugin_environment());
                echo '</div>';
                echo '<div class="wuunder-settings" style="width: 30%;float: left;">';
                WC_Admin_Settings::output_fields($this->wcwp_get_plugin_status());
                echo '</div>';
                echo '<div class="wuunder-settings" style="width: 30%;float: left;">';
                WC_Admin_Settings::output_fields($this->wcwp_get_checkout_status());
                echo '</div>';
                echo '</div>';

                echo '<div class="wuunder-settings">';
                WC_Admin_Settings::output_fields($this->wcwp_get_wuunder_shipping_settings());
                echo '</div>';

                echo '<div class="row" style="overflow:hidden; width: 100%;">';
                echo '<div class="wuunder-settings">';

                if (!empty(get_option('wuunder_webshop_id'))) {
                    WC_Admin_Settings::output_fields($this->wcwp_get_settings());
                    echo '</div>';
                    echo '<div class="row" style="overflow:hidden; width: 100%;">';
                    echo '<div class="wuunder-settings">';
                } else {
                    echo '<h2>Production settings</h2>';
                    echo '<div class="row" style="overflow:hidden;">';
                    echo '<a href="' . $current_url . $querySymbol . '&wuunder_setup=true" class="button-primary">Setup Production</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="wuunder-settings">';
                }

                if (!empty(get_option('wuunder_test_webshop_id'))) {
                    WC_Admin_Settings::output_fields($this->wcwp_get_test_settings());
                    echo '</div>';
                    echo '<div class="row" style="overflow:hidden; width: 100%;">';
                    echo '<div class="wuunder-settings">';
                } else {
                    echo '<h2>Staging settings</h2>';
                    echo '<div class="row" style="overflow:hidden;">';
                    echo '<a href="' . $current_url . $querySymbol . '&wuunder_setup=true&staging=true" class="button-primary">Setup Staging</a><br/><br/>';
                    echo '</div>';
                    echo '</div>';
                }
            }
        }

        /**
         * Save settings
         */
        public function save()
        {
            WC_Admin_Settings::save_fields($this->wcwp_get_plugin_status());
            $success = $this->before_save();

            $errorMessage = '<ul>';
            $warnings = false;

            if (!$success['selected_env_is_setup']) {
                $warnings = true;
                $errorMessage .= ' ' . __('<li>- <b>Selected environment is not set up! Please complete onboarding for selected environment.</b></li>');
            } else {
                WC_Admin_Settings::save_fields($this->wcwp_get_plugin_environment());
            }


            if (!$success['playground'] && !empty(get_option('wuunder_test_webshop_id'))) {
                $warnings = true;
                $errorMessage .= __('<li>- <b>Unable to save playground webshop integration settings. Could not verify test webshop_id or test api key.</b></li>');
            }

            if (!$success['production'] && !empty(get_option('wuunder_webshop_id'))) {
                $warnings = true;
                $errorMessage .= ' ' . __('<li>- <b>Unable to save production webshop integration settings. Could not verify webshop_id or api key.</b></li>');
            }

            if ($warnings) {
                $errorMessage .= '</ul>';
                $_POST['wcwp_error'] = trim($errorMessage);
            }

            if ($success['production']) {
                WC_Admin_Settings::save_fields($this->wcwp_get_settings());
            }

            if ($success['playground']) {
                WC_Admin_Settings::save_fields($this->wcwp_get_test_settings());
            }

            WC_Admin_Settings::save_fields($this->wcwp_get_wuunder_shipping_settings());
            WC_Admin_Settings::save_fields($this->wcwp_get_checkout_status());

            if (get_option('wuunder_plugin_status') === 'disable') {
                disable_all_webhooks();
            } else {
                // $targetEnvironment = $_POST['wuunder_api_environment'];
                delete_wuunder_webhooks();
                create_webhook();
                // switch_status_wuunder_webhooks($targetEnvironment);
            }
        }

        public function before_save()
        {
            $success = [
                'playground' => false,
                'production' => false,
                'selected_env_is_setup' => false
            ];

            $prod_webshop_id = get_option('wuunder_webshop_id');
            $play_webshop_id = get_option('wuunder_test_webshop_id');

            if (!empty($prod_webshop_id) && !empty($play_webshop_id)) {
                $success['selected_env_is_setup'] = true;
            } elseif ($_POST['wuunder_api_environment'] == 'playground' && !empty($play_webshop_id)) {
                $success['selected_env_is_setup'] = true;
            } elseif ($_POST['wuunder_api_environment'] == 'production' && !empty($prod_webshop_id)) {
                $success['selected_env_is_setup'] = true;
            }

            // check both environments only if configured.
            if (!empty($prod_webshop_id)) {
                $success['production'] = $this->check_mywuunder_settings('production');
            }

            if (!empty($play_webshop_id)) {
                $success['playground'] = $this->check_mywuunder_settings('playground');
            } elseif (empty(get_option('wuunder_test_webshop_id')) && empty(get_option('wuunder_webshop_id'))) {
                //Set both to true if there's no config for either, which really should never be the case. Maybe when testing.
                $success = [
                    'playground' => true,
                    'production' => true,
                    'selected_env_is_setup' => false
                ];
            }

            return $success;
        }

        public function check_mywuunder_settings($environment)
        {
            $webshop_id_var = $environment == 'production' ? 'wuunder_webshop_id' : 'wuunder_test_webshop_id';
            $api_id_var = $environment == 'production' ? 'wuunder_api_key' : 'wuunder_test_api_key';
            $wuunder_api_base_url = WuunderUtil::get_api_base_url($environment) . "checkout/";
            $wuunder_api_url = "{$wuunder_api_base_url}api/v1/auth/{$_POST[$webshop_id_var]}";

            $response = wp_remote_post(
                $wuunder_api_url,
                array(
                    'method'      => 'GET',
                    'timeout'     => 15,
                    'blocking'    => true,
                    'headers'     => array(
                        'Authorization' => "Bearer " . $_POST[$api_id_var],
                        'Content-Type' => "application/json"
                    )
                )
            );

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
    }

endif;
