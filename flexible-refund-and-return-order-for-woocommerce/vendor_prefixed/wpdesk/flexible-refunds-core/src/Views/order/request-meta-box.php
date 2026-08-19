<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
\defined('ABSPATH') || exit;
/** @var WC_Order $order */
/** @var RequestRecord[] $requests */
/** @var RequestRecord $selected */
$current_url = \remove_query_arg('fr_request_id');
$request_expanded = $selected->is_active() || isset($_GET['fr_request_id']);
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div
	class="panel-wrap woocommerce fr-request-meta-box"
	data-collapsed-url="<?php 
echo \esc_url($current_url);
?>"
>
	<input type="hidden" id="fr_refund_order_id" value="<?php 
echo \esc_attr($order->get_id());
?>" />

	<h2 class="fr-request-heading">
		<?php 
echo \esc_html(\sprintf(
    /* translators: %d: number of requests. */
    \__('Flexible Refunds - Requests (%d)', 'flexible-refund-and-return-order-for-woocommerce'),
    \count($requests)
));
?>
	</h2>

	<nav class="fr-request-selector" aria-label="<?php 
\esc_attr_e('Requests for this order', 'flexible-refund-and-return-order-for-woocommerce');
?>">
		<?php 
foreach ($requests as $request) {
    ?>
			<?php 
    $request_id = \absint($request->get_id());
    $is_expanded = $request_expanded && $request_id === $selected->get_id();
    $button_classes = ['button'];
    if ($is_expanded) {
        $button_classes[] = 'button-primary';
    }
    if (!$request->is_active()) {
        $button_classes[] = 'is-completed';
    }
    ?>
			<a
				class="<?php 
    echo \esc_attr(\implode(' ', $button_classes));
    ?>"
				href="<?php 
    echo \esc_url(\add_query_arg('fr_request_id', $request_id, $current_url));
    ?>"
				data-request-id="<?php 
    echo \esc_attr($request_id);
    ?>"
				aria-expanded="<?php 
    echo $is_expanded ? 'true' : 'false';
    ?>"
				aria-controls="fr-request-details-<?php 
    echo \esc_attr($request_id);
    ?>"
			>
				<?php 
    echo \esc_html(\sprintf('#%1$d %2$s', $request_id, RequestType::get_order_status_label($request->get_request_type())));
    ?>
				<?php 
    if (!$request->is_active()) {
        ?>
					<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php 
        \esc_html_e('Completed', 'flexible-refund-and-return-order-for-woocommerce');
        ?></span>
				<?php 
    }
    ?>
			</a>
		<?php 
}
?>
	</nav>

	<?php 
foreach ($requests as $request) {
    ?>
		<?php 
    $is_expanded = $request_expanded && $request->get_id() === $selected->get_id();
    require __DIR__ . '/request-details.php';
    ?>
	<?php 
}
?>
</div>
<?php 
