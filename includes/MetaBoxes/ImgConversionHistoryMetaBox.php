<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes;

use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\MetaBoxesBase\MetaBox;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\NoticeUtilsTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Image Conversion History Metabox.
 */
class ImgConversionHistoryMetaBox extends MetaBox {

	use NoticeUtilsTrait, ImgUtilsTrait;

	/**
	 * Singleton Instance.
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * MetaBox ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Set Metaboxes Arguments.
	 *
	 * @return void
	 */
	protected function set_metaboxes_args() {
		$this->id           = self::$plugin_info['name'] . '-img-conversion-history-metabox';
		$this->metabox_args = array(
			'id'       => $this->id,
			'title'    => esc_html__( 'Image Conversions History [GrandPlugins]', 'image-type-converter' ),
			'context'  => 'side',
			'priority' => 'high',
			'template' => 'img-conversion-history-metabox-template.php',
		);
	}

	/**
	 * Custom Condition before applying the metabox.
	 *
	 * @param string   $post_type
	 * @param \WP_Post $post
	 * @return bool
	 */
	protected function custom_condition( $post_type, $post ) {
		return ( 'attachment' === $post_type && wp_attachment_is_image( $post ) );
	}

	/**
	 * Get Metabox Screens.
	 *
	 * @return array<string>
	 */
	public static function get_screens() {
		return array( 'attachment' );
	}


	/**
	 * Conversion History Table HTML.
	 *
	 * @return void
	 */
	public function conversion_history_table( $_post_id ) {
		$conversions_history = array_reverse( ImageConverter::get_img_conversion_history( $_post_id ) );
		if ( ! empty( $conversions_history ) ) :
			?>
		<!-- Conversion History -->
		<div class="conversion-history my-3 border">
			<h5 class="border p-1 m-1 text-center"><?php esc_html_e( 'Conversion History', 'image-type-converter' ); ?></h5>
			<div class="container px-1">
				<?php foreach ( $conversions_history as $conversion_history ) : ?>
				<div class="conversion-history-item mt-3 border-top pt-3">
					<table class="table my-1 table-bordered">
						<thead>
							<tr>
								<th></th>
								<th><?php esc_html_e( 'Old' ); ?></th>
								<th><?php esc_html_e( 'New' ); ?></th>
							</tr>
						</thead>
						<tbody class="table-group-divider">
							<tr style="vertical-align:middle;">
								<th><?php esc_html_e( 'Size', 'image-type-converter' ); ?></th>
								<td><span><?php echo esc_html( size_format( $conversion_history['old_size'], 1 ) ); ?></span></td>
								<td class="d-flex flex-wrap align-items-center justify-content-center">
									<span><?php echo esc_html( size_format( $conversion_history['new_size'], 1 ) ); ?></span>
									<span class="my-1 ms-1" style="background: #<?php echo esc_attr( $conversion_history['new_size'] > $conversion_history['old_size'] ? 'f76e6e' : '00ffb8' ); ?>;padding: 5px;border-radius: 5px;font-weight: bolder;"><?php echo esc_html( '%' . number_format( ( abs( $conversion_history['old_size'] - $conversion_history['new_size'] ) / $conversion_history['old_size'] ) * 100 ) ); ?><?php echo esc_attr( $conversion_history['new_size'] > $conversion_history['old_size'] ? '⬆' : '⬇' ); ?></span>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Type', 'image-type-converter' ); ?></th>
								<td>
									<span><?php echo esc_html( strtoupper( $conversion_history['old_type'] ) ); ?></span>
								</td>
								<td>
									<span class="<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-conversion-history-new-type' ); ?>"><?php echo esc_html( strtoupper( $conversion_history['new_type'] ) ); ?></span>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Extension', 'image-type-converter' ); ?></th>
								<td>
									<span><?php echo esc_html( '.' . strtolower( $conversion_history['old_ext'] ) ); ?></span>
								</td>
								<td>
									<span><?php echo esc_html( '.' . strtolower( $conversion_history['new_ext'] ) ); ?></span>
								</td>
							</tr>
						</tbody>
					</table>
					<ul class="list-group my-1">
						<li class="list-group-item py-2 px-1 mb-0">
							<!-- Conversion Date -->
							<div class="row align-items-center px-1">
								<div class="col-md-6 my-1 border-end">
									<h6 class="mb-0"><?php esc_html_e( 'Date', 'image-type-converter' ); ?></h6>
								</div>
								<div class="col-md-6 my-1">
									<span><?php echo esc_html( gmdate( 'j F Y', $conversion_history['date'] ) ); ?></span>
								</div>
							</div>
						</li>
						<li class="list-group-item py-2 px-1 mb-0">
							<!-- Conversion Cotnext -->
							<div class="row align-items-center px-1">
								<div class="col-md-6 my-1 border-end">
									<h6 class="mb-0"><?php esc_html_e( 'Context', 'image-type-converter' ); ?></h6>
								</div>
								<div class="col-md-6 my-1">
									<span><?php echo esc_html( ( $conversion_history['conversion_context'] ) . ' ' . esc_html__( ' convert', 'image-type-converter' ) ); ?></span>
								</div>
							</div>
						</li>
					</ul>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
			<?php
		else :
			?>
			<small><?php esc_html_e( 'No conversions yet!', 'image-type-converter' ); ?></small>
			<?php
		endif;
	}

}
