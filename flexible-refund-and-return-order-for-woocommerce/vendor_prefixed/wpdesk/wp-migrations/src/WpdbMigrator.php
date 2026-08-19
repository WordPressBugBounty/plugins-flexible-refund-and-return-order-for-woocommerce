<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations;

use FRFreeVendor\Psr\Log\LoggerInterface;
use FRFreeVendor\WPDesk\Migrations\Finder\GlobFinder;
use FRFreeVendor\WPDesk\Migrations\Version\AlphabeticalComparator;
use FRFreeVendor\WPDesk\Migrations\Version\Comparator;
use FRFreeVendor\WPDesk\Migrations\Version\Version;
use FRFreeVendor\WPDesk\Migrations\Version\WpdbMigrationFactory;
use FRFreeVendor\WPDesk\Mutex\Mutex;
use FRFreeVendor\WPDesk\Mutex\WordpressPostMutex;
class WpdbMigrator implements Migrator
{
    private const MUTEX_POST_ID = 1;
    private const MUTEX_TIMEOUT = 3600;
    private const MUTEX_WAIT_FOR_LOCK_TIMEOUT = 5;
    /** @var \wpdb */
    private $wpdb;
    /** @var MigrationsRepository */
    private $migrations_repository;
    /** @var Comparator */
    private $comparator;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $option_name;
    /** @var Mutex */
    private $mutex;
    /** @var MigrationErrorNotice */
    private $migration_error_notice;
    /** @param string[] $migration_directories */
    public static function from_directories(array $migration_directories, string $option_name): self
    {
        global $wpdb;
        $logger = new WpdbLogger($option_name . '_log');
        return new self($wpdb, $option_name, new FilesystemMigrationsRepository($migration_directories, new GlobFinder(), new WpdbMigrationFactory($wpdb, $logger), new AlphabeticalComparator()), new AlphabeticalComparator(), $logger, self::create_mutex($option_name), new MigrationErrorNotice($option_name));
    }
    /** @param class-string<AbstractMigration>[] $migration_class_names */
    public static function from_classes(array $migration_class_names, string $option_name): self
    {
        global $wpdb;
        $logger = new WpdbLogger($option_name . '_log');
        return new self($wpdb, $option_name, new ArrayMigrationsRepository($migration_class_names, new WpdbMigrationFactory($wpdb, $logger), new AlphabeticalComparator()), new AlphabeticalComparator(), $logger, self::create_mutex($option_name), new MigrationErrorNotice($option_name));
    }
    private static function create_mutex(string $option_name): Mutex
    {
        return new WordpressPostMutex(self::MUTEX_POST_ID, self::get_mutex_name($option_name), self::MUTEX_TIMEOUT, self::MUTEX_WAIT_FOR_LOCK_TIMEOUT);
    }
    private static function get_mutex_name(string $option_name): string
    {
        return 'wpdesk_migration_' . md5($option_name);
    }
    public function __construct(\wpdb $wpdb, string $option_name, MigrationsRepository $migrations_repository, Comparator $comparator, LoggerInterface $logger, ?Mutex $mutex = null, ?MigrationErrorNotice $migration_error_notice = null)
    {
        $this->wpdb = $wpdb;
        $this->option_name = $option_name;
        $this->migrations_repository = $migrations_repository;
        $this->comparator = $comparator;
        $this->logger = $logger;
        $this->mutex = $mutex ?? self::create_mutex($option_name);
        $this->migration_error_notice = $migration_error_notice ?? new MigrationErrorNotice($option_name);
    }
    private function get_current_version(): Version
    {
        return new Version(get_option($this->option_name, ''));
    }
    private function needs_migration(): bool
    {
        $migrations = $this->migrations_repository->get_migrations();
        $last_migration = end($migrations);
        if ($last_migration === \false) {
            return \false;
        }
        if ($this->comparator->compare($last_migration->get_version(), $this->get_current_version()) > 0) {
            return \true;
        }
        return \false;
    }
    public function migrate(): void
    {
        require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
        $mutex = $this->mutex;
        if (!$mutex->acquireLock()) {
            $this->logger->warning('DB update skipped because another migration process is running');
            $this->migration_error_notice->display();
            return;
        }
        try {
            if (!$this->needs_migration()) {
                $this->migration_error_notice->clear();
                return;
            }
            $this->logger->info('DB update start');
            try {
                $this->do_migrate();
                $this->migration_error_notice->clear();
            } catch (\Throwable $e) {
                $error_msg = $this->get_error_message($e);
                $this->migration_error_notice->persist($error_msg);
                $this->migration_error_notice->display();
                $this->logger->error($error_msg);
                trigger_error(esc_html($error_msg), \E_USER_WARNING);
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
            }
            $this->logger->info('DB update finished');
        } finally {
            $mutex->releaseLock();
        }
    }
    private function get_error_message(\Throwable $e): string
    {
        $reason = $this->get_last_database_error();
        if ($reason === '') {
            $reason = $e->getMessage();
        }
        if ($reason === '') {
            $reason = get_class($e);
        }
        return sprintf('Error while upgrading a database: %s', $reason);
    }
    private function get_last_database_error(): string
    {
        $wpdb_properties = get_object_vars($this->wpdb);
        return isset($wpdb_properties['last_error']) && is_string($wpdb_properties['last_error']) ? $wpdb_properties['last_error'] : '';
    }
    private function update_current_version(Version $version): void
    {
        $version_string = (string) $version;
        if (update_option($this->option_name, $version_string, \true) || get_option($this->option_name, '') === $version_string) {
            return;
        }
        throw new \RuntimeException(sprintf('Could not persist database migration version "%s".', $version_string));
    }
    private function do_migrate(): void
    {
        $current_version = $this->get_current_version();
        foreach ($this->migrations_repository->get_migrations() as $migration) {
            if ($this->comparator->compare($migration->get_version(), $this->get_current_version()) > 0) {
                $this->logger->info(sprintf('DB update %s:%s', $current_version, $migration->get_version()));
                $success = null;
                if ($migration->get_migration()->is_needed()) {
                    $success = $migration->get_migration()->up();
                } else {
                    $success = \true;
                }
                if ($success) {
                    $this->logger->info(sprintf('DB update %s:%s -> ', $current_version, $migration->get_version()) . 'OK');
                    $this->update_current_version($migration->get_version());
                } else {
                    throw new \RuntimeException();
                }
            }
        }
    }
}
