<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\MetaBoxesBase;

defined( 'ABSPATH' ) || exit;

use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\ImageOptimizationHistoryMetaBox;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\ImgConversionHistoryMetaBox;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\ImgConverterMetaBox;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\ImgOptimizerMetaBox;
use GPLSCore\GPLS_PLUGIN_WICOR\MetaBoxes\ImgSubsizesMetaBox;

/**
 * Setup Metaboxes.
 *
 * @return void
 */
function setup_metaboxes() {
    ImgConverterMetaBox::init();
    ImgConversionHistoryMetaBox::init();
    ImgOptimizerMetaBox::init();
    ImageOptimizationHistoryMetaBox::init();
    ImgSubsizesMetaBox::init();
}
