<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Composer;

use FRFreeVendor\Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use FRFreeVendor\WPDesk\Codeception\Composer\Command\LegacyCommand;
final class CommandProvider implements CommandProviderCapability
{
    /**
     * @return list<LegacyCommand>
     */
    public function getCommands()
    {
        return [LegacyCommand::createInitCommand(), LegacyCommand::createDockerRunCommand(), LegacyCommand::createLocalRunCommand(), LegacyCommand::createLocalCoverageRunCommand(), LegacyCommand::createNoopCommand('prepare-codeception-db', 'Prepare codeception database.'), LegacyCommand::createNoopCommand('prepare-wordpress-for-codeception', 'Prepare wordpress installation for codeception tests.'), LegacyCommand::createNoopCommand('prepare-local-codeception-tests', 'Prepare local codeception tests.', ['prepare-local-codeception-test']), LegacyCommand::createNoopCommand('prepare-local-codeception-tests-with-coverage', 'Prepare local codeception tests with coverage.', ['prepare-local-codeception-test-with-coverage']), LegacyCommand::createNoopCommand('prepare-parallel-codeception-tests', 'Prepare parallel codeception tests.')];
    }
}
