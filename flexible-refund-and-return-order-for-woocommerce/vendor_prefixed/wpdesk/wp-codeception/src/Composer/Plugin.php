<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Codeception\Composer;

use FRFreeVendor\Composer\Composer;
use FRFreeVendor\Composer\IO\IOInterface;
use FRFreeVendor\Composer\Plugin\Capable;
use FRFreeVendor\Composer\Plugin\PluginInterface;
final class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io)
    {
    }
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
    /**
     * @return array<class-string, class-string>
     */
    public function getCapabilities()
    {
        return [\FRFreeVendor\Composer\Plugin\Capability\CommandProvider::class => CommandProvider::class];
    }
}
