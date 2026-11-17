<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Mutex;

interface Mutex
{
    public function acquireLock(): bool;
    public function releaseLock(): void;
}
