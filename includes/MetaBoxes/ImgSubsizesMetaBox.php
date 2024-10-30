<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes;

use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\MetaBoxesBase\MetaBox;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\NoticeUtilsTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Image Subsize list Metabox.
 */
class ImgSubsizesMetaBox extends MetaBox {
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
		$this->id           = self::$plugin_info['name'] . '-img-subsizes-list-metabox';
		$this->metabox_args = array(
			'id'       => $this->id,
			'title'    => esc_html__( 'Image Subsizes List [GrandPlugins]', 'image-type-converter' ),
			'template' => 'img-subsizes-list-metabox-template.php',
		);
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
	 * Generated Subsize HTML.
	 *
	 * @param int    $attachment_id
	 * @param array  $img_metadata
	 * @param string $size_name
	 * @param array  $size_arr
	 * @return void
	 */
	public function generated_subsize_html( $attachment_id, $size_name, $size_arr, $conversion_history = array(), $optimization_history = array() ) {
		$img_url    = wp_get_attachment_url( $attachment_id );
		$size_url   = str_replace( wp_basename( $img_url ), $size_arr['file'], $img_url );
		$size_title = $size_name;
		$size_path  = self::convert_url_to_path( $size_url );
		$size_url   = add_query_arg(
			array(
				'refresh' => wp_generate_password( 5, false, false ),
			),
			$size_url
		);
		if ( ! file_exists( $size_path ) ) {
			return;
		}
		?>
		<div class="col mb-4 subsize-item justify-content-center d-flex" id="<?php echo esc_attr( 'subsize-' . $size_name . '-col' ); ?>">
			<div class="card h-100 shadow-sm border w-100">
				<div class="card-body d-flex flex-column justify-content-between">
					<p class="card-title text-center border p-4 bg-secondary text-white fw-bolder">
						<?php echo esc_html( $size_title ); ?>
					</p>
					<a class="align-self-center d-inline-block thumbnail border" href="<?php echo esc_url( $size_url ); ?>" target="_blank">
						<img style="max-width:150px;max-height:150px;" src="<?php echo esc_url( $size_url ); ?>" alt="image-subsize" class="mx-auto">
					</a>
					<!-- Image Details -->
					<div class="subsize-image-details">
						<div class="d-flex justify-content-center flex-lg-row mx-auto flex-md-column flex-wrap">
							<span class="badge bg-light text-dark shadow-sm p-3 fs-6 text-left"><?php esc_html_e( 'Dimensions', 'image-type-converter' ); ?> : <?php echo esc_html( $size_arr['width'] . 'x' . $size_arr['height'] ); ?></span>
							<span class="badge bg-light text-dark shadow-sm p-3 fs-6 text-left">
								<span><?php esc_html_e( 'Size', 'image-type-converter' ); ?> : <?php echo esc_html( size_format( ! empty( $size_arr['filesize'] ) ? $size_arr['filesize'] : wp_filesize( $size_path ), 1 ) ); ?></span>
							</span>
						</div>
						<!-- Optimization history -->
						<?php
						if ( ! empty( $optimization_history ) ) :
							?>
							<div class="subsize-conversion-history d-flex flex-column justify-content-center align-items-center border my-4 p-3">
								<h6><?php esc_html_e( 'Optimization history', 'image-type-converter' ); ?><?php self::$core->new_keyword( 'New', false ); ?></h6>
								<div class="d-flex justify-content-start flex-lg-row mx-auto flex-md-column flex-wrap">
									<span class="mt-1 badge bg-light text-dark shadow-sm p-3 fs-6 text-start w-100 flex-grow-1"><?php esc_html_e( 'Old Size', 'image-type-converter' ); ?> : <?php echo esc_html( size_format( $optimization_history['old_size'], 1 ) ); ?></span>
									<span class="mt-1 badge bg-light text-dark shadow-sm p-3 fs-6 text-start w-100 flex-grow-1 d-flex justify-content-between">
										<?php esc_html_e( 'New Size', 'image-type-converter' ); ?> : <?php echo esc_html( size_format( $optimization_history['new_size'], 1 ) ); ?>
										<span style="background: #<?php echo esc_attr( $optimization_history['new_size'] > $optimization_history['old_size'] ? 'f76e6e' : '00ffb8' ); ?>;padding: 5px;border-radius: 5px;font-weight: bolder;"><?php echo esc_html( '%' . number_format( ( abs( $optimization_history['old_size'] - $optimization_history['new_size'] ) / $optimization_history['old_size'] ) * 100, 1 ) ); ?><?php echo esc_attr( $optimization_history['new_size'] > $optimization_history['old_size'] ? '⬆' : '⬇' ); ?></span>
									</span>
								</div>
							</div>
						<?php endif; ?>
						 <!-- Conversion history -->
						<?php
						if ( ! empty( $conversion_history ) ) :
							?>
							<div class="subsize-conversion-history d-flex flex-column justify-content-center align-items-center border my-4 p-3">
								<h6><?php esc_html_e( 'Conversion history', 'image-type-converter' ); ?></h6>
								<div class="d-flex justify-content-start flex-lg-row mx-auto flex-md-column flex-wrap">
									<span class="mt-1 badge bg-light text-dark shadow-sm p-3 fs-6 text-start w-100 flex-grow-1"><?php esc_html_e( 'Old Size', 'image-type-converter' ); ?> : <?php echo esc_html( size_format( $conversion_history['old_size'], 1 ) ); ?></span>
									<span class="mt-1 badge bg-light text-dark shadow-sm p-3 fs-6 text-start w-100 flex-grow-1 d-flex justify-content-between">
										<?php esc_html_e( 'New Size', 'image-type-converter' ); ?> : <?php echo esc_html( size_format( $conversion_history['new_size'], 1 ) ); ?>
										<span style="background: #<?php echo esc_attr( $conversion_history['new_size'] > $conversion_history['old_size'] ? 'f76e6e' : '00ffb8' ); ?>;padding: 5px;border-radius: 5px;font-weight: bolder;"><?php echo esc_html( '%' . number_format( ( abs( $conversion_history['old_size'] - $conversion_history['new_size'] ) / $conversion_history['old_size'] ) * 100, 1 ) ); ?><?php echo esc_attr( $conversion_history['new_size'] > $conversion_history['old_size'] ? '⬆' : '⬇' ); ?></span>
									</span>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

}
