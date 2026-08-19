<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
class EmailRefundRefused extends AbstractRefundEmail
{
    const ID = 'fr_email_refund_refused';
    public function __construct()
    {
        $this->title = esc_html__('[Flexible Refund] Request Refused', 'flexible-refund-and-return-order-for-woocommerce');
        $this->description = esc_html__('Email sent to the customer when a request is refused.', 'flexible-refund-and-return-order-for-woocommerce');
        parent::__construct();
    }
    public function get_default_subject()
    {
        return esc_html__('[{shop_title}] {request_type_label} request for order number #{order_number} is refused', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_heading()
    {
        return esc_html__('The {request_type_label} request has been refused!', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_additional_content()
    {
        return wpautop(wp_kses(__("Hi {customer_name},\n\nUnfortunately, your {request_type_label} request has been refused. The reason is provided below:\n\n{refund_note}\n\nIf you have questions about this decision, please contact us at {shop_email} and include order number {order_number}.\n\n{refund_info_page}\n\nSincerely,\nStore Team", 'flexible-refund-and-return-order-for-woocommerce'), EmailHelper::allowed_tags()));
    }
}
