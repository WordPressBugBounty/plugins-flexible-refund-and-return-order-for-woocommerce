<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\OrderReferenceResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\PublicRequestLink;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
class PublicRequestAccess
{
    public const NONCE_PREFIX = 'fr-request-';
    private const SESSION_KEY = 'fr_public_request';
    private RequestRepository $requests;
    private OrderReferenceResolver $order_reference_resolver;
    private string $lookup_notice = '';
    public function __construct(RequestRepository $requests, OrderReferenceResolver $order_reference_resolver)
    {
        $this->requests = $requests;
        $this->order_reference_resolver = $order_reference_resolver;
    }
    public function get_request_for_page(): ?RequestRecord
    {
        $token = self::input($_GET, PublicRequestLink::TOKEN_KEY);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!is_user_logged_in() && '' === $token) {
            return null;
        }
        $request = $this->get_linked_request();
        return null !== $request && (is_user_logged_in() || PublicRequestLink::is_valid($request, $token, PublicRequestLink::is_management_link())) ? $request : null;
    }
    private function get_linked_request(?string $type = null): ?RequestRecord
    {
        $id = self::input($_GET, PublicRequestLink::REQUEST_KEY);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $request = ctype_digit($id) && (int) $id > 0 ? $this->requests->find((int) $id) : null;
        return null !== $request && (null === $type || $request->get_request_type() === $type) ? $request : null;
    }
    public function get_guest_status_request(string $type): ?RequestRecord
    {
        return $this->get_guest_request($type);
    }
    public function get_status_request(string $type): ?RequestRecord
    {
        $request = $this->get_guest_status_request($type);
        if (null !== $request || !is_user_logged_in()) {
            return $request;
        }
        $request = $this->get_linked_request($type);
        return null !== $request && $this->get_owned_order(get_current_user_id(), $request->get_id(), 0, $type) ? $request : null;
    }
    public function get_guest_management_order(string $type): ?WC_Order
    {
        $request = $this->get_guest_request($type, \true);
        return null !== $request ? wc_get_order($request->get_order_id()) : null;
    }
    private function get_guest_request(string $type, bool $management = \false): ?RequestRecord
    {
        $request = $this->get_linked_request($type);
        if (null === $request || !PublicRequestLink::is_valid($request, self::input($_GET, PublicRequestLink::TOKEN_KEY), $management)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return null;
        }
        $order = wc_get_order($request->get_order_id());
        return $order && 0 === $order->get_customer_id() ? $request : null;
    }
    public function get_owned_order(int $user_id, int $request_id = 0, int $order_id = 0, ?string $type = null): ?WC_Order
    {
        if ($user_id <= 0) {
            return null;
        }
        if ($request_id > 0) {
            $request = $this->requests->find($request_id);
            if (null === $request || null !== $type && $request->get_request_type() !== $type) {
                return null;
            }
            $order_id = $request->get_order_id();
        }
        $order = $order_id > 0 ? wc_get_order($order_id) : null;
        return $order && $order->get_customer_id() === $user_id ? $order : null;
    }
    public function lookup_order(string $type, string $reference, string $email, string $nonce, string $client_ip): ?WC_Order
    {
        self::clear_public_request();
        if (!wp_verify_nonce($nonce, self::NONCE_PREFIX . $type)) {
            $this->lookup_notice = __('Form has expired. Please try again.', 'flexible-refund-and-return-order-for-woocommerce');
            return null;
        }
        $limit_key = 'fr_public_lookup_' . hash('sha256', $client_ip);
        $attempts = (int) get_transient($limit_key);
        if ($attempts >= 10) {
            $this->lookup_notice = __('Too many attempts. Please try again in ten minutes.', 'flexible-refund-and-return-order-for-woocommerce');
            return null;
        }
        set_transient($limit_key, $attempts + 1, 10 * \MINUTE_IN_SECONDS);
        $email = sanitize_email($email);
        $order = $this->order_reference_resolver->find_order($reference, $email);
        if (!$order) {
            $this->lookup_notice = __('The order number or email is incorrect.', 'flexible-refund-and-return-order-for-woocommerce');
            return null;
        }
        if ($order->get_customer_id() > 0) {
            return $order;
        }
        if (!WC()->session) {
            $this->lookup_notice = __('The request could not be verified. Please try again.', 'flexible-refund-and-return-order-for-woocommerce');
            return null;
        }
        WC()->session->set_customer_session_cookie(\true);
        WC()->session->set(self::SESSION_KEY, ['order_id' => $order->get_id(), 'email' => $email, 'type' => $type]);
        return $order;
    }
    public function get_lookup_notice(): string
    {
        return $this->lookup_notice;
    }
    public static function get_public_request_type(): ?string
    {
        $context = WC()->session ? WC()->session->get(self::SESSION_KEY, []) : [];
        $type = is_array($context) ? $context['type'] ?? '' : '';
        return in_array($type, RequestType::all(), \true) ? $type : null;
    }
    public static function get_public_request_order(): ?WC_Order
    {
        if (!Integration::is_super() || isset($_GET[PublicRequestLink::REQUEST_KEY]) || null === self::get_public_request_type()) {
            return null;
        }
        $context = WC()->session->get(self::SESSION_KEY, []);
        $order = wc_get_order((int) ($context['order_id'] ?? 0));
        return $order && 0 === $order->get_customer_id() && hash_equals(strtolower(trim($order->get_billing_email())), strtolower(trim($context['email'] ?? ''))) ? $order : null;
    }
    public static function clear_public_request(): void
    {
        if (WC()->session) {
            WC()->session->__unset(self::SESSION_KEY);
        }
    }
    private static function input(array $input, string $key): string
    {
        return isset($input[$key]) && is_scalar($input[$key]) ? sanitize_text_field(wp_unslash((string) $input[$key])) : '';
    }
}
