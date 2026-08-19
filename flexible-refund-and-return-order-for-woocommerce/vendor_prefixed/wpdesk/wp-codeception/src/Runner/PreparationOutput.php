<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

use FRFreeVendor\Symfony\Component\Console\Output\ConsoleOutputInterface;
use FRFreeVendor\Symfony\Component\Console\Output\ConsoleSectionOutput;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
final class PreparationOutput
{
    private OutputInterface $output;
    private ?ConsoleSectionOutput $section;
    private function __construct(OutputInterface $output, ?ConsoleSectionOutput $section)
    {
        $this->output = $output;
        $this->section = $section;
    }
    public static function start(OutputInterface $output): self
    {
        if ($output->isQuiet()) {
            return new self($output, null);
        }
        $section = null;
        if ($output instanceof ConsoleOutputInterface && $output->isDecorated() && !$output->isVerbose() && self::isInteractive()) {
            $section = $output->section();
            $output = $section;
        }
        $output->writeln('<comment>Preparing test environment...</comment>');
        return new self($output, $section);
    }
    public function output(): OutputInterface
    {
        return $this->output;
    }
    public function step(string $message): void
    {
        if ($this->output->isQuiet()) {
            return;
        }
        $this->output->writeln(sprintf('  %s', $message));
    }
    public function finish(): void
    {
        if ($this->output->isQuiet()) {
            return;
        }
        if ($this->section !== null) {
            $this->section->overwrite('<info>Prepared test environment.</info>');
            return;
        }
        $this->output->writeln('<info>Prepared test environment.</info>');
    }
    public static function isInteractive(): bool
    {
        return !self::isCi() && defined('STDOUT') && stream_isatty(\STDOUT);
    }
    private static function isCi(): bool
    {
        return (string) getenv('CI') !== '' || (string) getenv('GITLAB_CI') !== '';
    }
}
