<?php

namespace FRFreeVendor\WPDesk\Tracker;

interface HookableCollection extends Hookable
{
    public function add_hookable(Hookable $hookable_object);
    public function get_hookable_instance_by_class_name($class_name);
}
