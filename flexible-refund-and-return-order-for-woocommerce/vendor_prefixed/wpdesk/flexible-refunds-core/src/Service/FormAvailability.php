<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions\DateCondition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions\RefundCondition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
final class FormAvailability
{
    private bool $is_pro;
    public function __construct(bool $is_pro)
    {
        $this->is_pro = $is_pro;
    }
    public function can_start(FormDefinition $form, WC_Order $order, bool $allow_cancelled_order = \false): bool
    {
        if (!$form->is_enabled() || !$this->is_allowed_by_license($form)) {
            return \false;
        }
        $settings = $form->get_settings();
        if ($this->is_pro) {
            $conditions = is_array($settings['visibility_conditions'] ?? null) ? $settings['visibility_conditions'] : [];
            if (!(new RefundCondition($conditions, $order))->should_show($allow_cancelled_order)) {
                return \false;
            }
            if ('yes' === ($settings['auto_hide'] ?? 'no')) {
                $period = is_array($settings['auto_hide_settings'] ?? null) ? $settings['auto_hide_settings'] : [];
                if (!(new DateCondition($period, $order))->should_show()) {
                    return \false;
                }
            }
        }
        return \true;
    }
    public function is_allowed_by_license(FormDefinition $form): bool
    {
        return $this->is_pro || RequestType::REFUND === $form->get_request_type();
    }
}
