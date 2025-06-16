<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore;

use FRFreeVendor\Psr\Log\LoggerInterface;
use FRFreeVendor\WPDesk\Persistence\Adapter\WordPress\WordpressOptionsContainer;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\HookableParent;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\View\Renderer\SimplePhpRenderer;
use FRFreeVendor\WPDesk\View\Resolver\ChainResolver;
use FRFreeVendor\WPDesk\View\Resolver\DirResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\SettingsForm;
/**
 * Main class for integrate library with plugin.
 *
 * @package WPDesk\Library\CustomPrice
 */
class Integration implements Hookable
{
    const SETTING_PREFIX = 'fr_refund_';
    use HookableParent;
    /**
     * @var Renderer
     */
    protected $renderer;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var bool
     */
    private static $is_super = \true;
    /**
     * @param bool $is_super
     */
    public function __construct($is_super = \false)
    {
        self::$is_super = $is_super;
    }
    /**
     * @return bool
     */
    public static function is_super(): bool
    {
        return self::$is_super;
    }
    /**
     * @return string
     */
    public static function get_library_url(): string
    {
        return trailingslashit(plugin_dir_url(__DIR__));
    }
    /**
     * @return string
     */
    public static function get_library_path(): string
    {
        return trailingslashit(plugin_dir_path(__DIR__));
    }
    /**
     * @return string
     */
    public static function get_template_path(): string
    {
        return self::get_library_path() . 'src/Views/';
    }
    /**
     * Set renderer.
     */
    protected function get_renderer(): Renderer
    {
        $resolver = new ChainResolver();
        $resolver->appendResolver(new DirResolver(get_stylesheet_directory() . '/flexible-refunds/'));
        $resolver->appendResolver(new DirResolver(\WP_CONTENT_DIR . 'uploads/wpdesk/flexible-refunds/'));
        $resolver->appendResolver(new DirResolver(self::get_template_path()));
        return new SimplePhpRenderer($resolver);
    }
    protected function get_settings(): PersistentContainer
    {
        return new WordpressOptionsContainer(self::SETTING_PREFIX);
    }
    /**
     * Fire hooks.
     */
    public function hooks()
    {
        $renderer = $this->get_renderer();
        $settings = $this->get_settings();
        $this->add_hookable(new Integration\Assets(self::get_library_url()));
        $this->add_hookable(new SettingsForm());
        $ajax = new Integration\Ajax($settings, $renderer);
        $my_account = new Integration\MyAccount($renderer, $settings, $ajax);
        if (self::is_super()) {
            $this->add_hookable(new Integration\PublicRefundShortcode($renderer, $my_account));
        }
        if ($settings->get_fallback('refund_button', 'no') === 'yes') {
            $this->add_hookable($my_account);
            $this->add_hookable(new Integration\AdminMenu());
            $this->add_hookable(new Integration\OrderMetaBox($renderer, $settings));
            $this->add_hookable(new Integration\OrderNote());
            $this->add_hookable(new Emails\RegisterEmails());
        }
        $this->add_hookable(new Integration\RegisterOrderStatus());
        $this->add_hookable($ajax);
        $this->hooks_on_hookable_objects();
    }
}
