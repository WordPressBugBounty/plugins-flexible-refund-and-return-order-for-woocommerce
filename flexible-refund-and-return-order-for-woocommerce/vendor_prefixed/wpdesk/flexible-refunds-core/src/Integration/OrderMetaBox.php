<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use Throwable;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use WC_Order;
use WP_Post;
class OrderMetaBox implements Hookable
{
    private Renderer $renderer;
    private PersistentContainer $settings;
    private RequestRepository $requests;
    public function __construct(Renderer $renderer, PersistentContainer $settings, RequestRepository $requests)
    {
        $this->renderer = $renderer;
        $this->settings = $settings;
        $this->requests = $requests;
    }
    public function hooks()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 11, 2);
    }
    public function add_meta_boxes($post_type, $post)
    {
        $post_id = \method_exists($post, 'get_id') ? $post->get_id() : $post->ID;
        if ($post_id) {
            $order = wc_get_order($post_id);
            if ($order instanceof WC_Order) {
                try {
                    $has_requests = [] !== $this->requests->find_by_order($order->get_id());
                } catch (Throwable $e) {
                    $has_requests = \false;
                }
                if ($has_requests || !empty($order->get_meta('fr_refund_request_data'))) {
                    add_meta_box('shop_order_fr_meta_box', __('Customer requests', 'flexible-refund-and-return-order-for-woocommerce'), [$this, 'fr_meta_box_content'], $this->get_meta_box_screens(), 'normal', 'high', ['order' => $order]);
                }
            }
        }
    }
    private function get_meta_box_screens(): array
    {
        $screens = ['shop_subscription', 'woocommerce_page_wc-orders'];
        try {
            $hpos_controller = wc_get_container()->get('FRFreeVendor\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController');
            if ($hpos_controller->custom_orders_table_usage_is_enabled()) {
                return array_merge([wc_get_page_screen_id('shop-order')], $screens);
            } else {
                return array_merge(['shop_order'], $screens);
            }
        } catch (\Exception $e) {
            return array_merge(['shop_order'], $screens);
        }
    }
    /**
     * @param mixed $post_or_order_object Post or order object.
     * @param array $data Meta box data.
     *
     * @return void
     */
    public function fr_meta_box_content($post_or_order_object, array $data)
    {
        $order = $data['args']['order'];
        $requests = $this->requests->find_by_order($order->get_id());
        if ([] !== $requests) {
            $this->renderer->output_render('order/request-meta-box', ['order' => $order, 'requests' => $requests, 'selected' => $this->get_selected_request($requests)]);
            return;
        }
        $this->renderer->output_render('order/refund-meta-box', ['order' => $order, 'settings' => $this->settings]);
    }
    /**
     * @param RequestRecord[] $requests
     */
    private function get_selected_request(array $requests): RequestRecord
    {
        $selected_id = isset($_GET['fr_request_id']) ? absint($_GET['fr_request_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        foreach ($requests as $request) {
            if ($selected_id === $request->get_id()) {
                return $request;
            }
        }
        foreach ($requests as $request) {
            if ($request->is_active()) {
                return $request;
            }
        }
        return $requests[0];
    }
}
