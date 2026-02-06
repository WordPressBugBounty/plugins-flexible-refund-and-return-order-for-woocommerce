<?php

namespace FRFreeVendor;

/**
 * @var \WPDesk\Forms\Field            $field
 * @var \WPDesk\View\Renderer\Renderer $renderer
 * @var string                         $name_prefix
 * @var string                         $value
 * @var string                         $template_name Real field template.
 */
if (empty($value) || \is_string($value)) {
    $input_values[] = '';
} else {
    $input_values = $value;
}
?>
<div class="clone-element-container">
	<?php 
foreach ($input_values as $text_value) {
    ?>
		<?php 
    if (!\in_array($field->get_type(), ['number', 'text', 'hidden'], \true)) {
        ?>
			<input type="hidden" name="<?php 
        echo \esc_attr($name_prefix) . '[' . \esc_attr($field->get_name()) . ']';
        ?>" value="no"/>
		<?php 
    }
    ?>

		<?php 
    if ($field->get_type() === 'checkbox' && $field->has_sublabel()) {
        ?>
			<label>
		<?php 
    }
    ?>
		<div class="clone-wrapper">
			<input
				type="<?php 
    echo \esc_attr($field->get_type());
    ?>"
				name="<?php 
    echo \esc_attr($name_prefix) . '[' . \esc_attr($field->get_name()) . '][]';
    ?>"
				id="<?php 
    echo \esc_attr($field->get_id());
    ?>"

				<?php 
    if ($field->has_classes()) {
        ?>
					class="<?php 
        echo \esc_attr($field->get_classes());
        ?>"
				<?php 
    }
    ?>

				<?php 
    if ($field->get_type() === 'text' && $field->has_placeholder()) {
        ?>
					placeholder="<?php 
        echo \esc_html($field->get_placeholder());
        ?>"
					<?php 
    }
    foreach ($field->get_attributes() as $key => $atr_val) {
        echo \esc_attr($key) . '="' . \esc_attr($atr_val) . '"';
    }
    if ($field->is_required()) {
        ?>
					required="required"
					<?php 
    }
    if ($field->is_disabled()) {
        ?>
					disabled="disabled"
					<?php 
    }
    if ($field->is_readonly()) {
        ?>
					readonly="readonly"
					<?php 
    }
    if (\in_array($field->get_type(), ['number', 'text', 'hidden'], \true)) {
        ?>
					value="<?php 
        echo \esc_html($text_value);
        ?>"
				<?php 
    } else {
        ?>
					value="yes"
					<?php 
        if ($value === 'yes') {
            ?>
						checked="checked"
						<?php 
        }
    }
    ?>
			/>
			<span class="add-field <?php 
    echo $field->is_disabled() ? 'disabled' : '';
    ?>" style="<?php 
    echo $field->is_disabled() ? 'cursor: not-allowed; opacity: 0.5;' : 'cursor: pointer;';
    ?>">
			<span class="dashicons dashicons-plus-alt"></span>
		</span>
			<span class="remove-field hidden <?php 
    echo $field->is_disabled() ? 'disabled' : '';
    ?>" style="<?php 
    echo $field->is_disabled() ? 'cursor: not-allowed; opacity: 0.5;' : 'cursor: pointer;';
    ?>">
			<span class="dashicons dashicons-remove"></span>
		</span>
		</div>

		<?php 
    if ($field->get_type() === 'checkbox' && $field->has_sublabel()) {
        ?>
			<?php 
        echo \esc_html($field->get_sublabel());
        ?></label>
			<?php 
    }
}
?>
</div>
<style>
	.clone-element-container .clone-wrapper .add-field {
		display: none;
	}

	.clone-element-container .clone-wrapper:first-child .add-field {
		display: inline-block;
	}

	.clone-element-container .clone-wrapper .remove-field {
		display: inline-block;
	}

	.clone-element-container .clone-wrapper:first-child .remove-field {
		display: none;
	}
</style>

<script>
	if (typeof window.wpdesk_multiple_field_initialized === 'undefined') {
		window.wpdesk_multiple_field_initialized = true;

		jQuery(function ($) {
			$(document).on('click', '.clone-element-container .add-field', function (e) {
				e.preventDefault();
				let wrapper = $(this).closest('.clone-wrapper');
				let input = wrapper.find('input');

				if (input.is(':disabled') || $(this).hasClass('disabled')) {
					return;
				}

				let html = wrapper.clone();
				html.find('input').val('');
				wrapper.after(html);
			});

			$(document).on("click", ".clone-element-container .remove-field", function (e) {
				e.preventDefault();
				let wrapper = $(this).closest('.clone-wrapper');
				let input = wrapper.find('input');

				if (input.is(':disabled') || $(this).hasClass('disabled')) {
					return;
				}

				let is_disabled = $(this).hasClass('field-disabled');
				if (!is_disabled) {
					wrapper.remove();
				}
			});

			$('.form-table').find('input[data-disabled="yes"], select[data-disabled="yes"]').each(function (i, v) {
				$(this).attr('disabled', 'disabled');
				$(this).parent().addClass('field-disabled');
			});
		});
	}
</script>
<?php 
