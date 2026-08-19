<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
interface FormRepository
{
    public function find(int $id): ?FormDefinition;
    public function find_by_type(string $request_type): ?FormDefinition;
    /** @return FormDefinition[] */
    public function find_all(): array;
    public function save(FormDefinition $form): FormDefinition;
}
