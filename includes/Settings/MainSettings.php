<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Settings;

use GPLSCore\GPLS_PLUGIN_WICOR\Settings\SettingsBase\Settings;
use function GPLSCore\GPLS_PLUGIN_WICOR\Settings\Fields\setup_main_settings_fields;

/**
 * Main Settings CLass.
 */
final class MainSettings extends Settings {

	/**
	 * Singleton Instance.
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * Prepare Settings.
	 *
	 * @return void
	 */
	protected function prepare() {
		  $this->id     = self::$plugin_info['name'] . '-countdown-timer-cpt-settings';
		  $this->fields = setup_main_settings_fields( self::$core, self::$plugin_info );
	}

	/**
	 * Settings Hooks.
	 *
	 * @return void
	 */
	protected function hooks() {

	}
}
