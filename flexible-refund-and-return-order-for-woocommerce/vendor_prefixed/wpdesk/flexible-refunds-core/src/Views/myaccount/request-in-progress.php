<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestStatus;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer\FormValuesRenderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Statuses;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\MyAccount;
/** @var WC_Order $order */
/** @var RequestRecord $request */
$label = RequestType::get_label($request->get_request_type());
$values = $request->get_submitted_values();
$requested_items = \is_array($values['items'] ?? null) ? $values['items'] : [];
$is_refund = RequestType::supports_monetary_refund($request->get_request_type());
$show_shipping = $is_refund;
$request_total = 0.0;
if ($is_refund) {
    ?>
	<h2><?php 
    \printf(\esc_html__('Refund status: %s', 'flexible-refund-and-return-order-for-woocommerce'), \esc_html(Statuses::get_status_label($request->get_status())));
    ?></h2>
<?php 
} else {
    ?>
	<h2><?php 
    echo \esc_html($label);
    ?></h2>
	<p><?php 
    echo \esc_html(\sprintf(\__('Current status: %s', 'flexible-refund-and-return-order-for-woocommerce'), Statuses::get_status_label($request->get_status())));
    ?></p>
<?php 
}
if ('' !== $request->get_note()) {
    ?>
	<p><?php 
    echo \wp_kses_post($request->get_note());
    ?></p>
<?php 
}
if (\in_array($request->get_status(), [RequestStatus::REQUESTED, RequestStatus::VERIFYING], \true)) {
    ?>
	<?php 
    $cancel_request_url = \wp_nonce_url(\add_query_arg(['delete_refund_request' => $order->get_id()]), MyAccount::CANCEL_NONCE_ACTION);
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
		<a style="display: none;" href="<?php 
    echo \esc_url($cancel_request_url);
    ?>" class="button secondary-button cf-button"><?php 
    \esc_html_e('Yes, cancel request!', 'flexible-refund-and-return-order-for-woocommerce');
    ?></a>
	</section>
<?php 
}
?>

<h3 class="fr-myaccount-order-details-header"><?php 
\esc_html_e('Order details', 'flexible-refund-and-return-order-for-woocommerce');
?></h3>

<table class="woocommerce-table">
		<thead>
			<tr>
				<th class="product-name"><?php 
\esc_html_e('Product', 'flexible-refund-and-return-order-for-woocommerce');
?></th>
				<?php 
if ($is_refund) {
    ?>
					<th class="item-cost"><?php 
    \esc_html_e('Cost', 'flexible-refund-and-return-order-for-woocommerce');
    ?></th>
					<th class="item-total"><?php 
    \esc_html_e('Total', 'flexible-refund-and-return-order-for-woocommerce');
    ?></th>
				<?php 
}
?>
				<th class="item-real-qty"><?php 
\esc_html_e('Quantity', 'flexible-refund-and-return-order-for-woocommerce');
?></th>
				<th class="item-qty"><?php 
echo \esc_html($is_refund ? \__('Quantity to refund', 'flexible-refund-and-return-order-for-woocommerce') : \__('Requested quantity', 'flexible-refund-and-return-order-for-woocommerce'));
?></th>
				<?php 
if ($is_refund) {
    ?>
					<th class="item-total"><?php 
    \esc_html_e('Refund Total', 'flexible-refund-and-return-order-for-woocommerce');
    ?></th>
				<?php 
}
?>
			</tr>
		</thead>
		<tbody>
			<?php 
foreach ($order->get_items() as $item_id => $item) {
    ?>
				<?php 
    $requested_quantity = \absint($requested_items[$item_id]['qty'] ?? 0);
    ?>
				<?php 
    $item_total = 0.0;
    $requested_total = 0.0;
    if ($is_refund) {
        $item_total = (float) $item->get_total() + (float) $item->get_total_tax();
        $requested_total = \round($order->get_item_total($item, \true, \false) * $requested_quantity, \wc_get_price_decimals());
        $request_total += $requested_total;
    }
    ?>
				<tr class="product_item">
					<td class="item-name"><?php 
    echo \esc_html($item->get_name());
    ?></td>
					<?php 
    if ($is_refund) {
        ?>
						<td class="item-cost"><?php 
        echo \wp_kses_post(\wc_price($order->get_item_total($item, \true), ['currency' => $order->get_currency()]));
        ?></td>
						<td class="item-total"><?php 
        echo \wp_kses_post(\wc_price($item_total, ['currency' => $order->get_currency()]));
        ?></td>
					<?php 
    }
    ?>
					<td class="item-real-qty" style="width:160px;"><?php 
    echo \esc_html($item->get_quantity());
    ?></td>
					<td class="item-qty" style="width:160px;"><?php 
    echo \esc_html($requested_quantity);
    ?></td>
					<?php 
    if ($is_refund) {
        ?>
						<td class="item-refund-total"><?php 
        echo \wp_kses_post(\wc_price($requested_total, ['currency' => $order->get_currency()]));
        ?></td>
					<?php 
    }
    ?>
				</tr>
			<?php 
}
?>

			<?php 
if ($show_shipping) {
    ?>
				<?php 
    foreach ($order->get_items('shipping') as $item_id => $item) {
        ?>
					<?php 
        $requested_quantity = \absint($requested_items[$item_id]['qty'] ?? 0);
        ?>
					<?php 
        $shipping_total = (float) $item->get_total() + (float) $item->get_total_tax();
        $requested_shipping_total = $requested_quantity > 0 ? (float) ($requested_items[$item_id]['refund_amount'] ?? $shipping_total) : 0.0;
        $request_total += $requested_shipping_total;
        ?>
					<tr class="shipping-item">
						<td><?php 
        echo \esc_html(\sprintf(\__('Shipping: %s', 'flexible-refund-and-return-order-for-woocommerce'), $item->get_name()));
        ?></td>
						<td><?php 
        echo \wp_kses_post(\wc_price($shipping_total, ['currency' => $order->get_currency()]));
        ?></td>
						<td><?php 
        echo \wp_kses_post(\wc_price($shipping_total, ['currency' => $order->get_currency()]));
        ?></td>
						<td class="item-qty">
							<?php 
        if ($shipping_total > 0) {
            ?>
								<label>
									<input
										class="qty-input"
										type="checkbox"
										value="1"
										<?php 
            \checked($requested_quantity > 0);
            ?>
										name="fr_refund_form[items][<?php 
            echo \esc_attr($item_id);
            ?>][qty]"
										disabled="disabled"
									/>
								</label>
							<?php 
        }
        ?>
						</td>
						<td class="item-qty" style="width:160px;"><?php 
        echo \esc_html($requested_quantity);
        ?></td>
						<td><span class="item-total-refund-qty"><?php 
        echo \wp_kses_post(\wc_price($requested_shipping_total, ['currency' => $order->get_currency()]));
        ?></span></td>
					</tr>
				<?php 
    }
    ?>
			<?php 
}
?>
		</tbody>
		<?php 
if ($is_refund) {
    ?>
			<tfoot>
				<tr>
					<td colspan="5"></td>
					<td class="total-refund-amount"><?php 
    echo \wp_kses_post(\wc_price($request_total, ['currency' => $order->get_currency()]));
    ?></td>
				</tr>
			</tfoot>
		<?php 
}
?>
</table>
<?php 
echo \wp_kses_post((new FormValuesRenderer())->output($order, $request));
