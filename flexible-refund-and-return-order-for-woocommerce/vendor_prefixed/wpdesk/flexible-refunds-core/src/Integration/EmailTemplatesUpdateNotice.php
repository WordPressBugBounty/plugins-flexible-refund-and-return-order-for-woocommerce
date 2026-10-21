<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use WC_Email;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails\AbstractRefundEmail;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\SettingsIntegration;
use FRFreeVendor\WPDesk\Notice\AjaxHandler;
use FRFreeVendor\WPDesk\Notice\Notice;
use FRFreeVendor\WPDesk\Notice\PermanentDismissibleNotice;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
final class EmailTemplatesUpdateNotice implements Hookable
{
    // Increase this version whenever the default email templates change.
    public const TEMPLATES_VERSION = 2026101901;
    public const VERSION_OPTION = 'fr_email_templates_version';
    private const RESET_ACTION = 'fr_reset_email_templates';
    public function hooks(): void
    {
        add_action('current_screen', [$this, 'maybe_notice']);
        add_action('admin_post_' . self::RESET_ACTION, [$this, 'reset_templates']);
        (new AjaxHandler())->hooks();
    }
    public function maybe_notice(\WP_Screen $screen): void
    {
        if (!$this->is_refunds_or_email_settings_page($screen)) {
            return;
        }
        if ((int) get_option(self::VERSION_OPTION, 0) >= self::TEMPLATES_VERSION) {
            if (isset($_GET['fr_emails_updated']) && '1' === $_GET['fr_emails_updated']) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                new Notice(__('Flexible Refunds email templates have been restored to the latest defaults.', 'flexible-refund-and-return-order-for-woocommerce'), 'success');
            }
            return;
        }
        $url = wp_nonce_url(admin_url('admin-post.php?action=' . self::RESET_ACTION), self::RESET_ACTION . '_' . self::TEMPLATES_VERSION);
        new PermanentDismissibleNotice(sprintf(
            /* translators: %s: link to restore default email templates. */
            __('Recent Flexible Refunds updates changed the email messages and links. %s to use the latest templates. This replaces custom subjects, headings and message bodies for Flexible Refunds emails. Recipients and sending settings are preserved.', 'flexible-refund-and-return-order-for-woocommerce'),
            '<a href="' . esc_url($url) . '">' . esc_html__('Replace old messages with the new defaults', 'flexible-refund-and-return-order-for-woocommerce') . '</a>'
        ), 'fr_email_templates_update_' . self::TEMPLATES_VERSION, 'info');
    }
    public function reset_templates(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to update email templates.', 'flexible-refund-and-return-order-for-woocommerce'), '', ['response' => 403]);
        }
        check_admin_referer(self::RESET_ACTION . '_' . self::TEMPLATES_VERSION);
        foreach (WC()->mailer()->get_emails() as $email) {
            if (!$email instanceof AbstractRefundEmail) {
                continue;
            }
            if (!$this->set_email_to_default($email)) {
                wp_die(esc_html__('Email templates could not be updated. Please try again.', 'flexible-refund-and-return-order-for-woocommerce'), '', ['response' => 500]);
            }
        }
        if (!update_option(self::VERSION_OPTION, self::TEMPLATES_VERSION, \false) && (int) get_option(self::VERSION_OPTION, 0) !== self::TEMPLATES_VERSION) {
            wp_die(esc_html__('Email templates could not be updated. Please try again.', 'flexible-refund-and-return-order-for-woocommerce'), '', ['response' => 500]);
        }
        wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=email&fr_emails_updated=1'));
        exit;
    }
    private function set_email_to_default(WC_Email $email): bool
    {
        $key = $email->get_option_key();
        $settings = get_option($key, []);
        if (!is_array($settings)) {
            return \true;
        }
        $updated = $settings;
        unset($updated['subject'], $updated['heading'], $updated['additional_content']);
        if ($updated !== $settings && !update_option($key, $updated) && get_option($key) !== $updated) {
            return \false;
        }
        return \true;
    }
    private function is_refunds_or_email_settings_page(\WP_Screen $screen): bool
    {
        $is_email_settings = 'woocommerce_page_wc-settings' === $screen->id && isset($_GET['tab']) && 'email' === $_GET['tab'];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return current_user_can('manage_woocommerce') && ($is_email_settings || SettingsIntegration::is_settings_page());
    }
}
