<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings;

use FRFreeVendor\WPDesk\Forms\Resolver\DefaultFormFieldResolver;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\SimplePhpRenderer;
use FRFreeVendor\WPDesk\View\Resolver\ChainResolver;
use FRFreeVendor\WPDesk\View\Resolver\DirResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\FormService;
class SettingsForm implements Hookable
{
    private FormRepository $forms;
    private FormService $form_service;
    private bool $is_pro;
    public function __construct(FormRepository $forms, FormService $form_service, bool $is_pro)
    {
        $this->forms = $forms;
        $this->form_service = $form_service;
        $this->is_pro = $is_pro;
    }
    /**
     * Constructor.
     */
    public function hooks()
    {
        add_filter('woocommerce_admin_settings_sanitize_option_fr_refund_form_builder', [$this, 'undo_sanitize_html_values'], 10, 3);
        $renderer = $this->get_renderer();
        $controller = new FormsController($renderer, $this->forms, $this->form_service, new FormConfigurationSanitizer(), $this->is_pro);
        (new SettingsIntegration($controller, $renderer))->hooks();
    }
    private function get_renderer(): SimplePhpRenderer
    {
        $resolver = new ChainResolver();
        $resolver->appendResolver(new DirResolver(Integration::get_template_path() . 'settings'));
        $resolver->appendResolver(new DefaultFormFieldResolver());
        return new SimplePhpRenderer($resolver);
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
}
