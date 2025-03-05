<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer;

use FRFreeVendor\WPDesk\Persistence\Adapter\WordPress\WordpressOptionsContainer;
use WC_Order;
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
    public function output(WC_Order $order): string
    {
        $settings = new WordpressOptionsContainer(RefundOrderTab::SETTING_PREFIX);
        $fields = $settings->get_fallback('form_builder', []);
        $form_data = $order->get_meta(self::FIELD_DATA_KEY);
        $output = '';
        if (is_array($fields) && !empty($fields)) {
            foreach ($fields as $name => $field) {
                if ($field['type'] === 'upload') {
                    $output = $this->output_upload_field($field, $name, $form_data, $output);
                }
                if (isset($form_data[$name])) {
                    $output .= '<p><strong>' . $field['label'] . '</strong>: ' . (is_array($form_data[$name]) ? implode(', ', $form_data[$name]) : $form_data[$name]) . '<p>';
                }
            }
        }
        return $output;
    }
    private function output_upload_field(array $field, string $name, array $form_data, string $output)
    {
        if (isset($form_data['attachments'][$name]['file'])) {
            $file_name = basename($form_data['attachments'][$name]['file']);
            $file_url = $form_data['attachments'][$name]['url'];
            return $output . '<p><strong>' . $field['label'] . '</strong>: <a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a><p>';
        }
        if (!isset($form_data['attachments'][$name][0]['file'])) {
            return $output;
        }
        $output .= '<p><strong>' . $field['label'] . '</strong> : <ul>';
        foreach ($form_data['attachments'][$name] as $file) {
            $file_name = basename($file['file']);
            $file_url = $file['url'];
            $output .= '<li><a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a></li>';
        }
        $output .= '</ul></p>';
        return $output;
    }
}
