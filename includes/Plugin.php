<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR;

defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\Base;
use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\ImageOptimizer;
use GPLSCore\GPLS_PLUGIN_WICOR\QuickImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\TypesSupport;

use function GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\MetaBoxesBase\setup_metaboxes;
use function GPLSCore\GPLS_PLUGIN_WICOR\Pages\PagesBase\setup_pages;

/**
 * Plugin Class for Activation - Deactivation - Uninstall.
 */
class Plugin extends Base {

	/**
	 * Main Class Load.
	 *
	 * @return void
	 */
	public static function load() {
		setup_metaboxes();
		setup_pages();
		ImageConverter::init();
		ImageOptimizer::init();
		TypesSupport::init();
		QuickImageConverter::init();
	}

	/**
	 * Plugin is activated.
	 *
	 * @return void
	 */
	public static function activated() {
		// Activation Custom Code here...
	}

	/**
	 * Plugin is Deactivated.
	 *
	 * @return void
	 */
	public static function deactivated() {
		// Deactivation Custom Code here...
	}

	/**
	 * Plugin is Uninstalled.
	 *
	 * @return void
	 */
	public static function uninstalled() {
		// Uninstall Custom Code here...
	}

	/**
	 * Is Plugin Active.
	 *
	 * @param string $plugin_basename
	 * @return boolean
	 */
	public static function is_plugin_active( $plugin_basename ) {
		require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( $plugin_basename );
	}
}
