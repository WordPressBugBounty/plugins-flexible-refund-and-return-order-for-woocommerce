<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\OrderReferenceResolver;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
class PublicRefundShortcode implements Hookable
{
    private Renderer $renderer;
    private MyAccount $my_account;
    private OrderReferenceResolver $order_reference_resolver;
    private PersistentContainer $settings;
    private bool $is_pro;
    public const REFUND_REQUEST_GET_KEY = 'send_public_refund';
    public const EMAIL_REQUEST_KEY = 'refund_email';
    public const ORDER_ID_REQUEST_KEY = 'refund_order_id';
    private const CANCEL_NONCE_ACTION = 'cancel_refund';
    private const NONCE_PREFIX = 'fr-request-';
    private const REFUND_NONCE_NAME = '_shortcodenonce';
    private const SHORTCODES = [RequestType::REFUND => 'flexible_refund_public', RequestType::RECLAMATION => 'flexible_reclamation_public', RequestType::REPAIR => 'flexible_repair_public'];
    public function __construct(Renderer $renderer, MyAccount $my_account, OrderReferenceResolver $order_reference_resolver, PersistentContainer $settings, bool $is_pro = \true)
    {
        $this->renderer = $renderer;
        $this->my_account = $my_account;
        $this->order_reference_resolver = $order_reference_resolver;
        $this->settings = $settings;
        $this->is_pro = $is_pro;
    }
    public function hooks()
    {
        foreach (self::SHORTCODES as $shortcode) {
            add_shortcode($shortcode, [$this, 'shortcode']);
        }
        if ($this->is_pro) {
            add_filter('wp', [$this, 'cancel_refund_request_by_order_id'], 999);
        }
    }
    public function shortcode($atts = [], $content = null, string $shortcode = '')
    {
        if (!$this->is_pro) {
            return '';
        }
        $request_type = self::get_request_type_for_shortcode($shortcode);
        $submit_key = self::get_submit_field_name($request_type);
        if (isset($_GET[$submit_key])) {
            if (!$this->is_nonce_valid($request_type)) {
                return $this->render_form_with_notice(__('Form has expired. Please try again.', 'flexible-refund-and-return-order-for-woocommerce'), $request_type);
            }
            //phpcs:disable
            $email = sanitize_email(wp_unslash($_GET[self::get_email_field_name($request_type)] ?? ''));
            $order_reference = sanitize_text_field(wp_unslash($_GET[self::get_order_field_name($request_type)] ?? ''));
            //phpcs:enable
            $order = $this->order_reference_resolver->find_order($order_reference, $email);
            if ($order) {
                return wc_print_notices(\true) . $this->my_account->refund_public_request($order->get_id(), $request_type);
            }
            return $this->render_form_with_notice($this->render_invalid_request(), $request_type);
        }
        return $this->render_request_form($request_type);
    }
    public static function get_shortcode_for_type(string $request_type): string
    {
        RequestType::assert_valid($request_type);
        return '[' . self::SHORTCODES[$request_type] . ']';
    }
    public static function get_submit_field_name(string $request_type): string
    {
        RequestType::assert_valid($request_type);
        return RequestType::REFUND === $request_type ? self::REFUND_REQUEST_GET_KEY : 'send_public_' . $request_type;
    }
    public static function get_email_field_name(string $request_type): string
    {
        RequestType::assert_valid($request_type);
        return RequestType::REFUND === $request_type ? self::EMAIL_REQUEST_KEY : $request_type . '_email';
    }
    public static function get_order_field_name(string $request_type): string
    {
        RequestType::assert_valid($request_type);
        return RequestType::REFUND === $request_type ? self::ORDER_ID_REQUEST_KEY : $request_type . '_order_id';
    }
    private static function get_request_type_for_shortcode(string $shortcode): string
    {
        $request_type = array_search($shortcode, self::SHORTCODES, \true);
        return \false === $request_type ? RequestType::REFUND : $request_type;
    }
    private static function get_nonce_field_name(string $request_type): string
    {
        return RequestType::REFUND === $request_type ? self::REFUND_NONCE_NAME : '_' . $request_type . '_shortcodenonce';
    }
    private function is_nonce_valid(string $request_type): bool
    {
        $nonce_name = self::get_nonce_field_name($request_type);
        return isset($_GET[$nonce_name]) && \wp_verify_nonce(\sanitize_text_field(\wp_unslash($_GET[$nonce_name])), self::NONCE_PREFIX . $request_type);
    }
    private function render_request_form(string $request_type): string
    {
        $order_reference_label = $this->settings->get_fallback(OrderReferenceResolver::SEARCH_BY_ORDER_NUMBER_OPTION, 'no') === 'yes' ? esc_html__('Order number', 'flexible-refund-and-return-order-for-woocommerce') : esc_html__('Order ID', 'flexible-refund-and-return-order-for-woocommerce');
        return $this->renderer->render('public-refund/public-refund', ['submit_field_name' => self::get_submit_field_name($request_type), 'email_field_name' => self::get_email_field_name($request_type), 'order_field_name' => self::get_order_field_name($request_type), 'order_reference_label' => $order_reference_label, 'nonce' => self::NONCE_PREFIX . $request_type, 'nonce_field_name' => self::get_nonce_field_name($request_type)]);
    }
    private function render_invalid_request(): string
    {
        return $this->renderer->render('public-refund/invalid-order-id-or-email');
    }
    private function render_form_with_notice(string $notice, string $request_type): string
    {
        return $notice . $this->render_request_form($request_type);
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
            if ($order) {
                $this->my_account->cancel_request($order);
            }
            wp_safe_redirect(remove_query_arg(['delete_refund_request', '_wpnonce']), 301);
        }
    }
}
