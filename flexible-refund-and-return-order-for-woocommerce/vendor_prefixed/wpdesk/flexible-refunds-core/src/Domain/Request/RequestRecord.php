<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request;

use InvalidArgumentException;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
final class RequestRecord
{
    private ?int $id;
    private int $order_id;
    private int $form_id;
    private int $form_version;
    private string $request_type;
    private string $status;
    private string $previous_order_status;
    private array $form_snapshot;
    private array $submitted_values;
    private string $note;
    private ?int $legacy_order_id;
    private string $created_at;
    private string $updated_at;
    public function __construct(?int $id, int $order_id, int $form_id, int $form_version, string $request_type, string $status, string $previous_order_status, array $form_snapshot, array $submitted_values, string $note, ?int $legacy_order_id, string $created_at, string $updated_at)
    {
        RequestType::assert_valid($request_type);
        RequestStatus::assert_valid($status);
        if ($order_id < 1 || $form_id < 1 || $form_version < 1) {
            throw new InvalidArgumentException('Request order, form, and form version must be positive integers.');
        }
        $this->id = $id;
        $this->order_id = $order_id;
        $this->form_id = $form_id;
        $this->form_version = $form_version;
        $this->request_type = $request_type;
        $this->status = $status;
        $this->previous_order_status = $previous_order_status;
        $this->form_snapshot = $form_snapshot;
        $this->submitted_values = $submitted_values;
        $this->note = $note;
        $this->legacy_order_id = $legacy_order_id;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }
    public function get_id(): ?int
    {
        return $this->id;
    }
    public function get_order_id(): int
    {
        return $this->order_id;
    }
    public function get_form_id(): int
    {
        return $this->form_id;
    }
    public function get_form_version(): int
    {
        return $this->form_version;
    }
    public function get_request_type(): string
    {
        return $this->request_type;
    }
    public function get_status(): string
    {
        return $this->status;
    }
    public function get_previous_order_status(): string
    {
        return $this->previous_order_status;
    }
    public function get_form_snapshot(): array
    {
        return $this->form_snapshot;
    }
    public function get_submitted_values(): array
    {
        return $this->submitted_values;
    }
    public function get_note(): string
    {
        return $this->note;
    }
    public function get_legacy_order_id(): ?int
    {
        return $this->legacy_order_id;
    }
    public function get_created_at(): string
    {
        return $this->created_at;
    }
    public function get_updated_at(): string
    {
        return $this->updated_at;
    }
    public function is_active(): bool
    {
        return RequestStatus::is_active($this->status);
    }
    public function with_id(int $id): self
    {
        return new self($id, $this->order_id, $this->form_id, $this->form_version, $this->request_type, $this->status, $this->previous_order_status, $this->form_snapshot, $this->submitted_values, $this->note, $this->legacy_order_id, $this->created_at, $this->updated_at);
    }
    public function with_status(string $status, string $note, string $updated_at): self
    {
        return new self($this->id, $this->order_id, $this->form_id, $this->form_version, $this->request_type, $status, $this->previous_order_status, $this->form_snapshot, $this->submitted_values, $note, $this->legacy_order_id, $this->created_at, $updated_at);
    }
}
