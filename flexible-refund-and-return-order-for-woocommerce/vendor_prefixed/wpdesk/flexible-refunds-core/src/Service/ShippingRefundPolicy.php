<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use WC_Order;
final class ShippingRefundPolicy
{
    public const DISABLED = 'no';
    public const FULL_COST = 'yes';
    public const LOWEST_COST = 'lowest_cost';
    public const CUSTOMER_CHOICE = 'customer_choice';
    public function apply(WC_Order $order, array $items, string $mode, float $lowest_cost = 0.0): array
    {
        if (!in_array($mode, [self::FULL_COST, self::LOWEST_COST, self::CUSTOMER_CHOICE], \true)) {
            $mode = self::DISABLED;
        }
        $shipping_items = $order->get_items('shipping');
        $selected_shipping_items = [];
        foreach ($shipping_items as $item_id => $shipping_item) {
            if (isset($items[$item_id])) {
                $selected_shipping_items[$item_id] = $shipping_item;
            }
            unset($items[$item_id]);
        }
        if (self::DISABLED === $mode || !$this->has_selected_product($order, $items)) {
            return $items;
        }
        $is_full_return = $this->is_full_return($order, $items);
        if (self::CUSTOMER_CHOICE !== $mode) {
            $selected_shipping_items = $shipping_items;
        }
        $remaining_cost = self::LOWEST_COST === $mode && !$is_full_return ? max(0.0, $lowest_cost) : null;
        foreach ($selected_shipping_items as $item_id => $shipping_item) {
            $remaining = max(0, (int) $shipping_item->get_quantity() + (int) $order->get_qty_refunded_for_item($item_id, 'shipping'));
            if (0 === $remaining) {
                continue;
            }
            $items[$item_id] = ['qty' => $remaining];
            if (null !== $remaining_cost) {
                $item_cost = (float) $shipping_item->get_total() + (float) $shipping_item->get_total_tax();
                $refund_amount = min($remaining_cost, $item_cost);
                if ($refund_amount <= 0.0) {
                    unset($items[$item_id]);
                    continue;
                }
                $items[$item_id]['refund_amount'] = $refund_amount;
                $remaining_cost -= $refund_amount;
            }
        }
        return $items;
    }
    public function calculate_refund_amounts(float $total, array $taxes, float $gross_limit, int $decimals): array
    {
        $gross_total = $total + array_sum($taxes);
        $gross_refund = round(min(max(0.0, $gross_limit), $gross_total), $decimals);
        $ratio = $gross_total > 0.0 ? $gross_refund / $gross_total : 0.0;
        $refund_taxes = array_map(static function ($tax) use ($ratio, $decimals): float {
            return round((float) $tax * $ratio, $decimals);
        }, $taxes);
        return [max(0.0, $gross_refund - array_sum($refund_taxes)), $refund_taxes, $gross_refund];
    }
    private function has_selected_product(WC_Order $order, array $items): bool
    {
        foreach ($order->get_items() as $item_id => $item) {
            if ((int) ($items[$item_id]['qty'] ?? 0) > 0) {
                return \true;
            }
        }
        return \false;
    }
    public function is_full_return(WC_Order $order, array $items): bool
    {
        $has_remaining_products = \false;
        foreach ($order->get_items() as $item_id => $item) {
            $remaining = max(0, (int) $item->get_quantity() + (int) $order->get_qty_refunded_for_item($item_id));
            if (0 === $remaining) {
                continue;
            }
            $has_remaining_products = \true;
            if ((int) ($items[$item_id]['qty'] ?? 0) < $remaining) {
                return \false;
            }
        }
        return $has_remaining_products;
    }
}
