<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations\Finder;

use FRFreeVendor\WPDesk\Migrations\AbstractMigration;
interface MigrationFinder
{
    /**
     * @param string $directory
     * @return class-string<AbstractMigration>[]
     */
    public function find_migrations(string $directory): array;
}
