<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\MyAccount;
/** @var WC_Order $order */
/** @var bool $public_request */
$request_id = isset($request) ? $request->get_id() : 0;
$cancel_request_url = \wp_nonce_url(\add_query_arg(['delete_refund_request' => $order->get_id(), 'fr_cancel_request' => $request_id]), MyAccount::CANCEL_NONCE_ACTION);
?>
<section id="fr-cancel-request-section" class="fr-cancel-request-description">
	<p><?php 
\esc_html_e('You can cancel the request until the administrator accepts or rejects your request', 'flexible-refund-and-return-order-for-woocommerce');
?></p>
	<a href="#" class="button primary-button cr-button"><?php 
\esc_html_e('Cancel Request', 'flexible-refund-and-return-order-for-woocommerce');
?></a>
	<a style="display: none;" href="#" class="button secondary-button ds-button"><?php 
\esc_html_e('No, not yet!', 'flexible-refund-and-return-order-for-woocommerce');
?></a>
	<?php 
if (!empty($public_request)) {
    ?>
		<form method="post">
			<?php 
    \wp_nonce_field(MyAccount::CANCEL_NONCE_ACTION);
    ?>
			<input type="hidden" name="fr_cancel_order" value="<?php 
    echo \esc_attr($order->get_id());
    ?>" />
			<input type="hidden" name="fr_cancel_request" value="<?php 
    echo \esc_attr($request_id);
    ?>" />
			<button type="submit" style="display: none;" class="button secondary-button cf-button"><?php 
    \esc_html_e('Yes, cancel request!', 'flexible-refund-and-return-order-for-woocommerce');
    ?></button>
		</form>
	<?php 
} else {
    ?>
		<a style="display: none;" href="<?php 
    echo \esc_url($cancel_request_url);
    ?>" class="button secondary-button cf-button"><?php 
    \esc_html_e('Yes, cancel request!', 'flexible-refund-and-return-order-for-woocommerce');
    ?></a>
	<?php 
}
?>
</section>
<?php 
