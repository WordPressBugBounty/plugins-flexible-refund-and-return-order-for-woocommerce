<?php

declare (strict_types=1);
namespace FRFreeVendor\WPDesk\Migrations;

use FRFreeVendor\WPDesk\Notice\Factory as NoticeFactory;
use FRFreeVendor\WPDesk\Notice\Notice;
class MigrationErrorNotice
{
    /** @var string */
    private $option_name;
    public function __construct(string $option_name)
    {
        $this->option_name = $option_name . '_error';
    }
    public function persist(string $error_message): void
    {
        update_option($this->option_name, $error_message, \false);
    }
    public function clear(): void
    {
        delete_option($this->option_name);
    }
    public function display(): void
    {
        $error_message = get_option($this->option_name, '');
        if (!is_string($error_message) || $error_message === '') {
            return;
        }
        NoticeFactory::notice($error_message, Notice::NOTICE_TYPE_ERROR, \true);
    }
}
