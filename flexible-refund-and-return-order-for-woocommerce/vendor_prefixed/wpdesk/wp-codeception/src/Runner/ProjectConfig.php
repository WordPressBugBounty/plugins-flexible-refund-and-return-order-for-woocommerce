<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

final class ProjectConfig
{
    /**
     * @param list<string> $wordpressOrgDependencies
     * @param list<string> $localDependencies
     * @param list<string> $activatePlugins
     * @param list<string> $setupCommands
     */
    public function __construct(public readonly string $pluginDirectory, public readonly string $pluginFile, public readonly string $pluginSlug, public readonly string $pluginTitle, public readonly string $pluginProductId, public readonly bool $activatePlugin, public readonly array $wordpressOrgDependencies, public readonly array $localDependencies, public readonly array $activatePlugins, public readonly array $setupCommands)
    {
    }
    public function pluginRelativeFile(): string
    {
        return $this->pluginDirectory . '/' . $this->pluginFile;
    }
    public function hasWooCommerce(): bool
    {
        $plugins = array_merge($this->wordpressOrgDependencies, $this->localDependencies, $this->activatePlugins);
        foreach ($plugins as $plugin) {
            if ($plugin === 'woocommerce' || str_starts_with($plugin, 'woocommerce/')) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @return list<string>
     */
    public function pluginsToActivate(): array
    {
        $plugins = $this->activatePlugins;
        $primary = $this->pluginRelativeFile();
        if ($this->activatePlugin && !in_array($primary, $plugins, \true) && !in_array($this->pluginDirectory, $plugins, \true)) {
            $plugins[] = $primary;
        }
        return array_values(array_unique($plugins));
    }
    /**
     * @return array<string, string>
     */
    public function processEnvironment(): array
    {
        return ['WPDESK_PLUGIN_DIR' => $this->pluginDirectory, 'WPDESK_PLUGIN_FILE' => $this->pluginRelativeFile(), 'WPDESK_PLUGIN_SLUG' => $this->pluginSlug, 'WPDESK_PLUGIN_TITLE' => $this->pluginTitle, 'WPDESK_PLUGIN_PRODUCT_ID' => $this->pluginProductId];
    }
}
