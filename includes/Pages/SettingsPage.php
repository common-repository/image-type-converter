<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Pages;

use GPLSCore\GPLS_PLUGIN_WICOR\Pages\PagesBase\AdminPage;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\GeneralUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Settings\MainSettings;
use GPLSCore\GPLS_PLUGIN_WICOR\Modules\SelectImages\SelectImagesModule;
/**
 * Settings Page.
 */
class SettingsPage extends AdminPage {
	use GeneralUtilsTrait, ImgUtilsTrait;

	/**
	 * Singleton Instance.
	 *
	 * @var self
	 */
	protected static $instance = null;


	/**
	 * Select Images Modules.
	 *
	 * @var SelectImagesModule.
	 */
	public $select_images_module;

	/**
	 * Page Hooks.
	 *
	 * @return void
	 */
	protected function hooks() {
		add_action( self::$plugin_info['name'] . '-admin-page-assets', array( $this->select_images_module, 'enqueue_assets' ) );
		add_action( 'plugin_action_links_' . self::$plugin_info['basename'], array( $this, 'settings_link' ), 5, 1 );
	}

	/**
	 * Settings Link.
	 *
	 * @param array $links Plugin Row Links.
	 * @return array
	 */
	public function settings_link( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'upload.php?page=' . self::$plugin_info['name'] . '-settings&tab=status' ) ) . '">' . esc_html__( 'Settings', 'image-type-converter' ) . '</a>';
		return $links;
	}

	/**
	 * Prepare Page.
	 *
	 * @return void
	 */
	protected function prepare() {
		$this->page_props = array(
			'menu_title'  => esc_html__( 'Image Converter & Optimizer [GrandPlugins]', 'image-type-converter' ),
			'page_title'  => '',
			'parent_slug' => 'upload.php',
			'menu_slug'   => self::$plugin_info['name'] . '-settings',
			'tab_key'     => 'tab',
		);

		$this->tabs = array(
			'status'       => array(
				'title'    => esc_html__( 'Status', 'image-type-converter' ),
				'default'  => true,
				'template' => 'status-template.php',
			),
			'auto_convert' => array(
				'tab_title' => esc_html__( 'Auto Convert', 'image-type-converter' ) . self::$core->new_keyword( 'Pro' ),
				'title'     => esc_html__( 'Convert images automatically on upload', 'image-type-converter' ),
				'template'  => 'auto-convert-template.php',
			),
			'bulk_convert' => array(
				'tab_title'  => esc_html__( 'Bulk Convert', 'image-type-converter' ) . self::$core->new_keyword( 'Pro' ),
				'title'    => esc_html__( 'Convert images type in bulk', 'image-type-converter' ),
				'template' => 'bulk-convert-template.php',
			),
			'auto_optimize' => array(
				'tab_title' => esc_html__( 'Auto Optimize', 'image-type-converter' ) . self::$core->new_keyword() . self::$core->new_keyword( 'Pro' ),
				'title'     => esc_html__( 'Optimize images automatically on upload', 'image-type-converter' ) . self::$core->pro_btn( '', 'Pro', '', '', true ),
				'template'  => 'auto-optimize-template.php',
			),
			'bulk_optimize' => array(
				'tab_title' => esc_html__( 'Bulk Optimize', 'image-type-converter' ) . self::$core->new_keyword() . self::$core->new_keyword( 'Pro' ),
				'title'     => esc_html__( 'Optimize images in bulk', 'image-type-converter' ),
				'template'  => 'bulk-optimize-template.php',
			),
		);

		$this->settings             = MainSettings::init();
		$this->select_images_module = new SelectImagesModule();
	}

	/**
	 * Set Assets.
	 *
	 * @return void
	 */
	protected function set_assets() {
		$this->assets = array(
			array(
				'type'        => 'css',
				'handle'      => self::$plugin_info['name'] . '-select2-css',
				'url'         => self::$plugin_info['url'] . 'includes/Core/assets/libs/select2.min.css',
				'conditional' => array(
					'tab' => 'bulk_convert',
				),
			),
			array(
				'type'        => 'js',
				'handle'      => self::$plugin_info['name'] . '-select2-actions',
				'url'         => self::$plugin_info['url'] . 'includes/Core/assets/libs/select2.full.min.js',
				'conditional' => array(
					'tab' => 'bulk_convert',
				),
			),
		);
	}
}
