<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
\defined('ABSPATH') || exit;
/** @var WC_Order $order */
/** @var RequestRecord $request */
/** @var string $show_shipping */
$values = $request->get_submitted_values();
$items = \is_array($values['items'] ?? null) ? $values['items'] : [];
?>
<div style="margin-bottom: 40px;">
	<h3><?php 
\esc_html_e('Request details', 'flexible-refund-and-return-order-for-woocommerce');
?></h3>
	<table class="td" cellspacing="0" cellpadding="0" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
		<tr>
			<th class="td" scope="col"><?php 
\esc_html_e('Product', 'flexible-refund-and-return-order-for-woocommerce');
?></th>
			<th class="td" scope="col"><?php 
\esc_html_e('Quantity', 'flexible-refund-and-return-order-for-woocommerce');
?></th>
		</tr>
		</thead>
		<tbody>
		<?php 
foreach ($order->get_items() as $item_id => $item) {
    ?>
			<?php 
    $quantity = \absint($items[$item_id]['qty'] ?? 0);
    ?>
			<?php 
    if ($quantity > 0) {
        ?>
				<tr>
					<td class="td"><?php 
        echo \esc_html($item->get_name());
        ?></td>
					<td class="td"><?php 
        echo \esc_html($quantity);
        ?></td>
				</tr>
			<?php 
    }
    ?>
		<?php 
}
?>
		<?php 
if ('yes' === $show_shipping) {
    ?>
			<?php 
    foreach ($order->get_items('shipping') as $item_id => $item) {
        ?>
				<?php 
        if (\absint($items[$item_id]['qty'] ?? 0) > 0) {
            ?>
					<tr>
						<td class="td"><?php 
            echo \esc_html(\sprintf(\__('Shipping: %s', 'flexible-refund-and-return-order-for-woocommerce'), $item->get_name()));
            ?></td>
						<td class="td">1</td>
					</tr>
				<?php 
        }
        ?>
			<?php 
    }
    ?>
		<?php 
}
?>
		</tbody>
	</table>
</div>
<?php 
