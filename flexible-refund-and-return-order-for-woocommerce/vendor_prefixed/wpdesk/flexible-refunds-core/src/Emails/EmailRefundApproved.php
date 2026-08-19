<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
class EmailRefundApproved extends AbstractRefundEmail
{
    const ID = 'fr_email_refund_approved';
    public function __construct()
    {
        $this->title = esc_html__('[Flexible Refund] Request Approved', 'flexible-refund-and-return-order-for-woocommerce');
        $this->description = esc_html__('Email sent to the customer when a request is approved.', 'flexible-refund-and-return-order-for-woocommerce');
        parent::__construct();
    }
    public function get_default_subject()
    {
        return esc_html__('[{shop_title}] {request_type_label} request for order number #{order_number} is approved', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_heading()
    {
        return esc_html__('The {request_type_label} request has been approved!', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_additional_content()
    {
        return wpautop(wp_kses(__("Hi {customer_name},\n\nYour {request_type_label} request has been approved.\n\n{refund_specific_content}\n\nNote from store team: {refund_note}\n\nSincerely,\nStore Team", 'flexible-refund-and-return-order-for-woocommerce'), EmailHelper::allowed_tags()));
    }
    protected function get_refund_specific_content(WC_Order $order): string
    {
        return sprintf(
            /* translators: %s: order payment method. */
            esc_html__('The refund payment has been processed using %s.', 'flexible-refund-and-return-order-for-woocommerce'),
            $order->get_payment_method_title()
        );
    }
}
