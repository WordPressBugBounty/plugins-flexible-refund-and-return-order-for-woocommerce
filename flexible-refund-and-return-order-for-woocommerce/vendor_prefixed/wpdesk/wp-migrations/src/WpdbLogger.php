<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations;

use FRFreeVendor\Psr\Log\LoggerTrait;
class WpdbLogger implements \FRFreeVendor\Psr\Log\LoggerInterface
{
    use LoggerTrait;
    private const MAX_LOG_SIZE = 30;
    /** @var string[] */
    private $log = [];
    /** @var string */
    private $log_name;
    public function __construct(string $log_name)
    {
        $this->log_name = $log_name;
    }
    public function log($level, $message, array $context = []): void
    {
        if (count($this->log) === 0) {
            $this->log = json_decode(get_option($this->log_name, '[]'));
        }
        $this->log[] = date('Y-m-d G:i:s') . sprintf(': %s', $message);
        if (\count($this->log) > self::MAX_LOG_SIZE) {
            array_shift($this->log);
        }
        update_option($this->log_name, json_encode($this->log), \false);
    }
}
