<?php

namespace FRFreeVendor\WPDesk\Forms\Sanitizer;

use FRFreeVendor\WPDesk\Forms\Sanitizer;
class NoSanitize implements Sanitizer
{
    public function sanitize($value)
    {
        return $value;
    }
}
