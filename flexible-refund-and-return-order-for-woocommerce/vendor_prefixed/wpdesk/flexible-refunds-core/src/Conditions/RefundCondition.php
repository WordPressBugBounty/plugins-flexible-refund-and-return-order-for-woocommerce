<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions;

use WC_Order_Item_Product;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\RegisterOrderStatus;
class RefundCondition extends AbstractCondition
{
    const EXCLUDED_CONDITIONS = ['wc-cancelled', 'wc-refunded', 'wc-failed'];
    /**
     * @return bool
     */
    public function should_show(bool $allow_cancelled_order = \false): bool
    {
        $order_status = 'wc-' . $this->get_order()->get_status();
        if (in_array($order_status, self::EXCLUDED_CONDITIONS, \true) && (!$allow_cancelled_order || 'wc-cancelled' !== $order_status)) {
            return \false;
        }
        if ($order_status === RegisterOrderStatus::REQUEST_REFUND_STATUS) {
            return \true;
        }
        $conditions = $this->get_conditions();
        if (!isset($conditions['condition_type'])) {
            return \true;
        }
        foreach ($conditions['condition_type'] as $condition_type_key => $condition_type) {
            $operator = $conditions['condition_operator'][$condition_type_key] ?? [];
            $match = $conditions['condition_match'][$condition_type_key] ?? $this->get_legacy_match_mode($condition_type, $operator);
            $values = $conditions['condition_values'][$condition_type_key][$condition_type] ?? [];
            if (empty($values)) {
                continue;
            }
            if (!$this->condition_factory($condition_type, $operator, $values, $match)) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @param string       $type
     * @param string       $operator
     * @param string|array $values
     * @param string       $match
     *
     * @return bool
     */
    private function condition_factory(string $type, string $operator, $values, string $match): bool
    {
        switch ($type) {
            case 'order_statuses':
                return $this->order_statuses_condition($operator, $values);
            case 'user_roles':
                return $this->user_roles_condition($operator, $values);
            case 'product_cats':
                return $this->product_cats_condition($operator, $values, $match);
            case 'products':
                return $this->products_condition($operator, $values, $match);
            case 'payment_methods':
                return $this->payment_methods_condition($operator, $values);
        }
        return \false;
    }
    /**
     * @param string $operator
     * @param array  $values
     *
     * @return bool
     */
    private function order_statuses_condition(string $operator, array $values): bool
    {
        $status = in_array('wc-' . $this->get_order()->get_status(), $values, \true);
        if ($operator === 'is_not') {
            return !$status;
        }
        return $status;
    }
    /**
     * @param string $operator
     * @param array  $values
     *
     * @return bool
     */
    private function user_roles_condition(string $operator, array $values): bool
    {
        global $current_user;
        $user_role = \false;
        if (is_user_logged_in() && !empty($current_user->roles[0])) {
            $user_role = in_array($current_user->roles[0], $values, \true);
            if ($operator === 'is_not') {
                return !$user_role;
            }
        }
        return $user_role;
    }
    /**
     * @param string $operator
     * @param array  $values
     *
     * @return bool
     */
    private function product_cats_condition(string $operator, array $values, string $match): bool
    {
        $item_matches = [];
        foreach ($this->get_order()->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }
            $product = $item->get_product();
            if (!$product || !method_exists($product, 'get_category_ids')) {
                continue;
            }
            $order_product_cats = array_map('strval', $product->get_category_ids());
            $item_matches[] = !empty(array_intersect($order_product_cats, array_map('strval', $values)));
        }
        return $this->match_items($item_matches, $operator, $match);
    }
    /**
     * @param string $operator
     * @param array  $values
     *
     * @return bool
     */
    private function products_condition(string $operator, array $values, string $match): bool
    {
        $item_matches = [];
        $values = array_map('strval', $values);
        foreach ($this->get_order()->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }
            $item_matches[] = in_array((string) $item->get_product_id(), $values, \true);
        }
        return $this->match_items($item_matches, $operator, $match);
    }
    /**
     * @param bool[] $item_matches
     * @param string $operator
     * @param string $match
     *
     * @return bool
     */
    private function match_items(array $item_matches, string $operator, string $match): bool
    {
        if (empty($item_matches)) {
            return \false;
        }
        if ($operator === 'is_not') {
            $item_matches = array_map(static function (bool $item_match): bool {
                return !$item_match;
            }, $item_matches);
        }
        if ($match === 'all') {
            return !in_array(\false, $item_matches, \true);
        }
        return in_array(\true, $item_matches, \true);
    }
    private function get_legacy_match_mode(string $type, string $operator): string
    {
        if (!in_array($type, ['products', 'product_cats'], \true)) {
            return 'any';
        }
        return $operator === 'is_not' ? 'all' : 'any';
    }
    /**
     * @param string $operator
     * @param array  $values
     *
     * @return bool
     */
    private function payment_methods_condition(string $operator, array $values): bool
    {
        $payment_method = in_array($this->get_order()->get_payment_method(), $values, \true);
        if ($operator === 'is_not') {
            return !$payment_method;
        }
        return $payment_method;
    }
}
