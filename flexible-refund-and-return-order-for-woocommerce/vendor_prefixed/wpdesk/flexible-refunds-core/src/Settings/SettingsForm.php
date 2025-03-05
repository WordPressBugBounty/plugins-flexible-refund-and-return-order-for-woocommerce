<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings;

use FRFreeVendor\WPDesk\Forms\Resolver\DefaultFormFieldResolver;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\SimplePhpRenderer;
use FRFreeVendor\WPDesk\View\Resolver\ChainResolver;
use FRFreeVendor\WPDesk\View\Resolver\DirResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
class SettingsForm implements Hookable
{
    /**
     * Constructor.
     */
    public function hooks()
    {
        add_filter('woocommerce_get_settings_pages', [$this, 'add_settings_page']);
        add_filter('woocommerce_admin_settings_sanitize_option_fr_refund_form_builder', [$this, 'undo_sanitize_html_values'], 10, 3);
    }
    public function undo_sanitize_html_values($value, $option, $raw_value)
    {
        if (!empty($value)) {
            foreach ($value as $field_id => $field) {
                if ($field['type'] === 'html') {
                    $value[$field_id]['html'] = $raw_value[$field_id]['html'];
                }
            }
        }
        return $value;
    }
    private function get_renderer(): SimplePhpRenderer
    {
        $resolver = new ChainResolver();
        $resolver->appendResolver(new DirResolver(Integration::get_template_path() . 'settings'));
        $resolver->appendResolver(new DefaultFormFieldResolver());
        return new SimplePhpRenderer($resolver);
    }
    public function add_settings_page($settings)
    {
        $renderer = $this->get_renderer();
        $settings[] = new SettingsIntegration($renderer);
        return $settings;
    }
}
