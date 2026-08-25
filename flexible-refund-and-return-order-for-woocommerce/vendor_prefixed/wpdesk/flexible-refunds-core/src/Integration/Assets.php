<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\SettingsIntegration;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class Assets implements Hookable
{
    const SCRIPT_VERSION = 1;
    const SETTINGS_PAGE_ID = 'woocommerce_page_wc-settings';
    const SETTINGS_EMAIL_SECTION_ID = 'fr_email_refund_admin_requested';
    private string $scripts_version;
    private string $plugin_url;
    public function __construct(string $plugin_url)
    {
        $this->plugin_url = trailingslashit($plugin_url);
        $this->scripts_version = self::SCRIPT_VERSION . time();
    }
    /**
     * @return string
     */
    public function get_assets_css_url(): string
    {
        return $this->plugin_url . 'assets/css/';
    }
    public function get_assets_js_url(): string
    {
        return $this->plugin_url . 'assets/js/';
    }
    public function hooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 100);
        add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_scripts'], 100);
    }
    /**
     * Admin enqueue scripts.
     *
     * @internal You should not use this directly from another application
     */
    public function admin_enqueue_scripts(): void
    {
        $screen = get_current_screen();
        if (SettingsIntegration::is_settings_page()) {
            wp_enqueue_style('woocommerce_admin_styles');
            wp_enqueue_script('wc-enhanced-select');
            wp_enqueue_style('frc-admin-style', $this->get_assets_css_url() . 'settings.css', [], $this->scripts_version);
            $fr_fb_i18n = ['label' => esc_html__('Label', 'flexible-refund-and-return-order-for-woocommerce'), 'name' => esc_html__('Name', 'flexible-refund-and-return-order-for-woocommerce'), 'enable' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'required' => esc_html__('Required', 'flexible-refund-and-return-order-for-woocommerce'), 'options' => esc_html__('Options', 'flexible-refund-and-return-order-for-woocommerce'), 'value' => esc_html__('Value', 'flexible-refund-and-return-order-for-woocommerce'), 'remove' => esc_html__('Remove', 'flexible-refund-and-return-order-for-woocommerce'), 'remove_confirm' => esc_html__('Remove item?', 'flexible-refund-and-return-order-for-woocommerce'), 'remove_condition_confirm' => esc_html__('Remove condition?', 'flexible-refund-and-return-order-for-woocommerce'), 'type_validation_msg' => esc_html__('Select a field type from the list!', 'flexible-refund-and-return-order-for-woocommerce'), 'label_validation_msg' => esc_html__('Fill the Label field!', 'flexible-refund-and-return-order-for-woocommerce'), 'name_validation_msg' => esc_html__('Fill the Name field!', 'flexible-refund-and-return-order-for-woocommerce'), 'shipping_refund_descriptions' => ['no' => esc_html__('The return form will not include the shipping cost item.', 'flexible-refund-and-return-order-for-woocommerce'), 'customer_choice' => esc_html__('The plugin will leave the decision to the customer regarding whether to check the shipping cost refund item. For partial returns, refund the original shipping cost. For full returns, refund the original shipping cost.', 'flexible-refund-and-return-order-for-woocommerce'), 'yes' => esc_html__('The plugin will automatically check the shipping cost refund item. For partial returns, refund the original shipping cost. For full returns, refund the original shipping cost.', 'flexible-refund-and-return-order-for-woocommerce'), 'lowest_cost' => esc_html__('The plugin will automatically check the shipping cost refund item. For partial returns, refund the shipping cost equal to the lowest shipping rate available in the store. For full returns, refund the original shipping cost.', 'flexible-refund-and-return-order-for-woocommerce')], 'input_prefix' => 'fr_form[schema]', 'nonce' => wp_create_nonce('fr_form_builder_field')];
            wp_enqueue_script('frc-admin', $this->get_assets_js_url() . 'settings.js', ['jquery', 'jquery-ui-sortable'], $this->scripts_version, \true);
            wp_localize_script('frc-admin', 'fr_fb_i18n', $fr_fb_i18n);
            wp_enqueue_style('frc-marketing', $this->get_assets_css_url() . 'marketing.css', [], $this->scripts_version);
            wp_enqueue_style('frc-modal', $this->get_assets_css_url() . 'modal.css', [], $this->scripts_version);
            wp_enqueue_script('frc-modal', $this->get_assets_js_url() . 'modal.js', ['jquery'], $this->scripts_version, \true);
            \FRFreeVendor\WPDesk\Library\Marketing\Boxes\Assets::enqueue_assets();
            \FRFreeVendor\WPDesk\Library\Marketing\Boxes\Assets::enqueue_owl_assets();
        }
        if ($screen->id === self::SETTINGS_PAGE_ID && (isset($_GET['section']) && $_GET['section'] === self::SETTINGS_EMAIL_SECTION_ID)) {
            wp_enqueue_script('frc-email-recipients', $this->get_assets_js_url() . 'email-recipients.js', ['jquery'], $this->scripts_version, \true);
            $fr_email_recipients = ['is_super' => Integration::is_super() ? 'true' : 'false'];
            wp_localize_script('frc-email-recipients', 'fr_email_recipients', $fr_email_recipients);
        }
        $allowed_screens = ['shop_order', 'shop_subscription', 'woocommerce_page_wc-orders'];
        if (in_array($screen->id, $allowed_screens, \true)) {
            wp_enqueue_style('frc-meta-box', $this->get_assets_css_url() . 'meta-box.css', [], $this->scripts_version);
            wp_enqueue_script('frc-meta-box', $this->get_assets_js_url() . 'meta-box.js', ['jquery'], $this->scripts_version, \true);
            //phpcs:disable
            $fr_meta_box = ['redirect_url' => esc_url($_SERVER['REQUEST_URI']), 'price_decimals' => wc_get_price_decimals(), 'decimal_point' => wc_get_price_decimal_separator(), 'thousand_point' => wc_get_price_thousand_separator(), 'nonce' => wp_create_nonce('fr_update_request')];
            //phpcs:enable
            wp_localize_script('frc-meta-box', 'fr_meta_box', $fr_meta_box);
        }
    }
    public function wp_enqueue_scripts(): void
    {
        wp_enqueue_style('frc-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], $this->scripts_version);
        wp_enqueue_style('frc-front', $this->get_assets_css_url() . 'front.css', ['frc-select2'], $this->scripts_version);
        wp_enqueue_script('frc-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], $this->scripts_version, \true);
        wp_enqueue_script('frc-front', $this->get_assets_js_url() . 'front.js', ['jquery', 'frc-select2'], $this->scripts_version, \true);
        $fr_front_i18n = ['qty_empty' => esc_html__('Select at least one product and quantity.', 'flexible-refund-and-return-order-for-woocommerce'), 'required_field' => esc_html__('This field is required!', 'flexible-refund-and-return-order-for-woocommerce'), 'files_limit' => esc_html__('You have exceeded the file limit. Upload files again', 'flexible-refund-and-return-order-for-woocommerce'), 'price_decimals' => wc_get_price_decimals(), 'decimal_point' => wc_get_price_decimal_separator(), 'thousand_point' => wc_get_price_thousand_separator()];
        wp_localize_script('frc-front', 'fr_front_i18n', $fr_front_i18n);
    }
}
