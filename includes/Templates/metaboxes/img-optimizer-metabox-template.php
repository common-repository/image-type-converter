<?php
defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\ImageOptimizer;
use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\Pages\SettingsPage;

$plugin_info = $args['plugin_info'];
$core        = $args['core'];
$_post       = $args['post'];
$_post_id    = $args['id'];
$metabox     = $args['metabox'];
$img_details = ImageConverter::get_image_file_details( $_post_id );
?>
<div class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-img-optimizer-container' ); ?>" style="margin-top:10px;">
	<?php
	if ( is_wp_error( $img_details ) ) {
		$metabox::error_message( $img_details->get_error_message(), false, '', false );
		?>
		</div>
		<?php
		return;
	}
	if ( ! ImageOptimizer::is_img_type_optimizable( $img_details['ext'] ) ) {
		$metabox::error_message(
			sprintf(
				/* translators: %s Status Page link */
				esc_html__( 'Image type optimizer is not installed, Please check %s page for more details!', 'image-type-converter' ),
				'<a target="_blank" href="' . esc_url( SettingsPage::init()->get_page_path() ) . '">' . esc_html__( 'Status', 'image-type-converter' ) . '</a>'
			),
			false,
			'',
			false
		);
		?>
		</div>
		<?php
		return;
	}
	?>
	<div class="container-fluid g-0">
		<div class="row g-0 my-3">
			<div class="col-12 mb-3">
				<div class="row align-items-center">
					<div class="col-md-7 border-end">
						<span class="my-2"><?php esc_html_e( 'Quality:', 'image-type-converter' ); ?></span>
					</div>
					<div class="col-md-5">
						<input type="number" min="1" max="100" value="85" class="my-2 <?php echo esc_attr( $plugin_info['classes_prefix'] . '-new-img-quality' ); ?>" name="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-new-img-quality' ); ?>">
					</div>
					<div class="col-12">
						<small class="text-muted"><?php esc_html_e( 'note: setting high quality near to 100 may result in a bigger image size.', 'image-type-converter' ); ?></small>
					</div>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-attachment-id' ); ?>" name="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-attachment-id' ); ?>" value="<?php echo esc_attr( absint( $_post_id ) ); ?>">
	<button type="submit" style="margin-bottom:10px;" class="button button-primary <?php echo esc_attr( $plugin_info['classes_prefix'] . '-optimize-img-submit' ); ?>"><?php esc_html_e( 'Submit' ); ?></button>

    <?php $metabox::loader_html( $plugin_info['classes_prefix'] ); ?>
</div>
