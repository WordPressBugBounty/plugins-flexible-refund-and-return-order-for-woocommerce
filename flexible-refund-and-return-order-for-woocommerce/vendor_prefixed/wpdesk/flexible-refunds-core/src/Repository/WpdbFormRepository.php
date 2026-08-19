<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository;

use RuntimeException;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database\TableNames;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
final class WpdbFormRepository implements FormRepository
{
    private object $wpdb;
    private string $table;
    public function __construct(object $wpdb, TableNames $tables)
    {
        $this->wpdb = $wpdb;
        $this->table = $tables->forms();
    }
    public function find(int $id): ?FormDefinition
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
        $row = $this->wpdb->get_row(
            $sql,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            \ARRAY_A
        );
        return is_array($row) ? $this->hydrate($row) : null;
    }
    public function find_by_type(string $request_type): ?FormDefinition
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE request_type = %s", $request_type);
        $row = $this->wpdb->get_row(
            $sql,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            \ARRAY_A
        );
        return is_array($row) ? $this->hydrate($row) : null;
    }
    public function find_all(): array
    {
        $rows = $this->wpdb->get_results(
            "SELECT * FROM {$this->table} ORDER BY id ASC",
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
            \ARRAY_A
        );
        return array_map([$this, 'hydrate'], is_array($rows) ? $rows : []);
    }
    public function save(FormDefinition $form): FormDefinition
    {
        $data = ['request_type' => $form->get_request_type(), 'enabled' => $form->is_enabled() ? 1 : 0, 'button_label' => $form->get_button_label(), 'version' => $form->get_version(), 'schema_json' => $this->encode_json($form->get_schema()), 'settings_json' => $this->encode_json($form->get_settings()), 'created_at' => $form->get_created_at(), 'updated_at' => $form->get_updated_at()];
        if (null === $form->get_id()) {
            $result = $this->wpdb->insert($this->table, $data);
            if (\false === $result) {
                throw new RuntimeException('Could not create a form definition: ' . $this->wpdb->last_error);
            }
            return $form->with_id((int) $this->wpdb->insert_id);
        }
        $result = $this->wpdb->update($this->table, $data, ['id' => $form->get_id()]);
        if (\false === $result) {
            throw new RuntimeException('Could not update a form definition: ' . $this->wpdb->last_error);
        }
        return $form;
    }
    public function hydrate(array $row): FormDefinition
    {
        return new FormDefinition((int) $row['id'], (string) $row['request_type'], (bool) $row['enabled'], (string) $row['button_label'], (int) $row['version'], $this->decode_json((string) $row['schema_json']), $this->decode_json((string) $row['settings_json']), (string) $row['created_at'], (string) $row['updated_at']);
    }
    private function encode_json(array $value): string
    {
        $json = function_exists('wp_json_encode') ? wp_json_encode($value) : json_encode($value);
        if (\false === $json) {
            throw new RuntimeException('Could not encode form data as JSON.');
        }
        return $json;
    }
    private function decode_json(string $value): array
    {
        $decoded = json_decode($value, \true);
        return is_array($decoded) ? $decoded : [];
    }
}
