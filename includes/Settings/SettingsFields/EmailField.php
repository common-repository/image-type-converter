<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Settings\SettingsFields;

use GPLSCore\GPLS_PLUGIN_WICOR\Settings\SettingsFields\FieldBase;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Field.
 */
class EmailField extends TextField {


	/**
	 * Sanitize Email Field.
	 *
	 * @param string $value
	 * @return string
	 */
	public function sanitize_field( $value ) {
		return sanitize_email( $value );
	}
}
