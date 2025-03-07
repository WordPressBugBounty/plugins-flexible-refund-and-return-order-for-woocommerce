<?php

namespace FRFreeVendor;

//phpcs:disable
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
if (!\defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly 
?>

<?php 
\do_action('woocommerce_email_header', $email_heading, $email);
echo \wpautop(\wp_kses($additional_content, EmailHelper::allowed_tags()));
\do_action('woocommerce_email_footer');
