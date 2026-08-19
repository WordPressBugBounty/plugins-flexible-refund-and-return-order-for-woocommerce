<?php

namespace FRFreeVendor\WPDesk\Migrations;

interface MigrationsRepository
{
    /** @return iterable<AvailableMigration> */
    public function get_migrations(): iterable;
    /** @param class-string<AbstractMigration> $migration_class_name */
    public function register_migration(string $migration_class_name): void;
}
