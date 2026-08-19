<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner\Command;

use FRFreeVendor\Symfony\Component\Console\Command\Command;
use FRFreeVendor\Symfony\Component\Console\Input\InputArgument;
use FRFreeVendor\Symfony\Component\Console\Input\InputInterface;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
use FRFreeVendor\WPDesk\Codeception\Runner\CodeceptionRunner;
use FRFreeVendor\WPDesk\Codeception\Runner\DockerCompose;
use FRFreeVendor\WPDesk\Codeception\Runner\ProjectConfigReader;
use FRFreeVendor\WPDesk\Codeception\Runner\ProjectPaths;
use FRFreeVendor\WPDesk\Codeception\Runner\RuntimeEnvironment;
use FRFreeVendor\WPDesk\Codeception\Runner\RuntimeMode;
use FRFreeVendor\WPDesk\Codeception\Runner\WordPressPreparer;
final class RunCommand extends Command
{
    public function __construct(private readonly ProjectPaths $paths, private readonly DockerCompose $dockerCompose, private readonly ProjectConfigReader $configReader, private readonly WordPressPreparer $wordpressPreparer, private readonly CodeceptionRunner $codeceptionRunner, private readonly RuntimeMode $runtimeMode)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('run')->setAliases(['test', 'tests'])->setDescription('Run a Codeception suite.')->addArgument('suite', InputArgument::OPTIONAL, 'Codeception suite name.', 'acceptance')->addArgument('test', InputArgument::OPTIONAL, 'Optional Codeception test selector.')->addArgument('codeception-args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Additional arguments passed to Codeception after --.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $suite = (string) $input->getArgument('suite');
        $test = $input->getArgument('test');
        $codeceptionArgs = $input->getArgument('codeception-args');
        $codeceptionArgs = is_array($codeceptionArgs) ? $codeceptionArgs : [];
        if (is_string($test) && $test !== '' && strncmp($test, '-', 1) === 0) {
            array_unshift($codeceptionArgs, $test);
            $test = null;
        } else {
            $test = is_string($test) && $test !== '' ? $test : null;
        }
        $debugsCodeception = $this->debugsCodeception();
        if ($debugsCodeception && !in_array('--debug', $codeceptionArgs, \true)) {
            $codeceptionArgs[] = '--debug';
        }
        if ($debugsCodeception && !$this->hasVerboseArgument($codeceptionArgs)) {
            $codeceptionArgs[] = '--verbose';
        }
        if (!$this->runtimeMode->isDirect()) {
            $upExitCode = $this->dockerCompose->up($output, $this->localServicesForSuite($suite));
            if ($upExitCode !== self::SUCCESS) {
                return $upExitCode;
            }
            $arguments = ['run', $suite];
            if ($test !== null) {
                $arguments[] = $test;
            }
            if ($codeceptionArgs !== []) {
                $arguments[] = '--';
                $arguments = array_merge($arguments, $codeceptionArgs);
            }
            return $this->dockerCompose->execRunner($arguments, $output);
        }
        $paths = $this->paths;
        $config = $this->configReader->read($paths);
        $environment = RuntimeEnvironment::fromGlobals($this->runtimeMode, $paths);
        $this->wordpressPreparer->prepare($paths, $config, $environment, $output);
        return $this->codeceptionRunner->run($paths, $config, $environment, $suite, $test, $codeceptionArgs, $output);
    }
    /**
     * @return list<string>
     */
    private function localServicesForSuite(string $suite): array
    {
        $services = ['runner', 'wordpress', 'mysqltests'];
        if (!in_array(strtolower($suite), ['integration', 'unit', 'wpunit'], \true)) {
            $services[] = 'chrome';
        }
        return $services;
    }
    private function debugsCodeception(): bool
    {
        $debug = getenv('DEBUG');
        if (!is_string($debug) || $debug === '') {
            return \false;
        }
        if (in_array($debug, ['1', '*'], \true)) {
            return \true;
        }
        return in_array('codeception', preg_split('/[\s,]+/', $debug) ?: [], \true);
    }
    /**
     * @param list<string> $codeceptionArgs
     */
    private function hasVerboseArgument(array $codeceptionArgs): bool
    {
        foreach ($codeceptionArgs as $argument) {
            if (in_array($argument, ['--verbose', '-v', '-vv', '-vvv'], \true)) {
                return \true;
            }
        }
        return \false;
    }
}
