<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations;

use FRFreeVendor\Psr\Log\LoggerInterface;
abstract class AbstractMigration
{
    /** @var \wpdb */
    protected $wpdb;
    /** @var LoggerInterface */
    protected $logger;
    public function __construct(\wpdb $wpdb, LoggerInterface $logger)
    {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
    }
    abstract public function up(): bool;
    /**
     * Allow to skip migration if it is not needed. Tracking of migration version just by wp_options
     * value may be subject to random issues, so as a backup, this method can be used to avoid
     * errornous migrations like creating alredy exising columns. Schema migrations should inspect
     * the actual database state here before altering tables or columns.
     */
    public function is_needed(): bool
    {
        return \true;
    }
}
