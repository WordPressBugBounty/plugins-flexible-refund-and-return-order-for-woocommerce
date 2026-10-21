<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;

use RuntimeException;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
final class PublicRequestLink
{
    public const REQUEST_KEY = 'fr_request';
    public const TOKEN_KEY = 'fr_key';
    public const ROUTE_KEY = 'fr_public_type';
    public const MANAGEMENT_KEY = 'fr_manage';
    private const SECRET_OPTION = 'fr_public_request_secret';
    public static function get_base_url(string $type): string
    {
        RequestType::assert_valid($type);
        if ('' === get_option('permalink_structure', '')) {
            return add_query_arg(self::ROUTE_KEY, $type, home_url('/index.php'));
        }
        return home_url('/refund/' . $type . '/');
    }
    public static function get_email_url(WC_Order $order, ?RequestRecord $request = null): string
    {
        if (!Integration::is_super()) {
            return MyAccount::get_refund_url($order, null !== $request ? $request->get_form_id() : 0);
        }
        if (null === $request) {
            return self::get_base_url(RequestType::REFUND);
        }
        return self::get_url($request, 0 === $order->get_customer_id());
    }
    public static function get_management_email_url(WC_Order $order, ?RequestRecord $request = null): string
    {
        if (!Integration::is_super() || null === $request) {
            return self::get_email_url($order, $request);
        }
        return self::get_url($request, 0 === $order->get_customer_id(), \true);
    }
    public static function is_management_link(): bool
    {
        return isset($_GET[self::MANAGEMENT_KEY]) && '1' === $_GET[self::MANAGEMENT_KEY];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    public static function get_url(RequestRecord $request, bool $guest, bool $management = \false): string
    {
        $args = [self::REQUEST_KEY => $request->get_id()];
        if ($management) {
            $args[self::MANAGEMENT_KEY] = '1';
        }
        if ($guest) {
            $args[self::TOKEN_KEY] = self::get_token($request, $management);
        }
        return add_query_arg($args, self::get_base_url($request->get_request_type()));
    }
    public static function get_token(RequestRecord $request, bool $management = \false): string
    {
        if (!Integration::is_super() || null === $request->get_id()) {
            throw new RuntimeException('Public request access is unavailable.');
        }
        $secret = get_option(self::SECRET_OPTION, '');
        if (!is_string($secret) || '' === $secret) {
            // A separate persistent key keeps links valid when WordPress salts change.
            add_option(self::SECRET_OPTION, bin2hex(random_bytes(32)), '', \false);
            $secret = get_option(self::SECRET_OPTION, '');
        }
        if (!is_string($secret) || '' === $secret) {
            throw new RuntimeException('Could not store the public request key.');
        }
        return self::sign($request, $secret, $management);
    }
    public static function is_valid(RequestRecord $request, string $token, bool $management = \false): bool
    {
        if (!Integration::is_super()) {
            return \false;
        }
        $secret = get_option(self::SECRET_OPTION, '');
        return null !== $request->get_id() && is_string($secret) && '' !== $secret && hash_equals(self::sign($request, $secret, $management), $token);
    }
    private static function sign(RequestRecord $request, string $secret, bool $management): string
    {
        return hash_hmac('sha256', ($management ? 'request-management:' : 'status-view:') . $request->get_request_type() . ':' . $request->get_id(), $secret);
    }
}
