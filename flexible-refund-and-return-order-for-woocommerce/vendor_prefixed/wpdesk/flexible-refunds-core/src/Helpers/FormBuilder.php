<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
/**
 * Form builder helper functions.
 */
class FormBuilder
{
    /**
     * @param array $field
     *
     * @return array
     */
    public static function parse_field_args(array $field): array
    {
        return wp_parse_args($field, ['type' => 'text', 'name' => '', 'label' => '', 'html' => '', 'enable' => 1, 'required' => 0, 'options' => [], 'placeholder' => '', 'css' => '', 'description' => '', 'maxlength' => '', 'minlength' => '', 'files_limit' => 1]);
    }
    public static function buttons_field(): array
    {
        return ['text' => ['label' => esc_html__('Text', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => \true], 'textarea' => ['label' => esc_html__('Textarea', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => \true], 'checkbox' => ['label' => esc_html__('Checkbox', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => \true], 'radio' => ['label' => esc_html__('Radio', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => \true], 'select' => ['label' => esc_html__('Select', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => \true], 'multiselect' => ['label' => esc_html__('Multi Select', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => Integration::is_super()], 'upload' => ['label' => esc_html__('Upload', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => Integration::is_super()], 'html' => ['label' => esc_html__('HTML', 'flexible-refund-and-return-order-for-woocommerce'), 'free' => Integration::is_super()]];
    }
    /**
     * @param string $field_type
     *
     * @return bool
     */
    public static function is_field_type_available(string $field_type): bool
    {
        $buttons = self::buttons_field();
        return !isset($buttons[$field_type]) || !empty($buttons[$field_type]['free']);
    }
    /**
     * @param array<string, array<string, mixed>> $fields
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_supported_fields(array $fields): array
    {
        return array_filter($fields, static function (array $field): bool {
            $data = self::parse_field_args($field);
            return self::is_field_type_available($data['type']);
        });
    }
}
