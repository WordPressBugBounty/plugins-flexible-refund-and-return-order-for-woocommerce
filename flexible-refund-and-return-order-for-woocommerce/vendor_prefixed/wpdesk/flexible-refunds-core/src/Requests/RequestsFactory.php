<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Requests;

use Exception;
use FRFreeVendor\WPDesk\Persistence\PersistentContainer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Statuses;
class RequestsFactory
{
    /**
     * @var PersistentContainer
     */
    private $settings;
    public function __construct(PersistentContainer $settings)
    {
        $this->settings = $settings;
    }
    /**
     * @throws Exception
     */
    public function get_request(string $status)
    {
        switch ($status) {
            case Statuses::REQUESTED_STATUS:
                return new Requested($this->settings);
            case Statuses::APPROVED_STATUS:
                return new Approved($this->settings);
            case Statuses::VERIFYING_STATUS:
                return new Verifying($this->settings);
            case Statuses::SHIPMENT_STATUS:
                return new Shipment($this->settings);
            case Statuses::REFUSED_STATUS:
                return new Refused($this->settings);
            default:
                throw new Exception(sprintf(esc_html__('Unknown request status: %s', 'flexible-refund-and-return-order-for-woocommerce'), $status));
        }
    }
}
