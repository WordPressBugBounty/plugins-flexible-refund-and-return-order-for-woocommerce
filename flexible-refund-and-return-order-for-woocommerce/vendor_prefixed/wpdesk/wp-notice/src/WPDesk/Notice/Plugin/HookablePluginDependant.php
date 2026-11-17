<?php

namespace FRFreeVendor\WPDesk\Notice\Plugin;

use FRFreeVendor\WPDesk\PluginBuilder\Plugin\HookablePluginDependant as BuilderHookablePluginDependant;
if (interface_exists(BuilderHookablePluginDependant::class)) {
    class_alias(BuilderHookablePluginDependant::class, __NAMESPACE__ . '\HookablePluginDependant');
} else {
    interface HookablePluginDependant
    {
        /**
         * Init hooks (actions and filters).
         */
        public function hooks();
        /**
         * Set plugin.
         *
         * @param object $plugin Plugin.
         */
        public function set_plugin($plugin);
        /**
         * Get plugin.
         *
         * @return object|null
         */
        public function get_plugin();
    }
}
