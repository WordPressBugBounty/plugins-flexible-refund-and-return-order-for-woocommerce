<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Statuses;
\defined('ABSPATH') || exit;
/** @var int $order_id */
/** @var string $status */
?>
<dl class="fr-public-request-status">
	<dt><?php 
\esc_html_e('Order ID', 'flexible-refund-and-return-order-for-woocommerce');
?></dt>
	<dd><?php 
echo \esc_html($order_id);
?></dd>
	<dt><?php 
\esc_html_e('Request status', 'flexible-refund-and-return-order-for-woocommerce');
?></dt>
	<dd><?php 
echo \esc_html(Statuses::get_status_label($status));
?></dd>
</dl>
<?php 
