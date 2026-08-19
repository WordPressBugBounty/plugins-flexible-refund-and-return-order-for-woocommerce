<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Exception\DisabledForm;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Exception\EntityNotFound;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestStatus;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
final class RequestService
{
    private RequestRepository $requests;
    public function __construct(RequestRepository $requests)
    {
        $this->requests = $requests;
    }
    public function create(FormDefinition $form, int $order_id, array $submitted_values, string $previous_order_status): RequestRecord
    {
        if (!$form->is_enabled()) {
            throw new DisabledForm(sprintf('Form %s is disabled.', $form->get_request_type()));
        }
        if (null === $form->get_id()) {
            throw new EntityNotFound('A request cannot use a form which has not been persisted.');
        }
        $now = gmdate('Y-m-d H:i:s');
        $request = new RequestRecord(null, $order_id, $form->get_id(), $form->get_version(), $form->get_request_type(), RequestStatus::REQUESTED, $previous_order_status, $form->get_snapshot(), $submitted_values, '', null, $now, $now);
        return $this->requests->add($request);
    }
    public function change_status(int $request_id, string $status, string $note = ''): RequestRecord
    {
        RequestStatus::assert_valid($status);
        $request = $this->requests->find($request_id);
        if (null === $request) {
            throw new EntityNotFound(sprintf('Request %d does not exist.', $request_id));
        }
        return $this->requests->save($request->with_status($status, $note, gmdate('Y-m-d H:i:s')));
    }
}
