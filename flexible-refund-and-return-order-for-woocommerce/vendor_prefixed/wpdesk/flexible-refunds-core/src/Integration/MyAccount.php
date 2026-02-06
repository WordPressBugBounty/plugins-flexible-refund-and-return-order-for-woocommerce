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
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions\DateCondition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Conditions\RefundCondition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer\FieldRenderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;
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
    public function __construct(Renderer $renderer, PersistentContainer $settings, Ajax $ajax)
    {
        $this->form_settings = new WordpressOptionsContainer(RefundOrderTab::SETTING_PREFIX);
        $this->renderer = $renderer;
        $this->settings = $settings;
        $this->ajax = $ajax;
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
        $conditions = $this->settings->get_fallback('refund_conditions_setting', []);
        if (!is_array($conditions)) {
            $conditions = [];
        }
        $condition = new RefundCondition($conditions, $order);
        if ($condition->should_show() && !$this->should_auto_hide($order)) {
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
    private function should_auto_hide(WC_Order $order): bool
    {
        if ($this->settings->get_fallback('refund_auto_hide', 'no') === 'yes' && Integration::is_super()) {
            $conditions = $this->settings->get_fallback('refund_auto_hide_settings', []);
            if (!is_array($conditions)) {
                $conditions = [];
            }
            $date_condition = new DateCondition($conditions, $order);
            return !$date_condition->should_show();
        }
        return \false;
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
        $is_total_refunded = (float) $order->get_total() - (float) $order->get_total_refunded() === 0.0;
        $request_status = $order->get_meta('fr_refund_request_status');
        if ($is_total_refunded || !empty($request_status) && !in_array($order->get_meta('fr_refund_request_status'), ['approved', 'rejected'], \true)) {
            $this->renderer->output_render('myaccount/' . $this->get_template_name('refund-in-progress'), ['order' => $order, 'fields' => new FieldRenderer(), 'show_shipping' => $this->settings->get_fallback('refund_enable_shipment', 'no'), 'request_status' => $request_status]);
        } else {
            $this->renderer->output_render('myaccount/' . $this->get_template_name('refund'), ['show_shipping' => $this->settings->get_fallback('refund_enable_shipment', 'no'), 'order' => $order, 'fields' => new FieldRenderer(), 'request_status' => $request_status]);
        }
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
    public function refund_public_request($order_id): void
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $this->handle_refund_request($order);
        }
    }
    private function is_user_owner_of_the_order(\WC_Order $order): bool
    {
        $order_owner = $order->get_user_id();
        $current_user = get_current_user_id();
        return $order_owner === $current_user;
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
        $refund_order_id = $_GET['refund_order_id'] ?? 0;
        $order_id = $wp->query_vars[self::QUERY_VAR_KEY] ?? $refund_order_id;
        $post_data = $_POST[FieldRenderer::FIELD_PREFIX] ?? [];
        //phpcs:enable
        if (!$order_id || empty($post_data['items'])) {
            return;
        }
        $order = wc_get_order($order_id);
        $nonce = wp_verify_nonce($post_data['fr_refund_request'], 'fr_refund_request_send');
        $total_items = $this->count_refund_items($post_data['items']);
        unset($post_data['request_refund'], $post_data['fr_refund_request']);
        if (!$order || $total_items <= 0) {
            return;
        }
        if (!$nonce || $order->get_customer_id() !== get_current_user_id()) {
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
    private function get_upload_files_limit(string $field_name): string
    {
        $fields = $this->form_settings->get_fallback('form_builder', []);
        return isset($fields[$field_name]['files_limit']) ? (int) $fields[$field_name]['files_limit'] : 1;
    }
}
