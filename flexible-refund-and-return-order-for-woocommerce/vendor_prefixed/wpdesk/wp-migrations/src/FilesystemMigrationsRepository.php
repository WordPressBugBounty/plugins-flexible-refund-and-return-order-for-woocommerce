<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations;

use FRFreeVendor\WPDesk\Migrations\Finder\MigrationFinder;
use FRFreeVendor\WPDesk\Migrations\Version\Comparator;
use FRFreeVendor\WPDesk\Migrations\Version\MigrationFactory;
final class FilesystemMigrationsRepository extends AbstractMigrationsRepository
{
    /** @var bool */
    private $migrations_loaded = \false;
    /** @var MigrationFinder */
    private $migration_finder;
    /**
     * @param string[]  $migration_directories
     */
    public function __construct(array $migration_directories, MigrationFinder $migration_finder, MigrationFactory $version_factory, Comparator $comparator)
    {
        parent::__construct($migration_directories, $version_factory, $comparator);
        $this->migration_finder = $migration_finder;
    }
    /** @param class-string<AbstractMigration>[] $migrations */
    private function register_migrations(array $migrations): void
    {
        foreach ($migrations as $migration) {
            $this->register_migration($migration);
        }
    }
    protected function load_migrations(): void
    {
        if ($this->migrations_loaded) {
            return;
        }
        $this->migrations_loaded = \true;
        foreach ($this->migrations_source as $directory) {
            $migrations = $this->migration_finder->find_migrations($directory);
            $this->register_migrations($migrations);
        }
    }
}
