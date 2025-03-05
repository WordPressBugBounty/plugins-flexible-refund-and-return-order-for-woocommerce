<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions;

use DateTimeZone;
use Exception;
class DateCondition extends AbstractCondition
{
    /**
     * @return bool
     */
    public function should_show(): bool
    {
        try {
            $conditions = $this->get_conditions();
            if (empty($conditions)) {
                return \true;
            }
            $time_value = $conditions['time_value'] ?? 1;
            $time_period = $conditions['time_period'] ?? 'year';
            $order_date = $this->get_order()->get_date_created();
            $order_date->modify($time_value . ' ' . $time_period);
            $order_date->setTimezone(new DateTimeZone('UTC'));
            return $order_date->getTimestamp() > time();
        } catch (Exception $e) {
            return \true;
        }
    }
}
