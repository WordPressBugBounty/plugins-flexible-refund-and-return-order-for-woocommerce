<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

final class RuntimeEnvironment
{
    public function __construct(public readonly string $wpRoot, public readonly string $dbName, public readonly string $dbHost, public readonly string $dbUser, public readonly string $dbPassword, public readonly string $tablePrefix, public readonly string $siteUrl, public readonly string $siteDomain, public readonly string $adminEmail, public readonly string $adminUsername, public readonly string $adminPassword, public readonly string $adminPath, public readonly string $seleniumHost, public readonly string $seleniumPort, public readonly string $dependentPluginsDir)
    {
    }
    public static function fromGlobals(RuntimeMode $mode, ProjectPaths $paths): self
    {
        $wpRoot = self::env('WP_ROOT_FOLDER') ?: self::env('APACHE_DOCUMENT_ROOT');
        if ($wpRoot === '') {
            $wpRoot = $mode->isDirect() ? sys_get_temp_dir() . '/wptest-wordpress' : '/var/www/html';
        }
        $siteDomain = (self::env('TEST_SITE_WP_DOMAIN') ?: self::env('WOOTESTS_IP')) ?: ($mode->isDirect() ? '127.0.0.1:8080' : 'WordPress');
        $siteUrl = self::env('TEST_SITE_WP_URL') ?: 'http://' . $siteDomain;
        return new self($wpRoot, (self::env('TEST_SITE_DB_NAME') ?: self::env('MYSQL_DATABASE')) ?: 'wptest', (self::env('TEST_SITE_DB_HOST') ?: self::env('MYSQL_HOST')) ?: 'mysqltests', (self::env('TEST_SITE_DB_USER') ?: self::env('MYSQL_USER')) ?: 'mysql', (self::env('TEST_SITE_DB_PASSWORD') ?: self::env('MYSQL_PASSWORD')) ?: 'mysql', self::env('TEST_SITE_TABLE_PREFIX') ?: 'wp_', $siteUrl, $siteDomain, self::env('TEST_SITE_ADMIN_EMAIL') ?: 'tests@wpdesk.dev', self::env('TEST_SITE_ADMIN_USERNAME') ?: 'admin', self::env('TEST_SITE_ADMIN_PASSWORD') ?: 'admin', self::env('TEST_SITE_WP_ADMIN_PATH') ?: '/wp-admin', self::env('SELENIUM_HOST') ?: 'chrome', self::env('SELENIUM_PORT') ?: '4444', self::env('DEPENDENT_PLUGINS_DIR') ?: dirname($paths->projectRoot()));
    }
    /**
     * @return array<string, string>
     */
    public function processEnvironment(): array
    {
        return ['APACHE_DOCUMENT_ROOT' => $this->wpRoot, 'WP_ROOT_FOLDER' => $this->wpRoot, 'WOOTESTS_IP' => $this->siteDomain, 'TEST_SITE_WP_ADMIN_PATH' => $this->adminPath, 'TEST_SITE_DB_NAME' => $this->dbName, 'TEST_SITE_DB_HOST' => $this->dbHost, 'TEST_SITE_DB_USER' => $this->dbUser, 'TEST_SITE_DB_PASSWORD' => $this->dbPassword, 'TEST_SITE_TABLE_PREFIX' => $this->tablePrefix, 'TEST_DB_NAME' => $this->dbName, 'TEST_DB_HOST' => $this->dbHost, 'TEST_DB_USER' => $this->dbUser, 'TEST_DB_PASSWORD' => $this->dbPassword, 'TEST_TABLE_PREFIX' => $this->tablePrefix, 'TEST_SITE_WP_URL' => $this->siteUrl, 'TEST_SITE_WP_DOMAIN' => $this->siteDomain, 'TEST_SITE_ADMIN_EMAIL' => $this->adminEmail, 'TEST_SITE_ADMIN_USERNAME' => $this->adminUsername, 'TEST_SITE_ADMIN_PASSWORD' => $this->adminPassword, 'SELENIUM_HOST' => $this->seleniumHost, 'SELENIUM_PORT' => $this->seleniumPort, 'DEPENDENT_PLUGINS_DIR' => $this->dependentPluginsDir];
    }
    private static function env(string $name): string
    {
        $value = getenv($name);
        return is_string($value) ? $value : '';
    }
}
