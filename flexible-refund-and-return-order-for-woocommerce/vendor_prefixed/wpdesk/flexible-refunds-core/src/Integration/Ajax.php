<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use Exception;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\FormBuilder;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Requests\RequestsFactory;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\RequestWorkflow;
class Ajax implements Hookable
{
    private PersistentContainer $settings;
    private Renderer $renderer;
    private RequestRepository $requests;
    private RequestWorkflow $workflow;
    public function __construct(PersistentContainer $settings, Renderer $renderer, RequestRepository $requests, RequestWorkflow $workflow)
    {
        $this->settings = $settings;
        $this->renderer = $renderer;
        $this->requests = $requests;
        $this->workflow = $workflow;
    }
    public function hooks()
    {
        add_action('wp_ajax_fr_refund_request', [$this, 'create_refund']);
        add_action('wp_ajax_fr_fb_insert_field', [$this, 'form_builder_insert_field']);
    }
    /**
     * @param WC_Order $order
     * @param array    $post_data
     *
     * @return bool
     */
    public function should_auto_create_refund(WC_Order $order, array $post_data): bool
    {
        $is_auto_accept = $this->settings->get_fallback('refund_auto_accept', 'no');
        if ($is_auto_accept === 'yes' && Integration::is_super()) {
            $post_data = wp_parse_args($post_data, ['order_ID' => 0, 'note' => '', 'status' => '', 'form' => '', 'items' => []]);
            try {
                $request = (new RequestsFactory($this->settings))->get_request('approved');
                $request->do_action($order, $post_data);
                return \true;
            } catch (Exception $e) {
                return \false;
            }
        }
        return \false;
    }
    public function create_refund()
    {
        $post_data = wp_parse_args(
            //phpcs:ignore WordPress.Security.NonceVerification.Missing
            wp_unslash($_POST),
            ['order_ID' => 0, 'note' => '', 'status' => '', 'form' => '', 'items' => []]
        );
        $status = $post_data['status'];
        $order_ID = $post_data['order_ID'];
        $request_id = absint($post_data['request_id'] ?? 0);
        if (!$order_ID || !current_user_can('edit_shop_order', $order_ID) && !current_user_can('edit_post', $order_ID)) {
            wp_send_json_error(['error_details' => esc_html__('You are not allowed to create refund!', 'flexible-refund-and-return-order-for-woocommerce'), 'error_code' => 100]);
        }
        parse_str($post_data['form'], $form);
        $post_data['items'] = $form['fr_refund_form']['items'] ?? [];
        if (!empty($status)) {
            try {
                $order = wc_get_order($order_ID);
                if (!$order) {
                    throw new Exception(esc_html__('Order missing!', 'flexible-refund-and-return-order-for-woocommerce'));
                }
                if ($request_id > 0) {
                    check_ajax_referer('fr_update_request', 'nonce');
                    $request = $this->requests->find($request_id);
                    if (null === $request || $request->get_order_id() !== $order->get_id()) {
                        throw new Exception(esc_html__('Request missing!', 'flexible-refund-and-return-order-for-woocommerce'));
                    }
                    $this->workflow->change_status($order, $request, $status, sanitize_textarea_field($post_data['note']), $post_data['items']);
                    wp_send_json_success(['order_id' => $order->get_id(), 'request_id' => $request_id, 'status' => $status]);
                    return;
                }
                $request = (new RequestsFactory($this->settings))->get_request($status);
                $request->do_action($order, $post_data);
                wp_send_json_success(['order_id' => $post_data['order_ID'], 'status' => $post_data['status']]);
            } catch (Exception $e) {
                wp_send_json_error(['error_details' => self::get_refund_error_message($e->getMessage()), 'error_code' => $e->getCode()]);
            }
        }
    }
    public static function get_refund_error_message(string $message): string
    {
        $message_without_amount = preg_replace('/\s+\d+(?:[.,]\d+)?$/', '', trim($message)) ?? $message;
        $generic_message = strtolower(rtrim($message_without_amount, ". \t\n\r\x00\v"));
        $generic_messages = ['return failed', 'refund failed', strtolower(rtrim(__('Refund failed.', 'flexible-refund-and-return-order-for-woocommerce'), ". \t\n\r\x00\v"))];
        if (in_array($generic_message, $generic_messages, \true)) {
            return __('The automatic refund failed. Check that the payment gateway account has sufficient funds and is configured correctly, then try again.', 'flexible-refund-and-return-order-for-woocommerce');
        }
        return $message;
    }
    /**
     * @return void
     */
    public function form_builder_insert_field(): void
    {
        check_ajax_referer('fr_form_builder_field', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['error_details' => esc_html__('You are not allowed to edit forms.', 'flexible-refund-and-return-order-for-woocommerce')], 403);
        }
        $post_data = wp_unslash($_POST);
        $data = FormBuilder::parse_field_args($post_data);
        $input_prefix = isset($post_data['input_prefix']) && 'fr_form[schema]' === $post_data['input_prefix'] ? $post_data['input_prefix'] : 'fr_form[schema]';
        $data['field_name'] = $input_prefix . '[' . sanitize_key($data['name']) . ']';
        $field = $this->renderer->render('settings/form-builder-field', $data);
        wp_send_json_success(['field' => $field]);
    }
}
