<?php

namespace FRFreeVendor\WPDesk\Forms\Field;

use FRFreeVendor\WPDesk\Forms\Sanitizer;
use FRFreeVendor\WPDesk\Forms\Sanitizer\EmailSanitizer;
class InputEmailField extends BasicField
{
    public function get_type(): string
    {
        return 'email';
    }
    public function get_sanitizer(): Sanitizer
    {
        return new EmailSanitizer();
    }
    public function get_template_name(): string
    {
        return 'input-text';
    }
}
