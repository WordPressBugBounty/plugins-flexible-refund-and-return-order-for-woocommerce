<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form;

use InvalidArgumentException;
final class FormDefinition
{
    private ?int $id;
    private string $request_type;
    private bool $enabled;
    private string $button_label;
    private int $version;
    private array $schema;
    private array $settings;
    private string $created_at;
    private string $updated_at;
    public function __construct(?int $id, string $request_type, bool $enabled, string $button_label, int $version, array $schema, array $settings, string $created_at, string $updated_at)
    {
        RequestType::assert_valid($request_type);
        if ($version < 1) {
            throw new InvalidArgumentException('Form version must be greater than zero.');
        }
        if ('' === trim($button_label)) {
            throw new InvalidArgumentException('Form button label cannot be empty.');
        }
        $this->id = $id;
        $this->request_type = $request_type;
        $this->enabled = $enabled;
        $this->button_label = $button_label;
        $this->version = $version;
        $this->schema = $schema;
        $this->settings = $settings;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }
    public function get_id(): ?int
    {
        return $this->id;
    }
    public function get_request_type(): string
    {
        return $this->request_type;
    }
    public function is_enabled(): bool
    {
        return $this->enabled;
    }
    public function get_button_label(): string
    {
        return $this->button_label;
    }
    public function get_version(): int
    {
        return $this->version;
    }
    public function get_schema(): array
    {
        return $this->schema;
    }
    public function get_settings(): array
    {
        return $this->settings;
    }
    public function get_created_at(): string
    {
        return $this->created_at;
    }
    public function get_updated_at(): string
    {
        return $this->updated_at;
    }
    public function with_id(int $id): self
    {
        return new self($id, $this->request_type, $this->enabled, $this->button_label, $this->version, $this->schema, $this->settings, $this->created_at, $this->updated_at);
    }
    public function with_configuration(bool $enabled, string $button_label, array $schema, array $settings, string $updated_at): self
    {
        return new self($this->id, $this->request_type, $enabled, $button_label, $this->version + 1, $schema, $settings, $this->created_at, $updated_at);
    }
    public function get_snapshot(): array
    {
        return ['form_id' => $this->id, 'request_type' => $this->request_type, 'form_version' => $this->version, 'button_label' => $this->button_label, 'schema' => $this->schema, 'settings' => $this->settings];
    }
}
