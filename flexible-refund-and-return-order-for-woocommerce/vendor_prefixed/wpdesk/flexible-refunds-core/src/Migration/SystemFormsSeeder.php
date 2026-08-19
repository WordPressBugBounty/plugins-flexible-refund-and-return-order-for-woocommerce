<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
final class SystemFormsSeeder
{
    private FormRepository $forms;
    private LegacySettingsMapper $mapper;
    public function __construct(FormRepository $forms, LegacySettingsMapper $mapper)
    {
        $this->forms = $forms;
        $this->mapper = $mapper;
    }
    public function seed(): void
    {
        foreach ($this->mapper->get_system_forms() as $form) {
            if (null !== $this->forms->find_by_type($form->get_request_type())) {
                continue;
            }
            $this->forms->save($form);
        }
    }
}
