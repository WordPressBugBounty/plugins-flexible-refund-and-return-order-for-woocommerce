<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

use FRFreeVendor\Symfony\Component\Console\Helper\ProgressIndicator;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
final class WordPressPreparer
{
    private const WP = 'wp';
    private const DEFAULT_WOOCOMMERCE_THEME = 'storefront';
    public function __construct(private readonly ProcessRunner $processRunner)
    {
    }
    public function prepare(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $preparation = PreparationOutput::start($output);
        $output = $preparation->output();
        $this->exportConfigEnvironment($config);
        $this->ensureDirectory($environment->wpRoot);
        $preparation->step('WordPress root directory prepared.');
        $this->waitForWordPressBootstrap($environment, $output);
        $this->waitForDatabase($paths, $environment, $output);
        $this->ensureWordPressInstalled($paths, $environment, $output);
        $this->prepareSiteOptions($paths, $environment, $output);
        $preparation->step('WordPress site prepared.');
        $this->installProjectPlugin($paths, $config, $environment, $output);
        $preparation->step('Project plugin installed.');
        $this->installDependencies($paths, $config, $environment, $output);
        if ($config->wordpressOrgDependencies !== [] || $config->localDependencies !== []) {
            $preparation->step('Dependencies installed.');
        }
        if (is_file($paths->defaultDumpPath())) {
            $this->importDump($paths, $environment, $output);
            $preparation->step('Project database dump imported.');
        }
        $this->prepareDefaultWooCommerceTheme($paths, $config, $environment, $output);
        if ($config->hasWooCommerce()) {
            $preparation->step('WooCommerce theme prepared.');
        }
        $this->activatePlugins($paths, $config, $environment, $output);
        if ($config->pluginsToActivate() !== []) {
            $preparation->step('Plugins activated.');
        }
        $this->updateDatabases($paths, $config, $environment, $output);
        $preparation->step('Databases updated.');
        $this->runSetupCommands($paths, $config, $environment, $output);
        if ($config->setupCommands !== []) {
            $preparation->step('Setup commands completed.');
        }
        $this->exportDump($paths, $environment, $output);
        $preparation->step('Database dump exported.');
        $preparation->finish();
    }
    private function exportConfigEnvironment(ProjectConfig $config): void
    {
        foreach ($config->processEnvironment() as $name => $value) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    private function waitForWordPressBootstrap(RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $indicator = null;
        $waitingStarted = \false;
        for ($attempt = 1; $attempt <= 60; $attempt++) {
            if (is_file($environment->wpRoot . '/wp-includes/version.php') && is_file($environment->wpRoot . '/wp-config.php')) {
                $this->finishWait($indicator, $output, '  WordPress bootstrap ready.');
                return;
            }
            if (!$waitingStarted) {
                $indicator = $this->startWait($output, '  Waiting for WordPress bootstrap...');
                $waitingStarted = \true;
            }
            sleep(1);
            if ($indicator !== null) {
                $indicator->advance();
            }
        }
        $this->finishWait($indicator, $output, '  WordPress bootstrap was not prepared.', \false);
        throw new \RuntimeException(sprintf('WordPress files and wp-config.php were not prepared in "%s". Check the runner image entrypoint.', $environment->wpRoot));
    }
    private function waitForDatabase(ProjectPaths $paths, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $indicator = null;
        $waitingStarted = \false;
        $passwordArgument = $environment->dbPassword !== '' ? '-p' . $environment->dbPassword : '';
        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $command = array_values(array_filter(['mysqladmin', 'ping', '-h', $environment->dbHost, '-u', $environment->dbUser, $passwordArgument, '--silent'], static fn(string $argument): bool => $argument !== ''));
            $exitCode = $output->isVerbose() ? $this->processRunner->run($command, $paths->projectRoot(), $output, $environment->processEnvironment()) : $this->capturedExitCode($command, $paths->projectRoot(), $environment->processEnvironment());
            if ($exitCode === 0) {
                $this->finishWait($indicator, $output, '  Database ready.');
                return;
            }
            if (!$waitingStarted) {
                $indicator = $this->startWait($output, '  Waiting for database...');
                $waitingStarted = \true;
            }
            sleep(1);
            if ($indicator !== null) {
                $indicator->advance();
            }
        }
        $this->finishWait($indicator, $output, '  Database did not become available.', \false);
        throw new \RuntimeException('Database did not become available in time.');
    }
    private function ensureWordPressInstalled(ProjectPaths $paths, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $exitCode = $this->wpAllowFailure(['core', 'is-installed'], $paths, $environment, $output);
        if ($exitCode === 0) {
            return;
        }
        $this->wp(['core', 'install', '--url=' . $environment->siteUrl, '--title=WP Desk Tests', '--admin_user=' . $environment->adminUsername, '--admin_password=' . $environment->adminPassword, '--admin_email=' . $environment->adminEmail, '--skip-email'], $paths, $environment, $output);
    }
    private function prepareSiteOptions(ProjectPaths $paths, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        if ($this->wpOutput(['option', 'get', 'permalink_structure'], $paths, $environment) !== '/%postname%/') {
            $this->wp(['rewrite', 'structure', '/%postname%/'], $paths, $environment, $output);
        }
        $this->wp(['rewrite', 'flush', '--hard'], $paths, $environment, $output);
        $this->ensureHtaccess($environment);
        foreach (['siteurl', 'home'] as $option) {
            if ($this->wpOutput(['option', 'get', $option], $paths, $environment) !== $environment->siteUrl) {
                $this->wp(['option', 'update', $option, $environment->siteUrl], $paths, $environment, $output);
            }
        }
    }
    private function ensureHtaccess(RuntimeEnvironment $environment): void
    {
        $htaccess = $environment->wpRoot . '/.htaccess';
        if (is_file($htaccess)) {
            return;
        }
        $rules = <<<'HTACCESS'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS;
        if (file_put_contents($htaccess, $rules . \PHP_EOL) === \false) {
            throw new \RuntimeException(sprintf('Could not write WordPress rewrite rules to "%s".', $htaccess));
        }
    }
    private function installProjectPlugin(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $target = $environment->wpRoot . '/wp-content/plugins/' . $config->pluginDirectory;
        $this->ensureDirectory($target);
        $this->processRunner->mustRunQuietly($this->rsyncCommand($paths->projectRoot(), $target, ['.git', '.idea', 'node_modules', 'tests', 'var']), $paths->projectRoot(), $output, $environment->processEnvironment());
    }
    private function installDependencies(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        foreach ($config->wordpressOrgDependencies as $plugin) {
            if ($this->wpAllowFailure(['plugin', 'is-installed', $plugin], $paths, $environment, $output) === 0) {
                continue;
            }
            $this->wp(['plugin', 'install', $plugin], $paths, $environment, $output);
        }
        foreach ($config->localDependencies as $plugin) {
            $source = rtrim($environment->dependentPluginsDir, '/') . '/' . $plugin;
            if (!is_dir($source)) {
                throw new \RuntimeException(sprintf('Local dependency "%s" was not found at %s.', $plugin, $source));
            }
            $target = $environment->wpRoot . '/wp-content/plugins/' . basename($plugin);
            $this->ensureDirectory($target);
            $this->processRunner->mustRunQuietly($this->rsyncCommand($source, $target, ['.git', '.idea', 'node_modules', 'tests']), $paths->projectRoot(), $output, $environment->processEnvironment());
        }
    }
    private function importDump(ProjectPaths $paths, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $this->wp(['db', 'import', $paths->defaultDumpPath()], $paths, $environment, $output);
        $this->prepareSiteOptions($paths, $environment, $output);
    }
    private function activatePlugins(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        foreach ($config->pluginsToActivate() as $plugin) {
            $this->wp(['plugin', 'activate', $plugin], $paths, $environment, $output);
        }
    }
    private function prepareDefaultWooCommerceTheme(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        if (!$config->hasWooCommerce()) {
            return;
        }
        if ($this->wpAllowFailure(['theme', 'is-installed', self::DEFAULT_WOOCOMMERCE_THEME], $paths, $environment, $output) !== 0) {
            $this->wp(['theme', 'install', self::DEFAULT_WOOCOMMERCE_THEME], $paths, $environment, $output);
        }
        $this->wp(['theme', 'activate', self::DEFAULT_WOOCOMMERCE_THEME], $paths, $environment, $output);
    }
    private function updateDatabases(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $this->wp(['core', 'update-db'], $paths, $environment, $output);
        if (!$config->hasWooCommerce()) {
            return;
        }
        if ($this->wpAllowFailure(['plugin', 'is-active', 'woocommerce'], $paths, $environment, $output) !== 0) {
            return;
        }
        $this->wp(['wc', 'update'], $paths, $environment, $output);
    }
    private function runSetupCommands(ProjectPaths $paths, ProjectConfig $config, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        foreach ($config->setupCommands as $command) {
            $userArgument = preg_match('/(?:^|\s)--user(?:[=\s]|$)/', $command) === 1 ? '' : ' --user=' . escapeshellarg($environment->adminUsername);
            $this->processRunner->mustRunShellQuietly(sprintf('%s %s --allow-root%s --path=%s', self::WP, $command, $userArgument, escapeshellarg($environment->wpRoot)), $paths->projectRoot(), $output, $environment->processEnvironment());
        }
    }
    private function exportDump(ProjectPaths $paths, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $this->ensureDirectory(dirname($paths->defaultDumpPath()));
        $this->wp(['db', 'export', $paths->defaultDumpPath(), '--add-drop-table'], $paths, $environment, $output);
    }
    /**
     * @param list<string> $arguments
     */
    private function wp(array $arguments, ProjectPaths $paths, RuntimeEnvironment $environment, OutputInterface $output): void
    {
        $this->processRunner->mustRunQuietly($this->wpCommand($arguments, $environment), $paths->projectRoot(), $output, $environment->processEnvironment());
    }
    /**
     * @param list<string> $arguments
     */
    private function wpAllowFailure(array $arguments, ProjectPaths $paths, RuntimeEnvironment $environment, OutputInterface $output): int
    {
        $command = $this->wpCommand($arguments, $environment);
        if ($output->isVerbose()) {
            return $this->processRunner->run($command, $paths->projectRoot(), $output, $environment->processEnvironment());
        }
        return $this->capturedExitCode($command, $paths->projectRoot(), $environment->processEnvironment());
    }
    /**
     * @param list<string> $arguments
     */
    private function wpOutput(array $arguments, ProjectPaths $paths, RuntimeEnvironment $environment): string
    {
        [$exitCode, $output] = $this->processRunner->capture($this->wpCommand($arguments, $environment), $paths->projectRoot(), $environment->processEnvironment());
        if ($exitCode !== 0) {
            return '';
        }
        return trim($output);
    }
    /**
     * @param list<string> $arguments
     * @return list<string>
     */
    private function wpCommand(array $arguments, RuntimeEnvironment $environment): array
    {
        return array_merge([self::WP], $arguments, ['--allow-root', '--path=' . $environment->wpRoot]);
    }
    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0777, \true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }
    /**
     * @param list<string> $excludes
     * @return list<string>
     */
    private function rsyncCommand(string $source, string $target, array $excludes): array
    {
        $source = rtrim($source, '/');
        $target = rtrim($target, '/');
        $command = ['rsync', '-a', '--delete'];
        foreach ($excludes as $exclude) {
            $command[] = '--exclude=' . $exclude;
        }
        $distignore = $source . '/.distignore';
        if (is_file($distignore)) {
            $command[] = '--exclude-from=' . $distignore;
        }
        $command[] = $source . '/';
        $command[] = $target . '/';
        return $command;
    }
    private function startWait(OutputInterface $output, string $message): ?ProgressIndicator
    {
        if ($output->isQuiet()) {
            return null;
        }
        if (!$output->isDecorated() || !PreparationOutput::isInteractive()) {
            $output->writeln(sprintf('<comment>%s</comment>', $message));
            return null;
        }
        $indicator = new ProgressIndicator($output, null, 150, ['-', '\\', '|', '/'], 'OK');
        $indicator->start($message);
        return $indicator;
    }
    private function finishWait(?ProgressIndicator $indicator, OutputInterface $output, string $message, bool $success = \true): void
    {
        if ($output->isQuiet()) {
            return;
        }
        if ($indicator !== null) {
            $indicator->finish($message, $success ? 'OK' : '!!');
            return;
        }
        if ($success) {
            $output->writeln($message);
            return;
        }
        $output->writeln(sprintf('<error>%s</error>', $message));
    }
    /**
     * @param list<string> $command
     * @param array<string, string> $env
     */
    private function capturedExitCode(array $command, string $cwd, array $env): int
    {
        [$exitCode] = $this->processRunner->capture($command, $cwd, $env);
        return $exitCode;
    }
}
