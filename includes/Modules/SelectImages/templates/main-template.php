<?php

use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\Modules\SelectImages\Queries;

defined( 'ABSPATH' ) || exit;

$core        = $args['core'];
$plugin_info = $args['plugin_info'];
$module      = $args['module'];
?>

<div class="select-images-module-wrapper">
	<div class="accordion mb-5">
		<h5 class="mb-3">
			<?php esc_html_e( 'Select Images', 'image-type-converter' );  $core->pro_btn(); ?>
		</h5>
		<!-- Select direct images. -->
		<div class="mb-3">
			<input disabled="disabled" checked type="radio" id="select-images-direct" class="select-images-by-option" name="select-images-type" value="direct" >
			<label for="select-images-direct" class="mb-1"><?php esc_html_e( 'Select images directly', 'image-type-converter' ); ?></label>
			<small class="ms-4 d-block text-muted"><?php esc_html_e( 'Select images from media', 'image-type-converter' ); ?></small>
		</div>
		<!-- Select Images by post type -->
		<div class="mb-3">
			<input disabled="disabled" type="radio" id="select-images-by-post-type" class="select-images-by-option" name="select-images-type" value="cpt" >
			<label for="select-images-by-post-type" class="mb-1"><?php esc_html_e( 'Select Images by posts', 'image-type-converter' ); ?></label>
			<small class="ms-4 d-block text-muted"><?php esc_html_e( 'Select images attached to posts [ images uploaded to posts ]', 'image-type-converter' ); ?></small>
		</div>
		<!-- Full Conversion -->
		<div class="mb-3">
			<input disabled="disabled" type="radio" id="select-images-full" class="select-images-by-option" name="select-images-type" value="full" >
			<label for="select-images-full" class="mb-1"><?php esc_html_e( 'Full Select', 'image-type-converter' ); ?></label>
			<small class="ms-4 d-block text-muted"><?php esc_html_e( 'Convert all images', 'image-type-converter' ); ?></small>
		</div>
	</div>
</div>
