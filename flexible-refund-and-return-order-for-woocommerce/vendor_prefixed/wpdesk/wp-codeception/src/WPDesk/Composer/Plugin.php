<?php

namespace FRFreeVendor\WPDesk\Composer\Codeception;

use FRFreeVendor\Composer\Composer;
use FRFreeVendor\Composer\IO\IOInterface;
use FRFreeVendor\Composer\Plugin\Capable;
use FRFreeVendor\Composer\Plugin\PluginInterface;
/**
 * Composer plugin.
 *
 * @package WPDesk\Composer\Codeception
 */
class Plugin implements PluginInterface, Capable
{
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    /**
     * @inheritDoc
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    public function getCapabilities()
    {
        return [\FRFreeVendor\Composer\Plugin\Capability\CommandProvider::class => CommandProvider::class];
    }
}
