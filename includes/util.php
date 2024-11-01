<?php

class WuunderUtil
{

    public static function esc_html($html)
    {
        return wp_kses($html, self::get_allow_html());
    }

    public static function get_allow_html()
    {
        $allowed_atts = array(
            'onclick'    => array(),
            'align'      => array(),
            'class'      => array(),
            'type'       => array(),
            'id'         => array(),
            'dir'        => array(),
            'lang'       => array(),
            'style'      => array(),
            'xml:lang'   => array(),
            'src'        => array(),
            'alt'        => array(),
            'hidden'     => array(),
            'href'       => array(),
            'rel'        => array(),
            'rev'        => array(),
            'target'     => array(),
            'novalidate' => array(),
            'type'       => array(),
            'value'      => array(),
            'name'       => array(),
            'tabindex'   => array(),
            'action'     => array(),
            'method'     => array(),
            'for'        => array(),
            'width'      => array(),
            'height'     => array(),
            'data'       => array(),
            'title'      => array(),
        );
        $allowedposttags['form']     = $allowed_atts;
        $allowedposttags['label']    = $allowed_atts;
        $allowedposttags['button']    = $allowed_atts;
        $allowedposttags['input']    = $allowed_atts;
        $allowedposttags['textarea'] = $allowed_atts;
        $allowedposttags['iframe']   = $allowed_atts;
        $allowedposttags['script']   = $allowed_atts;
        $allowedposttags['style']    = $allowed_atts;
        $allowedposttags['strong']   = $allowed_atts;
        $allowedposttags['small']    = $allowed_atts;
        $allowedposttags['table']    = $allowed_atts;
        $allowedposttags['span']     = $allowed_atts;
        $allowedposttags['abbr']     = $allowed_atts;
        $allowedposttags['code']     = $allowed_atts;
        $allowedposttags['pre']      = $allowed_atts;
        $allowedposttags['div']      = $allowed_atts;
        $allowedposttags['img']      = $allowed_atts;
        $allowedposttags['h1']       = $allowed_atts;
        $allowedposttags['h2']       = $allowed_atts;
        $allowedposttags['h3']       = $allowed_atts;
        $allowedposttags['h4']       = $allowed_atts;
        $allowedposttags['h5']       = $allowed_atts;
        $allowedposttags['h6']       = $allowed_atts;
        $allowedposttags['ol']       = $allowed_atts;
        $allowedposttags['ul']       = $allowed_atts;
        $allowedposttags['li']       = $allowed_atts;
        $allowedposttags['em']       = $allowed_atts;
        $allowedposttags['hr']       = $allowed_atts;
        $allowedposttags['br']       = $allowed_atts;
        $allowedposttags['tr']       = $allowed_atts;
        $allowedposttags['td']       = $allowed_atts;
        $allowedposttags['p']        = $allowed_atts;
        $allowedposttags['a']        = $allowed_atts;
        $allowedposttags['b']        = $allowed_atts;
        $allowedposttags['i']        = $allowed_atts;

        return $allowedposttags;
    }

    /**
     * Get base url for mywuunder backend. 
     * When true is passed as parameter, when nessecary correct url for frontend use (clientside/js) will be returned
     * 
     * api environment values "playground" and "localhost" cannot be set via the settings page of the plugin. To use this, you need to manually set it in the database
     */
    public static function get_api_base_url($environment = false, $frontend = false)
    {
        if ($environment) {
            $api_environment = $environment;
        } else {
            $api_environment = get_option('wuunder_api_environment');
        }

        if ($api_environment == 'production') {
            return "https://wuunderconnect.com/";
        } elseif ($api_environment == 'playground') {
            return "https://mywuunder.playground-next.wearewuunder.com/";
        } elseif ($api_environment == 'localhost') {
            if ($frontend) {
                return "http://localhost:8000/";
            }
            return "http://host.docker.internal:8000/";
        } else {
            //staging
            return "https://mywuunder.playground-next.wearewuunder.com/";
        }
    }

    /**
     * Get API key for mywuunder api
     */
    public static function get_api_key()
    {
        $api_environment = get_option('wuunder_api_environment');

        if ('production' == $api_environment) {
            return trim(get_option('wuunder_api_key'));
        } else {
            return trim(get_option('wuunder_test_api_key')); // Return test API key also for playground and localhost
        }
    }

    /**
     * Get webshop unique id for mywuunder api
     */
    public static function get_webshop_id()
    {
        $api_environment = get_option('wuunder_api_environment');

        if ('production' == $api_environment) {
            return trim(get_option('wuunder_webshop_id'));
        } else {
            return trim(get_option('wuunder_test_webshop_id')); // Return test API key also for playground and localhost
        }
    }

    public static function get_status_plugin()
    {

        if ($status = get_option('wuunder_plugin_status')) {
            if ($status == 'enable') {
                return true;
            }
        }
        return false;
    }

    public static function get_status_checkout()
    {

        if ($status = get_option('wuunder_checkout_status')) {
            if ($status == 'enable') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get settings for shipping method button
     **/
    public static function get_checkout_button_settings()
    {

        $settings = [
            'btn_css' => get_option('wuunder_btn_css'),
            'btn_text' => __(get_option('wuunder_btn_text')),
        ];

        $defaults = [
            'btn_css' => 'background-color: #94d600;
border: none;
color: white;
padding: 15px 32px;
border-radius: 12px;
text-align: center;
text-decoration: none;
display: inline-block;
font-size: 16px;',
            'btn_text' => "Choose a Wuunder shipment",
        ];


        foreach ($settings as $name => $value) {
            if (!$value || empty($value)) {
                $settings[$name] = $defaults[$name];
            }
        }

        return $settings;
    }

    /**
     * Get settings for shipping method button
     */
    public static function get_checkout_description()
    {

        $description = get_option('wuunder_checkout_description');

        if (!$description || empty($description)) {
            $description = 'Choose a shipping method';
        }

        return __($description);
    }
}
