<?php

namespace FRFreeVendor\WPDesk\Tracker;

trait HookableParent
{
    /** @var Hookable[] */
    private $hookable_objects = [];
    public function add_hookable(Hookable $hookable_object)
    {
        $this->hookable_objects[] = $hookable_object;
    }
    public function get_hookable_instance_by_class_name($class_name)
    {
        foreach ($this->hookable_objects as $hookable_object) {
            if ($hookable_object instanceof $class_name) {
                return $hookable_object;
            }
        }
        return \false;
    }
    protected function hooks_on_hookable_objects()
    {
        foreach ($this->hookable_objects as $hookable_object) {
            if (!interface_exists(\FRFreeVendor\WPDesk\PluginBuilder\Plugin\Conditional::class) || !$hookable_object instanceof \FRFreeVendor\WPDesk\PluginBuilder\Plugin\Conditional || $hookable_object::is_needed()) {
                $hookable_object->hooks();
            }
        }
    }
}
