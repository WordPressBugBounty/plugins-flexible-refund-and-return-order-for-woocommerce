<?php

namespace FRFreeVendor;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\PublicRefundShortcode;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\PublicRequestLink;
\defined('ABSPATH') || exit;
$is_block_theme = \function_exists('wp_is_block_theme') && \wp_is_block_theme();
if ($is_block_theme) {
    ?>
<!doctype html>
<html <?php 
    \language_attributes();
    ?>>
<head>
	<meta charset="<?php 
    \bloginfo('charset');
    ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php 
    \wp_head();
    ?>
</head>
<body <?php 
    \body_class();
    ?>>
	<?php 
    \wp_body_open();
    \block_template_part('header');
} else {
    \get_header();
}
?>
<main class="fr-public-request-page">
	<?php 
echo \do_shortcode(PublicRefundShortcode::get_shortcode_for_type(\get_query_var(PublicRequestLink::ROUTE_KEY)));
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
?>
</main>
<?php 
if ($is_block_theme) {
    \block_template_part('footer');
    \wp_footer();
    ?>
</body>
</html>
	<?php 
} else {
    \get_footer();
}
