<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
final class DockerCompose
{
    public function __construct(private readonly ProjectPaths $paths, private readonly ProcessRunner $processRunner)
    {
    }
    /**
     * @param list<string> $services
     */
    public function up(OutputInterface $output, array $services = []): int
    {
        if (!$output->isVerbose() && !$output->isQuiet()) {
            $output->writeln('<info>Starting Docker test services.</info>');
        }
        $exitCode = $this->processRunner->run(array_merge($this->baseCommand(), ['up', '-d'], $services), $this->paths->projectRoot(), $output, $this->environment());
        if (!$output->isVerbose() && !$output->isQuiet() && $exitCode === 0) {
            $output->writeln('<info>Docker test services are ready.</info>');
        }
        if ($exitCode === 0 && $this->usesChrome($services)) {
            $this->writeBrowserUrl($output);
        }
        return $exitCode;
    }
    public function stop(OutputInterface $output): int
    {
        return $this->processRunner->runQuietly(array_merge($this->baseCommand(), ['stop']), $this->paths->projectRoot(), $output, $this->environment());
    }
    public function pull(OutputInterface $output): int
    {
        return $this->processRunner->run(array_merge($this->baseCommand(), ['pull']), $this->paths->projectRoot(), $output, $this->environment());
    }
    /**
     * @param list<string> $arguments
     */
    public function execRunner(array $arguments, OutputInterface $output): int
    {
        return $this->processRunner->run(array_merge($this->baseCommand(), ['exec', '-T', '--user', $this->runnerUser(), '-e', 'WPDESK_TEST_RUNTIME=container', 'runner', 'php', 'vendor/bin/wptest', '--ansi'], $arguments), $this->paths->projectRoot(), $output, $this->environment());
    }
    /**
     * @return list<string>
     */
    private function baseCommand(): array
    {
        $command = ['docker', 'compose', '--project-name', $this->projectName(), '-f', $this->paths->composePath()];
        if (is_file($this->paths->composeOverridePath())) {
            $command[] = '-f';
            $command[] = $this->paths->composeOverridePath();
        }
        return $command;
    }
    /**
     * @return array<string, string>
     */
    private function environment(): array
    {
        return ['WPDESK_TEST_PROJECT_DIR' => $this->paths->projectRoot(), 'WPDESK_TEST_DEPENDENT_PLUGINS_DIR' => dirname($this->paths->projectRoot()), 'WPTEST_UID' => $this->currentUserId()];
    }
    private function currentUserId(): string
    {
        $forced = getenv('WPTEST_UID');
        if (is_string($forced) && ctype_digit($forced)) {
            return $forced;
        }
        if (\PHP_OS_FAMILY !== 'Linux') {
            return '0';
        }
        if (function_exists('posix_getuid')) {
            return (string) posix_getuid();
        }
        $owner = fileowner($this->paths->projectRoot());
        return is_int($owner) ? (string) $owner : '0';
    }
    private function runnerUser(): string
    {
        return $this->currentUserId() . ':0';
    }
    /**
     * @param list<string> $services
     */
    private function usesChrome(array $services): bool
    {
        return $services === [] || in_array('chrome', $services, \true);
    }
    private function writeBrowserUrl(OutputInterface $output): void
    {
        if ($output->isQuiet()) {
            return;
        }
        [$exitCode, $address] = $this->processRunner->capture(array_merge($this->baseCommand(), ['port', 'chrome', '7900']), $this->paths->projectRoot(), $this->environment());
        $address = trim($address);
        if ($exitCode !== 0 || $address === '') {
            return;
        }
        $output->writeln(sprintf('<info>Browser noVNC:</info> http://%s/?autoconnect=1&resize=scale&password=secret', $address));
    }
    private function projectName(): string
    {
        $name = strtolower(basename($this->paths->projectRoot()));
        $name = preg_replace('/[^a-z0-9_-]+/', '-', $name) ?: 'project';
        return 'wptest-' . trim($name, '-');
    }
}
