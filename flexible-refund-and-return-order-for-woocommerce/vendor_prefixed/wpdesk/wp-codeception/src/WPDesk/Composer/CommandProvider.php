<?php

namespace FRFreeVendor\WPDesk\Composer\Codeception;

use FRFreeVendor\WPDesk\Composer\Codeception\Commands\CreateCodeceptionTests;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareCodeceptionDb;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTests;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTestsWithCoverage;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareParallelCodeceptionTests;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\PrepareWordpressForCodeception;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\RunCodeceptionTests;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTests;
use FRFreeVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTestsWithCoverage;
/**
 * Links plugin commands handlers to composer.
 */
class CommandProvider implements \FRFreeVendor\Composer\Plugin\Capability\CommandProvider
{
    public function getCommands()
    {
        return [new CreateCodeceptionTests(), new RunCodeceptionTests(), new RunLocalCodeceptionTests(), new RunLocalCodeceptionTestsWithCoverage(), new PrepareCodeceptionDb(), new PrepareWordpressForCodeception(), new PrepareLocalCodeceptionTests(), new PrepareLocalCodeceptionTestsWithCoverage(), new PrepareParallelCodeceptionTests()];
    }
}
