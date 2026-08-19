<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use Throwable;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Exception\ActiveRequestExists;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestStatus;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails\RequestEmailSender;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer\FieldRenderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\OrderReferenceResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\FormAvailability;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\RequestService;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\RequestWorkflow;
class MyAccount implements Hookable
{
    const QUERY_VAR_KEY = 'fr-refund';
    const CANCEL_NONCE_ACTION = 'cancel_refund';
    const CANCEL_ORDER_ACTION = 'fr_cancel_order_action';
    const CANCELABLE_STATUSEES = ['pending', 'on-hold'];
    private Renderer $renderer;
    private PersistentContainer $settings;
    private OrderReferenceResolver $order_reference_resolver;
    private FormRepository $forms;
    private RequestRepository $requests;
    private RequestService $request_service;
    private FormAvailability $form_availability;
    private RequestEmailSender $emails;
    private RequestWorkflow $workflow;
    /** @var array<int, RequestRecord|null> */
    private array $active_requests = [];
    public function __construct(Renderer $renderer, PersistentContainer $settings, OrderReferenceResolver $order_reference_resolver, FormRepository $forms, RequestRepository $requests, RequestService $request_service, FormAvailability $form_availability, RequestEmailSender $emails, RequestWorkflow $workflow)
    {
        $this->renderer = $renderer;
        $this->settings = $settings;
        $this->order_reference_resolver = $order_reference_resolver;
        $this->forms = $forms;
        $this->requests = $requests;
        $this->request_service = $request_service;
        $this->form_availability = $form_availability;
        $this->emails = $emails;
        $this->workflow = $workflow;
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
        add_action('woocommerce_order_details_before_order_table', [$this, 'render_request_before_order_details']);
        add_action('woocommerce_my_account_my_orders_column_order-status', [$this, 'render_my_account_order_status']);
        add_filter('woocommerce_order_details_status', [$this, 'filter_order_details_status'], 10, 2);
    }
    /**
     * @param array    $actions
     * @param WC_Order $order
     *
     * @return array
     */
    public function account_my_orders_actions(array $actions, WC_Order $order): array
    {
        if (!$this->has_active_request($order)) {
            foreach ($this->forms->find_all() as $form) {
                $form_id = $form->get_id();
                if (null === $form_id || !$this->form_availability->can_start($form, $order)) {
                    continue;
                }
                $actions['fr-request-' . $form->get_request_type()] = ['url' => Helpers\MyAccount::get_refund_url($order, $form_id), 'name' => esc_html($form->get_button_label())];
            }
        }
        if (Integration::is_super()) {
            $actions = $this->swap_cancel_order_action($actions, $order);
        }
        return $actions;
    }
    public function render_request_before_order_details(WC_Order $order): void
    {
        $current_user_id = get_current_user_id();
        if ($current_user_id < 1 || $order->get_customer_id() !== $current_user_id) {
            return;
        }
        $active_request = $this->get_active_request($order);
        if (null !== $active_request) {
            $this->renderer->output_render('myaccount/request-in-progress', ['order' => $order, 'request' => $active_request]);
            return;
        }
        if ($this->has_active_legacy_request($order)) {
            $this->renderer->output_render('myaccount/' . $this->get_template_name('refund-in-progress'), ['order' => $order, 'fields' => new FieldRenderer(), 'show_shipping' => $this->settings->get_fallback('refund_enable_shipment', 'no'), 'request_status' => (string) $order->get_meta('fr_refund_request_status')]);
        }
    }
    public function render_my_account_order_status(WC_Order $order): void
    {
        echo esc_html($this->get_my_account_order_status_label($order));
    }
    public function filter_order_details_status(string $status_text, WC_Order $order): string
    {
        if (RegisterOrderStatus::REQUEST_REFUND_STATUS !== 'wc-' . $order->get_status()) {
            return $status_text;
        }
        $created_at = $order->get_date_created();
        if (!$created_at) {
            return $status_text;
        }
        return sprintf(
            /* translators: 1: order number, 2: order date, 3: request status. */
            __('Order #%1$s was placed on %2$s and currently has status %3$s.', 'flexible-refund-and-return-order-for-woocommerce'),
            '<mark class="order-number">' . esc_html($order->get_order_number()) . '</mark>',
            '<mark class="order-date">' . esc_html(wc_format_datetime($created_at)) . '</mark>',
            '<mark class="order-status">' . esc_html($this->get_my_account_order_status_label($order)) . '</mark>'
        );
    }
    public function get_my_account_order_status_label(WC_Order $order): string
    {
        $status = $order->get_status();
        if (RegisterOrderStatus::REQUEST_REFUND_STATUS !== 'wc-' . $status) {
            return wc_get_order_status_name($status);
        }
        $active_request = $this->get_active_request($order);
        if (null !== $active_request) {
            return RequestType::get_order_status_label($active_request->get_request_type());
        }
        if (!empty($order->get_meta('fr_refund_request_data'))) {
            return RequestType::get_order_status_label(RequestType::REFUND);
        }
        return wc_get_order_status_name($status);
    }
    private function get_active_request(WC_Order $order): ?RequestRecord
    {
        $order_id = $order->get_id();
        if (!array_key_exists($order_id, $this->active_requests)) {
            $this->active_requests[$order_id] = $this->requests->find_active_by_order($order_id);
        }
        return $this->active_requests[$order_id];
    }
    private function has_active_request(WC_Order $order): bool
    {
        return null !== $this->get_active_request($order) || $this->has_active_legacy_request($order);
    }
    private function has_active_legacy_request(WC_Order $order): bool
    {
        if (empty($order->get_meta('fr_refund_request_data'))) {
            return \false;
        }
        $status = RequestStatus::normalize_legacy((string) $order->get_meta('fr_refund_request_status'));
        return RequestStatus::is_active($status);
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
            $form = $this->get_selected_form();
            // translators: 1: request label, 2: order number.
            return $order && $form ? sprintf(esc_html__('%1$s for order #%2$s', 'flexible-refund-and-return-order-for-woocommerce'), $form->get_button_label(), $order->get_order_number()) : '';
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
    private function handle_refund_request(\WC_Order $order, ?FormDefinition $form, bool $allow_cancelled_order = \false): void
    {
        $active_request = $this->get_active_request($order);
        if (null !== $active_request) {
            $this->renderer->output_render('myaccount/request-in-progress', ['order' => $order, 'request' => $active_request]);
            return;
        }
        if ($this->has_active_legacy_request($order)) {
            $this->renderer->output_render('myaccount/' . $this->get_template_name('refund-in-progress'), ['order' => $order, 'fields' => new FieldRenderer(), 'show_shipping' => $this->settings->get_fallback('refund_enable_shipment', 'no'), 'request_status' => (string) $order->get_meta('fr_refund_request_status')]);
            return;
        }
        if (null === $form || !$this->form_availability->can_start($form, $order, $allow_cancelled_order)) {
            $this->renderer->output_render('myaccount/refund-unavailable', ['title' => esc_html__('Request unavailable', 'flexible-refund-and-return-order-for-woocommerce'), 'message' => esc_html__('This request type is disabled or the order does not meet its eligibility rules.', 'flexible-refund-and-return-order-for-woocommerce')]);
            return;
        }
        $settings = $form->get_settings();
        $this->renderer->output_render('myaccount/' . $this->get_template_name('refund'), ['show_shipping' => RequestType::REFUND === $form->get_request_type() ? $settings['refund_shipping'] ?? 'no' : 'no', 'order' => $order, 'fields' => new FieldRenderer($form->get_schema()), 'request_status' => '', 'form' => $form]);
    }
    /**
     * @param mixed $order_id Order ID is passed as string.
     *
     * @return string|null
     */
    public function refund_account_endpoint($order_id): ?string
    {
        wc_print_notices();
        $order = wc_get_order($order_id);
        if ($order) {
            if (!$this->is_user_owner_of_the_order($order)) {
                $this->renderer->output_render('myaccount/invalid-order-or-user-id');
                return null;
            }
            $this->handle_refund_request($order, $this->get_selected_form());
        }
        return $order_id;
    }
    /**
     * @param mixed $order_id Order ID is passed as string.
     *
     * @return string|null
     */
    public function refund_public_request($order_id, string $request_type = RequestType::REFUND): ?string
    {
        RequestType::assert_valid($request_type);
        $order = wc_get_order($order_id);
        if (!$order) {
            return '';
        }
        ob_start();
        $request = $this->get_latest_request_by_type($order->get_id(), $request_type);
        if (null !== $request) {
            $this->renderer->output_render('myaccount/request-in-progress', ['order' => $order, 'request' => $request]);
        } else {
            $this->handle_refund_request($order, $this->forms->find_by_type($request_type), \true);
        }
        return ob_get_clean();
    }
    private function get_latest_request_by_type(int $order_id, string $request_type): ?RequestRecord
    {
        foreach ($this->requests->find_by_order($order_id) as $request) {
            if ($request_type === $request->get_request_type()) {
                return $request->is_active() ? $request : null;
            }
        }
        return null;
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
     * @param string $name
     *
     * @return array
     */
    private function upload_files(string $name, array $schema): array
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
        $files_limit = $this->get_upload_files_limit($name, $schema);
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
        if (!$order_id && !$this->has_public_refund_request_order_reference() || empty($post_data['items']) || empty($post_data['form_id'])) {
            return;
        }
        $order = $order_id ? wc_get_order($order_id) : $this->get_public_refund_request_order();
        $nonce = wp_verify_nonce($post_data['fr_refund_request'] ?? '', 'fr_refund_request_send');
        $form = $this->forms->find(absint($post_data['form_id']));
        unset($post_data['request_refund'], $post_data['fr_refund_request'], $post_data['form_id']);
        if (!$order || !$form) {
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
        $is_public = $this->is_public_refund_request_authorized($order);
        if ($is_public && $this->get_public_refund_request_type() !== $form->get_request_type() || !$this->form_availability->can_start($form, $order)) {
            return;
        }
        if ($this->has_active_request($order)) {
            wc_add_notice(__('This order already has an active request.', 'flexible-refund-and-return-order-for-woocommerce'), 'error');
            return;
        }
        $post_data['attachments'] = [];
        if (isset($post_data['upload_names'])) {
            foreach ($post_data['upload_names'] as $upload_name) {
                $upload_name = sanitize_key($upload_name);
                $file_data = $this->upload_files($upload_name, $form->get_schema());
                if ($file_data) {
                    $post_data['attachments'][$upload_name] = $file_data;
                }
            }
        }
        unset($post_data['upload_names']);
        $post_data = $this->sanitize_submitted_values($post_data, $form, $order);
        if (empty($post_data['items'])) {
            wc_add_notice(__('Select at least one order item.', 'flexible-refund-and-return-order-for-woocommerce'), 'error');
            return;
        }
        if (!$this->has_required_values($post_data, $form->get_schema())) {
            wc_add_notice(__('Complete all required form fields.', 'flexible-refund-and-return-order-for-woocommerce'), 'error');
            return;
        }
        try {
            $previous_status = $order->get_status();
            $request = $this->request_service->create($form, $order->get_id(), $post_data, $previous_status);
            $order->set_status(RegisterOrderStatus::REQUEST_REFUND_STATUS);
            $order->save();
        } catch (ActiveRequestExists $e) {
            wc_add_notice(__('This order already has an active request.', 'flexible-refund-and-return-order-for-woocommerce'), 'error');
            return;
        } catch (Throwable $e) {
            wc_add_notice(__('The request could not be saved. Please try again.', 'flexible-refund-and-return-order-for-woocommerce'), 'error');
            return;
        }
        $is_auto_approval = Integration::is_super() && RequestType::REFUND === $form->get_request_type() && 'yes' === ($form->get_settings()['auto_approval'] ?? 'no');
        if ($is_auto_approval) {
            try {
                $this->workflow->change_status($order, $request, RequestStatus::APPROVED, __('Your refund request has been accepted!', 'flexible-refund-and-return-order-for-woocommerce'), $post_data['items']);
                wc_add_notice(__('Your request has been accepted.', 'flexible-refund-and-return-order-for-woocommerce'), 'success');
                wp_safe_redirect(add_query_arg('request', 'auto-create'));
                exit;
            } catch (Throwable $e) {
                // Keep the request in the requested state for manual processing.
            }
        }
        $this->emails->send($order, $request, RequestStatus::REQUESTED);
        wc_add_notice(__('Your request has been sent.', 'flexible-refund-and-return-order-for-woocommerce'), 'success');
        wp_safe_redirect(add_query_arg('request', 'send'));
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
            if ($order && $current_user->ID > 0 && $order->get_customer_id() === $current_user->ID) {
                $this->cancel_request($order);
                wp_safe_redirect(remove_query_arg(['delete_refund_request', '_wpnonce']), 301);
            }
        }
    }
    public function cancel_request(WC_Order $order): void
    {
        $active_request = $this->requests->find_active_by_order($order->get_id());
        if (null !== $active_request && null !== $active_request->get_id()) {
            $this->request_service->change_status($active_request->get_id(), RequestStatus::CANCELED, __('Request cancelled by the customer.', 'flexible-refund-and-return-order-for-woocommerce'));
            if ('' !== $active_request->get_previous_order_status()) {
                $order->set_status($active_request->get_previous_order_status());
            }
            if (null !== $active_request->get_legacy_order_id()) {
                $this->delete_legacy_request_meta($order);
            }
        } elseif (!empty($order->get_meta('fr_refund_request_data'))) {
            $previous_order_status = $order->get_meta('fr_refund_previous_order_status');
            $order->update_meta_data('fr_refund_request_status', RequestStatus::CANCELED);
            if (!empty($previous_order_status)) {
                $order->set_status($previous_order_status);
            }
        }
        $order->save();
    }
    private function delete_legacy_request_meta(WC_Order $order): void
    {
        $order->delete_meta_data('fr_refund_request_data');
        $order->delete_meta_data('fr_refund_request_date');
        $order->delete_meta_data('fr_refund_request_status');
        $order->delete_meta_data('fr_refund_request_note');
        $order->delete_meta_data('fr_refund_previous_order_status');
    }
    private function sanitize_submitted_values(array $post_data, FormDefinition $form, WC_Order $order): array
    {
        $fields = $this->get_supported_form_fields($form->get_schema());
        $values = [];
        $items = [];
        $settings = $form->get_settings();
        $allow_shipping = RequestType::REFUND === $form->get_request_type() && 'yes' === ($settings['refund_shipping'] ?? 'no');
        $limits = $this->get_requestable_item_quantities($order, $allow_shipping);
        foreach ((array) ($post_data['items'] ?? []) as $item_id => $item) {
            $item_id = absint($item_id);
            $qty = absint(is_array($item) ? $item['qty'] ?? 0 : 0);
            if ($qty > 0 && isset($limits[$item_id])) {
                $items[$item_id] = ['qty' => min($qty, $limits[$item_id])];
            }
        }
        $values['items'] = $items;
        $values['total_refund_qty'] = array_sum(array_column($items, 'qty'));
        $values['attachments'] = is_array($post_data['attachments'] ?? null) ? $post_data['attachments'] : [];
        foreach ($fields as $name => $field) {
            if (!isset($post_data[$name]) || empty($field['enable'])) {
                continue;
            }
            $type = $field['type'] ?? '';
            if ('textarea' === $type && !is_array($post_data[$name])) {
                $values[$name] = sanitize_textarea_field(wp_unslash($post_data[$name]));
            } elseif (in_array($type, ['checkbox', 'select', 'multiselect', 'radio'], \true)) {
                $raw_values = is_array($post_data[$name]) ? $post_data[$name] : [$post_data[$name]];
                $values[$name] = array_values(array_map('sanitize_text_field', wp_unslash($raw_values)));
            } elseif ('text' === $type && !is_array($post_data[$name])) {
                $values[$name] = sanitize_text_field(wp_unslash($post_data[$name]));
            }
        }
        return $values;
    }
    private function get_requestable_item_quantities(WC_Order $order, bool $allow_shipping): array
    {
        $limits = [];
        foreach ($order->get_items() as $item_id => $item) {
            $limits[absint($item_id)] = max(0, (int) $item->get_quantity() + (int) $order->get_qty_refunded_for_item($item_id));
        }
        if ($allow_shipping) {
            foreach ($order->get_items('shipping') as $item) {
                $limits[absint($item->get_id())] = max(0, (int) $item->get_quantity() + (int) $order->get_qty_refunded_for_item($item->get_id(), 'shipping'));
            }
        }
        return $limits;
    }
    private function has_required_values(array $values, array $schema): bool
    {
        foreach ($this->get_supported_form_fields($schema) as $name => $field) {
            if (empty($field['enable']) || empty($field['required'])) {
                continue;
            }
            if ('upload' === ($field['type'] ?? '')) {
                if (empty($values['attachments'][$name])) {
                    return \false;
                }
            } elseif (!isset($values[$name]) || [] === $values[$name] || '' === $values[$name]) {
                return \false;
            }
        }
        return \true;
    }
    private function get_upload_files_limit(string $field_name, array $schema): int
    {
        $fields = $this->get_supported_form_fields($schema);
        return isset($fields[$field_name]['files_limit']) ? (int) $fields[$field_name]['files_limit'] : 1;
    }
    /**
     * @return array<string, array<string, mixed>>
     */
    private function get_supported_form_fields(array $schema): array
    {
        return Helpers\FormBuilder::get_supported_fields($schema);
    }
    private function get_selected_form(): ?FormDefinition
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing
        $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        if (0 === $form_id && isset($_POST[FieldRenderer::FIELD_PREFIX]['form_id'])) {
            $form_id = absint($_POST[FieldRenderer::FIELD_PREFIX]['form_id']);
        }
        // phpcs:enable
        return $form_id > 0 ? $this->forms->find($form_id) : null;
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
        $request_type = $this->get_public_refund_request_type();
        if (null === $request_type) {
            return '';
        }
        $email_field_name = PublicRefundShortcode::get_email_field_name($request_type);
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        return isset($_GET[$email_field_name]) ? sanitize_email(wp_unslash($_GET[$email_field_name])) : '';
        //phpcs:enable
    }
    private function get_public_refund_request_order_reference(): string
    {
        $request_type = $this->get_public_refund_request_type();
        if (null === $request_type) {
            return '';
        }
        $order_field_name = PublicRefundShortcode::get_order_field_name($request_type);
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        return isset($_GET[$order_field_name]) ? sanitize_text_field(wp_unslash($_GET[$order_field_name])) : '';
        //phpcs:enable
    }
    private function get_public_refund_request_type(): ?string
    {
        foreach (RequestType::all() as $request_type) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_GET[PublicRefundShortcode::get_submit_field_name($request_type)])) {
                return $request_type;
            }
        }
        return null;
    }
}
