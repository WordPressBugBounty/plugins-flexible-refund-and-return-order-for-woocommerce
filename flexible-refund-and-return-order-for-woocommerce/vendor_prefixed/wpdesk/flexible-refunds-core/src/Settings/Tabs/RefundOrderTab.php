<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\Tabs;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Plugin;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
/**
 * Refund order settings tab.
 */
final class RefundOrderTab extends AbstractSettingsTab
{
    const SETTING_PREFIX = 'fr_refund_';
    public function __construct(Renderer $renderer)
    {
        parent::__construct($renderer);
        add_action('woocommerce_admin_field_conditions_setting', [$this, 'refund_conditions_setting']);
        add_action('woocommerce_admin_field_auto_hide_setting', [$this, 'refund_auto_hide_setting']);
        add_action('woocommerce_admin_field_select_with_disable', [$this, 'refund_select_with_disable']);
        add_action('woocommerce_admin_field_post_select', [$this, 'refund_post_select']);
    }
    /**
     * @return array
     */
    public function get_fields(): array
    {
        $coupon_value = Integration::is_super() ? 'coupon' : 'should_disable';
        $coupon_label = Integration::is_super() ? esc_html__('On coupon', 'flexible-refund-and-return-order-for-woocommerce') : esc_html__('On coupon (PRO)', 'flexible-refund-and-return-order-for-woocommerce');
        $custom_attributes = Integration::is_super() ? [] : ['disabled' => 'disabled'];
        $docs_link = Plugin::get_url_to_docs();
        $pro_link = Plugin::get_url_to_pro();
        return [['title' => esc_html__('Order Refund', 'flexible-refund-and-return-order-for-woocommerce'), 'type' => 'title', 'desc' => sprintf(esc_html__('Define the settings for the refund button and the approval process. Read more in the %1$splugin documentation &rarr;%2$s', 'flexible-refund-and-return-order-for-woocommerce'), '<a href="' . esc_url($docs_link) . '" target="_blank" style="color: #D27334;">', '</a>'), 'id' => self::SETTING_PREFIX . 'refund_header'], ['title' => esc_html__('Refund order button', 'flexible-refund-and-return-order-for-woocommerce'), 'id' => self::SETTING_PREFIX . 'refund_button', 'desc' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'desc_tip' => esc_html__('Check this option to enable refund process button.', 'flexible-refund-and-return-order-for-woocommerce'), 'default' => 'no', 'type' => 'checkbox'], ['id' => self::SETTING_PREFIX . 'refund_conditions_setting', 'type' => 'conditions_setting'], [
            'title' => esc_html__('Refund type', 'flexible-refund-and-return-order-for-woocommerce'),
            /* translators: %s: URL to settings. */
            'desc' => '',
            'id' => self::SETTING_PREFIX . 'refund_type',
            'type' => 'select_with_disable',
            'options' => ['bank' => esc_html__('On bank account / On cash', 'flexible-refund-and-return-order-for-woocommerce'), $coupon_value => $coupon_label],
            'default' => 'bank',
            'class' => 'wc-enhanced-select',
            'css' => 'min-width:300px;',
        ], ['title' => esc_html__('Auto refund', 'flexible-refund-and-return-order-for-woocommerce'), 'desc' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'desc_tip' => Integration::is_super() ? esc_html__('Check this option to automatically accept order refund requests.', 'flexible-refund-and-return-order-for-woocommerce') : sprintf(esc_html__('Check this option to automatically accept order refund requests.%1$sUpgrade to PRO &rarr;%2$s', 'flexible-refund-and-return-order-for-woocommerce'), '<br><a href="' . esc_url($pro_link) . '" target="_blank" style="color:#FF9743;font-weight:600;margin-top:10px;display:inline-block;text-decoration:none;">', '</a>'), 'id' => self::SETTING_PREFIX . 'refund_auto_accept', 'default' => 'no', 'type' => 'checkbox', 'custom_attributes' => $custom_attributes], ['title' => esc_html__('Auto hide refund button', 'flexible-refund-and-return-order-for-woocommerce'), 'desc_tip' => Integration::is_super() ? esc_html__('Check this option to hide the refund button after a specified time.', 'flexible-refund-and-return-order-for-woocommerce') : sprintf(esc_html__('Check this option to hide the refund button after a specified time.%1$sUpgrade to PRO &rarr;%2$s', 'flexible-refund-and-return-order-for-woocommerce'), '<br><a href="' . esc_url($pro_link) . '" target="_blank" style="color:#FF9743;font-weight:600;margin-top:10px;display:inline-block;text-decoration:none;">', '</a>'), 'desc' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'id' => self::SETTING_PREFIX . 'refund_auto_hide', 'default' => 'no', 'type' => 'checkbox', 'class' => 'auto-hide-checkbox', 'custom_attributes' => $custom_attributes], ['id' => self::SETTING_PREFIX . 'refund_auto_hide_settings', 'type' => 'auto_hide_setting', 'should_disable' => !Integration::is_super()], ['title' => esc_html__('Allow to shipment refund', 'flexible-refund-and-return-order-for-woocommerce'), 'desc' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'id' => self::SETTING_PREFIX . 'refund_enable_shipment', 'default' => 'no', 'type' => 'checkbox'], ['title' => esc_html__('Cancel unpaid order button', 'flexible-refund-and-return-order-for-woocommerce'), 'desc_tip' => Integration::is_super() ? esc_html__('Check this if you want to show cancel order button.', 'flexible-refund-and-return-order-for-woocommerce') : sprintf(esc_html__('Check this if you want to show cancel order button. %1$sUpgrade to PRO &rarr;%2$s', 'flexible-refund-and-return-order-for-woocommerce'), '<br><a href="' . esc_url($pro_link) . '" target="_blank" style="color:#FF9743;font-weight:600;margin-top:10px;display:inline-block;text-decoration:none;">', '</a>'), 'desc' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'id' => self::SETTING_PREFIX . 'refund_cancel_button', 'default' => 'no', 'type' => 'checkbox', 'custom_attributes' => $custom_attributes], ['title' => esc_html__('Return Policy', 'flexible-refund-and-return-order-for-woocommerce'), 'desc' => esc_html__('Indicate a page with a return policy, a link to it will be included in the emails', 'flexible-refund-and-return-order-for-woocommerce'), 'id' => self::SETTING_PREFIX . 'selected_post_id', 'type' => 'post_select', 'default' => '', 'css' => 'min-width: 400px;'], ['type' => 'sectionend', 'id' => 'refund']];
    }
    /**
     * Name of hook must be unique.
     *
     * @param array $attr
     *
     * @return void
     */
    public function refund_conditions_setting(array $attr)
    {
        $this->get_renderer()->output_render('conditions', ['field' => $attr, 'custom_fields' => $this->get_condition_fields()]);
    }
    /**
     * Name of hook must be unique.
     *
     * @param array $attr
     *
     * @return void
     */
    public function refund_auto_hide_setting(array $attr)
    {
        $this->get_renderer()->output_render('auto_hide', ['field' => $attr, 'custom_fields' => $this->get_condition_fields()]);
    }
    /**
     * Name of hook must be unique.
     *
     * @param array $attr
     *
     * @return void
     */
    public function refund_select_with_disable(array $attr)
    {
        $this->get_renderer()->output_render('select-with-disable', ['field' => $attr, 'custom_fields' => $this->get_condition_fields()]);
    }
    public function refund_post_select(array $attr)
    {
        $this->get_renderer()->output_render('post-select', ['field' => $attr]);
    }
    /**
     * @return string
     */
    public static function get_tab_slug(): string
    {
        return 'refund';
    }
    /**
     * @return string
     */
    public static function get_tab_name(): string
    {
        return esc_html__('Refund', 'flexible-refund-and-return-order-for-woocommerce');
    }
}
