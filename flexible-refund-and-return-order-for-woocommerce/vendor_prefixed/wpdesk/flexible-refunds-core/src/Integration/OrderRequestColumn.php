<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestStatus;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Statuses;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
final class OrderRequestColumn implements Hookable
{
    public const COLUMN = 'fr_order_status';
    public const REQUEST_STATUS_COLUMN = 'fr_request_status';
    private RequestRepository $requests;
    /** @var array<int, RequestRecord|null> */
    private array $active_cache = [];
    /** @var array<int, RequestRecord|null> */
    private array $latest_cache = [];
    public function __construct(RequestRepository $requests)
    {
        $this->requests = $requests;
    }
    public function hooks(): void
    {
        add_filter('manage_edit-shop_order_columns', [$this, 'replace_status_column'], 20);
        add_action('manage_shop_order_posts_custom_column', [$this, 'render_legacy_column'], 20, 2);
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'replace_status_column'], 20);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'render_hpos_column'], 20, 2);
    }
    /**
     * @param array<string, string> $columns
     *
     * @return array<string, string>
     */
    public function replace_status_column(array $columns): array
    {
        $updated_columns = [];
        foreach ($columns as $column => $label) {
            if ('order_status' === $column) {
                $updated_columns[self::COLUMN] = $label;
                $updated_columns[self::REQUEST_STATUS_COLUMN] = esc_html__('Request status', 'flexible-refund-and-return-order-for-woocommerce');
                continue;
            }
            $updated_columns[$column] = $label;
        }
        return $updated_columns;
    }
    public function render_legacy_column(string $column, int $post_id): void
    {
        if (!in_array($column, [self::COLUMN, self::REQUEST_STATUS_COLUMN], \true)) {
            return;
        }
        $order = wc_get_order($post_id);
        if ($order instanceof WC_Order) {
            $this->render_column($column, $order);
        }
    }
    /** @param mixed $order */
    public function render_hpos_column(string $column, $order): void
    {
        if (in_array($column, [self::COLUMN, self::REQUEST_STATUS_COLUMN], \true) && $order instanceof WC_Order) {
            $this->render_column($column, $order);
        }
    }
    private function render_column(string $column, WC_Order $order): void
    {
        if (self::REQUEST_STATUS_COLUMN === $column) {
            $this->render_request_status($order);
            return;
        }
        $this->render_order_status($order);
    }
    private function render_order_status(WC_Order $order): void
    {
        $status = $order->get_status();
        $status_label = wc_get_order_status_name($status);
        if (RegisterOrderStatus::REQUEST_REFUND_STATUS === 'wc-' . $status) {
            $status_label = $this->get_request_status_label($order);
        }
        $this->render_status($status, $status_label);
    }
    private function render_request_status(WC_Order $order): void
    {
        $request = $this->get_latest_request($order->get_id());
        if ($request instanceof RequestRecord) {
            $this->render_status($request->get_status(), Statuses::get_status_label($request->get_status()));
            return;
        }
        if (!empty($order->get_meta('fr_refund_request_data'))) {
            $status = RequestStatus::normalize_legacy((string) $order->get_meta('fr_refund_request_status'));
            $this->render_status($status, Statuses::get_status_label($status));
            return;
        }
        echo '<span aria-hidden="true">&#8212;</span>';
    }
    private function render_status(string $status, string $label): void
    {
        printf('<mark class="order-status %1$s"><span>%2$s</span></mark>', esc_attr(sanitize_html_class('status-' . $status)), esc_html($label));
    }
    private function get_request_status_label(WC_Order $order): string
    {
        $order_id = $order->get_id();
        if (!array_key_exists($order_id, $this->active_cache)) {
            $this->active_cache[$order_id] = $this->requests->find_active_by_order($order_id);
        }
        if ($this->active_cache[$order_id] instanceof RequestRecord) {
            return RequestType::get_order_status_label($this->active_cache[$order_id]->get_request_type());
        }
        if (!empty($order->get_meta('fr_refund_request_data'))) {
            return RequestType::get_order_status_label(RequestType::REFUND);
        }
        return wc_get_order_status_name($order->get_status());
    }
    private function get_latest_request(int $order_id): ?RequestRecord
    {
        if (!array_key_exists($order_id, $this->latest_cache)) {
            $this->latest_cache[$order_id] = $this->requests->find_latest_by_order($order_id);
        }
        return $this->latest_cache[$order_id];
    }
}
