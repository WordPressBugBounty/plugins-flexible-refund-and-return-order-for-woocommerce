<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer\FormValuesRenderer;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Statuses;
\defined('ABSPATH') || exit;
/** @var WC_Order $order */
/** @var RequestRecord $request */
/** @var bool $is_expanded */
$request_id = \absint($request->get_id());
$snapshot = $request->get_form_snapshot();
$settings = \is_array($snapshot['settings'] ?? null) ? $snapshot['settings'] : [];
$values = $request->get_submitted_values();
$selected_items = \is_array($values['items'] ?? null) ? $values['items'] : [];
?>
<div
	id="fr-request-details-<?php 
echo \esc_attr($request_id);
?>"
	class="fr-request-content"
	data-request-id="<?php 
echo \esc_attr($request_id);
?>"
	<?php 
if (!$is_expanded) {
    ?>
		hidden
	<?php 
}
?>
>
	<div class="fr-request-summary">
		<strong><?php 
\esc_html_e('Request type:', 'flexible-refund-and-return-order-for-woocommerce');
?></strong> <?php 
echo \esc_html(RequestType::get_label($request->get_request_type()));
?>
		<span aria-hidden="true"> | </span>
		<strong><?php 
\esc_html_e('Submitted:', 'flexible-refund-and-return-order-for-woocommerce');
?></strong> <?php 
echo \esc_html($request->get_created_at());
?>
	</div>

	<div class="panel woocommerce-refund-data">
		<div class="flex-wrapper">
			<div class="col col-table">
				<h2><?php 
\esc_html_e('Requested products', 'flexible-refund-and-return-order-for-woocommerce');
?></h2>
				<table class="fr-refund-table widefat striped">
					<thead>
						<tr>
							<th><?php 
\esc_html_e('Product', 'flexible-refund-and-return-order-for-woocommerce');
?></th>
							<th><?php 
\esc_html_e('Requested quantity', 'flexible-refund-and-return-order-for-woocommerce');
?></th>
						</tr>
					</thead>
					<tbody>
						<?php 
foreach ($order->get_items() as $item_id => $item) {
    ?>
							<?php 
    $quantity = \absint($selected_items[$item_id]['qty'] ?? 0);
    ?>
							<?php 
    if ($quantity > 0) {
        ?>
								<tr>
									<td><?php 
        echo \esc_html($item->get_name());
        ?></td>
									<td>
										<?php 
        if (RequestType::REFUND === $request->get_request_type()) {
            ?>
											<input class="qty-input" type="number" min="0" max="<?php 
            echo \esc_attr($quantity);
            ?>" value="<?php 
            echo \esc_attr($quantity);
            ?>" name="fr_refund_form[items][<?php 
            echo \esc_attr($item_id);
            ?>][qty]" />
										<?php 
        } else {
            ?>
											<?php 
            echo \esc_html($quantity);
            ?>
										<?php 
        }
        ?>
									</td>
								</tr>
							<?php 
    }
    ?>
						<?php 
}
?>

						<?php 
if (RequestType::REFUND === $request->get_request_type() && 'yes' === ($settings['refund_shipping'] ?? 'no')) {
    ?>
							<?php 
    foreach ($order->get_items('shipping') as $item_id => $item) {
        ?>
								<?php 
        if (\absint($selected_items[$item_id]['qty'] ?? 0) > 0) {
            ?>
									<tr>
										<td><?php 
            echo \esc_html(\sprintf(\__('Shipping: %s', 'flexible-refund-and-return-order-for-woocommerce'), $item->get_name()));
            ?></td>
										<td>
											<input class="qty-input" type="checkbox" checked value="1" name="fr_refund_form[items][<?php 
            echo \esc_attr($item_id);
            ?>][qty]" />
										</td>
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

			<div class="col col-request">
				<div class="fr-request-fields">
					<h2><?php 
\esc_html_e('Customer answers', 'flexible-refund-and-return-order-for-woocommerce');
?></h2>
					<?php 
$form_values = (new FormValuesRenderer())->output($order, $request);
?>
					<?php 
if ('' !== $form_values) {
    ?>
						<?php 
    echo \wp_kses_post($form_values);
    ?>
					<?php 
} else {
    ?>
						<p class="description"><?php 
    \esc_html_e('No additional answers were submitted.', 'flexible-refund-and-return-order-for-woocommerce');
    ?></p>
					<?php 
}
?>
				</div>

				<div class="fr-refund-order-meta-box-actions">
					<h2><?php 
\esc_html_e('Request status', 'flexible-refund-and-return-order-for-woocommerce');
?></h2>
					<?php 
/* translators: %s: request status label. */
?>
					<p class="current-status"><strong><?php 
echo \esc_html(\sprintf(\__('Current status: %s', 'flexible-refund-and-return-order-for-woocommerce'), Statuses::get_status_label($request->get_status())));
?></strong></p>
					<?php 
if ('' !== $request->get_note()) {
    ?>
						<p><?php 
    echo \esc_html($request->get_note());
    ?></p>
					<?php 
}
?>

					<p><textarea class="regular-text fr-refund-request-note" placeholder="<?php 
\esc_attr_e('Request note', 'flexible-refund-and-return-order-for-woocommerce');
?>"></textarea></p>
					<p>
						<input type="hidden" class="fr-refund-request-id" value="<?php 
echo \esc_attr($request_id);
?>" />
						<select class="regular-text fr-refund-request-status">
							<option value=""><?php 
\esc_html_e('--- select status ---', 'flexible-refund-and-return-order-for-woocommerce');
?></option>
							<?php 
foreach (Statuses::get_statuses() as $status_id => $status_name) {
    ?>
								<option value="<?php 
    echo \esc_attr($status_id);
    ?>"><?php 
    echo \esc_html($status_name);
    ?></option>
							<?php 
}
?>
						</select>
						<button type="button" class="button button-primary fr-refund-button"><?php 
\esc_html_e('Update', 'flexible-refund-and-return-order-for-woocommerce');
?></button>
						<span class="spinner"></span>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
<?php 
