<?php

namespace FRFreeVendor\WPDesk\Forms\Sanitizer;

use FRFreeVendor\WPDesk\Forms\Sanitizer;
class EmailSanitizer implements Sanitizer
{
    public function sanitize($value): string
    {
        return sanitize_email($value);
    }
}
