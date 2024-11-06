<?php

namespace FRFreeVendor\WPDesk\Forms\Sanitizer;

use FRFreeVendor\WPDesk\Forms\Sanitizer;
class CallableSanitizer implements Sanitizer
{
    /** @var callable */
    private $callable;
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }
    public function sanitize($value): string
    {
        return call_user_func($this->callable, $value);
    }
}
