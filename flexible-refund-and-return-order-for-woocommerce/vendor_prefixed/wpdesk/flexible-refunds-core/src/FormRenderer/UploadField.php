<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer;

use FRFreeVendor\WPDesk\Forms\Field\InputTextField;
/**
 * Upload field.
 *
 * @package WPDesk\Library\FlexibleRefundsCore\FormRenderer
 */
class UploadField extends InputTextField
{
    public function get_type(): string
    {
        return 'file';
    }
    /**
     * @return string
     */
    public function get_template_name(): string
    {
        return 'upload-input';
    }
    public function get_files_limit(): int
    {
        return $this->is_attribute_set('data-files-limit') ? (int) $this->get_attribute('data-files-limit') : 1;
    }
}
