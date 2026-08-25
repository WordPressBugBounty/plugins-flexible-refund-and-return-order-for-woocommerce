<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
final class LegacySettingsMapper
{
    private const OPTION_PREFIX = 'fr_refund_';
    /** @return FormDefinition[] */
    public function get_system_forms(): array
    {
        return [$this->get_system_form(RequestType::REFUND), $this->get_system_form(RequestType::RECLAMATION), $this->get_system_form(RequestType::REPAIR)];
    }
    public function get_system_form(string $request_type): FormDefinition
    {
        RequestType::assert_valid($request_type);
        $now = gmdate('Y-m-d H:i:s');
        if (RequestType::REFUND === $request_type) {
            return $this->get_refund_form($now);
        }
        if (RequestType::RECLAMATION === $request_type) {
            return $this->get_default_form($request_type, __('Reclamation', 'flexible-refund-and-return-order-for-woocommerce'), $now);
        }
        return $this->get_default_form($request_type, __('Repair', 'flexible-refund-and-return-order-for-woocommerce'), $now);
    }
    private function get_refund_form(string $now): FormDefinition
    {
        $schema = $this->get_option('form_builder', []);
        if (!is_array($schema)) {
            $schema = [];
        }
        return new FormDefinition(null, RequestType::REFUND, 'yes' === $this->get_option('refund_button', 'no'), __('Refund', 'flexible-refund-and-return-order-for-woocommerce'), 1, $schema, ['subtype' => RequestType::REFUND, 'visibility_conditions' => $this->get_array_option('refund_conditions_setting'), 'auto_hide' => $this->get_option('refund_auto_hide', 'no'), 'auto_hide_settings' => $this->get_array_option('refund_auto_hide_settings'), 'refund_type' => $this->get_option('refund_type', 'bank'), 'auto_approval' => $this->get_option('refund_auto_accept', 'no'), 'refund_shipping' => $this->get_option('refund_enable_shipment', 'no'), 'refund_shipping_lowest_cost' => $this->get_option('refund_shipping_lowest_cost', 0), 'policy_page_id' => (int) $this->get_option('selected_post_id', 0)], $now, $now);
    }
    private function get_default_form(string $request_type, string $label, string $now): FormDefinition
    {
        return new FormDefinition(null, $request_type, \false, $label, 1, [], ['subtype' => $request_type, 'visibility_conditions' => [], 'auto_hide' => 'no', 'auto_hide_settings' => ['time_value' => 1, 'time_period' => 'days'], 'policy_page_id' => 0], $now, $now);
    }
    /** @return mixed */
    private function get_option(string $key, $default)
    {
        return get_option(self::OPTION_PREFIX . $key, $default);
    }
    private function get_array_option(string $key): array
    {
        $value = $this->get_option($key, []);
        return is_array($value) ? $value : [];
    }
}
