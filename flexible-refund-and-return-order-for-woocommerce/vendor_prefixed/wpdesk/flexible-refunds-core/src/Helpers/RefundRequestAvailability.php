<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;

use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions\DateCondition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions\RefundCondition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
class RefundRequestAvailability
{
    /**
     * @var PersistentContainer
     */
    private $settings;
    public function __construct(PersistentContainer $settings)
    {
        $this->settings = $settings;
    }
    public function can_initiate_refund_request(WC_Order $order): bool
    {
        return $this->meets_refund_conditions($order) && !$this->is_auto_hidden($order);
    }
    public function is_auto_hidden(WC_Order $order): bool
    {
        if ($this->settings->get_fallback('refund_auto_hide', 'no') !== 'yes' || !Integration::is_super()) {
            return \false;
        }
        $conditions = $this->settings->get_fallback('refund_auto_hide_settings', []);
        if (!is_array($conditions)) {
            $conditions = [];
        }
        $date_condition = new DateCondition($conditions, $order);
        return !$date_condition->should_show();
    }
    /**
     * @return array<string, mixed>
     */
    public function get_auto_hide_settings(): array
    {
        $conditions = $this->settings->get_fallback('refund_auto_hide_settings', []);
        return is_array($conditions) ? $conditions : [];
    }
    private function meets_refund_conditions(WC_Order $order): bool
    {
        $conditions = $this->settings->get_fallback('refund_conditions_setting', []);
        if (!is_array($conditions)) {
            $conditions = [];
        }
        $condition = new RefundCondition($conditions, $order);
        return $condition->should_show();
    }
}
