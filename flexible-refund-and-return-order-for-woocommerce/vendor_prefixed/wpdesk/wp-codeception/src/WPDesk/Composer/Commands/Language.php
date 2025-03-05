<?php

namespace FRFreeVendor\WPDesk\Composer\Codeception\Commands;

class Language
{
    private string $locale;
    private string $plugin_slug;
    private string $plugin_title;
    private string $plugin_description;
    public function __construct(string $locale, string $plugin_slug, string $plugin_title, string $plugin_description)
    {
        $this->plugin_slug = $plugin_slug;
        $this->locale = $locale;
        $this->plugin_title = $plugin_title;
        $this->plugin_description = $plugin_description;
    }
    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
    /**
     * @return string
     */
    public function getPluginSlug(): string
    {
        return $this->plugin_slug;
    }
    /**
     * @return string
     */
    public function getPluginTitle(): string
    {
        return $this->plugin_title;
    }
    /**
     * @return string
     */
    public function getPluginDescription(): string
    {
        return $this->plugin_description;
    }
}
