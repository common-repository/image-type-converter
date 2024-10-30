<?php
use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
defined( 'ABSPATH' ) || exit;

$plugin_info = $args['plugin_info'];
$core        = $args['core'];
$_post       = $args['post'];
$_post_id    = $args['id'];
$metabox     = $args['metabox'];
$img_details = ImageConverter::get_image_file_details( $_post_id );
?>
<div class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-img-converter-container' ); ?>" style="margin-top:10px;">
	<?php
	if ( is_wp_error( $img_details ) ) {
		$metabox::error_message( $img_details->get_error_message(), false, '', false );
		?>
		</div>
		<?php
		return;
	}
	?>
	<?php
	$is_animated = $metabox::is_img_animated( $img_details['path'] );
	if ( true === $is_animated ) :
	?>
	<div class="container">
		<?php $metabox::error_message( 'Animated images are not supported yet.', false, '', false ); ?>
	</div>
	<?php else : ?>
	<div class="container-fluid g-0">
		<div class="row g-0 my-3">

			<div class="col-12">
				<div class="row align-items-center">
					<div class="col-md-7 border-end">
						<span class="my-2"><?php esc_html_e( 'Convert To:', 'image-type-converter'  ); ?></span>
					</div>
					<div class="col-md-5">
						<select class="my-2 <?php echo esc_attr( $plugin_info['classes_prefix'] . '-new-img-type' ); ?>" name="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-new-img-type' ); ?>">
							<?php
							$supported_types = ImageConverter::get_supported_types();
							foreach ( $supported_types as $img_type ) :
								if ( $img_details['ext'] === $img_type ) {
									continue;
								}
								?>
								<option value="<?php echo esc_attr( $img_type ); ?>"><?php echo esc_html( $img_type ); ?></option>
								<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>

			<div class="col-12 mb-3">
				<div class="row align-items-center">
					<div class="col-md-7 border-end">
						<span class="my-2"><?php esc_html_e( 'Quality:', 'image-type-converter'  ); ?></span>
					</div>
					<div class="col-md-5">
						<input type="number" min="1" max="100" value="85" class="my-2 <?php echo esc_attr( $plugin_info['classes_prefix'] . '-new-img-quality' ); ?>" name="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-new-img-quality' ); ?>">
					</div>
					<div class="col-12">
						<small class="text-muted"><?php esc_html_e( 'note: setting high quality near to 100 may result in a bigger image size.', 'image-type-converter' ); ?></small>
					</div>
				</div>
			</div>

			<div class="col-12">
				<div class="row align-items-center">
					<div class="col-md-7 border-end">
						<span class="my-2">
							<?php esc_html_e( 'Keep file extension?', 'image-type-converter' ); $core->pro_btn( '', 'Pro' ); ?>
						</span>
					</div>
					<div class="col-md-5">
						<input disabled type="checkbox" class="my-2 <?php echo esc_attr( $plugin_info['classes_prefix'] . '-keep-img-extension' ); ?>" >
					</div>
					<div class="col-12">
						<small class="text-muted"><?php esc_html_e( 'This option will keep the same old image file extension after conversion. This will keep the same image URL without change.', 'image-type-converter' ); ?></small>
					</div>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-attachment-id' ); ?>" name="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-attachment-id' ); ?>" value="<?php echo esc_attr( absint( $_post_id ) ); ?>">
	<button type="submit" style="margin-bottom:10px;" class="button button-primary <?php echo esc_attr( $plugin_info['classes_prefix'] . '-convert-img-submit' ); ?>"><?php esc_html_e( 'Submit' ); ?></button>
		<?php
		$metabox::loader_html( $plugin_info['classes_prefix'] );
	endif;
	?>
</div>
