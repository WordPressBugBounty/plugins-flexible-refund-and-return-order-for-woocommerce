<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
class Plugin
{
    /**
     * Get URL to product page of Pro version.
     *
     * @return string
     */
    public static function get_url_to_pro(string $source = ''): string
    {
        switch ($source) {
            case 'email':
                return self::get_url_with_suffix('plugin-email');
            default:
                return self::get_url_with_suffix('plugin');
        }
    }
    /**
     * Get URL to the docs page.
     *
     * @return string
     */
    public static function get_url_to_docs(): string
    {
        return self::get_url_with_suffix('docs');
    }
    public static function get_support_url(): string
    {
        return self::get_url_with_suffix('support');
    }
    public static function get_homepage_url(): string
    {
        return self::get_url_with_suffix('homepage');
    }
    /**
     * Get URL to product page of Pro version.
     *
     * @return string
     */
    public static function add_row_class(): string
    {
        return Integration::is_super() ? 'add_row' : 'add-row-free';
    }
    public static function get_current_plugin_slug(): string
    {
        return Integration::is_super() ? 'flexible-refunds-pro' : 'flexible-refund-and-return-order-for-woocommerce';
    }
    public static function get_url_with_suffix(string $suffix): string
    {
        return get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/sk/' . self::get_current_plugin_slug() . '-' . $suffix . '-pl/' : 'https://www.wpdesk.net/sk/' . self::get_current_plugin_slug() . '-' . $suffix . '-en/';
    }
}
