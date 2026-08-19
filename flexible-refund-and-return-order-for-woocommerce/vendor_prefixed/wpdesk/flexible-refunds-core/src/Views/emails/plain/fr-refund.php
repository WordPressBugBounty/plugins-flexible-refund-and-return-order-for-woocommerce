<?php

namespace FRFreeVendor;

//phpcs:disable
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
if (!\defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly 
?>

<?php 
\do_action('woocommerce_email_header', $email_heading, $email);
/* translators: %s: request type label. */
echo \esc_html(\sprintf(\__('Request type: %s', 'flexible-refund-and-return-order-for-woocommerce'), $request_type_label));
if ($request instanceof RequestRecord && null !== $request->get_id()) {
    /* translators: %d: request ID. */
    echo ' ' . \esc_html(\sprintf(\__('(request #%d)', 'flexible-refund-and-return-order-for-woocommerce'), $request->get_id()));
}
echo "\n\n";
echo \wpautop(\wp_kses($additional_content, EmailHelper::allowed_tags()));
\do_action('woocommerce_email_footer');
