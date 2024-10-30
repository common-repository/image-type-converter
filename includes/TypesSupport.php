<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR;

use GPLSCore\GPLS_PLUGIN_WICOR\Plugin;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;

/**
 * Images Type Suppport Class.
 */
class TypesSupport extends Base {

	use ImgUtilsTrait;

	/**
	 * Singleton Instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
		if ( Plugin::is_plugin_active( 'gpls-avif-support/gpls-avif-support.php' ) ) {
			return;
		}
		$this->hooks();
	}

	/**
	 * Singleton Instance Init.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	private function hooks() {
		add_filter( 'getimagesize_mimes_to_exts', array( $this, 'filter_mime_to_exts' ), PHP_INT_MAX, 1 );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'fix_avif_images' ), PHP_INT_MAX, 3 );
		add_filter( 'file_is_displayable_image', array( $this, 'fix_avif_displayable' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Allow Avif mime support.
	 *
	 * @return void
	 */
	public static function allow_avif_support() {
		add_filter( 'mime_types', array( __CLASS__, 'filter_mime_types' ), PHP_INT_MAX, 1 );
	}

	/**
	 * Clear Avif Mime Support.
	 *
	 * @return void
	 */
	public static function clear_avif_support() {
		remove_filter( 'mime_types', array( __CLASS__, 'filter_mime_types' ), PHP_INT_MAX );
	}

	/**
	 * Filter Mime to Ext.
	 *
	 * @param array $mime_to_exsts
	 *
	 * @return array
	 */
	public function filter_mime_to_exts( $mime_to_exsts ) {
		$mime_to_exsts['image/avif'] = 'avif';
		return $mime_to_exsts;
	}

	/**
	 * Filter Mimes.
	 *
	 * @param array $mimes
	 * @return array
	 */
	public static function filter_mime_types( $mimes ) {
		$mimes['avif'] = 'image/avif';
		return $mimes;
	}

	/**
	 * Filter Allowed Mimes.
	 *
	 * @param array    $mimes
	 * @param \WP_User $user
	 * @return array
	 */
	public function filter_allowed_mimes( $mimes, $user ) {
		$mimes['avif'] = 'image/avif';
		return $mimes;
	}

	/**
	 * Fix AVif Displayable Image.
	 *
	 * @param boolean $result
	 * @param string  $path
	 * @return boolean
	 */
	public function fix_avif_displayable( $result, $path ) {
		// Pypass avif.
		if ( str_ends_with( $path, '.avif' ) ) {
			return true;
		}

		return $result;
	}

	/**
	 * Fix Avif Image Support.
	 *
	 * @param array  $metadata
	 * @param int    $attachment_id
	 * @param string $context
	 * @return array
	 */
	public function fix_avif_images( $metadata, $attachment_id, $context ) {
		// If it's empty, It's already failed.
		if ( empty( $metadata ) ) {
			return $metadata;
		}

		$attachemnt_post = get_post( $attachment_id );
		if ( ! $attachemnt_post || is_wp_error( $attachemnt_post ) ) {
			return $metadata;
		}

		if ( 'image/avif' !== $attachemnt_post->post_mime_type ) {
			return $metadata;
		}

		// Fix Width and Height in Metadata.
		$metadata = $this->fix_avif_metadata( $metadata, $attachment_id );

		// Fix scaled image.
		$metadata = $this->fix_avif_scaled_image( $metadata, $attachment_id );

		return $metadata;
	}

	/**
	 * Fix Avif Scaled Image Generation.
	 *
	 * @param array $metadata
	 * @param int   $attachment_id
	 * @return array
	 */
	private function fix_avif_scaled_image( $metadata, $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return $metadata;
		}

		// IF it's still zero, bail.
		if ( empty( $metadata ) || 0 === $metadata['width'] || 0 === $metadata['height'] ) {
			return $metadata;
		}

		$imagesize = self::get_imagesize( $file );
		$threshold = (int) apply_filters( 'big_image_size_threshold', 2560, $imagesize, $file, $attachment_id );

		// No Threshold, bail.
		if ( ! $threshold ) {
			return $metadata;
		}

		$exif_meta = wp_read_image_metadata( $file );
		if ( $exif_meta ) {
			$image_meta['image_meta'] = $exif_meta;
		}

		if ( $threshold && ( $metadata['width'] > $threshold || $metadata['height'] > $threshold ) ) {
			$editor = wp_get_image_editor( $file );

			if ( is_wp_error( $editor ) ) {
				// This image cannot be edited.
				return $metadata;
			}

			// Resize the image.
			$resized = $editor->resize( $threshold, $threshold );
			$rotated = null;

			// If there is EXIF data, rotate according to EXIF Orientation.
			if ( ! is_wp_error( $resized ) && is_array( $exif_meta ) ) {
				$resized = $editor->maybe_exif_rotate();
				$rotated = $resized;
			}

			if ( ! is_wp_error( $resized ) ) {
				$saved = $editor->save( $editor->generate_filename( 'scaled' ) );
				if ( ! is_wp_error( $saved ) ) {
					$metadata = _wp_image_meta_replace_original( $saved, $file, $metadata, $attachment_id );
					if ( true === $rotated && ! empty( $metadata['image_meta']['orientation'] ) ) {
						$metadata['image_meta']['orientation'] = 1;
					}
				} else {
					// TODO: Log errors.
				}
			} else {
				// TODO: Log errors.
			}
		}

		return $metadata;
	}

	/**
	 * Fix Avif Dimension Metadata.
	 *
	 * @param array  $metadata
	 * @param int    $attachment_id
	 * @param string $context
	 * @return array
	 */
	private function fix_avif_metadata( $metadata, $attachment_id ) {
		if ( ( ( 0 !== $metadata['width'] ) && 0 !== $metadata['height'] ) ) {
			return $metadata;
		}

		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return $metadata;
		}
		$avif_specs = self::get_image_specs( $file );
		if ( is_wp_error( $avif_specs ) || ! $avif_specs ) {
			return $metadata;
		}

		// Manual Avif Width and Height.
		if ( 0 === $avif_specs['width'] && 0 === $avif_specs['height'] ) {
			$avif_dim = self::get_avif_dim_manual( $file );

			if ( is_array( $avif_dim ) ) {
				$avif_specs['width']  = $avif_dim['width'];
				$avif_specs['height'] = $avif_dim['height'];
			}
		}

		$metadata['width']  = $avif_specs['width'];
		$metadata['height'] = $avif_specs['height'];

		return $metadata;
	}

}
