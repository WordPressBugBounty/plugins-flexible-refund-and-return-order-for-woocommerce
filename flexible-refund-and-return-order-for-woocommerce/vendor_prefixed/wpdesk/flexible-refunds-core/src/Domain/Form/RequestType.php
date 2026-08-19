<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form;

use InvalidArgumentException;
final class RequestType
{
    public const REFUND = 'refund';
    public const RECLAMATION = 'reclamation';
    public const REPAIR = 'repair';
    public static function all(): array
    {
        return [self::REFUND, self::RECLAMATION, self::REPAIR];
    }
    public static function assert_valid(string $type): void
    {
        if (!in_array($type, self::all(), \true)) {
            throw new InvalidArgumentException(sprintf('Unsupported request type: %s', $type));
        }
    }
    public static function supports_monetary_refund(string $type): bool
    {
        self::assert_valid($type);
        return self::REFUND === $type;
    }
    public static function get_label(string $type): string
    {
        self::assert_valid($type);
        $labels = [self::REFUND => __('Refund', 'flexible-refund-and-return-order-for-woocommerce'), self::RECLAMATION => __('Reclamation', 'flexible-refund-and-return-order-for-woocommerce'), self::REPAIR => __('Repair', 'flexible-refund-and-return-order-for-woocommerce')];
        return $labels[$type];
    }
    public static function get_order_status_label(string $type): string
    {
        self::assert_valid($type);
        $labels = [self::REFUND => __('Refund Request', 'flexible-refund-and-return-order-for-woocommerce'), self::RECLAMATION => __('Reclamation Request', 'flexible-refund-and-return-order-for-woocommerce'), self::REPAIR => __('Repair Request', 'flexible-refund-and-return-order-for-woocommerce')];
        return $labels[$type];
    }
    public static function get_settings_title(string $type): string
    {
        self::assert_valid($type);
        $titles = [self::REFUND => __('Refund settings', 'flexible-refund-and-return-order-for-woocommerce'), self::RECLAMATION => __('Reclamation settings', 'flexible-refund-and-return-order-for-woocommerce'), self::REPAIR => __('Repair settings', 'flexible-refund-and-return-order-for-woocommerce')];
        return $titles[$type];
    }
}
