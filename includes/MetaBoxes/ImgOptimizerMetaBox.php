<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes;

defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\ImageOptimizer;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\NoticeUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\MetaBoxesBase\MetaBox;

/**
 * Image Optimizer Metabox.
 */
class ImgOptimizerMetaBox extends MetaBox {

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
		$this->id           = self::$plugin_info['name'] . '-img-optimizer-metabox';
		$this->metabox_args = array(
			'id'       => $this->id,
			'title'    => esc_html__( 'Image Optimizer [GrandPlugins]', 'image-type-converter' ) . self::$core->new_keyword(),
			'context'  => 'side',
			'priority' => 'high',
			'template' => 'img-optimizer-metabox-template.php',
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
	 * Hooks.
	 *
	 * @return void
	 */
	protected function hooks() {
		add_action( 'wp_ajax_' . self::$plugin_info['name'] . '-optimize-img', array( $this, 'ajax_optimize_img' ) );
	}

	/**
	 * Custom Condition before applying the metabox.
	 *
	 * @param string   $post_type
	 * @param \WP_Post $post
	 * @return bool
	 */
	protected function custom_condition( $post_type, $post ) {
		return ( 'attachment' === $post_type && wp_attachment_is_image( $post ) && ( 'image/avif' !== $post->post_mime_type ) );
	}

	/**
	 * AJAX Optimize Image Type.
	 *
	 * @return void
	 */
	public function ajax_optimize_img() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::$plugin_info['name'] . '-img-converter-metabox' ) ) {
			self::expired_response();
		}

		$attachment_id = ! empty( $_POST['attachmentID'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['attachmentID'] ) ) ) : 0;
		$quality       = ! empty( $_POST['imgQuality'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['imgQuality'] ) ) ) : 85;

		if ( empty( $attachment_id ) ) {
			self::invalid_submitted_data_response();
		}

		$attachment_post = get_post( $attachment_id );
		if ( ! $attachment_post || ! is_a( $attachment_post, '\WP_Post' ) ) {
			self::invalid_submitted_data_response();
		}

		if ( ! wp_attachment_is_image( $attachment_post ) ) {
			self::invalid_submitted_data_response();
		}

		$image_details = self::get_image_file_details( $attachment_id );
		if ( is_wp_error( $image_details ) ) {
			wp_send_json_error(
				array(
					'status'  => 'error',
					'message' => self::error_message( esc_html__( 'Failed to fetch image details', 'image-type-converter' ) ),
				),
				400
			);
		}

		if ( ! ImageOptimizer::is_img_type_optimizable( $image_details['ext'] ) ) {
			wp_send_json_error(
				array(
					'status'  => 'error',
					'message' => self::error_message( esc_html__( 'Image type optimizer is not installed', 'image-type-converter' ) ),
				),
				400
			);
		}

		$options = array( 'quality' => $quality );
        $result  = ImageOptimizer::full_optimize( $attachment_id, $options, 'single' );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
				array(
					'status'  => 'error',
					'message' => self::error_message( $result->get_error_message() ),
				),
				400
			);
        }

		self::ajax_response(
			esc_html__( 'Image has been optimized successfully', 'image-type-converter' ),
			'success',
			200,
			'img-optimizer-metabox'
		);
	}
}
