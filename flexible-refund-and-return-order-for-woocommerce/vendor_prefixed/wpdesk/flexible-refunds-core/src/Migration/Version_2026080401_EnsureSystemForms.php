<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration;

use RuntimeException;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database\TableNames;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\WpdbFormRepository;
use FRFreeVendor\WPDesk\Migrations\AbstractMigration;
final class Version_2026080401_EnsureSystemForms extends AbstractMigration
{
    public function is_needed(): bool
    {
        $forms = $this->get_repository();
        foreach (RequestType::all() as $request_type) {
            if (null === $forms->find_by_type($request_type)) {
                return \true;
            }
        }
        return \false;
    }
    public function up(): bool
    {
        $forms = $this->get_repository();
        (new SystemFormsSeeder($forms, new LegacySettingsMapper()))->seed();
        foreach (RequestType::all() as $request_type) {
            if (null === $forms->find_by_type($request_type)) {
                throw new RuntimeException(sprintf('Could not create the %s system form.', $request_type));
            }
        }
        return \true;
    }
    private function get_repository(): WpdbFormRepository
    {
        return new WpdbFormRepository($this->wpdb, new TableNames($this->wpdb->prefix));
    }
}
