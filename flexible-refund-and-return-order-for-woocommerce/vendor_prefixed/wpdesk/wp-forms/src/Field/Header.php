<?php

namespace FRFreeVendor\WPDesk\Forms\Field;

use FRFreeVendor\WPDesk\Forms\Field;
class Header extends NoValueField
{
    public function __construct()
    {
        parent::__construct();
        $this->meta['header_size'] = '';
    }
    public function get_template_name(): string
    {
        return 'header';
    }
    public function should_override_form_template(): bool
    {
        return \true;
    }
    public function set_header_size(int $value): self
    {
        $this->meta['header_size'] = $value;
        return $this;
    }
}
