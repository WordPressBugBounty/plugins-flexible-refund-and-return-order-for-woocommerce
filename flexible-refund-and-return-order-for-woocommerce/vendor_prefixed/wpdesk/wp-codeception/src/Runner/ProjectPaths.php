<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

final class ProjectPaths
{
    public function __construct(private readonly string $projectRoot, private readonly string $packageRoot)
    {
    }
    public function projectRoot(): string
    {
        return $this->projectRoot;
    }
    public function packageRoot(): string
    {
        return $this->packageRoot;
    }
    public function codeceptionDir(): string
    {
        return $this->projectRoot . '/tests/codeception';
    }
    public function codeceptionTestsDir(): string
    {
        return $this->codeceptionDir() . '/tests';
    }
    public function codeceptionSupportDir(): string
    {
        return $this->codeceptionTestsDir() . '/_support';
    }
    public function codeceptionDataDir(): string
    {
        return $this->codeceptionTestsDir() . '/_data';
    }
    public function wpdeskConfigPath(): string
    {
        return $this->codeceptionDir() . '/wpdesk.yml';
    }
    public function rootCodeceptionConfigPath(): string
    {
        return $this->projectRoot . '/codeception.dist.yml';
    }
    public function acceptanceSuitePath(): string
    {
        return $this->codeceptionTestsDir() . '/acceptance.suite.yml';
    }
    public function integrationSuitePath(): string
    {
        return $this->codeceptionTestsDir() . '/integration.suite.yml';
    }
    public function envTestingPath(): string
    {
        return $this->projectRoot . '/.env.testing';
    }
    public function defaultDumpPath(): string
    {
        return $this->codeceptionDataDir() . '/db.sql';
    }
    public function composePath(): string
    {
        return $this->packageRoot . '/docker/docker-compose.yml';
    }
    public function composeOverridePath(): string
    {
        return $this->codeceptionDir() . '/docker-compose.override.yml';
    }
    public function vendorBin(string $binary): string
    {
        return $this->projectRoot . '/vendor/bin/' . $binary;
    }
    public function relativePath(string $path): string
    {
        if (str_starts_with($path, $this->projectRoot . '/')) {
            return substr($path, strlen($this->projectRoot) + 1);
        }
        return $path;
    }
}
