<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Exception\EntityNotFound;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
final class FormService
{
    private FormRepository $forms;
    public function __construct(FormRepository $forms)
    {
        $this->forms = $forms;
    }
    /** @return FormDefinition[] */
    public function get_all(): array
    {
        return $this->forms->find_all();
    }
    /** @return FormDefinition[] */
    public function get_enabled(): array
    {
        return array_values(array_filter($this->forms->find_all(), static function (FormDefinition $form): bool {
            return $form->is_enabled();
        }));
    }
    public function update(int $id, bool $enabled, string $button_label, array $schema, array $settings): FormDefinition
    {
        $form = $this->forms->find($id);
        if (null === $form) {
            throw new EntityNotFound(sprintf('Form %d does not exist.', $id));
        }
        $updated = $form->with_configuration($enabled, $button_label, $schema, $settings, gmdate('Y-m-d H:i:s'));
        return $this->forms->save($updated);
    }
}
