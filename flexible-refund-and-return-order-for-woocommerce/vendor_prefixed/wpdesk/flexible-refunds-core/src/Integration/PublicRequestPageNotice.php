<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\PublicRequestLink;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\PublicRequestPages;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\SettingsIntegration;
use FRFreeVendor\WPDesk\Notice\AjaxHandler;
use FRFreeVendor\WPDesk\Notice\Notice;
use FRFreeVendor\WPDesk\Notice\PermanentDismissibleNotice;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
final class PublicRequestPageNotice implements Hookable
{
    private FormRepository $forms;
    private PublicRequestPages $pages;
    public function __construct(FormRepository $forms, PublicRequestPages $pages)
    {
        $this->forms = $forms;
        $this->pages = $pages;
    }
    public function hooks(): void
    {
        if (!Integration::is_super()) {
            return;
        }
        add_action('admin_init', [$this, 'maybe_notice'], 20);
        // The library supplies the dismiss handler and assets; no custom AJAX is needed.
        (new AjaxHandler())->hooks();
    }
    public function maybe_notice(): void
    {
        if (!Integration::is_super() || !current_user_can('manage_woocommerce')) {
            return;
        }
        $type = SettingsIntegration::get_current_request_type();
        if (null === $type) {
            return;
        }
        $error = get_option(PublicRequestPages::ERROR_OPTION, '');
        if (is_string($error) && '' !== $error) {
            new Notice($error, 'error');
        }
        $form = $this->forms->find_by_type($type);
        if (null === $form || !$form->is_enabled()) {
            return;
        }
        $page = $this->pages->get_public_page($type);
        if (null === $page) {
            new Notice(__('Select a published page containing the correct shortcode so customers can submit requests without an account.', 'flexible-refund-and-return-order-for-woocommerce'), 'warning');
            return;
        }
        if ($this->is_in_menu($page->ID, $type)) {
            return;
        }
        $menu_url = function_exists('wp_is_block_theme') && wp_is_block_theme() ? admin_url('site-editor.php?path=/navigation') : admin_url('nav-menus.php');
        new PermanentDismissibleNotice(sprintf(
            /* translators: 1: public page link, 2: menu editor link. */
            __('Your public request page is not in a menu. Add %1$s to your %2$s so customers can find it.', 'flexible-refund-and-return-order-for-woocommerce'),
            '<a href="' . esc_url(get_permalink($page)) . '">' . esc_html(get_the_title($page)) . '</a>',
            '<a href="' . esc_url($menu_url) . '">' . esc_html__('navigation menu', 'flexible-refund-and-return-order-for-woocommerce') . '</a>'
        ), 'fr_public_menu_' . get_current_user_id() . '_' . $type);
    }
    public function is_in_menu(int $page_id, string $type): bool
    {
        $urls = [get_permalink($page_id), PublicRequestLink::get_base_url($type)];
        $locations = array_intersect_key(get_nav_menu_locations(), get_registered_nav_menus());
        foreach (array_unique(array_filter($locations)) as $menu_id) {
            foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
                if (is_object($item) && ('page' === $item->object && (int) $item->object_id === $page_id || $this->matches_url($item->url, $urls))) {
                    return \true;
                }
            }
        }
        if (function_exists('get_block_templates')) {
            foreach (get_block_templates([], 'wp_template') as $template) {
                if ($this->blocks_contain_page(parse_blocks($template->content), $page_id, $urls, \false)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function blocks_contain_page(array $blocks, int $page_id, array $urls, bool $in_navigation, array $seen = []): bool
    {
        if (function_exists('resolve_pattern_blocks')) {
            $blocks = resolve_pattern_blocks($blocks);
        }
        foreach ($blocks as $block) {
            $name = $block['blockName'] ?? '';
            $attrs = $block['attrs'] ?? [];
            if ('core/template-part' === $name && !empty($attrs['slug']) && function_exists('get_block_template')) {
                $theme = get_stylesheet();
                $id = $theme . '//' . $attrs['slug'];
                if (($attrs['theme'] ?? $theme) !== $theme || isset($seen[$id])) {
                    continue;
                }
                $part = get_block_template($id, 'wp_template_part');
                if (null !== $part && $this->blocks_contain_page(parse_blocks($part->content), $page_id, $urls, $in_navigation, $seen + [$id => \true])) {
                    return \true;
                }
                continue;
            }
            if (in_array($name, ['core/navigation', 'core/block'], \true) && !empty($attrs['ref'])) {
                $id = 'post:' . (int) $attrs['ref'];
                if (isset($seen[$id])) {
                    continue;
                }
                $post = get_post((int) $attrs['ref']);
                $post_type = 'core/navigation' === $name ? 'wp_navigation' : 'wp_block';
                if ($post instanceof \WP_Post && $post_type === $post->post_type && 'publish' === $post->post_status && $this->blocks_contain_page(parse_blocks($post->post_content), $page_id, $urls, $in_navigation || 'core/navigation' === $name, $seen + [$id => \true])) {
                    return \true;
                }
                continue;
            }
            if ($in_navigation && in_array($name, ['core/navigation-link', 'core/navigation-submenu'], \true) && ('page' === ($attrs['type'] ?? '') && $page_id === (int) ($attrs['id'] ?? 0) || $this->matches_url($attrs['url'] ?? '', $urls))) {
                return \true;
            }
            if ($in_navigation && 'core/page-list' === $name) {
                $parent = (int) ($attrs['parentPageID'] ?? 0);
                if (!$parent || in_array($parent, get_post_ancestors($page_id), \true)) {
                    return \true;
                }
            }
            if ($this->blocks_contain_page($block['innerBlocks'] ?? [], $page_id, $urls, $in_navigation || 'core/navigation' === $name, $seen)) {
                return \true;
            }
        }
        return \false;
    }
    private function matches_url(string $url, array $targets): bool
    {
        foreach ($targets as $target) {
            $host = wp_parse_url($url, \PHP_URL_HOST);
            if (null === $host && 0 === strpos($url, '/')) {
                $host = wp_parse_url($target, \PHP_URL_HOST);
            }
            if ($host === wp_parse_url($target, \PHP_URL_HOST) && untrailingslashit((string) wp_parse_url($url, \PHP_URL_PATH)) === untrailingslashit((string) wp_parse_url($target, \PHP_URL_PATH))) {
                parse_str((string) wp_parse_url($url, \PHP_URL_QUERY), $query);
                parse_str((string) wp_parse_url($target, \PHP_URL_QUERY), $target_query);
                foreach (['page_id', PublicRequestLink::ROUTE_KEY] as $key) {
                    if (($query[$key] ?? null) !== ($target_query[$key] ?? null)) {
                        continue 2;
                    }
                }
                return \true;
            }
        }
        return \false;
    }
}
