<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\ShippingRefundPolicy;
final class FormConfigurationSanitizer
{
    private const FIELD_TYPES = ['text', 'textarea', 'checkbox', 'select', 'multiselect', 'radio', 'upload', 'html'];
    public function sanitize(string $request_type, array $input, array $current_settings): array
    {
        RequestType::assert_valid($request_type);
        $label = sanitize_text_field((string) ($input['button_label'] ?? ''));
        if ('' === $label) {
            $label = $this->get_default_label($request_type);
        }
        $settings = $current_settings;
        $settings['subtype'] = $request_type;
        $raw_settings = is_array($input['settings'] ?? null) ? $input['settings'] : [];
        $settings['visibility_conditions'] = $this->sanitize_conditions($raw_settings['visibility_conditions'] ?? []);
        $settings['policy_page_id'] = absint($raw_settings['policy_page_id'] ?? 0);
        $settings['auto_hide'] = $this->sanitize_checkbox($raw_settings['auto_hide'] ?? 'no');
        $settings['auto_hide_settings'] = $this->sanitize_auto_hide($raw_settings['auto_hide_settings'] ?? []);
        if (RequestType::REFUND === $request_type) {
            $settings['refund_type'] = in_array($raw_settings['refund_type'] ?? '', ['bank', 'coupon'], \true) ? $raw_settings['refund_type'] : 'bank';
            $settings['auto_approval'] = $this->sanitize_checkbox($raw_settings['auto_approval'] ?? 'no');
            $shipping_mode = $raw_settings['refund_shipping'] ?? ShippingRefundPolicy::DISABLED;
            $raw_shipping_lowest_cost = $raw_settings['refund_shipping_lowest_cost'] ?? 0;
            $settings['refund_shipping'] = in_array($shipping_mode, [ShippingRefundPolicy::DISABLED, ShippingRefundPolicy::FULL_COST, ShippingRefundPolicy::LOWEST_COST, ShippingRefundPolicy::CUSTOMER_CHOICE], \true) ? $shipping_mode : ShippingRefundPolicy::DISABLED;
            $settings['refund_shipping_lowest_cost'] = wc_format_decimal(max(0.0, is_scalar($raw_shipping_lowest_cost) ? (float) $raw_shipping_lowest_cost : 0.0), wc_get_price_decimals());
        }
        return ['enabled' => 'yes' === ($input['enabled'] ?? 'no'), 'button_label' => $label, 'schema' => $this->sanitize_schema($input['schema'] ?? []), 'settings' => $settings];
    }
    private function sanitize_schema($schema): array
    {
        if (!is_array($schema)) {
            return [];
        }
        $sanitized = [];
        foreach ($schema as $name => $field) {
            $name = sanitize_key((string) $name);
            if ('' === $name || !is_array($field)) {
                continue;
            }
            $type = sanitize_key((string) ($field['type'] ?? ''));
            if (!in_array($type, self::FIELD_TYPES, \true)) {
                continue;
            }
            $sanitized[$name] = ['type' => $type, 'label' => sanitize_text_field((string) ($field['label'] ?? '')), 'enable' => isset($field['enable']) ? 1 : 0, 'required' => isset($field['required']) ? 1 : 0, 'description' => sanitize_textarea_field((string) ($field['description'] ?? '')), 'placeholder' => sanitize_text_field((string) ($field['placeholder'] ?? '')), 'css' => sanitize_html_class((string) ($field['css'] ?? '')), 'minlength' => max(0, absint($field['minlength'] ?? 0)), 'maxlength' => max(0, absint($field['maxlength'] ?? 0)), 'files_limit' => min(99, max(1, absint($field['files_limit'] ?? 1))), 'options' => $this->sanitize_string_list($field['options'] ?? [])];
            if ('html' === $type) {
                $sanitized[$name]['html'] = wp_kses_post((string) ($field['html'] ?? ''));
            }
        }
        return $sanitized;
    }
    private function sanitize_conditions($conditions): array
    {
        if (!is_array($conditions)) {
            return [];
        }
        $allowed_types = ['user_roles', 'order_statuses', 'product_cats', 'products', 'payment_methods'];
        $result = ['condition_type' => [], 'condition_operator' => [], 'condition_match' => [], 'condition_values' => []];
        foreach ((array) ($conditions['condition_type'] ?? []) as $index => $type) {
            $type = sanitize_key((string) $type);
            if (!in_array($type, $allowed_types, \true)) {
                continue;
            }
            $operator = ($conditions['condition_operator'][$index] ?? 'is') === 'is_not' ? 'is_not' : 'is';
            $match = ($conditions['condition_match'][$index] ?? 'any') === 'all' ? 'all' : 'any';
            $values = $conditions['condition_values'][$index][$type] ?? [];
            $sanitized_index = count($result['condition_type']);
            $result['condition_type'][$sanitized_index] = $type;
            $result['condition_operator'][$sanitized_index] = $operator;
            $result['condition_match'][$sanitized_index] = $match;
            $result['condition_values'][$sanitized_index][$type] = $this->sanitize_string_list($values);
        }
        return empty($result['condition_type']) ? [] : $result;
    }
    private function sanitize_auto_hide($settings): array
    {
        $settings = is_array($settings) ? $settings : [];
        $period = sanitize_key((string) ($settings['time_period'] ?? 'days'));
        if (!in_array($period, ['hours', 'days', 'weeks', 'months', 'years'], \true)) {
            $period = 'days';
        }
        return ['time_value' => min(10000, max(1, absint($settings['time_value'] ?? 1))), 'time_period' => $period];
    }
    private function sanitize_string_list($values): array
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        return array_values(array_filter(array_map(static function ($value): string {
            return sanitize_text_field((string) $value);
        }, $values), static function (string $value): bool {
            return '' !== $value;
        }));
    }
    private function sanitize_checkbox($value): string
    {
        return 'yes' === $value ? 'yes' : 'no';
    }
    private function get_default_label(string $request_type): string
    {
        return RequestType::get_label($request_type);
    }
}
