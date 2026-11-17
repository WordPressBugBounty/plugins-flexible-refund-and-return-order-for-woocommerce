<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner\Command;

use FRFreeVendor\Symfony\Component\Console\Command\Command;
use FRFreeVendor\Symfony\Component\Console\Input\InputInterface;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
use FRFreeVendor\WPDesk\Codeception\Runner\DockerCompose;
use FRFreeVendor\WPDesk\Codeception\Runner\ProjectConfigReader;
use FRFreeVendor\WPDesk\Codeception\Runner\ProjectPaths;
use FRFreeVendor\WPDesk\Codeception\Runner\RuntimeEnvironment;
use FRFreeVendor\WPDesk\Codeception\Runner\RuntimeMode;
use FRFreeVendor\WPDesk\Codeception\Runner\WordPressPreparer;
final class PrepareCommand extends Command
{
    public function __construct(private readonly ProjectPaths $paths, private readonly DockerCompose $dockerCompose, private readonly ProjectConfigReader $configReader, private readonly WordPressPreparer $wordpressPreparer, private readonly RuntimeMode $runtimeMode)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('prepare')->setDescription('Create the Codeception database dump from the project setup.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->runtimeMode->isDirect()) {
            $upExitCode = $this->dockerCompose->up($output, ['runner', 'wordpress', 'mysqltests']);
            if ($upExitCode !== self::SUCCESS) {
                return $upExitCode;
            }
            return $this->dockerCompose->execRunner(['prepare'], $output);
        }
        $config = $this->configReader->read($this->paths);
        $environment = RuntimeEnvironment::fromGlobals($this->runtimeMode, $this->paths);
        $this->wordpressPreparer->prepareDatabase($this->paths, $config, $environment, $output);
        return self::SUCCESS;
    }
}
