<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\SettingsIntegration;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class AdminMenu implements Hookable
{
    public function hooks()
    {
        add_action('admin_menu', [$this, 'admin_menu'], 20);
        add_action('admin_menu', [$this, 'menu_order_count'], 30);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_parent_menu_target']);
    }
    public function admin_menu()
    {
        add_submenu_page(
            SettingsIntegration::PAGE_REFUND,
            _x('Requests', 'Admin menu name', 'flexible-refund-and-return-order-for-woocommerce'),
            _x('Requests', 'Admin menu name', 'flexible-refund-and-return-order-for-woocommerce'),
            'manage_woocommerce',
            // TODO: handle links with HPOS. For now WC does the redirect.
            $this->get_requests_url(),
            '',
            0
        );
    }
    /**
     * Adds the order processing count to the menu.
     */
    public function menu_order_count()
    {
        global $submenu;
        if (isset($submenu[SettingsIntegration::PAGE_REFUND])) {
            if (apply_filters('woocommerce_include_processing_order_count_in_menu', \true) && current_user_can('edit_others_shop_orders')) {
                $order_count = wc_orders_count('refund-request');
                if ($order_count) {
                    foreach ($submenu[SettingsIntegration::PAGE_REFUND] as $key => $menu_item) {
                        if ($this->get_requests_url() === $menu_item[2]) {
                            $submenu[SettingsIntegration::PAGE_REFUND][$key][0] .= ' <span class="awaiting-mod update-plugins count-' . esc_attr($order_count) . '"><span class="processing-count">' . number_format_i18n($order_count) . '</span></span>';
                            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            break;
                        }
                    }
                }
            }
        }
    }
    public function enqueue_parent_menu_target(): void
    {
        $target = wp_json_encode(admin_url('admin.php?page=' . SettingsIntegration::PAGE_REFUND));
        if (\false === $target) {
            return;
        }
        wp_add_inline_script('common', 'document.addEventListener("DOMContentLoaded",function(){var menu=document.getElementById("toplevel_page_flexible-refunds");if(menu){var link=menu.querySelector("a.menu-top");if(link){link.href=' . $target . ';}}});');
    }
    private function get_requests_url(): string
    {
        return admin_url('edit.php?post_status=wc-refund-request&post_type=shop_order');
    }
}
