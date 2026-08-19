<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
use FRFreeVendor\Symfony\Component\Process\Process;
final class ProcessRunner
{
    /**
     * @param list<string> $command
     * @param array<string, string> $env
     */
    public function run(array $command, string $cwd, OutputInterface $output, array $env = []): int
    {
        $process = new Process($command, $cwd, $env === [] ? null : $env);
        $process->setTimeout(null);
        $this->writeCommand($command, $output);
        return $this->stream($process, $output);
    }
    /**
     * @param list<string> $command
     * @param array<string, string> $env
     */
    public function runQuietly(array $command, string $cwd, OutputInterface $output, array $env = []): int
    {
        if ($output->isVerbose()) {
            return $this->run($command, $cwd, $output, $env);
        }
        $process = new Process($command, $cwd, $env === [] ? null : $env);
        $process->setTimeout(null);
        $process->run();
        if (($process->getExitCode() ?? 1) !== 0) {
            $this->writeFailure($process, $command, $output);
        }
        return $process->getExitCode() ?? 1;
    }
    /**
     * @param list<string> $command
     * @param array<string, string> $env
     */
    public function mustRunQuietly(array $command, string $cwd, OutputInterface $output, array $env = []): void
    {
        $exitCode = $this->runQuietly($command, $cwd, $output, $env);
        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf('Command failed with exit code %d: %s', $exitCode, implode(' ', $command)));
        }
    }
    /**
     * @param list<string> $command
     * @param array<string, string> $env
     * @return array{int, string}
     */
    public function capture(array $command, string $cwd, array $env = []): array
    {
        $process = new Process($command, $cwd, $env === [] ? null : $env);
        $process->setTimeout(null);
        $process->run();
        return [$process->getExitCode() ?? 1, $process->getOutput()];
    }
    /**
     * @param array<string, string> $env
     */
    public function mustRunShell(string $command, string $cwd, OutputInterface $output, array $env = []): void
    {
        $process = Process::fromShellCommandline($command, $cwd, $env === [] ? null : $env);
        $process->setTimeout(null);
        if ($output->isVerbose()) {
            $output->writeln(sprintf('<fg=gray>$ %s</>', $command));
        }
        $exitCode = $this->stream($process, $output);
        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf('Command failed with exit code %d: %s', $exitCode, $command));
        }
    }
    /**
     * @param array<string, string> $env
     */
    public function mustRunShellQuietly(string $command, string $cwd, OutputInterface $output, array $env = []): void
    {
        if ($output->isVerbose()) {
            $this->mustRunShell($command, $cwd, $output, $env);
            return;
        }
        $process = Process::fromShellCommandline($command, $cwd, $env === [] ? null : $env);
        $process->setTimeout(null);
        $process->run();
        $exitCode = $process->getExitCode() ?? 1;
        if ($exitCode !== 0) {
            $this->writeFailure($process, [$command], $output);
            throw new \RuntimeException(sprintf('Command failed with exit code %d: %s', $exitCode, $command));
        }
    }
    /**
     * @param list<string> $command
     */
    private function writeCommand(array $command, OutputInterface $output): void
    {
        if ($output->isVerbose()) {
            $output->writeln(sprintf('<fg=gray>$ %s</>', implode(' ', $command)));
        }
    }
    private function stream(Process $process, OutputInterface $output): int
    {
        $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });
        return $process->getExitCode() ?? 1;
    }
    /**
     * @param list<string> $command
     */
    private function writeFailure(Process $process, array $command, OutputInterface $output): void
    {
        $output->writeln(sprintf('<error>Command failed: %s</error>', implode(' ', $command)));
        if ($process->getOutput() !== '') {
            $output->write($process->getOutput());
        }
        if ($process->getErrorOutput() !== '') {
            $output->write($process->getErrorOutput());
        }
    }
}
