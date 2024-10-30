<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Settings\Fields;

use GPLSCore\GPLS_PLUGIN_WICOR\ImageConverter;
use GPLSCore\GPLS_PLUGIN_WICOR\ImageOptimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Setup Settings Fields.
 *
 * @return array
 */
function setup_main_settings_fields( $core, $plugin_info ) {
	return array(
		'auto_convert' => array(
			'main_conversion_type' => array(
				'settings_list' => array(
					'status'              => array(
						'input_label'     => esc_html__( 'Enable', 'image-type-converter' ),
						'value'           => 'no',
						'type'            => 'checkbox',
						'input_suffix'    => esc_html__( 'Enable Auto Conversion', 'image-type-converter' ) . $core->pro_btn( '', 'Pro', '', '', true ),
						'wrapper_classes' => 'bg-light shadow-sm',
						'attrs'    => array(
							'disabled' => 'disabled',
						),
					),
					'conversion_type'     => array(
						'input_label'     => esc_html__( 'Conversion Type', 'image-type-converter' ),
						'type'            => 'radio',
						'value'           => 'no',
						'classes'         => $plugin_info['classes_prefix'] . '-general-conversion-type',
						'wrapper_classes' => 'bg-light shadow-sm',
						'options'         => array(
							array(
								'input_suffix' => esc_html__( 'General Conversion', 'image-type-converter' ),
								'input_footer' => esc_html__( 'Convert all images to single image type', 'image-type-converter' ),
								'value'        => 1,
							),
							array(
								'input_suffix' => esc_html__( 'Custom Conversion', 'image-type-converter' ),
								'input_footer' => esc_html__( 'Make custom conversions', 'image-type-converter' ),
								'value'        => 2,
							),
						),
						'attrs'    => array(
							'disabled' => 'disabled',
						),
					),
					'general_target_type' => array(
						'type'            => 'select',
						'input_footer'    => esc_html__( 'Target Type to convert all uploaded images to', 'image-type-converter' ),
						'options'         => ImageConverter::get_supported_types( true ),
						'value'           => 'webp',
						'wrapper_classes' => 'bg-light shadow-sm',
						'classes'         => 'bg-light border shadow-sm',
						'collapse'        => array(
							'collapse_source' => $plugin_info['classes_prefix'] . '-general-conversion-type',
							'collapse_value'  => 1,
						),
						'attrs'    => array(
							'disabled' => 'disabled',
						),
					),
				),
			),
		),
		'auto_optimize' => array(
			'main_optimization_type' => array(
				'settings_list' => array(
					'optimization_status'               => array(
						'input_label'     => esc_html__( 'Enable', 'image-type-converter' ),
						'value'           => 'no',
						'type'            => 'checkbox',
						'input_suffix'    => esc_html__( 'Enable Auto Optimization', 'image-type-converter' ),
						'wrapper_classes' => 'bg-light shadow-sm',
						'attrs'    => array(
							'disabled' => 'disabled',
						),
					),
					'optimization_type'    => array(
						'input_label'     => esc_html__( 'Optimization Type', 'image-type-converter' ),
						'type'            => 'radio',
						'value'           => 'no',
						'classes'         => $plugin_info['classes_prefix'] . '-general-optimization-type',
						'wrapper_classes' => 'bg-light shadow-sm',
						'options'         => array(
							array(
								'input_suffix' => esc_html__( 'General Optimization', 'image-type-converter' ),
								'input_footer' => esc_html__( 'Optimize all images', 'image-type-converter' ),
								'value'        => 1,
							),
							array(
								'input_suffix' => esc_html__( 'Custom Optimization', 'image-type-converter' ),
								'input_footer' => esc_html__( 'Make optimization to specific image types', 'image-type-converter' ),
								'value'        => 2,
							),
						),
						'attrs'    => array(
							'disabled' => 'disabled',
						),
					),
					'quality'              => array(
						'key'          => 'quality',
						'type'         => 'number',
						'input_footer' => esc_html__( 'Quality', 'image-type-converter' ),
						'value'        => 85,
						'attrs'        => array(
							'min' => 0,
							'max' => 100,
							'disabled' => 'disabled',
						),
					),
				),
			),
		),
	);
}
