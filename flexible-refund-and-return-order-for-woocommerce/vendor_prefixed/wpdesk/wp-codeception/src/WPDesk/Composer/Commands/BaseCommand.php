<?php

namespace FRFreeVendor\WPDesk\Composer\Codeception\Commands;

use FRFreeVendor\Composer\Command\BaseCommand as CodeceptionBaseCommand;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
/**
 * Base for commands - declares common methods.
 *
 * @package WPDesk\Composer\Codeception\Commands
 */
abstract class BaseCommand extends CodeceptionBaseCommand
{
    /**
     * @param string $command
     * @param OutputInterface $output
     */
    protected function execAndOutput($command, OutputInterface $output)
    {
        passthru($command);
    }
}
