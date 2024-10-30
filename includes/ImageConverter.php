<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR;

use GPLSCore\GPLS_PLUGIN_WICOR\Base;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;

/**
 * Images Converter Class.
 */
final class ImageConverter extends Base {

	use ImgUtilsTrait;

	/**
	 * Singular Instance.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Supported Image Types.
	 *
	 * @var array
	 */
	private static $img_types = array(
		'webp',
		'gif',
		'avif',
		'jpeg',
		'png',
	);

	/**
	 * Supported Image Types.
	 *
	 * @var array
	 */
	private static $image_extensions_mapping = array(
		'webp' => 'webp',
		'gif'  => 'gif',
		'avif' => 'avif',
		'jpeg' => 'jpg',
		'png'  => 'png',
	);

	/**
	 * Default Convert Options.
	 *
	 * @var array
	 */
	private static $default_convert_options = array(
		'quality'  => 85,
		'keep_ext' => false,
		'lib'      => 'imagick',
	);

	/**
	 * Conversion History Keys.
	 *
	 * @var array
	 */
	private static $conversion_history_keys = array(
		'date',
		'old_ext',
		'new_ext',
		'old_type',
		'new_type',
		'old_size',
		'new_size',
		'conversion_context',
		'keep_ext',
	);

	/**
	 * Conversion History Key.
	 *
	 * @var string
	 */
	private static $conversion_history_key;

	/**
	 * Init Function.
	 *
	 * @return mixed
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
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	private function setup() {
		self::$conversion_history_key = self::$plugin_info['classes_prefix'] . '-conversion-history';
	}

	/**
	 * Get Supported Types.
	 *
	 * @return array
	 */
	public static function get_supported_types( $for_select = false ) {
		$supported_types = array();
		foreach ( self::$img_types as $img_type ) {
			if ( ! self::is_type_supported( $img_type ) ) {
				continue;
			}
			$supported_types[] = $img_type;
		}

		if ( $for_select ) {
			$supported_types_for_select = array();
			foreach ( $supported_types as $supported_type ) {
				$supported_types_for_select[ $supported_type ] = strtoupper( $supported_type );
			}
			$supported_types = $supported_types_for_select;
		}
		return $supported_types;
	}

	/**
	 * Check if Image Type is supported.
	 *
	 * @param string $type
	 * @param string $lib_type [ gd - imagick ]
	 * @return bool
	 */
	public static function is_type_supported( $type ) {
		return ( self::is_type_supported_imagick( $type ) || self::is_type_supported_gd( $type ) );
	}

	/**
	 * Is Type Supported by GD.
	 *
	 * @param string $type
	 * @return boolean
	 */
	public static function is_type_supported_gd( $type ) {
		$gd_check = false;
		if ( self::is_gd_enabled() ) {
			$gd_info = gd_info();
			foreach ( $gd_info as $gd_key => $gd_value ) {
				if ( str_starts_with( strtolower( $gd_key ), strtolower( $type ) ) && $gd_value ) {
					return true;
				}
			}
		}
		return $gd_check;
	}

	/**
	 * Is Type Supported By Imagick.
	 *
	 * @param string $type
	 * @return boolean
	 */
	public static function is_type_supported_imagick( $type ) {
		$imagick_check = false;
		if ( self::is_imagick_enabled() ) {
			try {
				$imagick_check = (bool) @\Imagick::queryFormats( strtoupper( $type ) );
			} catch ( \Exception $e ) {
				// Just Proceed.
				$imagick_check = false;
			}
		}
		return $imagick_check;
	}

	/**
	 * Convert Attachment.
	 *
	 * @param int    $attachment_id
	 * @param string $new_type
	 * @param string $content
	 * @param array  $options
	 * @return true|\WP_Error
	 */
	public static function convert_attachment( $attachment_id, $new_type, $content, $options = array() ) {
		try {
			TypesSupport::allow_avif_support();
			$result = self::_convert_attachment( $attachment_id, $new_type, $content, $options );
			TypesSupport::clear_avif_support();
			return $result;
		} catch ( \Exception $e ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-convert-attachment-error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Convert Attachment Type.
	 *
	 * @param int    $attachment_id
	 * @param string $new_type
	 * @return true|\WP_Error
	 */
	private static function _convert_attachment( $attachment_id, $new_type, $context, $options = array() ) {
		$options     = array_merge( self::$default_convert_options, $options );
		$uploads     = wp_get_upload_dir();
		$img_details = self::get_image_file_details( $attachment_id );
		if ( is_wp_error( $img_details ) ) {
			return $img_details;
		}
		$is_animted         = self::is_img_animated( $img_details['path'] );
		$conversion_history = array(
			'date'               => current_time( 'timestamp' ),
			'old_type'           => $img_details['ext'],
			'new_type'           => $new_type,
			'old_ext'            => $img_details['file_ext'],
			'new_ext'            => $options['keep_ext'] ? $img_details['file_ext'] : self::$image_extensions_mapping[ $new_type ],
			'old_size'           => $img_details['specs']['size'],
			'conversion_context' => $context,
			'keep_ext'           => $options['keep_ext'],
		);

		if ( is_wp_error( $is_animted ) ) {
			return $is_animted;
		}

		if ( $is_animted ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-animated-img',
				esc_html__( 'Animated images are not supported yet.', 'image-type-converter' )
			);
		}

		if ( $img_details['ext'] === self::$image_extensions_mapping[ $new_type ] ) {
			return true;
		}


		$same_ext      = $img_details['file_ext'] === self::$image_extensions_mapping[ $new_type ];
		$img_metadata  = wp_get_attachment_metadata( $attachment_id );
		$main_img_path = trailingslashit( $uploads['basedir'] ) . $img_metadata['file'];
		if ( ! file_exists( $main_img_path ) ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-conversion-error',
				esc_html__( 'Image not found', 'image-type-converter' )
			);
		}

		if ( $same_ext ) {
			$new_img_path = $main_img_path;
		} else {
			$new_img_path = self::adjust_img_path( $main_img_path, $new_type );
		}

		$conversion_history['old_size'] = wp_filesize( $main_img_path );

		$new_main_img_path = self::convert( $main_img_path, $new_img_path, $new_type, $options );
		if ( is_wp_error( $new_main_img_path ) ) {
			return $new_main_img_path;
		}

		clearstatcache( true, $new_main_img_path );
		clearstatcache( true, $main_img_path );

		// Main File.
		$main_img_size            = wp_filesize( $new_main_img_path );
		$img_metadata['filesize'] = $main_img_size;
		$img_metadata['file']     = _wp_relative_upload_path( $new_main_img_path );
		$img_type_and_ext         = wp_check_filetype_and_ext( $new_main_img_path, wp_basename( $new_main_img_path ), self::get_mimes() );
		if ( ! $same_ext ) {
				@unlink( $main_img_path );
		} else {
			$img_metadata['mime_type'] = 'image/' . $new_type;
		}
		self::update_attachment_mime_type( $attachment_id, $img_type_and_ext['type'] );

		// Original Image.
		if ( ! empty( $img_metadata['original_image'] ) ) {
			$original_img_path = trailingslashit( $uploads['basedir'] ) . str_replace( wp_basename( $img_metadata['file'] ), $img_metadata['original_image'], $img_metadata['file'] );

			if ( ! file_exists( $original_img_path ) ) {
				return new \WP_Error(
					self::$plugin_info['name'] . '-img-conversion-error',
					esc_html__( 'The image file doesn\'t exist!', 'image-type-converter' )
				);
			}

			if ( $same_ext ) {
				$new_img_path = $original_img_path;
			} else {
				$new_img_path = self::adjust_img_path( $original_img_path, $new_type );
			}
			$new_original_img_path = self::convert( $original_img_path, $new_img_path, $new_type );

			if ( $new_original_img_path ) {
				if ( ! $same_ext ) {
					@unlink( $original_img_path );
				}
				$img_metadata['original_image'] = wp_basename( $new_original_img_path );
			}
		}

		// Sub-sizes.
		foreach ( $img_metadata['sizes'] as $size_name => &$size_arr ) {
			$subsize_path = trailingslashit( $uploads['basedir'] ) . str_replace( wp_basename( $img_metadata['file'] ), $size_arr['file'], $img_metadata['file'] );

			if ( ! file_exists( $subsize_path ) ) {
				continue;
			}

			$old_size = wp_filesize( $subsize_path );

			if ( $same_ext ) {
				$new_img_path = $subsize_path;
			} else {
				$new_img_path = self::adjust_img_path( $subsize_path, $new_type );
			}

			$new_subsize_path = self::convert( $subsize_path, $new_img_path, $new_type, $options );
			if ( is_wp_error( $new_subsize_path ) ) {
				continue;
			}
			if ( $new_subsize_path ) {
				if ( ! $same_ext ) {
					@unlink( $subsize_path );
				}

				$new_size                                  = wp_filesize( $new_subsize_path );
				$conversion_history['sizes'][ $size_name ] = array(
					'old_size' => $old_size,
					'new_size' => $new_size,
				);
				$img_type_and_ext                          = wp_check_filetype_and_ext( $new_subsize_path, wp_basename( $new_subsize_path ), self::get_mimes() );
				$size_arr['file']                          = wp_basename( $new_subsize_path );
				$size_arr['filesize']                      = $new_size;
				$size_arr['mime-type']                     = $img_type_and_ext['type'];
			}
		}

		$conversion_history['new_size'] = $img_metadata['filesize'];

		self::add_img_conversion_history( $attachment_id, $conversion_history );

		wp_update_attachment_metadata( $attachment_id, $img_metadata );
		update_attached_file( $attachment_id, $new_main_img_path );
		return true;
	}

	/**
	 * Convert Image Type.
	 *
	 * @param string $img_path
	 * @param string $old_ext
	 * @param string $new_ext
	 * @return string|\WP_Error
	 */
	public static function convert( $img_path, $new_img_path, $new_ext, $options = array() ) {
		$result = self::convert_handler( $img_path, $new_img_path, $new_ext, ! empty( $options['quanlity'] ) ? $options['quality'] : 85 );

		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return $new_img_path;
	}


	/**
	 * Convert Lib Handler.
	 *
	 * @param string $img_path
	 * @param string $new_img_path
	 * @param string $new_ext
	 * @return true|\WP_Error
	 */
	private static function convert_handler( $img_path, $new_img_path, $new_ext, $quality = 85 ) {
		wp_raise_memory_limit( 'image' );
		$img_ext = self::get_image_ext( $img_path );
		if ( self::is_type_supported_imagick( $img_ext ) && self::is_type_supported_imagick( $new_ext ) ) {
			return self::convert_imagick( $img_path, $new_img_path, $new_ext, $quality );
		}

		if ( self::is_type_supported_gd( $img_ext ) && self::is_type_supported_gd( $new_ext ) ) {
			return self::convert_gd( $img_path, $new_img_path, $new_ext, $quality );
		}

		return new \WP_Error(
			self::$plugin_info['prefix'] . '-unsupported-img-type',
			esc_html__( 'Target image type is unsupported', 'image-type-converter' )
		);
	}

	/**
	 * Convert using GD.
	 *
	 * @param string $img_path
	 * @param string $new_img_path
	 * @param string $new_ext
	 * @return true|\WP_Error
	 */
	private static function convert_gd( $img_path, $new_img_path, $new_ext, $quality ) {
		$new_type_func = 'image' . $new_ext;
		$img_string    = self::get_img_string( $img_path );
		$gd_img        = imagecreatefromstring( $img_string );

		if ( ! $gd_img || ! function_exists( $new_type_func ) ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-img-conversion-error',
				sprintf( esc_html__( 'Image type is not supported. new extension: %s, image path: %s', 'image-type-converter' ), $new_ext, $img_path )
			);
		}
		imagepalettetotruecolor( $gd_img );

		if ( 'png' === $new_ext ) {
			// no quality in png.
			$quality = 9;
		}

		try {
			if ( 'gif' === $new_ext ) {
				$new_type_func( $gd_img, $new_img_path );
			} else {
				$new_type_func( $gd_img, $new_img_path, $quality );
			}
		} catch ( \Exception $e ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-img-conversion-error',
				$e->getMessage()
			);
		}

		imagedestroy( $gd_img );
		return true;
	}

	/**
	 * Convert using Imagick.
	 *
	 * @param string $img_path
	 * @param string $new_img_path
	 * @param string $new_ext
	 * @return true|\WP_Error
	 */
	private static function convert_imagick( $img_path, $new_img_path, $new_ext, $quality ) {
		try {
			$imgick_img = new \Imagick( $img_path );
			$imgick_img->setImageFormat( $new_ext );
			$imgick_img->setImageCompressionQuality( $quality );
			$image_data = $imgick_img->getImageBlob();
			file_put_contents( $new_img_path, $image_data );
			$imgick_img->clear();
		} catch ( \Exception $e ) {
			return new \WP_Error(
				self::$plugin_info['prefix'] . '-convert-image-imagick',
				esc_html( $e->getMessage() )
			);
		}
		return true;
	}

	/**
	 * Write Image to file.
	 *
	 * @param \Imagick $imgick_img
	 * @param string $img_path
	 * @return void
	 */
	private static function writeImage( $imgick_img, $img_path ) {
		$imgick_img->writeImage( $img_path );
	}

	/**
	 * Adjust Image PATH from old PATH to New PATH.
	 *
	 * @param mixed $img_path
	 * @param mixed $new_ext
	 * @return string
	 */
	private static function adjust_img_path( $img_path, $new_ext ) {
		$new_ext  = self::$image_extensions_mapping[ $new_ext ];
		$img_path = substr( $img_path, 0, strrpos( $img_path, '.' ) ) . '.' . $new_ext;
		return $img_path;

	}

	/**
	 * Update Attachment Mime Type.
	 *
	 * @param int    $attachemnt_id
	 * @param string $mime_type
	 * @return mixed
	 */
	private static function update_attachment_mime_type( $attachemnt_id, $mime_type ) {
		global $wpdb;
		return $wpdb->update(
			$wpdb->posts,
			array(
				'post_mime_type' => $mime_type,
			),
			array(
				'ID' => $attachemnt_id,
			),
			array(
				'%s',
				'%d',
			)
		);
	}

	/**
	 * Get Image Conversion History.
	 *
	 * @param int $attachment_id
	 * @return array
	 */
	public static function get_img_conversion_history( $attachment_id ) {
		$conversion_history = get_post_meta( $attachment_id, self::$conversion_history_key, true );
		if ( empty( $conversion_history ) ) {
			return array();
		}
		return $conversion_history;
	}

	/**
	 * Get Image Type.
	 *
	 * @param int $attachment_id
	 * @return string|\WP_Error
	 */
	public static function get_image_type( $attachment_id, $check_conversion = false ) {
		if ( $check_conversion ) {
			$conversion_history = array_reverse( self::get_img_conversion_history( $attachment_id ) );
			if ( ! empty( $conversion_history ) ) {
				return $conversion_history[0]['new_type'];
			}
		}
		$img_details = self::get_image_file_details( $attachment_id );
		if ( is_wp_error( $img_details ) ) {
			return $img_details;
		}
		return $img_details['ext'];
	}

	/**
	 * Add Image Conversion History.
	 *
	 * @param int   $attachment_id
	 * @param array $conversion_history
	 * @return void
	 */
	public static function add_img_conversion_history( $attachment_id, $conversion_history ) {
		$conversions_history = get_post_meta( $attachment_id, self::$conversion_history_key, true );
		if ( empty( $conversions_history ) ) {
			$conversions_history = array( $conversion_history );
		} else {
			$conversions_history[] = $conversion_history;
		}
		update_post_meta( $attachment_id, self::$conversion_history_key, $conversions_history );
	}

	/**
	 * Get Conversion History Keys.
	 *
	 * @return array
	 */
	public static function get_conversion_history_keys() {
		return self::$conversion_history_keys;
	}

	/**
	 * Get Default Convert Options.
	 *
	 * @return array
	 */
	public static function get_default_convert_options() {
		return self::$default_convert_options;
	}

	/**
	 * Check if Avif Mime is allowed.
	 *
	 * @return boolean
	 */
	public static function is_avif_allowed() {
		$allowed_types = get_allowed_mime_types();
		return in_array( 'avif', array_keys( $allowed_types ) );
	}
}
