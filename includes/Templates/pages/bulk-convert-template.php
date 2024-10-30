<?php

defined( 'ABSPATH' ) || exit;

$core          = $args['core'];
$plugin_info   = $args['plugin_info'];
$template_page = $args['template_page'];
?>
<div class="container-fluid position-relative">
	<?php
	$GLOBALS[ $template_page->settings->get_id() . '-hide-save-btn' ] = true;
	$template_page->select_images_module->template();
	?>
</div>
