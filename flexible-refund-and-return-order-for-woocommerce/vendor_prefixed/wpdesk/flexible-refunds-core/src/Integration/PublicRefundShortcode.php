<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
class PublicRefundShortcode implements Hookable
{
    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var MyAccount
     */
    private $my_account;
    public const REFUND_REQUEST_GET_KEY = 'send_public_refund';
    private const EMAIL_REQUEST_KEY = 'refund_email';
    private const ORDER_ID_REQUEST_KEY = 'refund_order_id';
    private const CANCEL_NONCE_ACTION = 'cancel_refund';
    private const NONCE = 'fr-request-refund';
    private const NONCE_NAME = '_shortcodenonce';
    public function __construct(Renderer $renderer, MyAccount $my_account)
    {
        $this->renderer = $renderer;
        $this->my_account = $my_account;
    }
    public function hooks()
    {
        add_shortcode('flexible_refund_public', [$this, 'shortcode']);
        add_filter('wp', [$this, 'cancel_refund_request_by_order_id'], 999);
    }
    public function shortcode($atts)
    {
        if (isset($_GET[self::REFUND_REQUEST_GET_KEY])) {
            if (!$this->is_nonce_valid()) {
                return $this->render_form_with_notice(__('Form has expired. Please try again.', 'flexible-refund-and-return-order-for-woocommerce'));
            }
            //phpcs:disable
            $email = $_GET[self::EMAIL_REQUEST_KEY];
            $order_id = $_GET[self::ORDER_ID_REQUEST_KEY];
            //phpcs:enable
            $order = wc_get_order($order_id);
            if ($order && $order->get_billing_email() === $email) {
                $this->my_account->refund_public_request($order_id);
                return '';
            }
            return $this->render_form_with_notice($this->render_invalid_request());
        }
        return $this->render_refund_form();
    }
    private function is_nonce_valid(): bool
    {
        return isset($_GET[self::NONCE_NAME]) && \wp_verify_nonce(\sanitize_text_field(\wp_unslash($_GET[self::NONCE_NAME])), self::NONCE);
    }
    private function render_refund_form(): string
    {
        return $this->renderer->render('public-refund/public-refund', ['submit_field_name' => self::REFUND_REQUEST_GET_KEY, 'email_field_name' => self::EMAIL_REQUEST_KEY, 'order_field_name' => self::ORDER_ID_REQUEST_KEY, 'nonce' => self::NONCE, 'nonce_field_name' => self::NONCE_NAME]);
    }
    private function render_invalid_request(): string
    {
        return $this->renderer->render('public-refund/invalid-order-id-or-email');
    }
    private function render_form_with_notice(string $notice): string
    {
        return $notice . $this->render_refund_form();
    }
    public function cancel_refund_request_by_order_id(): void
    {
        //phpcs:disable
        $nonce_value = $_REQUEST['_wpnonce'] ?? '';
        $order_ID = $_REQUEST['delete_refund_request'] ?? 0;
        //phpcs:enable
        $nonce = wp_verify_nonce($nonce_value, self::CANCEL_NONCE_ACTION);
        if ($order_ID && $nonce) {
            $order = wc_get_order($order_ID);
            $previous_order_status = $order->get_meta('fr_refund_previous_order_status');
            $order->delete_meta_data('fr_refund_request_data');
            $order->delete_meta_data('fr_refund_request_date');
            $order->delete_meta_data('fr_refund_request_status');
            $order->delete_meta_data('fr_refund_request_note');
            $order->delete_meta_data('fr_refund_previous_order_status');
            if (!empty($previous_order_status)) {
                $order->set_status($previous_order_status);
            }
            $order->save();
            wp_safe_redirect(remove_query_arg(['delete_refund_request', '_wpnonce']), 301);
        }
    }
}
