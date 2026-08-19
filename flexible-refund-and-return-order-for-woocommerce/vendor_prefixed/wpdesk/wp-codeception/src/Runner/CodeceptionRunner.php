<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
final class CodeceptionRunner
{
    public function __construct(private readonly ProcessRunner $processRunner)
    {
    }
    /**
     * @param list<string> $additionalArguments
     */
    public function run(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, string $suite, ?string $test, array $additionalArguments, OutputInterface $output): int
    {
        $command = [$paths->vendorBin('codecept'), 'run', $suite];
        if ($additionalArguments === []) {
            $additionalArguments = ['-f', '--steps', '--html'];
        }
        if ($test !== null) {
            $command[] = $test;
            return $this->processRunner->run(array_merge($command, $additionalArguments), $paths->projectRoot(), $output, array_merge($environment->processEnvironment(), $config->processEnvironment()));
        }
        $parallelShard = $this->parallelShard($output);
        if ($parallelShard !== []) {
            return $this->processRunner->run(array_merge($command, $parallelShard, $additionalArguments), $paths->projectRoot(), $output, array_merge($environment->processEnvironment(), $config->processEnvironment()));
        }
        $parallelFiles = $this->parallelFiles($paths, $suite, $output);
        if ($parallelFiles !== null) {
            foreach ($parallelFiles as $file) {
                $exitCode = $this->processRunner->run(array_merge($command, [$file], $additionalArguments), $paths->projectRoot(), $output, array_merge($environment->processEnvironment(), $config->processEnvironment()));
                if ($exitCode !== 0) {
                    return $exitCode;
                }
            }
            return 0;
        }
        return $this->processRunner->run(array_merge($command, $additionalArguments), $paths->projectRoot(), $output, array_merge($environment->processEnvironment(), $config->processEnvironment()));
    }
    /**
     * @return list<string>
     */
    private function parallelShard(OutputInterface $output): array
    {
        $total = $this->positiveIntFromEnv('CI_NODE_TOTAL');
        $index = $this->positiveIntFromEnv('CI_NODE_INDEX');
        if ($total <= 1 || $index <= 0) {
            return [];
        }
        if (!$this->codeceptionSupportsShards()) {
            return [];
        }
        $shard = sprintf('%d/%d', $index, $total);
        $output->writeln(sprintf('<info>CI parallel split: running Codeception shard %s.</info>', $shard));
        return ['--shard', $shard];
    }
    /**
     * @return list<string>|null
     */
    private function parallelFiles(ProjectPaths $paths, string $suite, OutputInterface $output): ?array
    {
        $total = $this->positiveIntFromEnv('CI_NODE_TOTAL');
        $index = $this->positiveIntFromEnv('CI_NODE_INDEX');
        if ($total <= 1 || $index <= 0) {
            return null;
        }
        $suiteDir = $paths->codeceptionTestsDir() . '/' . $suite;
        if (!is_dir($suiteDir)) {
            return null;
        }
        $files = $this->testFiles($suiteDir);
        $nodeOffset = $index - 1;
        $assigned = [];
        foreach ($files as $position => $file) {
            if ($position % $total === $nodeOffset) {
                $assigned[] = $paths->relativePath($file);
            }
        }
        $output->writeln(sprintf('<info>CI parallel split: node %d/%d runs %d of %d files.</info>', $index, $total, count($assigned), count($files)));
        return $assigned;
    }
    /**
     * @return list<string>
     */
    private function testFiles(string $suiteDir): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($suiteDir, \FilesystemIterator::SKIP_DOTS));
        $files = [];
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            if (!str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $files[] = $file->getPathname();
        }
        sort($files);
        return $files;
    }
    private function codeceptionSupportsShards(): bool
    {
        try {
            return class_exists(\FRFreeVendor\Codeception\Command\Run::class) && (new \FRFreeVendor\Codeception\Command\Run())->getDefinition()->hasOption('shard');
        } catch (\Throwable) {
            return \false;
        }
    }
    private function positiveIntFromEnv(string $name): int
    {
        $value = getenv($name);
        if (!is_string($value) || !ctype_digit($value)) {
            return 0;
        }
        return max(0, (int) $value);
    }
}
