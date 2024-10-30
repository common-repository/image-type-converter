<?php
use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\ImageOptimizer;
defined( 'ABSPATH' ) || exit;

$plugin_info = $args['plugin_info'];
$core        = $args['core'];
$_post       = $args['post'];
$_post_id    = $args['id'];
$metabox     = $args['metabox'];
?>
<div class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-img-subsizes-list-container' ); ?> container-fluid" style="margin-top:10px;">
	<div class="row row-cols-1 row-cols-sm-1 row-cols-md-2 w-100 subsizes-list-container">
			<?php
			$img_metadata = wp_get_attachment_metadata( $_post_id );
			if ( ! empty( $img_metadata['sizes'] ) ) :
				$sizes                       = $img_metadata['sizes'];
				$conversions_history         = ImageConverter::get_img_conversion_history( $_post_id );
				$optimizations_history       = ImageOptimizer::get_optimization_history( $_post_id );
				$recent_conversion_history   = ! empty( $conversions_history ) ? $conversions_history[ array_key_last( $conversions_history ) ] : array();
				$recent_optimization_history = ! empty( $optimizations_history ) ? $optimizations_history[ array_key_last( $optimizations_history ) ] : array();
				foreach ( $sizes as $size_name => $size_arr ) :
					$subsize_conversion_history   = ( ! empty( $recent_conversion_history['sizes'] ) && ! empty( $recent_conversion_history['sizes'][ $size_name ] ) ) ? $recent_conversion_history['sizes'][ $size_name ] : array();
					$subsize_optimization_history = ( ! empty( $recent_optimization_history['sizes'] ) && ! empty( $recent_optimization_history['sizes'][ $size_name ] ) ) ? $recent_optimization_history['sizes'][ $size_name ] : array();
					// Subsize Item HTML.
					$metabox->generated_subsize_html( $_post_id, $size_name, $size_arr, $subsize_conversion_history, $subsize_optimization_history );
				endforeach;
			endif;
			?>
		</div>
</div>
