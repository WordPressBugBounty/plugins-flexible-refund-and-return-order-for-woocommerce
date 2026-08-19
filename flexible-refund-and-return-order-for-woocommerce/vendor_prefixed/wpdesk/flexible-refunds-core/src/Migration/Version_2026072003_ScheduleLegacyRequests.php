<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration;

use RuntimeException;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database\TableNames;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\WpdbFormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\WpdbRequestRepository;
use FRFreeVendor\WPDesk\Migrations\AbstractMigration;
final class Version_2026072003_ScheduleLegacyRequests extends AbstractMigration
{
    public function is_needed(): bool
    {
        return !$this->get_migrator()->is_complete();
    }
    public function up(): bool
    {
        if (!$this->get_migrator()->maybe_schedule()) {
            throw new RuntimeException('Could not schedule the legacy request migration.');
        }
        return \true;
    }
    private function get_migrator(): LegacyRequestMigrator
    {
        $tables = new TableNames($this->wpdb->prefix);
        return new LegacyRequestMigrator(new WpdbFormRepository($this->wpdb, $tables), new WpdbRequestRepository($this->wpdb, $tables));
    }
}
