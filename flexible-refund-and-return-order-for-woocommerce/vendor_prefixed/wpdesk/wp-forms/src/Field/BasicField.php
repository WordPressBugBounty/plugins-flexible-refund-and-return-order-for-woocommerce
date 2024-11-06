<?php

namespace FRFreeVendor\WPDesk\Forms\Field;

use BadMethodCallException;
use FRFreeVendor\WPDesk\Forms\Field;
use FRFreeVendor\WPDesk\Forms\Sanitizer;
use FRFreeVendor\WPDesk\Forms\Sanitizer\NoSanitize;
use FRFreeVendor\WPDesk\Forms\Serializer;
use FRFreeVendor\WPDesk\Forms\Validator;
use FRFreeVendor\WPDesk\Forms\Validator\ChainValidator;
use FRFreeVendor\WPDesk\Forms\Validator\RequiredValidator;
/**
 * Base class for fields. Is responsible for settings all required field values and provides standard implementation for
 * the field interface.
 */
abstract class BasicField implements Field
{
    use Field\Traits\HtmlAttributes;
    const DEFAULT_PRIORITY = 10;
    /** @var array{default_value: string, possible_values?: string[], sublabel?: string, priority: int, label: string, description: string, description_tip: string, data: array<string|int>} */
    protected $meta = ['priority' => self::DEFAULT_PRIORITY, 'default_value' => '', 'label' => '', 'description' => '', 'description_tip' => '', 'data' => [], 'type' => 'text'];
    public function should_override_form_template(): bool
    {
        return \false;
    }
    public function get_type(): string
    {
        return $this->meta['type'];
    }
    public function set_type(string $type): self
    {
        $this->meta['type'] = $type;
        return $this;
    }
    public function get_validator(): Validator
    {
        $chain = new ChainValidator();
        if ($this->is_required()) {
            $chain->attach(new RequiredValidator());
        }
        return $chain;
    }
    public function get_sanitizer(): Sanitizer
    {
        return new NoSanitize();
    }
    public function has_serializer(): bool
    {
        return \false;
    }
    public function get_serializer(): Serializer
    {
        throw new BadMethodCallException('You must define your serializer in a child class.');
    }
    final public function get_name(): string
    {
        return $this->attributes['name'] ?? '';
    }
    final public function get_label(): string
    {
        return $this->meta['label'] ?? '';
    }
    final public function set_label(string $value): self
    {
        $this->meta['label'] = $value;
        return $this;
    }
    final public function get_description_tip(): string
    {
        return $this->meta['description_tip'] ?? '';
    }
    final public function has_description_tip(): bool
    {
        return !empty($this->meta['description_tip']);
    }
    final public function get_description(): string
    {
        return $this->meta['description'] ?? '';
    }
    final public function has_label(): bool
    {
        return !empty($this->meta['label']);
    }
    final public function has_description(): bool
    {
        return !empty($this->meta['description']);
    }
    final public function set_description(string $value): self
    {
        $this->meta['description'] = $value;
        return $this;
    }
    final public function set_description_tip(string $value): self
    {
        $this->meta['description_tip'] = $value;
        return $this;
    }
    final public function set_placeholder(string $value): self
    {
        $this->attributes['placeholder'] = $value;
        return $this;
    }
    final public function has_placeholder(): bool
    {
        return !empty($this->attributes['placeholder']);
    }
    final public function get_placeholder(): string
    {
        return $this->attributes['placeholder'] ?? '';
    }
    final public function set_name(string $name): self
    {
        $this->attributes['name'] = $name;
        return $this;
    }
    final public function get_meta_value(string $name)
    {
        return $this->meta[$name] ?? '';
    }
    final public function get_classes(): string
    {
        return implode(' ', $this->attributes['class'] ?? []);
    }
    final public function has_classes(): bool
    {
        return !empty($this->attributes['class']);
    }
    final public function has_data(): bool
    {
        return !empty($this->meta['data']);
    }
    final public function get_data(): array
    {
        return $this->meta['data'] ?? [];
    }
    final public function get_possible_values()
    {
        return !empty($this->meta['possible_values']) ? $this->meta['possible_values'] : [];
    }
    final public function get_id(): string
    {
        return $this->attributes['id'] ?? sanitize_title($this->get_name());
    }
    final public function is_multiple(): bool
    {
        return isset($this->attributes['multiple']);
    }
    final public function set_disabled(): self
    {
        $this->attributes['disabled'] = 'disabled';
        return $this;
    }
    final public function is_disabled(): bool
    {
        return $this->attributes['disabled'] ?? \false;
    }
    final public function set_readonly(): self
    {
        $this->attributes['readonly'] = 'readonly';
        return $this;
    }
    final public function is_readonly(): bool
    {
        return $this->attributes['readonly'] ?? \false;
    }
    final public function set_required(): self
    {
        $this->attributes['required'] = 'required';
        return $this;
    }
    final public function add_class(string $class_name): self
    {
        $this->attributes['class'][$class_name] = $class_name;
        return $this;
    }
    final public function unset_class(string $class_name): self
    {
        unset($this->attributes['class'][$class_name]);
        return $this;
    }
    final public function add_data(string $data_name, string $data_value): Field
    {
        if (empty($this->meta['data'])) {
            $this->meta['data'] = [];
        }
        $this->meta['data'][$data_name] = $data_value;
        return $this;
    }
    final public function unset_data(string $data_name): Field
    {
        unset($this->meta['data'][$data_name]);
        return $this;
    }
    final public function is_meta_value_set(string $name): bool
    {
        return !empty($this->meta[$name]);
    }
    final public function is_class_set(string $name): bool
    {
        return !empty($this->attributes['class'][$name]);
    }
    final public function get_default_value(): string
    {
        return $this->meta['default_value'] ?? '';
    }
    final public function set_default_value(string $value): self
    {
        $this->meta['default_value'] = $value;
        return $this;
    }
    final public function is_required(): bool
    {
        return isset($this->attributes['required']);
    }
    final public function get_priority(): int
    {
        return $this->meta['priority'];
    }
    /**
     * Fields are sorted by lowest priority value first, when getting FormWithFields
     *
     * @see FormWithFields::get_fields()
     */
    final public function set_priority(int $priority): self
    {
        $this->meta['priority'] = $priority;
        return $this;
    }
}
