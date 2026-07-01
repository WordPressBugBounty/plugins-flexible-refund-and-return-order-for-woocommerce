<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;

use WC_Order;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
class OrderReferenceResolver
{
    public const SEARCH_BY_ORDER_NUMBER_OPTION = 'refund_search_by_order_number';
    private const ORDER_REFERENCE_META_KEYS_FILTER = 'flexible_refunds_order_reference_meta_keys';
    private const ORDER_REFERENCE_RESOLVER_FILTER = 'flexible_refunds_resolve_order_reference';
    /**
     * @var PersistentContainer
     */
    private $settings;
    public function __construct(PersistentContainer $settings)
    {
        $this->settings = $settings;
    }
    public function find_order(string $reference, string $email = ''): ?WC_Order
    {
        $reference = $this->normalize_reference($reference);
        $email = $this->normalize_email($email);
        if ('' === $reference || '' === $email) {
            return null;
        }
        if (!$this->search_by_order_number_enabled()) {
            return $this->find_order_by_id($reference, $email);
        }
        $filtered_order = $this->get_filtered_order($reference, $email);
        if ($filtered_order instanceof WC_Order) {
            return $filtered_order;
        }
        $matched_by_number = [];
        foreach ($this->get_candidate_orders($reference) as $order) {
            if ('' !== $email && !$this->is_matching_email($order, $email)) {
                continue;
            }
            if ($this->is_matching_order_number($order, $reference)) {
                $matched_by_number[] = $order;
            }
        }
        return $matched_by_number[0] ?? null;
    }
    public function search_by_order_number_enabled(): bool
    {
        return 'yes' === $this->settings->get_fallback(self::SEARCH_BY_ORDER_NUMBER_OPTION, 'no');
    }
    private function find_order_by_id(string $reference, string $email): ?WC_Order
    {
        if (!is_numeric($reference)) {
            return null;
        }
        $order = wc_get_order((int) $reference);
        if (!$order instanceof WC_Order) {
            return null;
        }
        if ('' !== $email && !$this->is_matching_email($order, $email)) {
            return null;
        }
        return $order;
    }
    /**
     * @return WC_Order[]
     */
    private function get_candidate_orders(string $reference): array
    {
        $orders = [];
        $seen = [];
        if (is_numeric($reference)) {
            $this->append_order_by_id($orders, $seen, (int) $reference);
        }
        if (function_exists('wc_order_search')) {
            $order_ids = wc_order_search($reference);
            if (is_array($order_ids)) {
                foreach ($order_ids as $order_id) {
                    $this->append_order_by_id($orders, $seen, (int) $order_id);
                }
            }
        }
        foreach ($this->get_searchable_meta_keys($reference) as $meta_key) {
            if (!function_exists('wc_get_orders')) {
                break;
            }
            $order_ids = wc_get_orders(['limit' => 10, 'return' => 'ids', 'meta_key' => $meta_key, 'meta_value' => $reference]);
            if (!is_array($order_ids)) {
                continue;
            }
            foreach ($order_ids as $order_id) {
                $this->append_order_by_id($orders, $seen, (int) $order_id);
            }
        }
        return $orders;
    }
    private function append_order_by_id(array &$orders, array &$seen, int $order_id): void
    {
        if ($order_id <= 0 || isset($seen[$order_id])) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }
        $orders[] = $order;
        $seen[$order_id] = \true;
    }
    /**
     * @return string[]
     */
    private function get_searchable_meta_keys(string $reference): array
    {
        $meta_keys = apply_filters(self::ORDER_REFERENCE_META_KEYS_FILTER, ['_order_number'], $reference);
        if (!is_array($meta_keys)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('strval', $meta_keys))));
    }
    private function get_filtered_order(string $reference, string $email): ?WC_Order
    {
        $order = apply_filters(self::ORDER_REFERENCE_RESOLVER_FILTER, null, $reference, $email);
        if (is_numeric($order)) {
            $order = wc_get_order((int) $order);
        }
        if (!$order instanceof WC_Order) {
            return null;
        }
        if (!$this->is_matching_order_number($order, $reference)) {
            return null;
        }
        if ('' !== $email && !$this->is_matching_email($order, $email)) {
            return null;
        }
        return $order;
    }
    private function is_matching_order_number(WC_Order $order, string $reference): bool
    {
        return $this->normalize_reference((string) $order->get_order_number()) === $reference;
    }
    private function is_matching_email(WC_Order $order, string $email): bool
    {
        $billing_email = $this->normalize_email((string) $order->get_billing_email());
        return '' !== $billing_email && hash_equals($billing_email, $email);
    }
    private function normalize_reference(string $reference): string
    {
        return ltrim(trim($reference), '#');
    }
    private function normalize_email(string $email): string
    {
        return strtolower(trim($email));
    }
}
