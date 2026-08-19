<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request;

use InvalidArgumentException;
final class RequestStatus
{
    public const REQUESTED = 'requested';
    public const APPROVED = 'approved';
    public const SHIPMENT = 'shipment';
    public const VERIFYING = 'verifying';
    public const REFUSED = 'refused';
    public const CANCELED = 'canceled';
    public static function all(): array
    {
        return array_merge(self::active(), self::terminal());
    }
    public static function active(): array
    {
        return [self::REQUESTED, self::SHIPMENT, self::VERIFYING];
    }
    public static function terminal(): array
    {
        return [self::APPROVED, self::REFUSED, self::CANCELED];
    }
    public static function assert_valid(string $status): void
    {
        if (!in_array($status, self::all(), \true)) {
            throw new InvalidArgumentException(sprintf('Unsupported request status: %s', $status));
        }
    }
    public static function is_active(string $status): bool
    {
        self::assert_valid($status);
        return in_array($status, self::active(), \true);
    }
    public static function normalize_legacy(string $status): string
    {
        if ('rejected' === $status) {
            return self::REFUSED;
        }
        return in_array($status, self::all(), \true) ? $status : self::REQUESTED;
    }
}
