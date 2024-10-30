<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes;

defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\NoticeUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\MetaBoxesBase\MetaBox;

/**
 * Image Converter Metabox.
 */
class ImgConverterMetaBox extends MetaBox {

	use NoticeUtilsTrait;
	use ImgUtilsTrait;

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
		$this->id           = self::$plugin_info['name'] . '-img-converter-metabox';
		$this->metabox_args = array(
			'id'       => $this->id,
			'title'    => esc_html__( 'Image Type Converter [GrandPlugins]', 'image-type-converter' ),
			'context'  => 'side',
			'priority' => 'high',
			'template' => 'img-converter-metabox-template.php',
		);
		$this->assets       = array(
			array(
				'handle' => $this->id . '-bootstrap-css',
				'type'   => 'css',
				'url'    => self::$core->core_assets_lib( 'bootstrap', 'css' ),
			),
			array(
				'handle' => $this->id . '-css',
				'type'   => 'css',
				'url'    => self::$plugin_info['url'] . 'assets/libs/notice.min.css',
			),
			array(
				'handle'    => $this->id . '-js',
				'type'      => 'js',
				'url'       => self::$plugin_info['url'] . 'assets/dist/js/admin/img-converter-metabox.min.js',
				'localized' => array(
					'name' => str_replace( '-', '_', self::$plugin_info['name'] ),
					'data' => array(
						'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
						'prefix'         => self::$plugin_info['classes_prefix'],
						'convertAction'  => self::$plugin_info['name'] . '-convert-img-type',
						'optimizeAction' => self::$plugin_info['name'] . '-optimize-img',
						'nonce'          => wp_create_nonce( $this->id ),
						'labels'         => array(
							'confirmConvert'  => esc_html__( 'You are about to convert the image type, proceed?', 'image-type-converter' ),
							'confirmOptimize' => esc_html__( 'The image will be optimized, proceed?', 'image-type-converter' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Convert To Select field.
	 *
	 * @return void
	 */
	public static function convert_to_select( $image_id, $check_for_ext = true ) {
		?>
		<select class="my-2 <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-new-img-type' ); ?>" name="<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-new-img-type' ); ?>">
			<?php
			$supported_types = ImageConverter::get_supported_types();
			if ( $check_for_ext ) {
				$img_details = ImageConverter::get_image_file_details( $image_id );
			}

			foreach ( $supported_types as $img_type ) :
				if ( $check_for_ext && ( $img_details['ext'] === $img_type ) ) {
					continue;
				}
				?>
				<option value="<?php echo esc_attr( $img_type ); ?>"><?php echo esc_html( $img_type ); ?></option>
				<?php endforeach; ?>
		</select>
		<?php
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
	 * Hooks.
	 *
	 * @return void
	 */
	protected function hooks() {
		add_action( 'wp_ajax_' . self::$plugin_info['name'] . '-convert-img-type', array( $this, 'ajax_convert_img' ) );
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
	 * AJAX Convert Image Type.
	 *
	 * @return void
	 */
	public function ajax_convert_img() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->id ) ) {
			self::expired_response();
		}

		$img_type            = ! empty( $_POST['imgType'] ) ? sanitize_text_field( wp_unslash( $_POST['imgType'] ) ) : '';
		$attachment_id       = ! empty( $_POST['attachmentID'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['attachmentID'] ) ) ) : 0;
		$keep_file_extension = ( ! empty( $_POST['keepExt'] ) && filter_var( sanitize_text_field( wp_unslash( $_POST['keepExt'] ) ), \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE ) ) ? true : false;
		$quality             = ! empty( $_POST['imgQuality'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['imgQuality'] ) ) ) : 85;

		if ( empty( $img_type ) || empty( $attachment_id ) ) {
			self::invalid_submitted_data_response();
		}

		$attachment_post = get_post( $attachment_id );
		if ( ! $attachment_post || ! is_a( $attachment_post, '\WP_Post' ) ) {
			self::invalid_submitted_data_response();
		}

		if ( ! wp_attachment_is_image( $attachment_post ) ) {
			self::invalid_submitted_data_response();
		}

		if ( ! in_array( $img_type, ImageConverter::get_supported_types() ) ) {
			self::invalid_submitted_data_response();
		}

		$options = array(
			'quality'  => $quality,
			'keep_ext' => $keep_file_extension,
		);

		$result = ImageConverter::convert_attachment( $attachment_id, $img_type, 'single', $options );

		if ( is_wp_error( $result ) ) {
			self::ajax_response(
				$result->get_error_message(),
				'error',
				200,
				'img-converter-metabox'
			);
		}

		self::ajax_response(
			esc_html__( 'Image type has been converted successfully', 'image-type-converter' ),
			'success',
			200,
			'img-converter-metabox'
		);
	}
}
