<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\OrderReferenceResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\PublicRequestLink;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\PublicRequestAccess;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
class PublicRefundShortcode implements Hookable
{
    public const REFUND_REQUEST_GET_KEY = 'send_public_refund';
    public const EMAIL_REQUEST_KEY = 'refund_email';
    public const ORDER_ID_REQUEST_KEY = 'refund_order_id';
    private const SHORTCODES = [RequestType::REFUND => 'flexible_refund_public', RequestType::RECLAMATION => 'flexible_reclamation_public', RequestType::REPAIR => 'flexible_repair_public'];
    private Renderer $renderer;
    private MyAccount $my_account;
    private PersistentContainer $settings;
    private FormRepository $forms;
    private PublicRequestAccess $access;
    private PublicRequestController $controller;
    private bool $is_pro;
    public function __construct(Renderer $renderer, MyAccount $my_account, PersistentContainer $settings, FormRepository $forms, PublicRequestAccess $access, PublicRequestController $controller, bool $is_pro = \true)
    {
        $this->renderer = $renderer;
        $this->my_account = $my_account;
        $this->settings = $settings;
        $this->forms = $forms;
        $this->access = $access;
        $this->controller = $controller;
        $this->is_pro = $is_pro;
    }
    public function hooks(): void
    {
        foreach (self::SHORTCODES as $shortcode) {
            add_shortcode($shortcode, [$this, 'shortcode']);
        }
    }
    public function shortcode($atts = [], $content = null, string $shortcode = ''): string
    {
        if (!$this->is_pro) {
            return '';
        }
        $type = self::get_request_type_for_shortcode($shortcode);
        if (isset($_GET[PublicRequestLink::REQUEST_KEY])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (PublicRequestLink::is_management_link()) {
                $order = $this->access->get_guest_management_order($type);
                if ($this->controller->is_fallback()) {
                    return $this->render_notice(__('The public request page is unavailable. Please contact the store.', 'flexible-refund-and-return-order-for-woocommerce'));
                }
                return null !== $order ? $this->render_request_form($type, $order) : $this->render_notice(__('This request link is invalid or unavailable.', 'flexible-refund-and-return-order-for-woocommerce'));
            }
            $request = $this->access->get_status_request($type);
            return null !== $request ? $this->renderer->render('public-refund/status', ['order_id' => $request->get_order_id(), 'status' => $request->get_status()]) : $this->render_notice(__('This request link is invalid or unavailable.', 'flexible-refund-and-return-order-for-woocommerce'));
        }
        if ($this->controller->is_fallback()) {
            return $this->render_notice(__('The public request page is unavailable. Please contact the store.', 'flexible-refund-and-return-order-for-woocommerce'));
        }
        if ('' !== $this->controller->get_lookup_notice()) {
            return $this->render_notice($this->controller->get_lookup_notice()) . $this->render_request_form($type);
        }
        $order = PublicRequestAccess::get_public_request_order();
        if (null !== $order && $type === PublicRequestAccess::get_public_request_type()) {
            return wc_print_notices(\true) . $this->my_account->refund_public_request($order->get_id(), $type);
        }
        $form = $this->forms->find_by_type($type);
        if (null === $form || !$form->is_enabled()) {
            return $this->render_notice(__('This request type is currently unavailable.', 'flexible-refund-and-return-order-for-woocommerce'));
        }
        return $this->render_request_form($type);
    }
    public static function get_shortcode_for_type(string $type): string
    {
        RequestType::assert_valid($type);
        return '[' . self::SHORTCODES[$type] . ']';
    }
    public static function get_submit_field_name(string $type): string
    {
        RequestType::assert_valid($type);
        return RequestType::REFUND === $type ? self::REFUND_REQUEST_GET_KEY : 'send_public_' . $type;
    }
    public static function get_email_field_name(string $type): string
    {
        RequestType::assert_valid($type);
        return RequestType::REFUND === $type ? self::EMAIL_REQUEST_KEY : $type . '_email';
    }
    public static function get_order_field_name(string $type): string
    {
        RequestType::assert_valid($type);
        return RequestType::REFUND === $type ? self::ORDER_ID_REQUEST_KEY : $type . '_order_id';
    }
    private static function get_request_type_for_shortcode(string $shortcode): string
    {
        $type = array_search($shortcode, self::SHORTCODES, \true);
        return \false === $type ? RequestType::REFUND : $type;
    }
    public static function get_nonce_field_name(string $type): string
    {
        return RequestType::REFUND === $type ? '_shortcodenonce' : '_' . $type . '_shortcodenonce';
    }
    private function render_request_form(string $type, ?\WC_Order $order = null): string
    {
        $submit_labels = [RequestType::REFUND => __('View refund form', 'flexible-refund-and-return-order-for-woocommerce'), RequestType::RECLAMATION => __('View reclamation form', 'flexible-refund-and-return-order-for-woocommerce'), RequestType::REPAIR => __('View repair form', 'flexible-refund-and-return-order-for-woocommerce')];
        $login_descriptions = [RequestType::REFUND => __('Log in to submit a refund request from your order history.', 'flexible-refund-and-return-order-for-woocommerce'), RequestType::RECLAMATION => __('Log in to submit a reclamation request from your order history.', 'flexible-refund-and-return-order-for-woocommerce'), RequestType::REPAIR => __('Log in to submit a repair request from your order history.', 'flexible-refund-and-return-order-for-woocommerce')];
        return wc_print_notices(\true) . $this->renderer->render('public-refund/public-refund', ['submit_label' => $submit_labels[$type], 'form_action' => remove_query_arg([PublicRequestLink::REQUEST_KEY, PublicRequestLink::TOKEN_KEY, PublicRequestLink::MANAGEMENT_KEY, 'delete_refund_request', '_wpnonce']), 'order_reference' => null !== $order ? $this->settings->get_fallback(OrderReferenceResolver::SEARCH_BY_ORDER_NUMBER_OPTION, 'no') === 'yes' ? $order->get_order_number() : $order->get_id() : '', 'email' => null !== $order ? $order->get_billing_email() : '', 'login_description' => $login_descriptions[$type], 'submit_field_name' => self::get_submit_field_name($type), 'email_field_name' => self::get_email_field_name($type), 'order_field_name' => self::get_order_field_name($type), 'order_reference_label' => $this->settings->get_fallback(OrderReferenceResolver::SEARCH_BY_ORDER_NUMBER_OPTION, 'no') === 'yes' ? __('Order number', 'flexible-refund-and-return-order-for-woocommerce') : __('Order ID', 'flexible-refund-and-return-order-for-woocommerce'), 'nonce' => PublicRequestAccess::NONCE_PREFIX . $type, 'nonce_field_name' => self::get_nonce_field_name($type), 'login_url' => PublicRequestController::get_login_url()]);
    }
    private function render_notice(string $message): string
    {
        return '<p class="woocommerce-info" role="status">' . esc_html($message) . '</p>';
    }
}
