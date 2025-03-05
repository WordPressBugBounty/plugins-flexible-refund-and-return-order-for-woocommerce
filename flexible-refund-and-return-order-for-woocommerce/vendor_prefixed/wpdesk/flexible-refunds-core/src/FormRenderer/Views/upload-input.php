<?php

/**
 * @template upload-input.php
 */
namespace FRFreeVendor\RRWProVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\FormRenderer\UploadField;
/**
 * @var UploadField $field
 * @var string      $name_prefix
 * @var string      $value
 */
if ($field->get_type() === 'checkbox' && $field->has_sublabel()) {
    ?>
	<label>
	<?php 
}
?>
	<input
		name="<?php 
echo esc_attr($name_prefix);
?>[upload_names][]"
		value="<?php 
echo esc_attr($field->get_name());
?>"
		type="hidden"
	/>
	<input
		type="<?php 
echo esc_attr($field->get_type());
?>"
		name="<?php 
echo esc_attr($field->get_name() . ($field->get_files_limit() > 1 ? '[]' : ''));
?>"
		<?php 
if ($field->get_files_limit() > 1) {
    ?>
			multiple
		<?php 
}
?>
		<?php 
if ($field->has_classes()) {
    ?>
			class="<?php 
    echo esc_attr($field->get_classes());
    ?>"
		<?php 
}
?>
		<?php 
foreach ($field->get_attributes() as $key => $atr_val) {
    echo ' ' . esc_attr($key) . '="' . esc_attr($atr_val) . '"';
}
?>
		<?php 
if (in_array($field->get_type(), ['number', 'text', 'hidden'], \true)) {
    ?>
			value="<?php 
    echo esc_html($value);
    ?>"
		<?php 
} else {
    ?>
			value="yes"
			<?php 
    if ('yes' === $value) {
        ?>
				checked="checked"
			<?php 
    }
    ?>
		<?php 
}
?>
	/>
	<?php 
if ('checkbox' === $field->get_type() && $field->has_sublabel()) {
    echo esc_html($field->get_sublabel());
    ?>
	</label>
		<?php 
}
