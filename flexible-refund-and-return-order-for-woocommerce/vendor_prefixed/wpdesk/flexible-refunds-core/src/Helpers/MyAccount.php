<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;

use WC_Order;
class MyAccount
{
    const QUERY_REFUND_KEY = 'fr-refund';
    public static function get_refund_url(WC_Order $order, int $form_id = 0): string
    {
        $url = wc_get_endpoint_url(self::QUERY_REFUND_KEY, $order->get_id(), wc_get_page_permalink('myaccount'));
        return $form_id > 0 ? add_query_arg('form_id', $form_id, $url) : $url;
    }
}
