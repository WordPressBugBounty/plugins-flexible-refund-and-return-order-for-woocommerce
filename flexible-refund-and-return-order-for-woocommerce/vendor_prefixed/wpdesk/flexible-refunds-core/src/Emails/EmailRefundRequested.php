<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
class EmailRefundRequested extends AbstractRefundEmail
{
    const ID = 'fr_email_refund_requested';
    public function __construct()
    {
        $this->title = esc_html__('[Flexible Refund] Request Submitted', 'flexible-refund-and-return-order-for-woocommerce');
        $this->description = esc_html__('Email sent to the customer when a new request is submitted.', 'flexible-refund-and-return-order-for-woocommerce');
        parent::__construct();
    }
    public function get_default_subject()
    {
        return esc_html__('[{shop_title}] New {request_type_label} request for order number #{order_number}', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_heading()
    {
        return esc_html__('A new {request_type_label} request has been submitted!', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_additional_content()
    {
        return wpautop(wp_kses(__("Hi {customer_name},\n\nThank you. Your {request_type_label} request was submitted on {refund_request_date}. We will review it and notify you about the next steps. <a href='{refund_url}' target='_blank'>Click here to cancel your request</a>.\n\nBelow you will find the products included in your request.\n\n{refund_order_table}\n\n{refund_info_page}\n\nSincerely,\nStore Team", 'flexible-refund-and-return-order-for-woocommerce'), EmailHelper::allowed_tags()));
    }
}
