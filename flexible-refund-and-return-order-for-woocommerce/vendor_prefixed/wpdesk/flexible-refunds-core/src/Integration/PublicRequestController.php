<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use WP_Post;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\PublicRequestLink;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\MyAccount as MyAccountLinks;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\PublicRequestAccess;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\PublicRequestPages;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class PublicRequestController implements Hookable
{
    private PublicRequestAccess $access;
    private PublicRequestPages $pages;
    private bool $is_pro;
    private string $lookup_notice = '';
    private bool $fallback = \false;
    public function __construct(PublicRequestAccess $access, PublicRequestPages $pages, bool $is_pro = \true)
    {
        $this->access = $access;
        $this->pages = $pages;
        $this->is_pro = $is_pro;
    }
    public function hooks(): void
    {
        if (!$this->is_pro) {
            return;
        }
        // Run before request saving and before canonical redirects or theme output.
        add_action('wp', [$this, 'prepare_public_page'], 20);
        add_filter('template_include', [$this, 'fallback_template']);
        add_action('woocommerce_login_form_end', [$this, 'login_fields']);
        add_filter('woocommerce_login_redirect', [$this, 'login_redirect'], 10, 2);
    }
    public function prepare_public_page(): void
    {
        global $wp, $wp_query;
        if (!$this->is_pro) {
            return;
        }
        $route_type = self::get_route_type((string) $wp->request);
        if (null === $route_type) {
            $query_type = self::input($_GET, PublicRequestLink::ROUTE_KEY);
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $route_type = in_array($query_type, RequestType::all(), \true) ? $query_type : null;
        }
        $type = $route_type ?? $this->get_page_type();
        if (null === $type) {
            return;
        }
        self::disable_cache();
        $has_link = isset($_GET[PublicRequestLink::REQUEST_KEY]);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $management = PublicRequestLink::is_management_link();
        if ($has_link) {
            $valid_guest_link = '' !== self::input($_GET, PublicRequestLink::TOKEN_KEY) && ($management ? null !== $this->access->get_guest_management_order($type) : null !== $this->access->get_guest_status_request($type));
            $request_id = absint(self::input($_GET, PublicRequestLink::REQUEST_KEY));
            if (!$valid_guest_link && is_user_logged_in()) {
                $order = $this->access->get_owned_order(get_current_user_id(), $request_id, 0, $type);
                if (!$order) {
                    $this->lookup_notice = __('This request link is invalid or unavailable.', 'flexible-refund-and-return-order-for-woocommerce');
                } elseif ($management) {
                    wp_safe_redirect(MyAccountLinks::get_request_view_url($order, $request_id));
                    exit;
                }
            } elseif (!$valid_guest_link) {
                $this->redirect_to_login(0, $request_id, $management ? '' : $type);
            }
            PublicRequestAccess::clear_public_request();
        } elseif (isset($_GET['fr_order'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $order_id = absint(self::input($_GET, 'fr_order'));
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $form_id = absint(self::input($_GET, 'form_id'));
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (is_user_logged_in()) {
                $order = $this->access->get_owned_order(get_current_user_id(), 0, $order_id);
                if ($order && $form_id > 0) {
                    wp_safe_redirect(MyAccountLinks::get_refund_url($order, $form_id));
                    exit;
                }
                $this->lookup_notice = __('This request link is invalid or unavailable.', 'flexible-refund-and-return-order-for-woocommerce');
            } else {
                $this->redirect_to_login($order_id, 0, '', $form_id);
            }
        } elseif (isset($_POST[PublicRefundShortcode::get_submit_field_name($type)])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $order = $this->access->lookup_order(
                $type,
                self::input($_POST, PublicRefundShortcode::get_order_field_name($type)),
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                self::input($_POST, PublicRefundShortcode::get_email_field_name($type)),
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                self::input($_POST, PublicRefundShortcode::get_nonce_field_name($type)),
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                self::input($_SERVER, 'REMOTE_ADDR')
            );
            $this->lookup_notice = $this->access->get_lookup_notice();
            if ($order && $order->get_customer_id() > 0) {
                $form_id = $this->pages->get_form_id($type);
                if ($form_id <= 0) {
                    $this->lookup_notice = __('This request type is currently unavailable.', 'flexible-refund-and-return-order-for-woocommerce');
                } elseif (is_user_logged_in()) {
                    if ($order->get_customer_id() === get_current_user_id()) {
                        wp_safe_redirect(MyAccountLinks::get_refund_url($order, $form_id));
                        exit;
                    }
                    $this->lookup_notice = __('This order belongs to another account. Log in as its owner to access the request.', 'flexible-refund-and-return-order-for-woocommerce');
                } else {
                    $this->redirect_to_login($order->get_id(), 0, '', $form_id);
                }
            }
        }
        if (null === $route_type) {
            return;
        }
        $page = $this->pages->get_public_page($type);
        if (null !== $page) {
            if (get_queried_object_id() !== $page->ID) {
                $args = [];
                if (isset($_GET[PublicRequestLink::REQUEST_KEY])) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $args[PublicRequestLink::REQUEST_KEY] = self::input($_GET, PublicRequestLink::REQUEST_KEY);
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $args[PublicRequestLink::TOKEN_KEY] = self::input($_GET, PublicRequestLink::TOKEN_KEY);
                    if ($management) {
                        $args[PublicRequestLink::MANAGEMENT_KEY] = '1';
                    }
                }
                wp_safe_redirect(add_query_arg($args, get_permalink($page)));
                exit;
            }
            return;
        }
        $this->fallback = \true;
        set_query_var(PublicRequestLink::ROUTE_KEY, $type);
        $wp_query->is_404 = \false;
        status_header(!$management && null !== $this->access->get_status_request($type) ? 200 : 404);
        remove_action('template_redirect', 'redirect_canonical');
    }
    public function fallback_template(string $template): string
    {
        return $this->fallback ? Integration::get_template_path() . 'public-refund/status-page.php' : $template;
    }
    public function is_fallback(): bool
    {
        return $this->fallback;
    }
    public function get_lookup_notice(): string
    {
        return $this->lookup_notice;
    }
    public static function get_route_type(string $path): ?string
    {
        return preg_match('#^refund/(refund|reclamation|repair)/?$#D', ltrim($path, '/'), $matches) ? $matches[1] : null;
    }
    private function get_page_type(): ?string
    {
        $post = get_queried_object();
        if (!$post instanceof WP_Post || 'page' !== $post->post_type) {
            return null;
        }
        $types = array_values(array_filter(RequestType::all(), static function (string $type) use ($post): bool {
            return PublicRequestPages::has_shortcode($post, $type);
        }));
        if (empty($types)) {
            return null;
        }
        $linked = $this->access->get_request_for_page();
        foreach ($types as $type) {
            if (isset($_POST[PublicRefundShortcode::get_submit_field_name($type)]) || null !== $linked && $linked->get_request_type() === $type) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                return $type;
            }
        }
        return $types[0];
    }
    public static function get_login_url(int $order_id = 0, int $request_id = 0, string $status_type = '', int $form_id = 0): string
    {
        $page_id = wc_get_page_id('myaccount');
        if ($page_id <= 0 || 'publish' !== get_post_status($page_id)) {
            return '';
        }
        $args = ['fr_public_login' => 1];
        if ($order_id > 0) {
            $args['fr_order'] = $order_id;
            if ($form_id > 0) {
                $args['form_id'] = $form_id;
            }
        }
        if ($request_id > 0) {
            $args[PublicRequestLink::REQUEST_KEY] = $request_id;
        }
        if (in_array($status_type, RequestType::all(), \true)) {
            $args['fr_status_type'] = $status_type;
        }
        return add_query_arg($args, wc_get_page_permalink('myaccount'));
    }
    private function redirect_to_login(int $order_id = 0, int $request_id = 0, string $status_type = '', int $form_id = 0): void
    {
        PublicRequestAccess::clear_public_request();
        $url = self::get_login_url($order_id, $request_id, $status_type, $form_id);
        if ('' === $url) {
            $this->lookup_notice = __('Please contact the store to access your account and submit a request.', 'flexible-refund-and-return-order-for-woocommerce');
            return;
        }
        if (WC()->session) {
            WC()->session->set_customer_session_cookie(\true);
            wc_add_notice($order_id > 0 ? __('This order is linked to an account. Please log in to submit or view your request.', 'flexible-refund-and-return-order-for-woocommerce') : __('Please log in to view your request.', 'flexible-refund-and-return-order-for-woocommerce'), 'notice');
        }
        wp_safe_redirect($url);
        exit;
    }
    public function login_fields(): void
    {
        if ('1' !== self::input($_GET, 'fr_public_login')) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        echo '<input type="hidden" name="fr_public_login" value="1" />';
        echo '<input type="hidden" name="fr_status_type" value="' . esc_attr(self::input($_GET, 'fr_status_type')) . '" />';
        if ('' === self::input($_GET, PublicRequestLink::REQUEST_KEY)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            echo '<input type="hidden" name="fr_order" value="' . esc_attr(self::input($_GET, 'fr_order')) . '" />';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            echo '<input type="hidden" name="form_id" value="' . esc_attr(self::input($_GET, 'form_id')) . '" />';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        echo '<input type="hidden" name="' . esc_attr(PublicRequestLink::REQUEST_KEY) . '" value="' . esc_attr(self::input($_GET, PublicRequestLink::REQUEST_KEY)) . '" />';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    public function login_redirect(string $redirect, $user): string
    {
        if ('1' !== self::input($_POST, 'fr_public_login')) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return $redirect;
        }
        $request_id = absint(self::input($_POST, PublicRequestLink::REQUEST_KEY));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $status_type = self::input($_POST, 'fr_status_type');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce has already verified login.
        $status_type = in_array($status_type, RequestType::all(), \true) ? $status_type : null;
        $order = $this->access->get_owned_order($user->ID, $request_id, absint(self::input($_POST, 'fr_order')), $status_type);
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce has already verified login.
        if ($order && $request_id > 0 && null !== $status_type) {
            return add_query_arg(PublicRequestLink::REQUEST_KEY, $request_id, PublicRequestLink::get_base_url($status_type));
        }
        $form_id = absint(self::input($_POST, 'form_id'));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce has already verified login.
        if ($order && 0 === $request_id && $form_id > 0) {
            return MyAccountLinks::get_refund_url($order, $form_id);
        }
        return $order ? MyAccountLinks::get_request_view_url($order, $request_id) : wc_get_account_endpoint_url('orders');
    }
    private static function input(array $input, string $key): string
    {
        return isset($input[$key]) && is_scalar($input[$key]) ? sanitize_text_field(wp_unslash((string) $input[$key])) : '';
    }
    private static function disable_cache(): void
    {
        if (!defined('FRFreeVendor\DONOTCACHEPAGE')) {
            define('FRFreeVendor\DONOTCACHEPAGE', \true);
        }
        nocache_headers();
        if (!headers_sent()) {
            header('Referrer-Policy: no-referrer');
        }
    }
}
