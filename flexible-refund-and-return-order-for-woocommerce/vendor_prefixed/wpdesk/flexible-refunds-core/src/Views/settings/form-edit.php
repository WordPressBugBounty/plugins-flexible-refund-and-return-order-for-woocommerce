<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\FormDefinition;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\PublicRefundShortcode;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings\FormsController;
/** @var FormDefinition $form */
/** @var string $active_tab */
/** @var bool $is_pro */
/** @var bool $is_readonly */
/** @var array $settings */
$settings = isset($settings) && \is_array($settings) ? $settings : $form->get_settings();
$request_type = $form->get_request_type();
$is_refund = RequestType::REFUND === $request_type;
$policy_id = (int) ($settings['policy_page_id'] ?? 0);
$is_enabled = $is_readonly ? \false : $form->is_enabled();
$pro_value = $is_pro ? 'yes' : '0';
if (FormsController::TAB_SETTINGS === $active_tab) {
    ?>
<tr>
	<th scope="row" class="titledesc"><?php 
    \esc_html_e('Enable form', 'flexible-refund-and-return-order-for-woocommerce');
    ?></th>
	<td>
		<label><input type="checkbox" name="fr_form[enabled]" value="<?php 
    echo \esc_attr($is_readonly ? '0' : 'yes');
    ?>" <?php 
    \checked($is_enabled);
    ?> <?php 
    \disabled($is_readonly);
    ?> /> <?php 
    \esc_html_e('Show this request type to eligible customers', 'flexible-refund-and-return-order-for-woocommerce');
    ?></label>
		<?php 
    if ($is_readonly) {
        ?>
			<p class="description"><?php 
        \esc_html_e('Additional request forms are available in PRO.', 'flexible-refund-and-return-order-for-woocommerce');
        ?></p>
		<?php 
    }
    ?>
	</td>
</tr>
<tr>
	<th scope="row" class="titledesc"><label for="fr-form-button-label"><?php 
    \esc_html_e('Button label', 'flexible-refund-and-return-order-for-woocommerce');
    ?></label></th>
	<td><label for="fr-form-button-label"><input id="fr-form-button-label" class="regular-text" type="text" required name="fr_form[button_label]" value="<?php 
    echo \esc_attr($form->get_button_label());
    ?>" <?php 
    \disabled($is_readonly);
    ?> /></label>
		<p class="description"> <?php 
    \esc_html_e('Enter a custom label for the button or leave the default one.', 'flexible-refund-and-return-order-for-woocommerce');
    ?></p>
	</td>
</tr>

	<?php 
    $shortcode_id = 'fr-public-shortcode-' . $request_type;
    ?>
	<tr>
		<th scope="row" class="titledesc"><label for="<?php 
    echo \esc_attr($shortcode_id);
    ?>"><?php 
    \esc_html_e('Public form shortcode', 'flexible-refund-and-return-order-for-woocommerce');
    ?></label></th>
		<td>
			<div class="fr-shortcode-copy">
				<input id="<?php 
    echo \esc_attr($shortcode_id);
    ?>" class="regular-text fr-shortcode-copy__value" type="text" readonly value="<?php 
    echo \esc_attr(PublicRefundShortcode::get_shortcode_for_type($request_type));
    ?>" <?php 
    \disabled(!$is_pro);
    ?> />
				<button type="button" class="button fr-shortcode-copy__button" data-copied-label="<?php 
    \esc_attr_e('Copied!', 'flexible-refund-and-return-order-for-woocommerce');
    ?>" <?php 
    \disabled(!$is_pro);
    ?>><?php 
    \esc_html_e('Copy shortcode', 'flexible-refund-and-return-order-for-woocommerce');
    ?></button>
				<span class="screen-reader-text fr-shortcode-copy__status" aria-live="polite"></span>
			</div>
			<p class="description">
				<?php 
    if ($is_pro) {
        ?>
					<?php 
        \esc_html_e('Use this shortcode to display the public request form on any page.', 'flexible-refund-and-return-order-for-woocommerce');
        ?>
				<?php 
    } else {
        ?>
					<?php 
        \esc_html_e('Public request forms are available in PRO.', 'flexible-refund-and-return-order-for-woocommerce');
        ?>
				<?php 
    }
    ?>
			</p>
		</td>
	</tr>

	<?php 
    if ($is_pro) {
        ?>
		<?php 
        $field = ['id' => 'fr_form[settings][visibility_conditions]', 'value' => $settings['visibility_conditions'] ?? []];
        $custom_fields = $condition_fields;
        require __DIR__ . '/conditions.php';
        ?>
	<?php 
    } else {
        ?>
	<tr>
		<th scope="row" class="titledesc"><?php 
        \esc_html_e('Button visibility', 'flexible-refund-and-return-order-for-woocommerce');
        ?></th>
		<td><p class="description">
			<?php 
        if ($is_readonly) {
            ?>
				<?php 
            \esc_html_e('Conditional visibility is available in PRO.', 'flexible-refund-and-return-order-for-woocommerce');
            ?>
			<?php 
        } else {
            ?>
				<?php 
            \esc_html_e('Conditional visibility is available in PRO. The Refund button is shown for all eligible orders in Free.', 'flexible-refund-and-return-order-for-woocommerce');
            ?>
			<?php 
        }
        ?>
		</p></td>
	</tr>
	<?php 
    }
    ?>

	<?php 
    if ($is_refund) {
        ?>
	<tr>
		<th scope="row" class="titledesc"><label for="fr-refund-type"><?php 
        \esc_html_e('Refund type', 'flexible-refund-and-return-order-for-woocommerce');
        ?></label></th>
		<td>
			<select id="fr-refund-type" name="fr_form[settings][refund_type]">
				<option value="bank" <?php 
        \selected($settings['refund_type'] ?? 'bank', 'bank');
        ?>><?php 
        \esc_html_e('Bank account / cash', 'flexible-refund-and-return-order-for-woocommerce');
        ?></option>
				<option value="coupon" <?php 
        \selected($settings['refund_type'] ?? 'bank', 'coupon');
        ?> <?php 
        \disabled(!$is_pro);
        ?>><?php 
        \esc_html_e('Coupon', 'flexible-refund-and-return-order-for-woocommerce');
        ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row" class="titledesc"><?php 
        \esc_html_e('Automatic approval', 'flexible-refund-and-return-order-for-woocommerce');
        ?></th>
		<td><label><input type="checkbox" name="fr_form[settings][auto_approval]" value="<?php 
        echo \esc_attr($pro_value);
        ?>" <?php 
        \checked($is_pro && 'yes' === ($settings['auto_approval'] ?? 'no'));
        ?> <?php 
        \disabled(!$is_pro);
        ?> /> <?php 
        \esc_html_e('Check this option to automatically accept order refund requests.', 'flexible-refund-and-return-order-for-woocommerce');
        ?></label></td>
	</tr>
	<tr>
		<th scope="row" class="titledesc"><?php 
        \esc_html_e('Refund shipping', 'flexible-refund-and-return-order-for-woocommerce');
        ?></th>
		<td><label><input type="checkbox" name="fr_form[settings][refund_shipping]" value="yes" <?php 
        \checked($settings['refund_shipping'] ?? 'no', 'yes');
        ?> /> <?php 
        \esc_html_e('Check this option to allow returns for shipping', 'flexible-refund-and-return-order-for-woocommerce');
        ?></label></td>
	</tr>
	<?php 
    }
    ?>

	<tr>
		<th scope="row" class="titledesc"><?php 
    \esc_html_e('Availability period', 'flexible-refund-and-return-order-for-woocommerce');
    ?></th>
		<td><label><input class="auto-hide-checkbox" type="checkbox" name="fr_form[settings][auto_hide]" value="<?php 
    echo \esc_attr($pro_value);
    ?>" <?php 
    \checked($is_pro && 'yes' === ($settings['auto_hide'] ?? 'no'));
    ?> <?php 
    \disabled(!$is_pro);
    ?> /> <?php 
    \esc_html_e('Check this option to hide the refund button after a specified time.', 'flexible-refund-and-return-order-for-woocommerce');
    ?></label></td>
	</tr>
		<?php 
    $field = ['id' => 'fr_form[settings][auto_hide_settings]', 'value' => $settings['auto_hide_settings'] ?? [], 'should_disable' => !$is_pro];
    require __DIR__ . '/auto_hide.php';
    ?>

<tr>
	<th scope="row" class="titledesc"><label for="fr-policy-page"><?php 
    \esc_html_e('Policy page', 'flexible-refund-and-return-order-for-woocommerce');
    ?></label></th>
	<td>
		<?php 
    $policy_dropdown = \wp_dropdown_pages(['name' => 'fr_form[settings][policy_page_id]', 'id' => 'fr-policy-page', 'show_option_none' => \esc_html__('No page selected', 'flexible-refund-and-return-order-for-woocommerce'), 'option_none_value' => 0, 'selected' => \esc_attr($policy_id), 'echo' => \false]);
    if ($is_readonly) {
        $policy_dropdown = \str_replace('<select ', '<select disabled="disabled" ', $policy_dropdown);
    }
    echo \wp_kses($policy_dropdown, ['select' => ['name' => \true, 'id' => \true, 'class' => \true, 'disabled' => \true], 'option' => ['value' => \true, 'selected' => \true, 'class' => \true]]);
    ?>
	</td>
</tr>
<?php 
}
?>

<?php 
if (FormsController::TAB_FORM === $active_tab) {
    ?>
<tr>
	<td colspan="2">
		<?php 
    $field = ['id' => 'fr_form[schema]', 'value' => $form->get_schema()];
    require __DIR__ . '/form-builder.php';
    ?>
	</td>
</tr>
<?php 
}
