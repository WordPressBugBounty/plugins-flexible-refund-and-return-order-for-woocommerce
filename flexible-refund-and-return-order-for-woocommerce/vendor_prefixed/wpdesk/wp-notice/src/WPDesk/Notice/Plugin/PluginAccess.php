<?php

namespace FRFreeVendor\WPDesk\Notice\Plugin;

use FRFreeVendor\WPDesk\PluginBuilder\Plugin\PluginAccess as BuilderPluginAccess;
if (trait_exists(BuilderPluginAccess::class)) {
    class_alias(BuilderPluginAccess::class, __NAMESPACE__ . '\PluginAccess');
} else {
    trait PluginAccess
    {
        /**
         * Plugin.
         *
         * @var object|null
         */
        private $plugin;
        /**
         * Set plugin.
         *
         * @param object $plugin Plugin.
         */
        public function set_plugin($plugin)
        {
            $this->plugin = $plugin;
        }
        /**
         * Get plugin.
         *
         * @return object|null
         */
        public function get_plugin()
        {
            return $this->plugin;
        }
    }
}
