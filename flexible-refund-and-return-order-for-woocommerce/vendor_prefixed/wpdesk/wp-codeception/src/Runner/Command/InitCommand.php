<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner\Command;

use FRFreeVendor\Symfony\Component\Console\Command\Command;
use FRFreeVendor\Symfony\Component\Console\Input\InputInterface;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
use FRFreeVendor\WPDesk\Codeception\Runner\ProjectPaths;
final class InitCommand extends Command
{
    public function __construct(private readonly ProjectPaths $paths)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('init')->setDescription('Create missing wptest Codeception files without overwriting existing files.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = $this->paths;
        $created = [];
        $this->ensureDirectory($paths->codeceptionTestsDir());
        $this->ensureDirectory($paths->codeceptionSupportDir() . '/Helper');
        $this->ensureDirectory($paths->codeceptionDataDir());
        $this->ensureDirectory($paths->codeceptionTestsDir() . '/_output');
        $this->writeIfMissing($paths->wpdeskConfigPath(), $this->wpdeskConfigTemplate($paths), $created);
        $this->writeIfMissing($paths->rootCodeceptionConfigPath(), $this->rootCodeceptionTemplate(), $created);
        $this->writeIfMissing($paths->acceptanceSuitePath(), $this->acceptanceSuiteTemplate(), $created);
        $this->writeIfMissing($paths->integrationSuitePath(), $this->integrationSuiteTemplate(), $created);
        $this->writeIfMissing($paths->envTestingPath(), $this->envTestingTemplate(), $created);
        $this->writeIfMissing($paths->codeceptionSupportDir() . '/AcceptanceTester.php', $this->acceptanceTesterTemplate(), $created);
        $this->writeIfMissing($paths->codeceptionSupportDir() . '/IntegrationTester.php', $this->integrationTesterTemplate(), $created);
        $this->writeIfMissing($paths->codeceptionSupportDir() . '/Helper/Acceptance.php', $this->acceptanceHelperTemplate(), $created);
        $this->writeIfMissing($paths->codeceptionSupportDir() . '/Helper/Integration.php', $this->integrationHelperTemplate(), $created);
        if ($created === []) {
            $output->writeln('<info>No files created; project already has the expected wptest files.</info>');
            return self::SUCCESS;
        }
        foreach ($created as $file) {
            $output->writeln(sprintf('<info>Created %s</info>', $paths->relativePath($file)));
        }
        return self::SUCCESS;
    }
    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0777, \true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }
    /**
     * @param list<string> $created
     */
    private function writeIfMissing(string $path, string $content, array &$created): void
    {
        if (is_file($path)) {
            return;
        }
        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $content);
        $created[] = $path;
    }
    private function wpdeskConfigTemplate($paths): string
    {
        $directory = basename($paths->projectRoot());
        return <<<YAML
plugin:
  directory: {$directory}
  file: {$directory}.php
  slug: {$directory}
  title: "{$directory}"
  activate: false

dependencies:
  wordpress_org:
    - woocommerce
  local: []
  activate:
    - woocommerce

setup:
  wp_cli: []

YAML;
    }
    private function rootCodeceptionTemplate(): string
    {
        return <<<'YAML'
paths:
    tests: tests/codeception/tests
    output: tests/codeception/tests/_output
    data: tests/codeception/tests/_data
    support: tests/codeception/tests/_support
    envs: tests/codeception/tests/_envs
actor_suffix: Tester
extensions:
    enabled:
        - Codeception\Extension\RunFailed
params:
    - .env.testing

YAML;
    }
    private function acceptanceSuiteTemplate(): string
    {
        return <<<'YAML'
actor: AcceptanceTester
modules:
    enabled:
        - Cli
        - WPCLI
        - REST
        - WPDb
        - WPWebDriver
        - WPFilesystem
        - \Helper\Acceptance
    config:
        WPDb:
            dsn: 'mysql:host=%TEST_SITE_DB_HOST%;dbname=%TEST_SITE_DB_NAME%'
            user: '%TEST_SITE_DB_USER%'
            password: '%TEST_SITE_DB_PASSWORD%'
            dump: 'tests/codeception/tests/_data/db.sql'
            populate: true
            cleanup: true
            waitlock: 10
            url: '%TEST_SITE_WP_URL%'
            originalUrl: '%TEST_SITE_WP_URL%'
            urlReplacement: true
            tablePrefix: '%TEST_SITE_TABLE_PREFIX%'
        WPBrowser:
            url: '%TEST_SITE_WP_URL%'
            adminUsername: '%TEST_SITE_ADMIN_USERNAME%'
            adminPassword: '%TEST_SITE_ADMIN_PASSWORD%'
            adminPath: '%TEST_SITE_WP_ADMIN_PATH%'
        WPWebDriver:
            browser: chrome
            url: '%TEST_SITE_WP_URL%'
            host: '%SELENIUM_HOST%'
            port: '%SELENIUM_PORT%'
            window_size: '%BROWSER_WINDOW_SIZE%'
            adminUsername: '%TEST_SITE_ADMIN_USERNAME%'
            adminPassword: '%TEST_SITE_ADMIN_PASSWORD%'
            adminPath: '%TEST_SITE_WP_ADMIN_PATH%'
            wait: 10
            restart: false
            clear_cookies: true
            log_js_errors: true
            capabilities:
                unexpectedAlertBehaviour: "accept"
        WPCLI:
            path: '%WP_ROOT_FOLDER%'
            allow-root: true
            throw: true
        WPFilesystem:
            wpRootFolder: '%WP_ROOT_FOLDER%'
        REST:
            depends: WPBrowser
            url: '%TEST_SITE_WP_URL%'

YAML;
    }
    private function integrationSuiteTemplate(): string
    {
        return <<<'YAML'
actor: IntegrationTester
modules:
    enabled:
        - \Helper\Integration
        - lucatume\WPBrowser\Module\WPLoader:
            wpRootFolder: '%WP_ROOT_FOLDER%'
            dbUrl: 'mysql://%TEST_SITE_DB_USER%:%TEST_SITE_DB_PASSWORD%@%TEST_SITE_DB_HOST%:3306/%TEST_SITE_DB_NAME%'
            tablePrefix: '%TEST_SITE_TABLE_PREFIX%'
            domain: '%TEST_SITE_WP_DOMAIN%'
            adminEmail: '%TEST_SITE_ADMIN_EMAIL%'
            title: 'Integration Tests'
            plugins:
                - '%WPDESK_PLUGIN_FILE%'

YAML;
    }
    private function envTestingTemplate(): string
    {
        return <<<'ENV'
WP_ROOT_FOLDER="${APACHE_DOCUMENT_ROOT}"
TEST_SITE_WP_ADMIN_PATH="/wp-admin"
TEST_SITE_DB_NAME="wptest"
TEST_SITE_DB_HOST="mysqltests"
TEST_SITE_DB_USER="mysql"
TEST_SITE_DB_PASSWORD="mysql"
TEST_SITE_TABLE_PREFIX="wp_"
TEST_SITE_WP_URL="http://${WOOTESTS_IP}"
TEST_SITE_WP_DOMAIN="${WOOTESTS_IP}"
TEST_SITE_ADMIN_EMAIL="tests@wpdesk.dev"
TEST_SITE_ADMIN_USERNAME="admin"
TEST_SITE_ADMIN_PASSWORD="admin"
SELENIUM_HOST="chrome"
SELENIUM_PORT=4444
BROWSER_WINDOW_SIZE="1920,1080"
ENV;
    }
    private function acceptanceTesterTemplate(): string
    {
        return <<<'PHP'
<?php

namespace FRFreeVendor;

class AcceptanceTester extends \FRFreeVendor\Codeception\Actor
{
    use _generated\AcceptanceTesterActions;
    use \FRFreeVendor\WPDesk\Codeception\Tests\Acceptance\Tester\TesterWordpressActions;
    use \FRFreeVendor\WPDesk\Codeception\Tests\Acceptance\Tester\TesterWooCommerceActions;
    use \FRFreeVendor\WPDesk\Codeception\Tests\Acceptance\Tester\TesterWPDeskActions;
}
PHP;
    }
    private function integrationTesterTemplate(): string
    {
        return <<<'PHP'
<?php

namespace FRFreeVendor;

class IntegrationTester extends \FRFreeVendor\Codeception\Actor
{
    use _generated\IntegrationTesterActions;
}
PHP;
    }
    private function acceptanceHelperTemplate(): string
    {
        return <<<'PHP'
<?php

namespace FRFreeVendor\Helper;

class Acceptance extends \FRFreeVendor\Codeception\Module
{
}
PHP;
    }
    private function integrationHelperTemplate(): string
    {
        return <<<'PHP'
<?php

namespace FRFreeVendor\Helper;

class Integration extends \FRFreeVendor\Codeception\Module
{
}
PHP;
    }
}
