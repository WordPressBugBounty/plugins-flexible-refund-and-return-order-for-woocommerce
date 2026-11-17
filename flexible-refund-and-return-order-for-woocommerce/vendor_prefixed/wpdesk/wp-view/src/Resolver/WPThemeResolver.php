<?php

namespace FRFreeVendor\WPDesk\View\Resolver;

use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\View\Resolver\Exception\CanNotResolve;
/**
 * Class should resolve name by standard wp theme resolve
 *
 * @package WPDesk\View\Resolver
 */
class WPThemeResolver implements Resolver
{
    /** @var string */
    private $template_base_path;
    /**
     * Base path for templates ie. subdir
     *
     * @param $template_base_path
     */
    public function __construct($template_base_path)
    {
        $this->template_base_path = $template_base_path;
    }
    /**
     * Resolve name to full path
     *
     * @param string $name
     * @param Renderer|null $renderer
     *
     * @return string
     */
    public function resolve($name, ?Renderer $renderer = null)
    {
        $templateFile = locate_template([trailingslashit($this->template_base_path) . $name]);
        if (!$templateFile) {
            throw new CanNotResolve("Cannot resolve {$name}");
        }
        $full_name = realpath($templateFile);
        foreach ([get_stylesheet_directory(), get_template_directory()] as $theme_directory) {
            $base = realpath($theme_directory . '/' . $this->template_base_path);
            if ($base && $full_name && strpos($full_name, $base . \DIRECTORY_SEPARATOR) === 0) {
                return $full_name;
            }
        }
        throw new CanNotResolve("Cannot resolve {$name}");
    }
}
