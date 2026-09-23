<?php

namespace FRFreeVendor;

//phpcs:disable
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\EmailHelper;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
/**
 * @var string $email_heading
 * @var string $email
 * @var string $additional_content
 * @var RequestRecord|null $request
 * @var string $request_label
 * @var string $request_type_label
 */
if (!\defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly 
?>

<?php 
\do_action('woocommerce_email_header', $email_heading, $email);
echo \wpautop(\wp_kses($additional_content, EmailHelper::allowed_tags()));
\do_action('woocommerce_email_footer');
