<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class RegisterEmails implements Hookable
{
    public function hooks()
    {
        add_filter('woocommerce_email_classes', [$this, 'add_email_classes'], 999, 1);
    }
    public function add_email_classes($classes)
    {
        $classes[EmailRefundRequested::ID] = new EmailRefundRequested();
        $classes[EmailRefundApproved::ID] = new EmailRefundApproved();
        $classes[EmailRefundVerifying::ID] = new EmailRefundVerifying();
        $classes[EmailRefundShipment::ID] = new EmailRefundShipment();
        $classes[EmailRefundRefused::ID] = new EmailRefundRefused();
        $classes[EmailRefundRequestedAdmin::ID] = new EmailRefundRequestedAdmin();
        return $classes;
    }
}
