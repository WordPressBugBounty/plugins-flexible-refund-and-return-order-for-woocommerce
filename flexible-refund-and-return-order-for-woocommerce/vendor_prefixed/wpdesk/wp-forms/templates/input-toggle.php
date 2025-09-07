<?php

namespace FRFreeVendor;

/**
 * @var \WPDesk\Forms\Field $field
 * @var \WPDesk\View\Renderer\Renderer $renderer
 * @var string $name_prefix
 * @var string $value
 * @var string $template_name Real field template.
 */
?>
	<style>
		.wpd-toggle-field {
			position: absolute;
			opacity: 0;
			width: 32px;
			height: 16px;
			margin: 0;
			pointer-events: none;
		}

		label:has(> input.wpd-toggle-field) {
			display: inline-flex;
			align-items: center;
			position: relative;
			user-select: none;
			padding-left: 40px;
			font-size: 16px;
			line-height: 1;
			color: #000;
			height: 16px;
		}

		label:has(> input.wpd-toggle-field)::before {
			cursor: pointer;
			content: "";
			position: absolute;
			left: 0;
			top: 0;
			width: 32px;
			height: 16px;
			background: #fff;
			border: 1px solid #949494;
			border-radius: 200px;
			transition: background 0.25s, border-color 0.25s;
			box-sizing: content-box;
		}

		label:has(> input.wpd-toggle-field)::after {
			content: "";
			position: absolute;
			left: 2px;
			top: 2px;
			width: 14px;
			height: 14px;
			background: #1c1c1c;
			border-radius: 50%;
			transition: left 0.25s ease-out, background 0.25s;
			box-sizing: border-box;
		}

		label:has(> input.wpd-toggle-field:checked)::before {
			background: var(--wp-admin-theme-color, #1851e0);
			border-color: var(--wp-admin-theme-color, #1851e0);
		}

		label:has(> input.wpd-toggle-field:checked)::after {
			left: 18px;
			background: #fff;
		}

		label:has(> input.wpd-toggle-field:disabled)::before {
			opacity: 0.9;
			cursor: unset;
		}

		label:has(> input.wpd-toggle-field:disabled)::after {
			opacity: 0.5;
		}

		input.wpd-toggle-field:disabled{
			opacity: 0;
		}
	</style>
<?php 
$renderer->output_render('input', ['field' => $field, 'renderer' => $renderer, 'name_prefix' => $name_prefix, 'value' => $value]);
