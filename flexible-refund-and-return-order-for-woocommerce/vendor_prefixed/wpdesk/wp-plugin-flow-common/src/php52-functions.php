<?php

namespace FRFreeVendor;

if (!\function_exists('FRFreeVendor\wpdesk_plugin_flow_php52_admin_notice')) {
    /**
     * Display an admin notice about an unsupported PHP version.
     *
     * @return void
     */
    function wpdesk_plugin_flow_php52_admin_notice()
    {
        echo \sprintf(\__('<p><strong style="color: red;">PHP version is older than 5.3 so no WP Desk plugins will work. Please contact your host and ask them to upgrade. </strong></p>', 'flexible-refund-and-return-order-for-woocommerce'));
    }
}
