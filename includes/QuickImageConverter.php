<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR;

defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\Utils\NoticeUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\GeneralUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\ImgConverterMetaBox;

/**
 * Quick Image Converter Class
 *
 * @since 1.0.4
 */
class QuickImageConverter extends Base {

	use GeneralUtilsTrait;
	use NoticeUtilsTrait;
	use ImgUtilsTrait;

	/**
	 * Instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Converter Column Name.
	 *
	 * @var string
	 */
	private $converter_column_name;

	/**
	 * Singular Init.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->setup();
		$this->hooks();
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	private function setup() {
		$this->converter_column_name = self::$plugin_info['prefix'] . '-converter-column';
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	private function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'manage_media_columns', array( $this, 'filter_media_table_columns' ), PHP_INT_MAX, 1 );
		add_action( 'manage_media_custom_column', array( $this, 'media_converter_column_content' ), PHP_INT_MAX, 2 );
		add_action( 'wp_ajax_' . self::$plugin_info['prefix'] . '-quick-converter-action', array( $this, 'ajax_quick_converter' ) );
		add_action( 'admin_footer', array( $this, 'quick_converter_popup' ) );
	}

	/**
	 * Quick Converter popup.
	 *
	 * @return void
	 */
	public function quick_converter_popup() {
		global $current_screen;
		if ( ! is_a( $current_screen, '\WP_Screen' ) || 'upload' !== $current_screen->base ) {
			return;
		}
		?>
		<div class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-quick-converter-popup' ); ?>" style="display:none;">
			<div class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-quick-img-converter-wrapper' ); ?>" style="padding:15px;overflow:hidden;display:flex;flex-direction:column;align-items:center;position:relative;">
				<!-- Convert to -->
				<div class="convert-to-wrapper" style="border: 1px solid #EEE;width:100%;display: flex;flex-direction: row;align-items: center;justify-content: space-between;padding: 10px;margin: 5px;">
					<span class="my-2" style="width:200px;text-align:left;"><?php esc_html_e( 'Convert To:', 'image-type-converter' ); ?></span>
					<?php ImgConverterMetaBox::convert_to_select( 0, false ); ?>
				</div>
				<!-- Quality -->
				<div class="quality-wrapper" style="border: 1px solid #EEE;width:100%;display: flex;flex-direction: row;align-items: center;justify-content: space-between;padding: 10px;margin: 5px;">
					<span class="my-2" style="width:200px;text-align:left;"><?php esc_html_e( 'Quality:', 'image-type-converter' ); ?></span>
					<input type="number" min="1" max="100" value="85" class="my-2 <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-new-img-quality' ); ?>" name="<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-new-img-quality' ); ?>">
				</div>
				<!-- Keep Ext -->
				<div class="keep-ext-wrapper" style="border: 1px solid #EEE;width:100%;display: flex;flex-direction: row;align-items: center;justify-content: space-between;padding: 10px;margin: 5px;">
					<span class="my-2" style="width:200px;text-align:left;"><?php esc_html_e( 'Keep file extension:', 'image-type-converter' ); ?> <?php echo wp_kses_post( self::$core->pro_btn( '', 'Pro', '', '', true ) ); ?></span>
					<input disabled type="checkbox" class="my-2 <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-keep-img-extension' ); ?>" >
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * AJAX Quick Converter.
	 *
	 * @return void
	 */
	public function ajax_quick_converter() {
		self::ajax_request_handle( self::$plugin_info['prefix'] . '-quick-converter-nonce' );

		$image_id = ! empty( $_POST['imageID'] ) ? absint( sanitize_text_field( $_POST['imageID'] ) ) : 0;
		if ( ! wp_attachment_is_image( $image_id ) ) {
			self::ajax_error_response( 'Invalid image ID' );
		}

		$keep_ext        = ( ! empty( $_POST['keepExt'] ) && filter_var( sanitize_text_field( wp_unslash( $_POST['keepExt'] ) ), \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE ) ) ? true : false;
		$type            = ! empty( $_POST['targetType'] ) ? sanitize_text_field( wp_unslash( $_POST['targetType'] ) ) : '';
		$supported_types = ImageConverter::get_supported_types();

		if ( ! in_array( $type, $supported_types ) ) {
			self::ajax_error_response( esc_html__( 'Invalid image type!', 'image-type-converter' ) );
		}

		$quality = ! empty( $_POST['quality'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['quality'] ) ) ) : 85;
		$options = array(
			'keep_ext' => $keep_ext,
			'quality'  => $quality,
		);

		$result = ImageConverter::convert_attachment( $image_id, $type, 'quick', $options );
		if ( is_wp_error( $result ) ) {
			self::ajax_error_response( $result->get_error_message() );
		}

		self::ajax_response(
			esc_html__( 'Image type has been converted successfully', 'image-type-converter' ),
			'success',
			200,
			'quick-converter',
			array(
				'new_row' => $this->get_new_table_row( $image_id ),
			),
			array(),
			true
		);
	}

	/**
	 * Get New Table Row.
	 *
	 * @param int $image_id
	 * @return string
	 */
	private function get_new_table_row( $image_id ) {
		require_once \ABSPATH . 'wp-admin/includes/class-wp-media-list-table.php';
		$media_table = new \WP_Media_List_Table();
		$post        = get_post( $image_id );
		$post_owner  = ( get_current_user_id() === (int) $post->post_author ) ? 'self' : 'other';
		setup_postdata( $post );
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		ob_start();
		?>
		<tr id="post-<?php echo esc_attr( $post->ID ); ?>" class="<?php echo esc_attr( trim( ' author-' . $post_owner . ' status-' . $post->post_status ) ); ?>">
			<?php $media_table->single_row_columns( $post ); ?>
		</tr>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Enqueue Assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		global $current_screen;
		if ( ! is_a( $current_screen, '\WP_Screen' ) || 'upload' !== $current_screen->base ) {
			return;
		}
        wp_enqueue_style( self::$plugin_info['prefix'] . '-notices-css', self::$plugin_info['url'] . 'assets/libs/notice.min.css', array(), self::$plugin_info['version'], 'all' );
		wp_enqueue_script( self::$plugin_info['prefix'] . '-quick-img-converter', self::$plugin_info['url'] . 'assets/dist/js/admin/quick-image-converter.min.js', array( 'jquery' ), self::$plugin_info['version'], true );
		wp_localize_script(
			self::$plugin_info['prefix'] . '-quick-img-converter',
			str_replace( '-', '_', self::$plugin_info['prefix'] . '-quick-converter-localize-vars' ),
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( self::$plugin_info['prefix'] . '-quick-converter-nonce' ),
				'prefix'               => self::$plugin_info['prefix'],
				'quickConverterAction' => self::$plugin_info['prefix'] . '-quick-converter-action',
				'labels'               => array(
					'convertBtn' => esc_html__( 'Convert', 'image-type-converter' ),
				),
			)
		);
	}

	/**
	 * Filter Media Table COlumns.
	 *
	 * @param array   $columns
	 * @param boolean $is_detached
	 * @return array
	 */
	public function filter_media_table_columns( $columns ) {
		$columns[ $this->converter_column_name ] = esc_html__( 'Quick Image type converter', 'image-type-converter' );
		return $columns;
	}

	/**
	 * Get Current Image Type.
	 *
	 * @param int $image_id
	 * @return string
	 */
	private function get_current_image_type( $image_id, $conversion_check = false ) {
		$image_type = ImageConverter::get_image_type( $image_id, $conversion_check );
		if ( is_wp_error( $image_type ) ) {
			return '——';
		}
		return strtoupper( $image_type );
	}

	/**
	 * Media Converter Column Content.
	 *
	 * @param string $column_name
	 * @param int    $attachment_id
	 * @return void
	 */
	public function media_converter_column_content( $column_name, $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) || ( $this->converter_column_name !== $column_name ) ) {
			return;
		}
		?>
		<div class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-quick-img-converter-box' ); ?>" style="display:flex;flex-direction:column;justify-content:center;">
			<div class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-quick-img-converter-box-wrapper' ); ?>" style="overflow:hidden;display:flex;flex-direction:row;justify-content:space-around;align-items:center;position:relative;">
				<?php self::loader_html( self::$plugin_info['prefix'] . '-quick-converter' ); ?>
				<!-- Current Type -->
				<div class="current-type-wrapper" style="display:flex;flex-direction:column;align-items:center; border:1px solid #EEE; margin-right:10px;padding:9px;">
					<span class="my-2" style="margin-bottom:10px;"><?php esc_html_e( 'Image type:', 'image-type-converter' ); ?></span>
					<span><strong style="background:#000;color:#FFF;padding:6px;"><?php echo esc_html( $this->get_current_image_type( $attachment_id, true ) ); ?></strong></span>
				</div>
				<!-- Actions -->
				<div class="img-actions" style="text-align:center;display:flex;flex-direction:column;">
					<?php
					$img_details = self::get_image_file_details( $attachment_id );
					if ( is_array( $img_details ) ) {
						?>
						<button data-image_id="<?php echo esc_attr( $attachment_id ); ?>" type="submit" class="button button-primary <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-quick-converter-submit' ); ?>"><?php esc_html_e( 'Convert' ); ?></button>
						<?php
						ImageOptimizer::optimize_btn( $attachment_id, $img_details['ext'] );
					}
					?>
				</div>
			</div>
			<div class="notices-wrapper" style="margin-top:5px;">

			</div>
		</div>
		<?php
	}
}
