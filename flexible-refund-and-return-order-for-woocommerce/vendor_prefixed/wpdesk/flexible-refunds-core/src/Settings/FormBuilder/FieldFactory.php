<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\FormBuilder;

use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\View\Renderer\SimplePhpRenderer;
use FRFreeVendor\WPDesk\View\Resolver\ChainResolver;
use FRFreeVendor\WPDesk\View\Resolver\DirResolver;
class FieldFactory
{
    /**
     * @return Renderer
     */
    private function get_renderer(): Renderer
    {
        $chain = new ChainResolver();
        $chain->appendResolver(new DirResolver(trailingslashit(dirname(__FILE__)) . 'Views'));
        return new SimplePhpRenderer($chain);
    }
    /**
     * @param string $type
     * @param string $name
     * @param array  $data
     *
     * @return string
     */
    public function get_field(string $type, string $name, array $data = []): string
    {
        $data = wp_parse_args($data, ['type' => $type, 'name' => $name, 'default' => '', 'options' => '']);
        return $this->get_renderer()->render('form-field', $data);
    }
}
