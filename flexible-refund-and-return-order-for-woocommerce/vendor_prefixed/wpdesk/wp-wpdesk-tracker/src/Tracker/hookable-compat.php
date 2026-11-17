<?php

namespace FRFreeVendor\WPDesk\Tracker;

if (interface_exists(\FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable::class) && interface_exists(\FRFreeVendor\WPDesk\PluginBuilder\Plugin\HookableCollection::class)) {
    class_alias(\FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable::class, Hookable::class);
    class_alias(\FRFreeVendor\WPDesk\PluginBuilder\Plugin\HookableCollection::class, HookableCollection::class);
} else {
    require_once __DIR__ . '/Hookable.php';
    require_once __DIR__ . '/HookableCollection.php';
}
