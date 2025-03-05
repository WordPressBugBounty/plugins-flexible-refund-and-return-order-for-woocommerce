<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Plugin;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration;
class EmailRefundRequestedAdmin extends AbstractRefundEmail
{
    const ID = 'fr_email_refund_admin_requested';
    public function __construct()
    {
        $this->id = self::ID;
        $this->title = esc_html__('[Flexible Refund] New Refund Request', 'flexible-refund-and-return-order-for-woocommerce');
        $this->description = esc_html__('New refund request', 'flexible-refund-and-return-order-for-woocommerce');
        $this->init_form_fields();
        $this->init_settings();
        parent::__construct();
        $this->customer_email = \false;
        $this->enabled = 'yes';
        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
    }
    public function init_form_fields()
    {
        parent::init_form_fields();
        $recipient_field = ['title' => esc_html__('Recipient(s)', 'flexible-refund-and-return-order-for-woocommerce'), 'type' => Integration::is_super() ? 'hidden' : 'text', 'desc_tip' => sprintf(esc_html__('Enter recipient(s). Defaults to administrator email address.', 'flexible-refund-and-return-order-for-woocommerce'), get_option('admin_email')), 'disabled' => !Integration::is_super(), 'placeholder' => '', 'description' => Integration::is_super() ? '' : sprintf(__('Edit the recipient’s email, or add a new address with the PRO version of the plugin. %1$sUpgrade to PRO &rarr;%2$s', 'flexible-refund-and-return-order-for-woocommerce'), '<br/><a href="' . esc_url(Plugin::get_url_to_pro('email')) . '?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=flexible-refund-pro&utm_content=main-settings-button-visibility" target="_blank" style="color:#FF9743;font-weight:600;margin-top:10px;display:inline-block;text-decoration:none;">', '</a>'), 'default' => get_option('admin_email')];
        $this->form_fields = array_merge(array_slice($this->form_fields, 0, 1, \true), ['recipient' => $recipient_field], array_slice($this->form_fields, 1, null, \true));
    }
    public function get_default_subject()
    {
        return esc_html__('[{shop_title}] New refund request #{order_number}', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_heading()
    {
        return esc_html__('The new order refund request has been requested!', 'flexible-refund-and-return-order-for-woocommerce');
    }
    public function get_default_additional_content()
    {
        return wpautop(wp_kses(__("Hi Admin,\n\nA new refund request for the order {order_number} appeared in {shop_url}\n\n<a href=\"{admin_order_url}\" target=\"_blank\">Click here to go to the order.</a>\n<a href=\"{admin_refunds_url}\" target=\"_blank\">Or click here to go to refund requests list.</a>", 'flexible-refund-and-return-order-for-woocommerce'), EmailHelper::allowed_tags()));
    }
}
