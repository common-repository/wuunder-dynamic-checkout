<?php

// TODO: remove local URL's
// TODO: change playground to staging
// TODO: change staging and production url
// TODO: encrypt data before send

add_action('wp_loaded', function () {
    if (false !== strpos($_SERVER['REQUEST_URI'], 'wuunder_setup=true') && 'GET' === $_SERVER['REQUEST_METHOD']) {
        wcwp_setup_connection();
        exit;
    }
});

add_action('rest_api_init', 'wcwp_register_rest_route');

$API_NAME = 'Wuunder Shipping API';

function wcwp_setup_connection()
{

    global $wp;
    global $API_NAME;
    $current_url = home_url(add_query_arg($wp->query_vars, $wp->request));
    //using this var for the zip script
    $testing = false;
    $environment = 'staging';
    if ($testing) {
        $url = "http://localhost:8000";
    } elseif (isset($_GET['staging'])) {
        $url = "https://mywuunder.playground-next.wearewuunder.com";
    } else {
        $url = "https://my.production-next.wearewuunder.com";
        //for testing with local mw2
        $environment = 'production';
    }

    $api_credentials = wpwc_create_api_credentials($environment);

    $data = array(
        "api_key" => $api_credentials['consumer_key'],
        "api_secret" => $api_credentials['consumer_secret'],
        "base_url" => home_url(),
        "setup_callback_url_plugin" => home_url() . '/wp-json/wuunder/v1/setup',
        "redirect_url_plugin" => admin_url('admin.php?page=wc-settings&tab=wuunder_connector', is_ssl() ? 'https' : 'http'),
        "name" => get_option('blogname'),
        "webshop_type" => "woocommerce",
        "webshop_version" => WOOCOMMERCE_VERSION,
        "environment" => $environment,
    );



    echo WuunderUtil::esc_html('<form id="form" action="' . $url . '/login/plugin" method="POST" style="display:none">');
    foreach ($data as $key => $value) {
        echo WuunderUtil::esc_html('<input type="text" name="' . $key . '" value="' . $value . '" />');
    }
    echo WuunderUtil::esc_html("</form>");
    echo WuunderUtil::esc_html("<script>");
    echo WuunderUtil::esc_html("document.getElementById('form').submit();");
    echo WuunderUtil::esc_html("</script>");
}

function wpwc_remove_old_api_key($environment)
{
    global $wpdb;
    global $API_NAME;

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}woocommerce_api_keys
            WHERE description = %s",
            $API_NAME . ' ' . $environment
        )
    );
}

function wpwc_create_api_credentials($environment)
{
    global $API_NAME;

    wpwc_remove_old_api_key($environment);
    try {
        global $wpdb;
        $consumer_key    = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();

        $wpdb->insert(
            $wpdb->prefix . 'woocommerce_api_keys',
            array(
                'user_id'         => get_current_user_id(),
                'description'     => $API_NAME . ' ' . $environment,
                'permissions'     => 'read_write',
                'consumer_key'    => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key'   => substr($consumer_key, -7),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
    } catch (\Throwable $e) {
        return false;
    }

    $api_credentials = [];
    $api_credentials['consumer_key'] = $consumer_key;
    $api_credentials['consumer_secret'] = $consumer_secret;
    return $api_credentials;
}

function wcwp_register_rest_route()
{
    register_rest_route('wuunder/v1', '/setup', array(
        'methods' => 'POST',
        'callback' => 'wcwp_receive_setup_callback',
        'permission_callback' => '__return_true'
    ));
}

function create_webhook()
{

    $api_environment = get_option('wuunder_api_environment');

    $deliveryURL = WuunderUtil::get_api_base_url($api_environment);
    $webshop_uuid = WuunderUtil::get_webshop_id();

    // $status = get_option('wuunder_plugin_environment') === 'production' ? 'active' : 'disabled';
    // if(get_option('wuunder_plugin_status') === 'disable'){
    // }
    $status = 'disabled';
    $wuunder_plugin_status = get_option('wuunder_plugin_status');
    if ($wuunder_plugin_status === 'enable') {
        $status = 'active';
    }

    $topics = [
        //articles
        ["entity" => "article", "topic" => "product.created", "url_topic" => "create"],
        ["entity" => "article", "topic" => "product.updated", "url_topic" => "update"],
        ["entity" => "article", "topic" => "product.deleted", "url_topic" => "delete"],
        //orders
        ["entity" => "order", "topic" => "order.created", "url_topic" => "create"],
        ["entity" => "order", "topic" => "order.updated", "url_topic" => "update"],
        ["entity" => "order", "topic" => "order.deleted", "url_topic" => "delete"],
        ["entity" => "order", "topic" => "order.restored", "url_topic" => "restore"]
    ];
    foreach ($topics as $topic) {
        $webhook = new WC_Webhook();
        $webhook->set_name('wuunder ' . $topic['entity'] . " " . $topic['url_topic']);
        $webhook->set_user_id(get_current_user_id()); // User ID used while generating the webhook payload.
        $webhook->set_topic($topic['topic']); // Event used to trigger a webhook.
        $webhook->set_secret($webshop_uuid); // Secret to validate webhook when received.
        $webhook->set_delivery_url($deliveryURL . "api/v1/webshop/event/" . $topic['entity'] . "/" . $topic['url_topic'] . "/" . $webshop_uuid); // api/v1/webshop/event/article/delete/6a83154c-e496-4a98-b7d9-5f2623d72d85
        $webhook->set_status($status);

        $webhook->save();
    }
}

function delete_wuunder_webhooks()
{
    global $wpdb;
    $results = $wpdb->get_results("SELECT webhook_id, name FROM {$wpdb->prefix}wc_webhooks");
    foreach ($results as $result) {
        if (strpos($result->name, 'wuunder') !== false) {
            $wh = new WC_Webhook();
            $wh->set_id($result->webhook_id);
            $wh->delete();
        }
    }
}

// Based on the environment, enable the webhooks and deactivate webhooks of other environment.
// function switch_status_wuunder_webhooks($targetEnv) {
//     global $wpdb;
//     $productionUrl = 'my.production-next.wearewuunder.com';
//     $stagingUrl = 'mywuunder.playground-next.wearewuunder.com';
//     $activateWebhooksEnv = $targetEnv === 'production' ? $productionUrl : $stagingUrl; 
//     $disableWebhooksEnv = $targetEnv === 'production' ? $stagingUrl : $productionUrl;

//     $wpdb->query('START TRANSACTION');

//     $wpdb->query(
//         "
//         UPDATE {$wpdb->prefix}wc_webhooks
//         SET status='active'
//         WHERE delivery_url LIKE '%{$activateWebhooksEnv}%'
//         "
//     );

//     $wpdb->query(
//         "
//         UPDATE {$wpdb->prefix}wc_webhooks
//         SET status='disabled'
//         WHERE delivery_url LIKE '%{$disableWebhooksEnv}%'
//         "
//     );

//     $wpdb->query('COMMIT');
// }

function disable_all_webhooks()
{
    global $wpdb;
    $wpdb->query(
        "
        UPDATE {$wpdb->prefix}wc_webhooks
        SET status='disabled'
        "
    );
}

function wcwp_receive_setup_callback(WP_REST_Request $request)
{
    $data = $request->get_json_params();
    if ($data['environment'] == 'production') {
        update_option("wuunder_setup_success", true);
        update_option("wuunder_webshop_id", $data['webshop_id']);
        update_option("wuunder_api_key", $data['api_key']);
    } elseif ($data['environment'] == 'staging') {
        update_option("wuunder_setup_success", true);
        update_option("wuunder_test_webshop_id", $data['webshop_id']);
        update_option("wuunder_test_api_key", $data['api_key']);
    }
}
