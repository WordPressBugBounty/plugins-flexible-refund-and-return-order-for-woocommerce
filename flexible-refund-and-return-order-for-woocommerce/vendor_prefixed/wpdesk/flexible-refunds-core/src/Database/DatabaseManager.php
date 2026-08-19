<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration\LegacyRequestMigrator;
use FRFreeVendor\WPDesk\Migrations\Migrator;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
final class DatabaseManager implements Hookable
{
    private Migrator $migrator;
    private LegacyRequestMigrator $legacy_request_migrator;
    public function __construct(Migrator $migrator, LegacyRequestMigrator $legacy_request_migrator)
    {
        $this->migrator = $migrator;
        $this->legacy_request_migrator = $legacy_request_migrator;
    }
    public function hooks(): void
    {
        add_action('init', [$this, 'initialize'], 5);
        $this->legacy_request_migrator->hooks();
    }
    /**
     * Public so a host plugin can also call it from its activation hook.
     */
    public function initialize(): void
    {
        $this->migrator->migrate();
        $this->legacy_request_migrator->maybe_schedule();
    }
}
