<?php

namespace FRFreeVendor;

/**
 * @var array $params
 */
//phpcs:disable
\defined('ABSPATH') || exit;
?>
<div class="fr-refund-shortcode-columns">
	<section class="fr-refund-shortcode-guest">
		<h2><?php 
\esc_html_e('I do not have an account in the store', 'flexible-refund-and-return-order-for-woocommerce');
?></h2>
		<form method="post" action="<?php 
echo \esc_url($params['form_action']);
?>" class="fr-refund-shortcode-wrapper">
			<?php 
\wp_nonce_field($params['nonce'], $params['nonce_field_name']);
?>

			<div class="fr-refund-shortcode-field-wrapper">
				<label for="<?php 
\esc_attr_e($params['order_field_name']);
?>" class="fr-refund-shortcode-label">
					<?php 
echo \esc_html($params['order_reference_label']);
?>
				</label>
				<input class="fr-refund-shortcode-field" type="text"
						id="<?php 
\esc_attr_e($params['order_field_name']);
?>"
						name="<?php 
\esc_attr_e($params['order_field_name']);
?>" value="<?php 
echo \esc_attr($params['order_reference']);
?>"
						placeholder="<?php 
echo \esc_attr($params['order_reference_label']);
?>" required/>
			</div>

			<div class="fr-refund-shortcode-field-wrapper">
				<label for="<?php 
\esc_attr_e($params['email_field_name']);
?>" class="fr-refund-shortcode-label">
					<?php 
\esc_html_e('Email address', 'flexible-refund-and-return-order-for-woocommerce');
?>
				</label>
				<input class="fr-refund-shortcode-field" type="email"
						id="<?php 
\esc_attr_e($params['email_field_name']);
?>"
						name="<?php 
\esc_attr_e($params['email_field_name']);
?>" value="<?php 
echo \esc_attr($params['email']);
?>"
						placeholder="<?php 
\esc_attr_e('Email address', 'flexible-refund-and-return-order-for-woocommerce');
?>" required/>
			</div>

			<div class="fr-refund-shortcode-field-wrapper">
				<input class="button fr-refund-shortcode-submit" type="submit"
						name="<?php 
\esc_attr_e($params['submit_field_name']);
?>"
						value="<?php 
echo \esc_attr($params['submit_label']);
?>"/>
			</div>
		</form>
	</section>
	<?php 
if (!empty($params['login_url'])) {
    ?>
		<section class="fr-refund-shortcode-account">
			<h2><?php 
    \esc_html_e('I have an account in the store', 'flexible-refund-and-return-order-for-woocommerce');
    ?></h2>
			<p><?php 
    echo \esc_html($params['login_description']);
    ?></p>
			<a class="button" href="<?php 
    echo \esc_url($params['login_url']);
    ?>"><?php 
    \esc_html_e('Log in', 'flexible-refund-and-return-order-for-woocommerce');
    ?></a>
		</section>
	<?php 
}
?>
</div>
<?php 
