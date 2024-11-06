<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer;

use FRFreeVendor\WPDesk\Forms\Resolver\DefaultFormFieldResolver;
use FRFreeVendor\WPDesk\Persistence\Adapter\WordPress\WordpressOptionsContainer;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\View\Renderer\SimplePhpRenderer;
use FRFreeVendor\WPDesk\View\Resolver\ChainResolver;
use FRFreeVendor\WPDesk\View\Resolver\DirResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\FormBuilder;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\Tabs\RefundOrderTab;
class FieldRenderer
{
    const FIELD_PREFIX = 'fr_refund_form';
    /**
     * @var WordpressOptionsContainer
     */
    private $settings;
    public function __construct()
    {
        $this->settings = new WordpressOptionsContainer(RefundOrderTab::SETTING_PREFIX);
    }
    /**
     * @return Renderer
     */
    private function get_renderer(): Renderer
    {
        $chain = new ChainResolver();
        $resolver_list = (array) apply_filters('fr/core/form_builder', [new DirResolver(trailingslashit(dirname(__FILE__)) . 'Views'), new DefaultFormFieldResolver()]);
        foreach ($resolver_list as $resolver) {
            $chain->appendResolver($resolver);
        }
        return new SimplePhpRenderer($chain);
    }
    /**
     * @return string
     */
    public function output(): string
    {
        $fields = $this->settings->get_fallback('form_builder', []);
        $field_factory = new FieldFactory($this->get_renderer());
        $output_fields = '';
        if (is_array($fields) && !empty($fields)) {
            foreach ($fields as $name => $field) {
                $field['name'] = $name;
                $data = FormBuilder::parse_field_args($field);
                if ((int) $data['enable'] === 1) {
                    $output_fields .= $field_factory->get_field($data['type'], $data);
                }
            }
        }
        return (string) apply_filters('wpdesk/fr/form-builder/front/form-output', $output_fields, $fields, $field_factory);
    }
}
