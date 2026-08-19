<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations;

class ArrayMigrationsRepository extends AbstractMigrationsRepository
{
    protected function load_migrations(): void
    {
        /** @var class-string<AbstractMigration> $class */
        foreach ($this->migrations_source as $class) {
            $this->register_migration($class);
        }
    }
}
