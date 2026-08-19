<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database\TableNames;
use FRFreeVendor\WPDesk\Migrations\AbstractMigration;
final class Version_2026072001_CreateRequestTables extends AbstractMigration
{
    public function is_needed(): bool
    {
        $tables = new TableNames($this->wpdb->prefix);
        return !$this->table_exists($tables->forms()) || !$this->table_exists($tables->requests());
    }
    public function up(): bool
    {
        $tables = new TableNames($this->wpdb->prefix);
        $charset_collate = $this->wpdb->get_charset_collate();
        $forms_table = $tables->forms();
        $requests_table = $tables->requests();
        $forms_sql = "CREATE TABLE {$forms_table} (\n\t\t\tid bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n\t\t\trequest_type varchar(32) NOT NULL,\n\t\t\tenabled tinyint(1) unsigned NOT NULL DEFAULT 0,\n\t\t\tbutton_label varchar(191) NOT NULL,\n\t\t\tversion int(10) unsigned NOT NULL DEFAULT 1,\n\t\t\tschema_json longtext NOT NULL,\n\t\t\tsettings_json longtext NOT NULL,\n\t\t\tcreated_at datetime NOT NULL,\n\t\t\tupdated_at datetime NOT NULL,\n\t\t\tPRIMARY KEY  (id),\n\t\t\tUNIQUE KEY request_type (request_type)\n\t\t) {$charset_collate};";
        // MySQL permits multiple NULL values here, but only one active row per order ID.
        $requests_sql = "CREATE TABLE {$requests_table} (\n\t\t\tid bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n\t\t\torder_id bigint(20) unsigned NOT NULL,\n\t\t\tactive_order_id bigint(20) unsigned NULL,\n\t\t\tform_id bigint(20) unsigned NOT NULL,\n\t\t\tform_version int(10) unsigned NOT NULL,\n\t\t\trequest_type varchar(32) NOT NULL,\n\t\t\tstatus varchar(32) NOT NULL,\n\t\t\tprevious_order_status varchar(32) NOT NULL DEFAULT '',\n\t\t\tform_snapshot longtext NOT NULL,\n\t\t\tsubmitted_values longtext NOT NULL,\n\t\t\tnote longtext NULL,\n\t\t\tlegacy_order_id bigint(20) unsigned NULL,\n\t\t\tcreated_at datetime NOT NULL,\n\t\t\tupdated_at datetime NOT NULL,\n\t\t\tPRIMARY KEY  (id),\n\t\t\tUNIQUE KEY active_order_id (active_order_id),\n\t\t\tUNIQUE KEY legacy_order_id (legacy_order_id),\n\t\t\tKEY order_id (order_id),\n\t\t\tKEY form_id (form_id),\n\t\t\tKEY request_type (request_type),\n\t\t\tKEY status (status)\n\t\t) {$charset_collate};";
        if (!$this->table_exists($forms_table) && \false === $this->wpdb->query($forms_sql)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
            return \false;
        }
        if (!$this->table_exists($requests_table) && \false === $this->wpdb->query($requests_sql)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
            return \false;
        }
        return \true;
    }
    private function table_exists(string $table): bool
    {
        $sql = $this->wpdb->prepare('SHOW TABLES LIKE %s', $this->wpdb->esc_like($table));
        return $table === $this->wpdb->get_var($sql);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
}
