<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Database;

final class TableNames
{
    private string $prefix;
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }
    public function forms(): string
    {
        return $this->prefix . 'fr_forms';
    }
    public function requests(): string
    {
        return $this->prefix . 'fr_requests';
    }
}
