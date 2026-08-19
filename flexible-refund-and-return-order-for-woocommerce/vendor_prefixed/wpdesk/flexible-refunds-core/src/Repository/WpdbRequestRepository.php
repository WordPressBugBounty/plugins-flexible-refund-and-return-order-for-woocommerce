<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository;

use RuntimeException;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database\TableNames;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Exception\ActiveRequestExists;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
final class WpdbRequestRepository implements RequestRepository
{
    private object $wpdb;
    private string $table;
    public function __construct(object $wpdb, TableNames $tables)
    {
        $this->wpdb = $wpdb;
        $this->table = $tables->requests();
    }
    public function find(int $id): ?RequestRecord
    {
        return $this->find_one_by('id', $id);
    }
    public function find_active_by_order(int $order_id): ?RequestRecord
    {
        return $this->find_one_by('active_order_id', $order_id);
    }
    public function find_latest_by_order(int $order_id): ?RequestRecord
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE order_id = %d ORDER BY created_at DESC, id DESC LIMIT 1", $order_id);
        $row = $this->wpdb->get_row(
            $sql,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            \ARRAY_A
        );
        return is_array($row) ? $this->hydrate($row) : null;
    }
    public function find_by_legacy_order(int $order_id): ?RequestRecord
    {
        return $this->find_one_by('legacy_order_id', $order_id);
    }
    public function find_by_order(int $order_id): array
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE order_id = %d ORDER BY created_at DESC, id DESC", $order_id);
        $rows = $this->wpdb->get_results(
            $sql,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            \ARRAY_A
        );
        return array_map([$this, 'hydrate'], is_array($rows) ? $rows : []);
    }
    public function add(RequestRecord $request): RequestRecord
    {
        if (null !== $request->get_id()) {
            throw new RuntimeException('Cannot add a request which already has an ID.');
        }
        $this->assert_no_other_active_request($request);
        $result = $this->wpdb->insert($this->table, $this->to_row($request));
        if (\false === $result) {
            $this->assert_no_other_active_request($request);
            throw new RuntimeException('Could not create a request: ' . $this->wpdb->last_error);
        }
        return $request->with_id((int) $this->wpdb->insert_id);
    }
    public function save(RequestRecord $request): RequestRecord
    {
        if (null === $request->get_id()) {
            throw new RuntimeException('Cannot update a request without an ID.');
        }
        $this->assert_no_other_active_request($request);
        $result = $this->wpdb->update($this->table, $this->to_row($request), ['id' => $request->get_id()]);
        if (\false === $result) {
            $this->assert_no_other_active_request($request);
            throw new RuntimeException('Could not update a request: ' . $this->wpdb->last_error);
        }
        return $request;
    }
    private function assert_no_other_active_request(RequestRecord $request): void
    {
        if (!$request->is_active()) {
            return;
        }
        $active = $this->find_active_by_order($request->get_order_id());
        if (null !== $active && $active->get_id() !== $request->get_id()) {
            throw ActiveRequestExists::for_order($request->get_order_id());
        }
    }
    private function find_one_by(string $column, int $value): ?RequestRecord
    {
        $allowed_columns = ['id', 'active_order_id', 'legacy_order_id'];
        if (!in_array($column, $allowed_columns, \true)) {
            throw new RuntimeException('Unsupported request lookup column.');
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE {$column} = %d", $value);
        $row = $this->wpdb->get_row(
            $sql,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            \ARRAY_A
        );
        return is_array($row) ? $this->hydrate($row) : null;
    }
    private function to_row(RequestRecord $request): array
    {
        return ['order_id' => $request->get_order_id(), 'active_order_id' => $request->is_active() ? $request->get_order_id() : null, 'form_id' => $request->get_form_id(), 'form_version' => $request->get_form_version(), 'request_type' => $request->get_request_type(), 'status' => $request->get_status(), 'previous_order_status' => $request->get_previous_order_status(), 'form_snapshot' => $this->encode_json($request->get_form_snapshot()), 'submitted_values' => $this->encode_json($request->get_submitted_values()), 'note' => $request->get_note(), 'legacy_order_id' => $request->get_legacy_order_id(), 'created_at' => $request->get_created_at(), 'updated_at' => $request->get_updated_at()];
    }
    public function hydrate(array $row): RequestRecord
    {
        return new RequestRecord((int) $row['id'], (int) $row['order_id'], (int) $row['form_id'], (int) $row['form_version'], (string) $row['request_type'], (string) $row['status'], (string) $row['previous_order_status'], $this->decode_json((string) $row['form_snapshot']), $this->decode_json((string) $row['submitted_values']), (string) $row['note'], null === $row['legacy_order_id'] ? null : (int) $row['legacy_order_id'], (string) $row['created_at'], (string) $row['updated_at']);
    }
    private function encode_json(array $value): string
    {
        $json = function_exists('wp_json_encode') ? wp_json_encode($value) : json_encode($value);
        if (\false === $json) {
            throw new RuntimeException('Could not encode request data as JSON.');
        }
        return $json;
    }
    private function decode_json(string $value): array
    {
        $decoded = json_decode($value, \true);
        return is_array($decoded) ? $decoded : [];
    }
}
