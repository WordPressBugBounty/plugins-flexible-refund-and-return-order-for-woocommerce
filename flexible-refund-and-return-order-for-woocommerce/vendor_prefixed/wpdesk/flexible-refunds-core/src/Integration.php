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
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\OrderReferenceResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\SettingsForm;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database\DatabaseManager;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database\TableNames;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration\LegacyRequestMigrator;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration\Version_2026072001_CreateRequestTables;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration\Version_2026072002_SeedSystemForms;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration\Version_2026072003_ScheduleLegacyRequests;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration\Version_2026080401_EnsureSystemForms;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\WpdbFormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\WpdbRequestRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\FormService;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\FormAvailability;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\RequestService;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\RequestWorkflow;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails\RequestEmailSender;
use FRFreeVendor\WPDesk\Migrations\WpdbMigrator;
/**
 * Main class for integrate library with plugin.
 *
 * @package WPDesk\Library\CustomPrice
 */
class Integration implements Hookable
{
    const SETTING_PREFIX = 'fr_refund_';
    use HookableParent;
    protected Renderer $renderer;
    private LoggerInterface $logger;
    private static bool $is_super = \true;
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
        global $wpdb;
        $renderer = $this->get_renderer();
        $settings = $this->get_settings();
        $tables = new TableNames($wpdb->prefix);
        $forms = new WpdbFormRepository($wpdb, $tables);
        $requests = new WpdbRequestRepository($wpdb, $tables);
        $request_service = new RequestService($requests);
        $email_sender = new RequestEmailSender();
        $workflow = new RequestWorkflow($requests, $request_service, $email_sender, new Integration\OrderNote());
        $migrator = new LegacyRequestMigrator($forms, $requests);
        $this->add_hookable(new DatabaseManager(WpdbMigrator::from_classes([Version_2026072001_CreateRequestTables::class, Version_2026072002_SeedSystemForms::class, Version_2026072003_ScheduleLegacyRequests::class, Version_2026080401_EnsureSystemForms::class], 'fr_refunds_migration_version'), $migrator));
        $this->add_hookable(new Integration\Assets(self::get_library_url()));
        $this->add_hookable(new SettingsForm($forms, new FormService($forms), self::is_super()));
        $ajax = new Integration\Ajax($settings, $renderer, $requests, $workflow);
        $order_reference_lookup = new OrderReferenceResolver($settings);
        $my_account = new Integration\MyAccount($renderer, $settings, $order_reference_lookup, $forms, $requests, $request_service, new FormAvailability(self::is_super()), $email_sender, $workflow);
        $this->add_hookable($my_account);
        $this->add_hookable(new Integration\PublicRefundShortcode($renderer, $my_account, $order_reference_lookup, $settings, self::is_super()));
        $this->add_hookable(new Integration\AdminMenu());
        $this->add_hookable(new Integration\OrderMetaBox($renderer, $settings, $requests));
        $this->add_hookable(new Integration\OrderRequestColumn($requests));
        $this->add_hookable(new Integration\OrderNote());
        $this->add_hookable(new Emails\RegisterEmails());
        $this->add_hookable(new Integration\RegisterOrderStatus());
        $this->add_hookable($ajax);
        $this->hooks_on_hookable_objects();
    }
}
