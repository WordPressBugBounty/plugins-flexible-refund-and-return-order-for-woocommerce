<?php

namespace FRFreeVendor;

/**
 * @var string                                                       $plugin_version
 * @var string                                                       $plugin_name
 * @var string                                                       $plugin_class_name
 * @var string                                                       $plugin_text_domain
 * @var string                                                       $plugin_dir
 * @var string                                                       $plugin_file
 * @var array                                                        $requirements
 * @var string                                                       $product_id
 * @var WPDesk\Plugin\Flow\Initialization\InitializationFactory|void $plugin_init_factory
 */
if (!\defined('ABSPATH')) {
    die;
}
// Code in PHP >= 5.3 but understandable by older parsers
if (\PHP_VERSION_ID > 50300) {
    require_once $plugin_dir . '/vendor/autoload.php';
    if (!isset($plugin_init_factory)) {
        $plugin_init_factory = new \FRFreeVendor\WPDesk\Plugin\Flow\Initialization\Simple\SimpleFactory();
    }
    if (!isset($plugin_shops) || !\is_array($plugin_shops)) {
        $plugin_shops = array();
    }
    $bootstrap = new WPDesk\Plugin\Flow\PluginBootstrap(
        $plugin_version,
        null,
        // deprecated
        $plugin_name,
        $plugin_class_name,
        $plugin_text_domain,
        $plugin_dir,
        $plugin_file,
        $requirements,
        $product_id,
        $plugin_init_factory,
        $plugin_shops
    );
    $bootstrap->run();
    // all optional vars must be cleared
    unset($plugin_init_factory);
} else {
    require_once \dirname(__FILE__) . '/php52-functions.php';
    \add_action('admin_notices', 'wpdesk_plugin_flow_php52_admin_notice');
}
