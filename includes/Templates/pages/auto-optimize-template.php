<?php

defined( 'ABSPATH' ) || exit;

$core          = $args['core'];
$plugin_info   = $args['plugin_info'];
$template_page = $args['template_page'];
?>
<div class="container">
	<?php
	$GLOBALS[ $template_page->settings->get_id() . '-hide-save-btn' ] = true;
	$template_page->settings->print_settings( 'auto_optimize' );
	?>
</div>
