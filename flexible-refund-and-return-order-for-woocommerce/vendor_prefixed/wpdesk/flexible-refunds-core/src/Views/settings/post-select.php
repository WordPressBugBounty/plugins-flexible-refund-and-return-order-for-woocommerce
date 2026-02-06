<?php

namespace FRFreeVendor;

/**
 * @var array $field
 */
$post_id = $field['value'];
//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$post_title = '';
if (!empty($post_id)) {
    $post = \get_post($post_id);
    //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    if ($post) {
        $post_title = $post->post_title . ' (ID: ' . $post->ID . ')';
    }
}
?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php 
echo \esc_attr($field['id']);
?>"><?php 
echo \esc_html($field['title']);
?></label>
	</th>
	<td class="forminp forminp-<?php 
echo \esc_attr(\sanitize_title($field['type']));
?>">
		<select
			name="<?php 
echo \esc_attr($field['id']);
?>"
			id="<?php 
echo \esc_attr($field['id']);
?>"
			style="<?php 
echo \esc_attr($field['css']);
?>"
			class="wc-page-search"
			data-placeholder="<?php 
\esc_attr_e('Search for a page...', 'flexible-refund-and-return-order-for-woocommerce');
?>"
			data-post_type="post"
			data-allow_clear="true"
		>
			<option value=""></option>
			<?php 
if (!empty($post_id) && !empty($post_title)) {
    ?>
				<option value="<?php 
    echo \esc_attr($post_id);
    ?>" selected="selected">
					<?php 
    echo \esc_html($post_title);
    ?>
				</option>
			<?php 
}
?>
		</select>

		<?php 
if (!empty($field['desc'])) {
    ?>
			<p class="description"><?php 
    echo \wp_kses_post($field['desc']);
    ?></p>
		<?php 
}
?>
	</td>
</tr>
<?php 
