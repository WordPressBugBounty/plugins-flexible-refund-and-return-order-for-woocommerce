<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

use FRFreeVendor\Symfony\Component\Yaml\Yaml;
final class ProjectConfigReader
{
    public function read(ProjectPaths $paths): ProjectConfig
    {
        $raw = [];
        if (is_file($paths->wpdeskConfigPath())) {
            $parsed = Yaml::parseFile($paths->wpdeskConfigPath());
            $raw = is_array($parsed) ? $parsed : [];
        }
        $plugin = $this->pluginConfig($raw, $paths);
        $dependencies = $this->arrayValue($raw['dependencies'] ?? []);
        $legacyPlugins = $this->arrayValue($raw['plugins'] ?? []);
        return new ProjectConfig($plugin['directory'], $plugin['file'], $plugin['slug'], $plugin['title'], $plugin['product_id'], $plugin['activate'], $this->stringList($dependencies['wordpress_org'] ?? $legacyPlugins['repository'] ?? []), $this->stringList($dependencies['local'] ?? $legacyPlugins['local'] ?? []), $this->stringList($dependencies['activate'] ?? $legacyPlugins['activate'] ?? []), $this->setupCommands($raw));
    }
    /**
     * @return array{directory: string, file: string, slug: string, title: string, product_id: string, activate: bool}
     */
    private function pluginConfig(array $raw, ProjectPaths $paths): array
    {
        $plugin = $this->arrayValue($raw['plugin'] ?? []);
        $legacyPluginFile = isset($raw['plugin-file']) && is_string($raw['plugin-file']) ? $raw['plugin-file'] : '';
        [$legacyDirectory, $legacyFile] = $this->splitPluginFile($legacyPluginFile);
        $inferred = $this->inferPluginFromProject($paths);
        $directory = ($this->stringValue($plugin['directory'] ?? null) ?: $legacyDirectory) ?: $inferred['directory'];
        $file = ($this->stringValue($plugin['file'] ?? null) ?: $legacyFile) ?: $inferred['file'];
        return ['directory' => $directory, 'file' => $file, 'slug' => ($this->stringValue($plugin['slug'] ?? null) ?: $this->stringValue($raw['plugin-slug'] ?? null)) ?: $directory, 'title' => ($this->stringValue($plugin['title'] ?? null) ?: $this->stringValue($raw['plugin-title'] ?? null)) ?: $directory, 'product_id' => $this->stringValue($plugin['product_id'] ?? null) ?: $this->stringValue($raw['plugin-product-id'] ?? null), 'activate' => isset($plugin['activate']) && is_bool($plugin['activate']) ? $plugin['activate'] : \false];
    }
    /**
     * @return array{directory: string, file: string}
     */
    private function inferPluginFromProject(ProjectPaths $paths): array
    {
        $directory = basename($paths->projectRoot());
        $default = ['directory' => $directory, 'file' => $directory . '.php'];
        $files = glob($paths->projectRoot() . '/*.php') ?: [];
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            if (is_string($contents) && str_contains($contents, 'Plugin Name:')) {
                return ['directory' => $directory, 'file' => basename($file)];
            }
        }
        return $default;
    }
    /**
     * @return array{0: string, 1: string}
     */
    private function splitPluginFile(string $pluginFile): array
    {
        if ($pluginFile === '') {
            return ['', ''];
        }
        $parts = explode('/', $pluginFile, 2);
        if (count($parts) === 1) {
            return ['', $parts[0]];
        }
        return [$parts[0], $parts[1]];
    }
    /**
     * @return list<string>
     */
    private function setupCommands(array $raw): array
    {
        $setup = $this->arrayValue($raw['setup'] ?? []);
        $commands = $setup['wp_cli'] ?? $raw['prepare-database'] ?? [];
        return $this->stringList($commands);
    }
    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $strings = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }
        return $strings;
    }
}
