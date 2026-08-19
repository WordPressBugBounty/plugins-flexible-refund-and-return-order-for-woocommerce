<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use WC_Order;
class MonetaryRefundTracker
{
    private const META_KEY = '_fr_processed_monetary_refund_request_ids';
    public function has_processed(WC_Order $order, int $request_id): bool
    {
        return in_array($request_id, $this->get_processed_ids($order), \true);
    }
    public function mark_processed(WC_Order $order, int $request_id): void
    {
        $processed_ids = $this->get_processed_ids($order);
        if (in_array($request_id, $processed_ids, \true)) {
            return;
        }
        $processed_ids[] = $request_id;
        $order->update_meta_data(self::META_KEY, $processed_ids);
        $order->save_meta_data();
    }
    /** @return int[] */
    private function get_processed_ids(WC_Order $order): array
    {
        $value = $order->get_meta(self::META_KEY);
        return is_array($value) ? array_values(array_unique(array_map('intval', $value))) : [];
    }
}
