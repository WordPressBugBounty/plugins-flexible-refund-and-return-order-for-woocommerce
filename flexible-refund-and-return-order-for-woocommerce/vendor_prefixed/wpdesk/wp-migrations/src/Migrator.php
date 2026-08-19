<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations;

interface Migrator
{
    public function migrate(): void;
}
