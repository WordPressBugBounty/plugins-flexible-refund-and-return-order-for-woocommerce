<?php

namespace FRFreeVendor;

\defined('ABSPATH') || exit;
?>
<h3><?php 
echo \esc_html($title ?? '');
?></h3>
<p><?php 
echo \esc_html($message ?? '');
?></p>
<?php 
