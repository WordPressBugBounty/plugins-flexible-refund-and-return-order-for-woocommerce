<?php

namespace FRFreeVendor\WPDesk\Codeception\Command;

use FRFreeVendor\Codeception\Command\GenerateTest;
use FRFreeVendor\Codeception\CustomCommandInterface;
use FRFreeVendor\Symfony\Component\Console\Input\InputInterface;
use FRFreeVendor\Symfony\Component\Console\Output\OutputInterface;
/**
 * Generates codeception example test for WP Desk plugin activation.
 *
 * @package WPDesk\Codeception\Command
 */
class GenerateWooCommerce extends GenerateTest implements CustomCommandInterface
{
    /**
     * Get codeception command description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Generates woocommerce tests.';
    }
    /**
     * Returns the name of the command.
     *
     * @return string
     */
    public static function getCommandName(): string
    {
        return 'generate:woocommerce';
    }
    /**
     * Get generator class.
     *
     * @param array  $config .
     * @param string $class .
     * @return WooCommerceTestGenerator
     */
    protected function getGenerator($config, $class)
    {
        return new WooCommerceTestGenerator($config, $class);
    }
    /**
     * Execute command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $suite = $input->getArgument('suite');
        $class = $input->getArgument('class');
        $config = $this->getSuiteConfig($suite);
        $className = $this->getShortClassName($class);
        $path = $this->createDirectoryFor($config['path'], $class);
        $filename = $this->completeSuffix($className, 'Cest');
        $filename = $path . $filename;
        $gen = $this->getGenerator($config, $class);
        $res = $this->createFile($filename, $gen->produce());
        if (!$res) {
            $output->writeln("<error>Test {$filename} already exists</error>");
            return self::FAILURE;
        }
        $output->writeln("<info>Test was created in {$filename}</info>");
        return self::SUCCESS;
    }
}
