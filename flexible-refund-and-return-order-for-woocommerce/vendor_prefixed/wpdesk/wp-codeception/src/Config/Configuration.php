<?php

namespace FRFreeVendor\WPDesk\Codeception\Config;

class Configuration
{
    private string $plugin_slug;
    private string $plugin_dir;
    private string $plugin_file;
    private string $plugin_title;
    private string $plugin_product_id;
    /**
     * @var Language[]
     */
    private array $languages;
    /**
     * @param Language[] $languages
     */
    public function __construct(string $plugin_slug, string $plugin_dir, string $plugin_file, string $plugin_title, string $plugin_product_id, array $languages)
    {
        $this->plugin_slug = $plugin_slug;
        $this->plugin_dir = $plugin_dir;
        $this->plugin_file = $plugin_file;
        $this->plugin_title = $plugin_title;
        $this->plugin_product_id = $plugin_product_id;
        $this->languages = $languages;
    }
    public function getPluginSlug(): string
    {
        return $this->plugin_slug;
    }
    public function getPluginDir(): string
    {
        return $this->plugin_dir;
    }
    public function getPluginFile(): string
    {
        return $this->plugin_file;
    }
    public function getPluginTitle(): string
    {
        return $this->plugin_title;
    }
    public function getPluginProductId(): string
    {
        return $this->plugin_product_id;
    }
    /**
     * @return Language[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }
    public function prepareEnvForConfiguration(): void
    {
        $this->putEnv('WPDESK_PLUGIN_SLUG', $this->plugin_slug);
        $this->putEnv('WPDESK_PLUGIN_DIR', $this->plugin_dir);
        $this->putEnv('WPDESK_PLUGIN_FILE', $this->plugin_file);
        $this->putEnv('WPDESK_PLUGIN_TITLE', $this->plugin_title);
        $this->putEnv('WPDESK_PLUGIN_PRODUCT_ID', $this->plugin_product_id);
    }
    private function putEnv(string $env_variable, string $value): void
    {
        putenv($env_variable . '=' . $value);
    }
    public static function createFromEnvAndConfiguration(array $configuration): self
    {
        $plugin_config = isset($configuration['plugin']) && is_array($configuration['plugin']) ? $configuration['plugin'] : [];
        $plugin_slug = self::stringSetting($plugin_config, 'slug') ?: self::stringSetting($configuration, 'plugin-slug');
        if ($plugin_slug === '') {
            throw new SettingsException('Missing plugin-slug setting!');
        }
        $plugin_file = self::pluginFile($configuration, $plugin_config);
        if ($plugin_file === '') {
            throw new SettingsException('Missing plugin-file setting!');
        }
        $plugin_title = self::stringSetting($plugin_config, 'title') ?: self::stringSetting($configuration, 'plugin-title');
        if ($plugin_title === '') {
            throw new SettingsException('Missing plugin-title setting!');
        }
        $plugin_product_id = self::stringSetting($plugin_config, 'product_id') ?: self::stringSetting($configuration, 'plugin-product-id');
        return new self($plugin_slug, self::pluginDirectory($plugin_file), $plugin_file, $plugin_title, $plugin_product_id, self::languages($configuration, $plugin_slug));
    }
    private static function pluginFile(array $configuration, array $plugin_config): string
    {
        $directory = self::stringSetting($plugin_config, 'directory');
        $file = self::stringSetting($plugin_config, 'file');
        if ($directory !== '' && $file !== '') {
            return $directory . '/' . $file;
        }
        return self::stringSetting($configuration, 'plugin-file');
    }
    private static function pluginDirectory(string $plugin_file): string
    {
        return explode('/', $plugin_file, 2)[0];
    }
    /**
     * @return Language[]
     */
    private static function languages(array $configuration, string $plugin_slug): array
    {
        $languages_config = isset($configuration['languages']) && is_array($configuration['languages']) ? $configuration['languages'] : [];
        $languages = [];
        foreach ($languages_config as $language => $language_config) {
            $language_config = is_array($language_config) ? $language_config : [];
            $languages[] = new Language((string) $language, self::stringSetting($language_config, 'plugin-slug') ?: $plugin_slug, self::stringSetting($language_config, 'plugin-title'), self::stringSetting($language_config, 'plugin-description'));
        }
        return $languages;
    }
    private static function stringSetting(array $settings, string $key): string
    {
        return isset($settings[$key]) && is_string($settings[$key]) ? $settings[$key] : '';
    }
}
