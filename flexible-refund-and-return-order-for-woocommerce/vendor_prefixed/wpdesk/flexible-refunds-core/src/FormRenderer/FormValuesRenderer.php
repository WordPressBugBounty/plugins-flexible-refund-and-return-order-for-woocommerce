<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer;

use FRFreeVendor\WPDesk\Persistence\Adapter\WordPress\WordpressOptionsContainer;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\Tabs\RefundOrderTab;
class FormValuesRenderer
{
    const FIELD_DATA_KEY = 'fr_refund_request_data';
    const FIELD_UPLOAD_KEY = 'fr_refund_request_file';
    /**
     * @param WC_Order $order
     *
     * @return string
     */
    public function output(WC_Order $order, ?RequestRecord $request = null): string
    {
        if (null !== $request) {
            $snapshot = $request->get_form_snapshot();
            $fields = is_array($snapshot['schema'] ?? null) ? $snapshot['schema'] : [];
            $form_data = $request->get_submitted_values();
        } else {
            $settings = new WordpressOptionsContainer(RefundOrderTab::SETTING_PREFIX);
            $fields = $settings->get_fallback('form_builder', []);
            $form_data = $order->get_meta(self::FIELD_DATA_KEY);
        }
        $output = '';
        if (is_array($fields) && !empty($fields)) {
            foreach ($fields as $name => $field) {
                if (empty($field['enable'])) {
                    continue;
                }
                if (($field['type'] ?? '') === 'upload') {
                    $output = $this->output_upload_field($field, $name, $form_data, $output);
                }
                if (isset($form_data[$name])) {
                    $value = is_array($form_data[$name]) ? implode(', ', array_map('esc_html', $form_data[$name])) : esc_html($form_data[$name]);
                    $output .= '<p><strong>' . esc_html($field['label'] ?? $name) . '</strong>: ' . $value . '</p>';
                }
            }
        }
        return $output;
    }
    private function output_upload_field(array $field, string $name, array $form_data, string $output)
    {
        if (isset($form_data['attachments'][$name]['file'], $form_data['attachments'][$name]['url'])) {
            $file_name = basename($form_data['attachments'][$name]['file']);
            $file_url = $form_data['attachments'][$name]['url'];
            return $output . '<p><strong>' . esc_html($field['label']) . '</strong>: <a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a></p>';
        }
        if (!isset($form_data['attachments'][$name][0]['file'])) {
            return $output;
        }
        $output .= '<p><strong>' . esc_html($field['label']) . '</strong> : <ul>';
        foreach ($form_data['attachments'][$name] as $file) {
            if (!isset($file['file'], $file['url'])) {
                continue;
            }
            $file_name = basename($file['file']);
            $file_url = $file['url'];
            $output .= '<li><a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a></li>';
        }
        $output .= '</ul></p>';
        return $output;
    }
}
