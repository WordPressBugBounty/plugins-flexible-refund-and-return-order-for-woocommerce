<?php

namespace FRFreeVendor;

\defined('ABSPATH') || exit;
?>
<form method="get">
	<div class="fr-refund-shortcode-wrapper">
		<?php 
\wp_nonce_field($params['nonce'], $params['nonce_field_name']);
?>

		<div class="fr-refund-shortcode-field-wrapper">
			<label for="<?php 
\esc_attr_e($params['order_field_name']);
?>" class="fr-refund-shortcode-label">
				<?php 
\esc_html_e('Order number', 'flexible-refund-and-return-order-for-woocommerce');
?>
			</label>
			<input class="fr-refund-shortcode-field" type="text"
				   id="<?php 
\esc_attr_e($params['order_field_name']);
?>"
				   name="<?php 
\esc_attr_e($params['order_field_name']);
?>" value=""
				   placeholder="<?php 
\esc_attr_e('Order number', 'flexible-refund-and-return-order-for-woocommerce');
?>"/>
		</div>

		<div class="fr-refund-shortcode-field-wrapper">
			<label for="<?php 
\esc_attr_e($params['email_field_name']);
?>" class="fr-refund-shortcode-label">
				<?php 
\esc_html_e('Email', 'flexible-refund-and-return-order-for-woocommerce');
?>
			</label>
			<input class="fr-refund-shortcode-field" type="email"
				   id="<?php 
\esc_attr_e($params['email_field_name']);
?>"
				   name="<?php 
\esc_attr_e($params['email_field_name']);
?>" value=""
				   placeholder="<?php 
\esc_attr_e('Email', 'flexible-refund-and-return-order-for-woocommerce');
?>"/>
		</div>

		<div class="fr-refund-shortcode-field-wrapper">
			<input class="fr-refund-shortcode-submit" type="submit"
				   name="<?php 
\esc_attr_e($params['submit_field_name']);
?>"
				   value="<?php 
\esc_attr_e('Send', 'flexible-refund-and-return-order-for-woocommerce');
?>"/>
		</div>
	</div>
</form>
<?php 
