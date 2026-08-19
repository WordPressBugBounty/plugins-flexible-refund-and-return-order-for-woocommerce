<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Composer\Command;

use FRFreeVendor\Composer\Command\BaseCommand;
use FRFreeVendor\Symfony\Component\Console\Input\InputArgument;
use FRFreeVendor\Symfony\Component\Console\Input\InputInterface;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
use FRFreeVendor\Symfony\Component\Process\Process;
final class LegacyCommand extends BaseCommand
{
    private const MODE_INIT = 'init';
    private const MODE_DOCKER_RUN = 'docker_run';
    private const MODE_LOCAL_RUN = 'local_run';
    private const MODE_LOCAL_COVERAGE = 'local_coverage';
    private const MODE_NOOP = 'noop';
    /**
     * @param list<string> $aliases
     */
    private function __construct(string $name, string $description, string $mode, array $aliases = [])
    {
        $this->legacyDescription = $description;
        $this->mode = $mode;
        parent::__construct($name);
        $this->setAliases($aliases);
    }
    /** @var string */
    private $legacyDescription;
    /** @var string */
    private $mode;
    public static function createInitCommand(): self
    {
        return new self('create-codeception-tests', 'Create codeception tests directories and files.', self::MODE_INIT);
    }
    public static function createDockerRunCommand(): self
    {
        return new self('run-codeception-tests', 'Run codeception tests.', self::MODE_DOCKER_RUN, ['run-codeception-test']);
    }
    public static function createLocalRunCommand(): self
    {
        return new self('run-local-codeception-tests', 'Run local codeception tests.', self::MODE_LOCAL_RUN, ['run-local-codeception-test']);
    }
    public static function createLocalCoverageRunCommand(): self
    {
        return new self('run-local-codeception-tests-with-coverage', 'Run local codeception tests with coverage.', self::MODE_LOCAL_COVERAGE, ['run-local-codeception-test-with-coverage']);
    }
    /**
     * @param list<string> $aliases
     */
    public static function createNoopCommand(string $name, string $description, array $aliases = []): self
    {
        return new self($name, $description, self::MODE_NOOP, $aliases);
    }
    protected function configure(): void
    {
        $this->setDescription($this->legacyDescription);
        if (in_array($this->mode, [self::MODE_DOCKER_RUN, self::MODE_LOCAL_RUN, self::MODE_LOCAL_COVERAGE], \true)) {
            $this->addArgument('single', InputArgument::OPTIONAL, 'Name of single test to run.', 'all');
        }
        if ($this->mode === self::MODE_DOCKER_RUN) {
            $this->addArgument('fast', InputArgument::OPTIONAL, 'Fast tests - do not shutdown docker-compose.', 'slow');
            $this->addArgument('woo_version', InputArgument::OPTIONAL, 'Deprecated WooCommerce version argument.', '');
        }
        if ($this->mode === self::MODE_NOOP && $this->getName() === 'prepare-parallel-codeception-tests') {
            $this->addArgument('number_of_jobs', InputArgument::OPTIONAL, 'Deprecated number of jobs argument.', '4');
        }
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->deprecationNotice($output);
        if ($this->mode === self::MODE_NOOP) {
            return 0;
        }
        if ($this->mode === self::MODE_INIT) {
            return $this->runProcess([$this->projectBin('wptest'), 'init'], $output);
        }
        if ($this->mode === self::MODE_DOCKER_RUN) {
            $exitCode = $this->runProcess($this->dockerRunCommand($input), $output, ['WPDESK_TEST_MODE' => 'docker']);
            if ($exitCode === 0 && (string) $input->getArgument('fast') !== 'fast') {
                return $this->runProcess([$this->projectBin('wptest'), 'stop'], $output, ['WPDESK_TEST_MODE' => 'docker']);
            }
            return $exitCode;
        }
        return $this->runProcess($this->localRunCommand($input), $output);
    }
    private function deprecationNotice(OutputInterface $output)
    {
        if ($this->mode === self::MODE_NOOP) {
            $output->writeln(sprintf('<comment>Composer command "%s" is deprecated and now does nothing. Preparation happens during "vendor/bin/wptest run".</comment>', $this->getName()));
            return;
        }
        $output->writeln(sprintf('<comment>Composer command "%s" is deprecated. Use "%s" instead.</comment>', $this->getName(), $this->replacementCommand()));
    }
    private function replacementCommand(): string
    {
        switch ($this->mode) {
            case self::MODE_INIT:
                return 'vendor/bin/wptest init';
            case self::MODE_DOCKER_RUN:
                return 'vendor/bin/wptest run acceptance';
            case self::MODE_LOCAL_RUN:
            case self::MODE_LOCAL_COVERAGE:
                return 'vendor/bin/wptest-direct run acceptance';
            default:
                return 'vendor/bin/wptest';
        }
    }
    /**
     * @return list<string>
     */
    private function dockerRunCommand(InputInterface $input): array
    {
        $command = [$this->projectBin('wptest'), 'run', 'acceptance'];
        $this->appendTestArgument($command, (string) $input->getArgument('single'));
        return $command;
    }
    /**
     * @return list<string>
     */
    private function localRunCommand(InputInterface $input): array
    {
        $command = [$this->projectBin('wptest-direct'), 'run', 'acceptance'];
        $hasTest = $this->appendTestArgument($command, (string) $input->getArgument('single'));
        if ($this->mode === self::MODE_LOCAL_COVERAGE) {
            if (!$hasTest) {
                $command[] = '';
            }
            $command[] = '--';
            $command[] = '--coverage';
            $command[] = '--coverage-xml';
            $command[] = '--coverage-html';
        }
        return $command;
    }
    /**
     * @param list<string> $command
     */
    private function appendTestArgument(array &$command, string $test): bool
    {
        $test = trim($test);
        if ($test !== '' && $test !== 'all') {
            $command[] = $test;
            return \true;
        }
        return \false;
    }
    private function projectBin(string $name): string
    {
        return getcwd() . '/vendor/bin/' . $name;
    }
    /**
     * @param list<string>          $command
     * @param array<string,string> $env
     */
    private function runProcess(array $command, OutputInterface $output, array $env = []): int
    {
        $process = new Process($command, getcwd(), $env);
        $process->setTimeout(null);
        return $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });
    }
}
