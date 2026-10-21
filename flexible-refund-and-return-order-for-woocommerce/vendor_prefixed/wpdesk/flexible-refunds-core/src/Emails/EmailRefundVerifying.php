<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
class EmailRefundVerifying extends AbstractRefundEmail
{
    public const ID = 'fr_email_refund_verifying';
    public function __construct()
    {
        $this->title = esc_html__('[Flexible Refund] Request Being Verified', 'flexible-refund-and-return-order-for-woocommerce');
        $this->description = esc_html__('Email sent to the customer while a request is being verified.', 'flexible-refund-and-return-order-for-woocommerce');
        parent::__construct();
    }
    public function get_default_subject()
    {
        return esc_html__('[{shop_title}] {request_type_label} request for order number #{order_number} is being verified', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_heading()
    {
        return esc_html__('The {request_type_label} request is being verified', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_additional_content()
    {
        $content = __("Hi {customer_name},\n\nYour {request_type_label} request is currently being reviewed. We will notify you when its status changes.\n\n{refund_info_page}\n\nNote from store team: {refund_note}\n\n<a href='{refund_url}' target='_blank'>Click here to view your request status</a>.\n\n{refund_management_link}\n\nSincerely,\nStore Team", 'flexible-refund-and-return-order-for-woocommerce');
        return wpautop(wp_kses(str_replace('{refund_management_link}', $this->get_default_management_link(), $content), EmailHelper::allowed_tags()));
    }
}
