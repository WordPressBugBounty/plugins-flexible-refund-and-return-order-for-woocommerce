<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails\EmailRefundRequested;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails\EmailRefundRequestedAdmin;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\Tabs\RefundOrderTab;
use FRFreeVendor\WPDesk\Persistence\Adapter\WordPress\WordpressOptionsContainer;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer\FieldRenderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\OrderReferenceResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\RefundRequestAvailability;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
class MyAccount implements Hookable
{
    const QUERY_VAR_KEY = 'fr-refund';
    const CANCEL_NONCE_ACTION = 'cancel_refund';
    const CANCEL_ORDER_ACTION = 'fr_cancel_order_action';
    const CANCELABLE_STATUSEES = ['pending', 'on-hold'];
    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var PersistentContainer
     */
    private $settings;
    /**
     * @var Ajax
     */
    private $ajax;
    /**
     * @var WordpressOptionsContainer
     */
    private $form_settings;
    /**
     * @var OrderReferenceResolver
     */
    private $order_reference_resolver;
    /**
     * @var RefundRequestAvailability
     */
    private $refund_request_availability;
    public function __construct(Renderer $renderer, PersistentContainer $settings, Ajax $ajax, OrderReferenceResolver $order_reference_resolver, RefundRequestAvailability $refund_request_availability)
    {
        $this->form_settings = new WordpressOptionsContainer(RefundOrderTab::SETTING_PREFIX);
        $this->renderer = $renderer;
        $this->settings = $settings;
        $this->ajax = $ajax;
        $this->order_reference_resolver = $order_reference_resolver;
        $this->refund_request_availability = $refund_request_availability;
    }
    public function hooks()
    {
        add_filter('woocommerce_my_account_my_orders_actions', [$this, 'account_my_orders_actions'], 100, 2);
        add_filter('woocommerce_endpoint_' . self::QUERY_VAR_KEY . '_title', [$this, 'refund_endpoint_title'], 100);
        add_filter('woocommerce_account_' . self::QUERY_VAR_KEY . '_endpoint', [$this, 'refund_account_endpoint'], 100, 1);
        add_filter('woocommerce_get_query_vars', [$this, 'add_query_vars'], 10);
        add_filter('wp', [$this, 'save_refund_request'], 999);
        add_filter('wp', [$this, 'cancel_refund_request_by_user'], 999);
        add_action('wp', [$this, 'process_cancel_unpaid_order'], 999);
    }
    /**
     * @param array    $actions
     * @param WC_Order $order
     *
     * @return array
     */
    public function account_my_orders_actions(array $actions, WC_Order $order): array
    {
        if ($this->refund_request_availability->can_initiate_refund_request($order)) {
            $actions['refund'] = ['url' => Helpers\MyAccount::get_refund_url($order), 'name' => esc_html__('Refund', 'flexible-refund-and-return-order-for-woocommerce')];
        }
        if (Integration::is_super()) {
            $actions = $this->swap_cancel_order_action($actions, $order);
        }
        return $actions;
    }
    private function swap_cancel_order_action(array $actions, WC_Order $order): array
    {
        if (isset($actions['cancel'])) {
            unset($actions['cancel']);
        }
        $cancel_button_enabled = filter_var($this->settings->get_fallback('refund_cancel_button', 'no'), \FILTER_VALIDATE_BOOLEAN);
        if ($cancel_button_enabled && in_array($order->get_status(), self::CANCELABLE_STATUSEES, \true)) {
            $cancel_url = wp_nonce_url(add_query_arg(['fr_action' => 'cancel_unpaid', 'order_id' => $order->get_id()]), self::CANCEL_ORDER_ACTION);
            $actions['cancel'] = ['url' => $cancel_url, 'name' => esc_html__('Cancel', 'woocommerce')];
        }
        return $actions;
    }
    public function process_cancel_unpaid_order()
    {
        if (isset($_GET['fr_action'], $_GET['order_id']) && 'cancel_unpaid' === $_GET['fr_action']) {
            $nonce = $_REQUEST['_wpnonce'] ?? '';
            //phpcs:ignore
            if (!wp_verify_nonce($nonce, self::CANCEL_ORDER_ACTION)) {
                return;
            }
            $order_id = absint($_GET['order_id']);
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }
            if ($order->get_customer_id() !== get_current_user_id()) {
                return;
            }
            if (!in_array($order->get_status(), self::CANCELABLE_STATUSEES, \true)) {
                wc_add_notice(__('This order cannot be cancelled.', 'flexible-refund-and-return-order-for-woocommerce'), 'error');
                return;
            }
            $order->update_status('cancelled', __('Order cancelled by customer via My Account.', 'flexible-refund-and-return-order-for-woocommerce'));
            wc_add_notice(__('Order has been cancelled.', 'flexible-refund-and-return-order-for-woocommerce'), 'success');
            wp_safe_redirect(wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount')));
            exit;
        }
    }
    /**
     * @param $title
     *
     * @return string
     */
    public function refund_endpoint_title($title): string
    {
        global $wp;
        if (isset($wp->query_vars[self::QUERY_VAR_KEY])) {
            $order = wc_get_order($wp->query_vars[self::QUERY_VAR_KEY]);
            // translators: %s: order number.
            return $order ? sprintf(esc_html__('Order Refund #%s', 'flexible-refund-and-return-order-for-woocommerce'), $order->get_order_number()) : '';
        }
        return $title;
    }
    /**
     * @param array $query_vars
     *
     * @return array
     */
    public function add_query_vars(array $query_vars): array
    {
        $query_vars[self::QUERY_VAR_KEY] = self::QUERY_VAR_KEY;
        return $query_vars;
    }
    /**
     * @param string $template
     *
     * @return string
     */
    private function get_template_name(string $template): string
    {
        $suffix = '-free';
        if (Integration::is_super()) {
            $suffix = '-pro';
        }
        return $template . $suffix;
    }
    /**
     * @param \WC_Order $order .
     *
     * @return void
     */
    private function handle_refund_request(\WC_Order $order): void
    {
        $is_total_refunded = $this->is_order_fully_refunded($order);
        $request_status = $order->get_meta('fr_refund_request_status');
        if ($is_total_refunded || !empty($request_status) && !in_array($order->get_meta('fr_refund_request_status'), ['approved', 'rejected'], \true)) {
            $this->renderer->output_render('myaccount/' . $this->get_template_name('refund-in-progress'), ['order' => $order, 'fields' => new FieldRenderer(), 'show_shipping' => $this->settings->get_fallback('refund_enable_shipment', 'no'), 'request_status' => $request_status]);
        } elseif (!$this->refund_request_availability->can_initiate_refund_request($order)) {
            $this->renderer->output_render('myaccount/refund-unavailable', ['title' => $this->get_refund_unavailable_title($order), 'message' => $this->get_refund_unavailable_message($order)]);
        } else {
            $this->renderer->output_render('myaccount/' . $this->get_template_name('refund'), ['show_shipping' => $this->settings->get_fallback('refund_enable_shipment', 'no'), 'order' => $order, 'fields' => new FieldRenderer(), 'request_status' => $request_status]);
        }
    }
    private function is_order_fully_refunded(\WC_Order $order): bool
    {
        foreach ($order->get_items() as $item_id => $item) {
            if (absint($item->get_quantity()) > absint($order->get_qty_refunded_for_item($item_id))) {
                return \false;
            }
        }
        foreach ($order->get_items('shipping') as $shipping_item) {
            if (absint($shipping_item->get_quantity()) > absint($order->get_qty_refunded_for_item($shipping_item->get_id(), 'shipping'))) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @param mixed $order_id Order ID is passed as string.
     *
     * @return string|null
     */
    public function refund_account_endpoint($order_id): ?string
    {
        $order = wc_get_order($order_id);
        if ($order) {
            if (!$this->is_user_owner_of_the_order($order)) {
                $this->renderer->output_render('myaccount/invalid-order-or-user-id');
                return null;
            }
            $this->handle_refund_request($order);
        }
        return $order_id;
    }
    /**
     * @param mixed $order_id Order ID is passed as string.
     *
     * @return void
     */
    public function refund_public_request($order_id): ?string
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return '';
        }
        ob_start();
        $this->handle_refund_request($order);
        return ob_get_clean();
    }
    private function is_user_owner_of_the_order(\WC_Order $order): bool
    {
        $order_owner = $order->get_user_id();
        $current_user = get_current_user_id();
        return $order_owner === $current_user;
    }
    private function is_public_refund_request_authorized(\WC_Order $order): bool
    {
        $authorized_order = $this->get_public_refund_request_order();
        return $authorized_order instanceof WC_Order && $authorized_order->get_id() === $order->get_id();
    }
    /**
     * @param array $refund_items
     *
     * @return int
     */
    private function count_refund_items(array $refund_items): int
    {
        $total = 0;
        foreach ($refund_items as $refund_item) {
            $total += (int) $refund_item['qty'];
        }
        return $total;
    }
    /**
     * @param string $name
     *
     * @return array
     */
    private function upload_files(string $name): array
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (!isset($_FILES[$name])) {
            return [];
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $files = $_FILES[$name];
        if (!function_exists('wp_handle_upload')) {
            require_once \ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!is_array($files['name'])) {
            return wp_handle_upload($files, ['test_form' => \false]);
        }
        $files_data = [];
        $files_limit = $this->get_upload_files_limit($name);
        foreach ($files['name'] as $index => $filename) {
            if (empty($filename) || $index >= $files_limit) {
                continue;
            }
            $single_file = ['name' => $files['name'][$index], 'type' => $files['type'][$index], 'tmp_name' => $files['tmp_name'][$index], 'error' => $files['error'][$index], 'size' => $files['size'][$index]];
            $file_data = wp_handle_upload($single_file, ['test_form' => \false]);
            if (!empty($file_data['error'])) {
                continue;
            }
            $files_data[] = $file_data;
        }
        return $files_data;
    }
    /**
     * @return void
     */
    public function save_refund_request(): void
    {
        global $wp;
        //phpcs:disable
        $order_id = $wp->query_vars[self::QUERY_VAR_KEY] ?? 0;
        $post_data = $_POST[FieldRenderer::FIELD_PREFIX] ?? [];
        //phpcs:enable
        if (!$order_id && !$this->has_public_refund_request_order_reference() || empty($post_data['items'])) {
            return;
        }
        $order = $order_id ? wc_get_order($order_id) : $this->get_public_refund_request_order();
        $nonce = wp_verify_nonce($post_data['fr_refund_request'], 'fr_refund_request_send');
        $total_items = $this->count_refund_items($post_data['items']);
        unset($post_data['request_refund'], $post_data['fr_refund_request']);
        if (!$order || $total_items <= 0) {
            return;
        }
        if (!$nonce) {
            return;
        }
        $is_authorized = $order->get_customer_id() === get_current_user_id();
        if (!$is_authorized) {
            $is_authorized = $this->is_public_refund_request_authorized($order);
        }
        if (!$is_authorized) {
            return;
        }
        if (!$this->refund_request_availability->can_initiate_refund_request($order)) {
            return;
        }
        $post_data['attachments'] = [];
        if (isset($post_data['upload_names'])) {
            foreach ($post_data['upload_names'] as $upload_name) {
                $file_data = $this->upload_files($upload_name);
                if ($file_data) {
                    $post_data['attachments'][$upload_name] = $file_data;
                }
            }
        }
        $post_data = $this->sanitize_text_fields($post_data);
        $order->update_meta_data('fr_refund_request_data', $post_data);
        $order->update_meta_data('fr_refund_request_date', time());
        $order->update_meta_data('fr_refund_request_status', Helpers\Statuses::REQUESTED_STATUS);
        $order->update_meta_data('fr_refund_previous_order_status', $order->get_status());
        $order->set_status(RegisterOrderStatus::REQUEST_REFUND_STATUS);
        $order->save();
        $auto_create = $this->ajax->should_auto_create_refund($order, ['order_ID' => $order->get_id(), 'note' => esc_html__('Your refund request has been accepted!', 'flexible-refund-and-return-order-for-woocommerce'), 'status' => 'approved', 'form' => '', 'items' => $post_data['items']]);
        if ($auto_create) {
            $order->set_status(RegisterOrderStatus::REQUEST_REFUND_STATUS);
            $order->update_meta_data('fr_refund_request_status', Helpers\Statuses::APPROVED_STATUS);
            $order->save();
            $this->send_email($order);
            wp_safe_redirect(add_query_arg('request', 'auto-create'), 301);
            exit;
        }
        $this->send_email($order);
        wp_safe_redirect(add_query_arg('request', 'send'), 301);
        exit;
    }
    /**
     * Delete refund request by User.
     *
     * @return void
     */
    public function cancel_refund_request_by_user(): void
    {
        global $current_user;
        //phpcs:disable
        $nonce_value = $_REQUEST['_wpnonce'] ?? '';
        $order_ID = $_REQUEST['delete_refund_request'] ?? 0;
        //phpcs:enable
        $nonce = wp_verify_nonce($nonce_value, self::CANCEL_NONCE_ACTION);
        if ($order_ID && $nonce) {
            $order = wc_get_order($order_ID);
            if ($order && $order->get_customer_id() === $current_user->ID) {
                $previous_order_status = $order->get_meta('fr_refund_previous_order_status');
                $order->delete_meta_data('fr_refund_request_data');
                $order->delete_meta_data('fr_refund_request_date');
                $order->delete_meta_data('fr_refund_request_status');
                $order->delete_meta_data('fr_refund_request_note');
                $order->delete_meta_data('fr_refund_previous_order_status');
                if (!empty($previous_order_status)) {
                    $order->set_status($previous_order_status);
                }
                $order->save();
                wp_safe_redirect(remove_query_arg(['delete_refund_request', '_wpnonce']), 301);
            }
        }
    }
    public function send_email(\WC_Order $order)
    {
        $mailer = \WC()->mailer();
        $emails = $mailer->get_emails();
        if (isset($emails[EmailRefundRequested::ID])) {
            $emails[EmailRefundRequested::ID]->trigger($order);
        }
        if (isset($emails[EmailRefundRequestedAdmin::ID])) {
            $emails[EmailRefundRequestedAdmin::ID]->trigger($order);
        }
    }
    private function sanitize_text_fields(array $post_data): array
    {
        $fields = $this->form_settings->get_fallback('form_builder', []);
        foreach ($fields as $name => $field) {
            $post_data = $this->sanitize_text_field_value($post_data, $name, $field['type'] ?? '');
        }
        return $post_data;
    }
    private function sanitize_text_field_value(array $post_data, string $name, string $type): array
    {
        if (!isset($post_data[$name]) || is_array($post_data[$name])) {
            return $post_data;
        }
        if ($type === 'text') {
            $post_data[$name] = sanitize_text_field(wp_unslash($post_data[$name]));
        }
        if ($type === 'textarea') {
            $post_data[$name] = sanitize_textarea_field(wp_unslash($post_data[$name]));
        }
        return $post_data;
    }
    private function get_upload_files_limit(string $field_name): string
    {
        $fields = $this->form_settings->get_fallback('form_builder', []);
        return isset($fields[$field_name]['files_limit']) ? (int) $fields[$field_name]['files_limit'] : 1;
    }
    private function has_public_refund_request_order_reference(): bool
    {
        return '' !== $this->get_public_refund_request_order_reference();
    }
    private function get_public_refund_request_order(): ?WC_Order
    {
        $reference = $this->get_public_refund_request_order_reference();
        $email = $this->get_public_refund_request_email();
        if ('' === $reference || '' === $email) {
            return null;
        }
        return $this->order_reference_resolver->find_order($reference, $email);
    }
    private function get_public_refund_request_email(): string
    {
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        return isset($_GET[PublicRefundShortcode::EMAIL_REQUEST_KEY]) ? sanitize_email(wp_unslash($_GET[PublicRefundShortcode::EMAIL_REQUEST_KEY])) : '';
        //phpcs:enable
    }
    private function get_public_refund_request_order_reference(): string
    {
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        return isset($_GET[PublicRefundShortcode::ORDER_ID_REQUEST_KEY]) ? sanitize_text_field(wp_unslash($_GET[PublicRefundShortcode::ORDER_ID_REQUEST_KEY])) : '';
        //phpcs:enable
    }
    private function get_refund_unavailable_title(WC_Order $order): string
    {
        if ($this->refund_request_availability->is_auto_hidden($order)) {
            return esc_html__('Refund time has expired', 'flexible-refund-and-return-order-for-woocommerce');
        }
        return esc_html__('Refund unavailable', 'flexible-refund-and-return-order-for-woocommerce');
    }
    private function get_refund_unavailable_message(WC_Order $order): string
    {
        if ($this->refund_request_availability->is_auto_hidden($order)) {
            return sprintf(
                /* translators: %s: refund availability duration. */
                esc_html__('Refund requests are available for %s after placing the order.', 'flexible-refund-and-return-order-for-woocommerce'),
                $this->get_auto_hide_duration_label()
            );
        }
        return esc_html__('Refund request is unavailable for this order based on the current refund conditions.', 'flexible-refund-and-return-order-for-woocommerce');
    }
    private function get_auto_hide_duration_label(): string
    {
        $conditions = $this->refund_request_availability->get_auto_hide_settings();
        $time_value = max(1, (int) ($conditions['time_value'] ?? 1));
        $time_period = (string) ($conditions['time_period'] ?? 'days');
        $period_label = $this->get_auto_hide_period_label($time_period, $time_value);
        return sprintf('%d %s', $time_value, $period_label);
    }
    private function get_auto_hide_period_label(string $time_period, int $time_value): string
    {
        switch ($time_period) {
            case 'hours':
                return 1 === $time_value ? esc_html__('hour', 'flexible-refund-and-return-order-for-woocommerce') : esc_html__('hours', 'flexible-refund-and-return-order-for-woocommerce');
            case 'weeks':
                return 1 === $time_value ? esc_html__('week', 'flexible-refund-and-return-order-for-woocommerce') : esc_html__('weeks', 'flexible-refund-and-return-order-for-woocommerce');
            case 'months':
                return 1 === $time_value ? esc_html__('month', 'flexible-refund-and-return-order-for-woocommerce') : esc_html__('months', 'flexible-refund-and-return-order-for-woocommerce');
            case 'years':
                return 1 === $time_value ? esc_html__('year', 'flexible-refund-and-return-order-for-woocommerce') : esc_html__('years', 'flexible-refund-and-return-order-for-woocommerce');
            case 'days':
            default:
                return 1 === $time_value ? esc_html__('day', 'flexible-refund-and-return-order-for-woocommerce') : esc_html__('days', 'flexible-refund-and-return-order-for-woocommerce');
        }
    }
}
