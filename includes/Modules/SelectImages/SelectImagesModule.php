<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Modules\SelectImages;

use GPLSCore\GPLS_PLUGIN_WICOR\Base;

/**
 * Select Images Module
 *
 * Select Images Direclty or by posts.
 */
class SelectImagesModule extends Base {

	/**
	 * Distinct Date Options For All CPTs.  [ cpt_slug ] => array( array( month => , year => , post_type => ) )
	 *
	 * @var array
	 */
	private $cpts_date_options = array();

	/**
	 * Distinct Date Options For All CPTs.  [ cpt_slug ] => array( array( author_obj => , post_type => ) )
	 *
	 * @var array
	 */
	private $cpts_author_options = array();

	/**
	 * CPTs Taxonomies and Terms Array Mapping.  [ cpt_slug ] => array of taxonomies names => array of terms objects
	 *
	 * @var array
	 */
	private $cpts_taxonomies_terms = array();

	/**
	 * Step Offset.
	 *
	 * @var integer
	 */
	private $step_offset = 3;

	/**
	 * Constrcutor.
	 *
	 * @param array  $plugin_info
	 * @param object $core
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Enqueue Assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		if ( ! wp_script_is( 'jquery' ) ) {
			wp_enqueue_script( 'jquery' );
		}

		wp_enqueue_media();

		if ( ! wp_style_is( 'wp-jquery-ui-dialo' ) ) {
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
		}

		if ( ! wp_script_is( 'jquery-ui-dialog' ) ) {
			wp_enqueue_script( 'jquery-ui-dialog' );
		}

		wp_enqueue_style( self::$plugin_info['name'] . '-select-images-styles', self::$plugin_info['url'] . 'includes/Modules/SelectImages/assets/dist/css/select-images-styles.min.css', array(), self::$plugin_info['version'], 'all' );
		wp_enqueue_script( self::$plugin_info['name'] . '-select-images-actions', self::$plugin_info['url'] . 'includes/Modules/SelectImages/assets/dist/js/select-images-actions.min.js', array( 'jquery' ), self::$plugin_info['version'], true );
		wp_localize_script(
			self::$plugin_info['name'] . '-select-images-actions',
			str_replace( '-', '_', self::$plugin_info['name'] . '_localize_vars' ),
			array(
				'offsetLength'            => $this->step_offset,
				'prefix'                  => self::$plugin_info['name'],
				'classes_prefix'          => self::$plugin_info['classes_prefix'],
				'nonce'                   => wp_create_nonce( self::$plugin_info['name'] . '-ajax-nonce' ),
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'labels'                  => array(
					'select_images'        => esc_html__( 'Select images', 'image-type-converter' ),
					'preview_image'        => esc_html__( 'Preview Image', 'image-type-converter' ),
					'select_preview_image' => esc_html__( 'Select Preview Image', 'image-type-converter' ),
					'search_term'          => esc_html__( 'Search Term', 'image-type-converter' ),
				),
			)
		);
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
	}

	/**
	 * Frontend Template.
	 *
	 * @return void
	 */
	public function template() {
		load_template(
			self::$plugin_info['path'] . 'includes/Modules/SelectImages/templates/main-template.php',
			false,
			array(
				'core'        => self::$core,
				'plugin_info' => self::$plugin_info,
				'module'      => $this,
			)
		);
	}

	/**
	 * Loader HTML Code.
	 *
	 * @return void
	 */
	public function loader_html() {
		?>
		<div class="loader w-100 h-100 position-absolute" style="display:none;">
			<div class="text-white wrapper text-center position-absolute d-block w-100 ">
				<img src="<?php echo esc_url_raw( admin_url( 'images/spinner-2x.gif' ) ); ?>"  />
			</div>
			<div class="overlay position-absolute d-block w-100 h-100"></div>
		</div>
		<?php
	}
}
