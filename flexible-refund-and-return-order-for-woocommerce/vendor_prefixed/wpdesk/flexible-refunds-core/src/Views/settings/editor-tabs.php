<?php

namespace FRFreeVendor;

/**
 * @var array<int, array{label: string, url: string, is_active: bool, is_disabled: bool}> $tabs
 */
?>
<nav class="nav-tab-wrapper">
	<?php 
foreach ($tabs as $editor_tab) {
    ?>
		<?php 
    if ($editor_tab['is_disabled']) {
        ?>
			<span class="nav-tab fr-nav-tab-disabled" aria-disabled="true"><?php 
        echo \esc_html($editor_tab['label']);
        ?></span>
			<?php 
        continue;
        ?>
		<?php 
    }
    ?>
		<a class="nav-tab<?php 
    echo $editor_tab['is_active'] ? ' nav-tab-active' : '';
    ?>" href="<?php 
    echo \esc_url($editor_tab['url']);
    ?>"><?php 
    echo \esc_html($editor_tab['label']);
    ?></a>
	<?php 
}
?>
</nav>
<?php 
