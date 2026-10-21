<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use RuntimeException;
use Throwable;
use WP_Post;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\PublicRefundShortcode;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
final class PublicRequestPages implements Hookable
{
    public const PAGE_SETTING = 'public_page_id';
    public const ERROR_OPTION = 'fr_public_pages_error';
    private const DONE_OPTION = 'fr_public_pages_initialized';
    private const LOCK_OPTION = 'fr_public_pages_initializing';
    private const PAGE_META = '_fr_public_request_type';
    private FormRepository $forms;
    public function __construct(FormRepository $forms)
    {
        $this->forms = $forms;
    }
    public function hooks(): void
    {
        if (!Integration::is_super()) {
            return;
        }
        add_action('init', [$this, 'initialize'], 10);
        add_action('flexible_refunds_form_updated', [$this, 'form_updated'], 10, 2);
    }
    public function initialize(): void
    {
        if (!Integration::is_super() || (bool) get_option(self::DONE_OPTION)) {
            return;
        }
        $lock = (int) get_option(self::LOCK_OPTION);
        if ($lock && $lock < time() - 300) {
            delete_option(self::LOCK_OPTION);
        }
        if (!add_option(self::LOCK_OPTION, time(), '', \false)) {
            return;
        }
        try {
            $pages = get_posts(['post_type' => 'page', 'post_status' => ['publish', 'draft', 'pending', 'private', 'future'], 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC']);
            foreach (RequestType::all() as $type) {
                $form = $this->forms->find_by_type($type);
                if (null === $form) {
                    throw new RuntimeException('Request forms are not available yet.');
                }
                $settings = $form->get_settings();
                $page_id = (int) ($settings[self::PAGE_SETTING] ?? 0);
                $page = $page_id > 0 ? get_post($page_id) : null;
                if (!$page instanceof WP_Post || 'page' !== $page->post_type || 'trash' === $page->post_status) {
                    $page_id = 0;
                    foreach ($pages as $candidate) {
                        if (self::has_shortcode($candidate, $type)) {
                            $page_id = $candidate->ID;
                            break;
                        }
                    }
                    if (!$page_id) {
                        $page_id = wp_insert_post(['post_type' => 'page', 'post_title' => RequestType::get_label($type), 'post_name' => $type, 'post_content' => PublicRefundShortcode::get_shortcode_for_type($type), 'post_status' => $form->is_enabled() ? 'publish' : 'draft', 'meta_input' => [self::PAGE_META => $type]], \true);
                        if (is_wp_error($page_id) || !$page_id) {
                            throw new RuntimeException('Could not create a public request page.');
                        }
                    }
                    $settings[self::PAGE_SETTING] = (int) $page_id;
                    $this->forms->save($form->with_configuration($form->is_enabled(), $form->get_button_label(), $form->get_schema(), $settings, gmdate('Y-m-d H:i:s')));
                }
                if ($form->is_enabled()) {
                    self::mark_completed(self::enabled_option($type));
                }
            }
            self::mark_completed(self::DONE_OPTION);
            delete_option(self::ERROR_OPTION);
        } catch (Throwable $e) {
            update_option(self::ERROR_OPTION, __('Public request pages could not be initialized. The plugin will retry automatically.', 'flexible-refund-and-return-order-for-woocommerce'), \false);
        } finally {
            delete_option(self::LOCK_OPTION);
        }
    }
    public function form_updated(FormDefinition $previous, FormDefinition $updated): void
    {
        if (!Integration::is_super() || $previous->is_enabled() || !$updated->is_enabled() || (bool) get_option(self::enabled_option($updated->get_request_type()))) {
            return;
        }
        $page_id = (int) ($updated->get_settings()[self::PAGE_SETTING] ?? 0);
        $page = $page_id > 0 ? get_post($page_id) : null;
        if ($page instanceof WP_Post && 'draft' === $page->post_status && $updated->get_request_type() === get_post_meta($page_id, self::PAGE_META, \true)) {
            $result = wp_update_post(['ID' => $page_id, 'post_status' => 'publish'], \true);
            if (is_wp_error($result) || !$result) {
                throw new RuntimeException('Could not publish the public request page.');
            }
        }
        self::mark_completed(self::enabled_option($updated->get_request_type()));
    }
    public function get_form_id(string $type): int
    {
        $form = $this->forms->find_by_type($type);
        return null !== $form ? (int) $form->get_id() : 0;
    }
    public function get_public_page(string $type): ?WP_Post
    {
        $form = $this->forms->find_by_type($type);
        $page_id = null !== $form ? (int) ($form->get_settings()[self::PAGE_SETTING] ?? 0) : 0;
        $page = $page_id > 0 ? get_post($page_id) : null;
        return $page instanceof WP_Post && 'page' === $page->post_type && 'publish' === $page->post_status && '' === $page->post_password && self::has_shortcode($page, $type) ? $page : null;
    }
    public static function has_shortcode(WP_Post $page, string $type): bool
    {
        return has_shortcode($page->post_content, trim(PublicRefundShortcode::get_shortcode_for_type($type), '[]'));
    }
    private static function enabled_option(string $type): string
    {
        return 'fr_public_page_enabled_' . $type;
    }
    private static function mark_completed(string $option): void
    {
        if (!update_option($option, \true, \false) && !(bool) get_option($option)) {
            throw new RuntimeException('Could not store the public request page state.');
        }
    }
}
