<?php

namespace FRFreeVendor\RRWProVendor;

use FRFreeVendor\WPDesk\Forms\Field;
/**
 * Render a select input field.
 *
 * @var Field  $field       Field object.
 * @var string $name_prefix Name prefix for the field.
 * @var mixed  $value       Current value.
 */
?>
<select
	id="<?php 
echo esc_attr($field->get_id());
?>"
	<?php 
if ($field->has_classes()) {
    ?>
		class="<?php 
    echo esc_attr($field->get_classes());
    ?>"
	<?php 
}
?>
	name="<?php 
echo esc_attr($name_prefix);
?>[<?php 
echo esc_attr($field->get_name());
?>]<?php 
echo $field->is_multiple() ? '[]' : '';
?>"
	<?php 
foreach ($field->get_attributes() as $key => $attr_val) {
    echo ' ' . esc_attr($key) . '="' . esc_attr($attr_val) . '"';
}
?>
>
	<?php 
if ($field->has_placeholder()) {
    ?>
		<option value=""><?php 
    echo esc_html($field->get_placeholder());
    ?></option>
	<?php 
}
?>

	<?php 
foreach ($field->get_possible_values() as $possible_value => $label) {
    ?>
		<?php 
    if ($possible_value === $value) {
        $is_selected = \true;
    } elseif (is_array($value)) {
        $is_selected = in_array($possible_value, $value, \true) || is_numeric($possible_value) && in_array((string) $possible_value, array_map('strval', $value), \true);
    } else {
        $is_selected = is_numeric($possible_value) && is_numeric($value) && (int) $possible_value === (int) $value;
    }
    ?>
		<option
			<?php 
    selected($is_selected);
    ?>
			value="<?php 
    echo esc_attr($possible_value);
    ?>"
		>
			<?php 
    echo esc_html($label);
    ?>
		</option>
	<?php 
}
?>
</select>
<?php 
