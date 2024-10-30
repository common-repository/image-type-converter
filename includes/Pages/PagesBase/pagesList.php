<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Pages\PagesBase;

use GPLSCore\GPLS_PLUGIN_WICOR\Pages\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Pages.
 */
function setup_pages() {
	SettingsPage::init();
}
