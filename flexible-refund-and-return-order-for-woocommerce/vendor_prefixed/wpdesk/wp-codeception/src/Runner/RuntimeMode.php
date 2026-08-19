<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Runner;

enum RuntimeMode : string
{
    case Docker = 'docker';
    case Direct = 'direct';
    public static function detect(): self
    {
        $forced = strtolower((string) getenv('WPDESK_TEST_MODE'));
        if (in_array($forced, ['local', 'docker'], \true)) {
            return self::Docker;
        }
        if (in_array($forced, ['ci', 'direct', 'container'], \true)) {
            return self::Direct;
        }
        if ((string) getenv('WPDESK_TEST_RUNTIME') === 'container') {
            return self::Direct;
        }
        if ((string) getenv('GITLAB_CI') !== '' || (string) getenv('CI') !== '') {
            return self::Direct;
        }
        return self::Docker;
    }
    public function isDirect(): bool
    {
        // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts
        return $this === self::Direct;
    }
}
