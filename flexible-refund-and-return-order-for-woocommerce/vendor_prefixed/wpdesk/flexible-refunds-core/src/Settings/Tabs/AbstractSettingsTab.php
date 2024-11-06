<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\Tabs;

use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\ConditionSettingFactory;
abstract class AbstractSettingsTab
{
    /**
     * @var Renderer
     */
    protected $renderer;
    /**
     * @param Renderer $renderer
     */
    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }
    /**
     * @return Renderer
     */
    protected function get_renderer(): Renderer
    {
        return $this->renderer;
    }
    protected function get_condition_fields(): ConditionSettingFactory
    {
        return new ConditionSettingFactory(self::get_renderer());
    }
    /**
     * @return array
     */
    abstract public function get_fields(): array;
    /**
     * @return string
     */
    abstract public static function get_tab_slug(): string;
    /**
     * @return string
     */
    abstract public static function get_tab_name(): string;
}
