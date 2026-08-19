<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
class EmailRefundShipment extends AbstractRefundEmail
{
    const ID = 'fr_email_refund_shipment';
    public function __construct()
    {
        $this->id = self::ID;
        $this->title = esc_html__('[Flexible Refund] Request Awaiting Shipment', 'flexible-refund-and-return-order-for-woocommerce');
        $this->description = esc_html__('Email sent to the customer when a request is awaiting shipment.', 'flexible-refund-and-return-order-for-woocommerce');
        parent::__construct();
    }
    public function get_default_subject()
    {
        return esc_html__('[{shop_title}] {request_type_label} request for order number #{order_number} is awaiting shipment', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_heading()
    {
        return esc_html__('The {request_type_label} request is awaiting shipment', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_additional_content()
    {
        return wpautop(wp_kses(__("Hi {customer_name},\n\nYour {request_type_label} request is awaiting shipment. Please send the package to the following address: {shop_address}\n\n{refund_specific_content}\n\nOptional administrator note: {refund_note}\n\nIf you have changed your mind and wish to cancel the request, please email us at {shop_email}.\n\nSincerely,\nStore Team", 'flexible-refund-and-return-order-for-woocommerce'), EmailHelper::allowed_tags()));
    }
    protected function get_refund_specific_content(WC_Order $order): string
    {
        return esc_html__('The refund payment will be processed after the package arrives.', 'flexible-refund-and-return-order-for-woocommerce');
    }
}
