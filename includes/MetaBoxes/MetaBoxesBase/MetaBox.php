<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\MetaBoxesBase;

defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\Utils\GeneralUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Base;

/**
 * MetaBox Class.
 */
abstract class MetaBox extends Base {

	use GeneralUtilsTrait;

	/**
	 * Metabox Arguments
	 *
	 * @var array
	 */
	protected $metabox_args = array();

	/**
	 * Metabox Assets.
	 *
	 * @var array
	 */
	protected $assets = array();

	/**
	 * Admin Page Constructor.
	 *
	 */
	protected function __construct() {
		$this->setup();
		$this->base_hooks();
	}

	/**
	 * Initialize Page.
	 *
	 */
	public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Setup
	 *
	 * @return void
	 */
	private function setup() {
		$this->set_metaboxes_args();
		$this->metabox_args = array_merge(
			array(
				'title'         => esc_html__( 'Metabox Title' ),
				'screen'        => static::get_screens(),
				'context'       => 'advanced', // 'normal', 'side', 'advanced'.
				'priority'      => 'default', // 'high', 'core', 'default', 'low'.
				'callback_args' => null,
			),
			$this->metabox_args
		);
	}

	 /**
	  * Hooks.
	  *
	  * @return void
	  */
	private function base_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ), 100, 2 );

		if ( ! empty( $this->assets ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_assets' ) );
		}

		if ( method_exists( $this, 'hooks' ) ) {
			$this->hooks();
		}
	}

	/**
	 * Register Metabox.
	 *
	 * @return void
	 */
	public function register_metabox( $post_type, $post ) {
		if ( empty( $this->metabox_args['id'] ) || empty( $this->metabox_args['screen'] ) ) {
			return;
		}

		if ( method_exists( $this, 'custom_condition' ) && ! $this->custom_condition( $post_type, $post ) ) {
			return;
		}

		add_meta_box(
			$this->metabox_args['id'],
			$this->metabox_args['title'],
			array( $this, 'metabox_html' ),
			$this->metabox_args['screen'],
			$this->metabox_args['context'],
			$this->metabox_args['priority'],
			$this->metabox_args['callback_args'],
		);
	}

	/**
	 * Add Metabox Assets.
	 *
	 * @return void
	 */
	public function metabox_assets() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}
		$this->handle_enqueue_assets( $this->assets );
	}

	/**
	 * Check if current CPT Page.
	 *
	 * @return boolean
	 */
	protected function is_cpt_page() {
		$screen = get_current_screen();
		return ( is_object( $screen ) && ! is_wp_error( $screen ) && ! empty( $screen->post_type ) && ( 'post' === $screen->base ) && ( in_array( $screen->post_type, static::get_screens() ) ) );
	}

	/**
	 * Set Metaboxes Arguments.
	 *
	 * @return void
	 */
	abstract protected function set_metaboxes_args();

	/**
	 * Get Metabox CPT Name.
	 *
	 * @return array
	 */
	abstract public static function get_screens();

	/**
	 * MetaBox HTML.
	 *
	 * @return void
	 */
	public function metabox_html( $post ) {
		if ( empty( $this->metabox_args['template'] ) ) {
			return;
		}

		$metabox_template_args = array(
			'post'        => $post,
			'id'          => $post->ID,
			'plugin_info' => self::$plugin_info,
			'core'        => self::$core,
			'metabox'     => $this,
		);
		load_template(
			self::$plugin_info['path'] . 'includes/Templates/metaboxes/' . $this->metabox_args['template'],
			false,
			$metabox_template_args
		);
	}
}
