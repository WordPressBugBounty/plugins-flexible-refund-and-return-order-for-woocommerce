<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use Exception;
use WC_Order;
use WP_Error;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Coupon\Coupon;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Requests\Approved;
use FRFreeVendor\WPDesk\Persistence\Adapter\ArrayContainer;
class MonetaryRefundProcessor
{
    public function process(WC_Order $order, RequestRecord $request, array $items, callable $on_refund_created): void
    {
        $submitted_items = $request->get_submitted_values()['items'] ?? [];
        $refund_items = [];
        foreach (is_array($submitted_items) ? $submitted_items : [] as $item_id => $submitted_item) {
            $requested_qty = absint(is_array($submitted_item) ? $submitted_item['qty'] ?? 0 : 0);
            $selected_item = $items[$item_id] ?? $submitted_item;
            $selected_qty = absint(is_array($selected_item) ? $selected_item['qty'] ?? 0 : 0);
            if ($requested_qty > 0 && $selected_qty > 0) {
                $refund_items[$item_id] = ['qty' => min($requested_qty, $selected_qty)];
            }
        }
        if ([] === $refund_items) {
            throw new Exception(__('Select at least one order item to refund.', 'flexible-refund-and-return-order-for-woocommerce'));
        }
        $snapshot = $request->get_form_snapshot();
        $settings = is_array($snapshot['settings'] ?? null) ? $snapshot['settings'] : [];
        $refund = (new Approved(new ArrayContainer($settings)))->refund_line_items($order, ['items' => $refund_items]);
        if (is_wp_error($refund) && $refund instanceof WP_Error) {
            throw new Exception($refund->get_error_message());
        }
        $on_refund_created();
        if ('coupon' === ($settings['refund_type'] ?? 'bank') && Integration::is_super() && method_exists($refund, 'get_total')) {
            $total = abs((float) $refund->get_total());
            if ($total > 0) {
                (new Coupon($order))->create_coupon($total);
            }
        }
    }
}
