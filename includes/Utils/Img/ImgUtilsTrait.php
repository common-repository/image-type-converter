<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img;

/**
 * Image Related Functions Utils.
 */
trait ImgUtilsTrait {

	/**
	 * Check if the imagick lib is enabled.
	 *
	 * @return boolean
	 */
	public static function is_imagick_enabled() {
		return ( extension_loaded( 'imagick' ) && class_exists( '\Imagick', false ) && class_exists( '\ImagickPixel', false ) );
	}

	/**
	 * Check if the gd lib is enabled.
	 *
	 * @return boolean
	 */
	public static function is_gd_enabled() {
		return ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) );
	}

	/**
	 * Convert Image URL to PATH.
	 *
	 * @param string $img_url
	 * @return string
	 */
	public static function convert_url_to_path( $img_url ) {
		$uploads  = wp_get_upload_dir();
		$img_path = str_replace( $uploads['baseurl'], $uploads['basedir'], $img_url );
		return $img_path;
	}

	/**
	 * Convert Image PATH to URL.
	 *
	 * @param string $img_path
	 * @return string
	 */
	public static function convert_path_to_url( $img_path ) {
		$uploads = wp_get_upload_dir();
		$img_url = str_replace( $uploads['basedir'], $uploads['baseurl'], $img_path );
		return $img_url;
	}

	/**
	 * Get Image Specs.
	 *
	 * @param string $image_path
	 * @return array|false
	 */	
	public static function get_image_specs( $image_path ) {
		$img_details = self::get_imagesize( $image_path );
		if ( ! $img_details ) {
			return false;
		}

		$file_size = filesize( $image_path );
		return array(
			'width'       => $img_details[0],
			'height'      => $img_details[1],
			'mime'        => $img_details['mime'],
			'dimension'   => $img_details[0] . 'x' . $img_details[1],
			'ext'         => str_replace( 'image/', '', $img_details['mime'] ),
			'file_ext'    => pathinfo( $image_path, \PATHINFO_EXTENSION ),
			'size'        => $file_size,
			'size_format' => size_format( $file_size, 1 ),
		);
	}

	/**
	 * Get Image Ext.
	 *
	 * @param string $img_path
	 * @return string|false
	 */
	public static function get_image_ext( $img_path ) {
		$img_details = self::get_imagesize( $img_path );
		if ( ! $img_details ) {
			return false;
		}
		return str_replace( 'image/', '', $img_details['mime'] );
	}

	/**
	 * Get Image Path - Filename - size - ext details.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $img_size_name Get the details to specific size name.
	 * @return array|\WP_Error
	 */
	public static function get_image_file_details( $attachment_id, $img_size_name = 'original' ) {
		$img_details       = array();
		$uploads           = wp_get_upload_dir();
		$img_meta          = wp_get_attachment_metadata( $attachment_id );
		$img_relative_path = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$img_file_name     = wp_basename( $img_relative_path );

		// Check if the image is scaled, get the original image.
		if ( ! empty( $img_meta['original_image'] ) && ( $img_file_name !== $img_meta['original_image'] ) ) {
			$original_img_filename      = $img_meta['original_image'];
			$original_img_relative_path = str_replace( $img_file_name, $img_meta['original_image'], $img_relative_path );
			$original_full_path         = trailingslashit( $uploads['basedir'] ) . $original_img_relative_path;
			// check if the original exists.
			if ( @file_exists( $original_full_path ) ) {
				$img_details['scaled_path'] = get_attached_file( $attachment_id );
				$img_relative_path          = $original_img_relative_path;
				$img_file_name              = $original_img_filename;
			}
		}

		$img_relative_subdirectory_path = str_replace( $img_file_name, '', $img_relative_path );
		if ( 'original' === $img_size_name ) {
			$img_full_path = trailingslashit( $uploads['basedir'] ) . $img_relative_path;
			$img_full_url  = trailingslashit( $uploads['baseurl'] ) . $img_relative_path;
			$filetype      = wp_check_filetype_and_ext( $img_full_path, $img_file_name, self::get_mimes() );

		} elseif ( 'original' !== $img_size_name && ! empty( $img_meta['sizes'] ) && ! empty( $img_meta['sizes'][ $img_size_name ] ) ) {
			$size_file_name    = $img_meta['sizes'][ $img_size_name ]['file'];
			$img_relative_path = str_replace( $img_file_name, $size_file_name, $img_relative_path );
			$img_file_name     = wp_basename( $img_relative_path );
			$img_full_path     = trailingslashit( $uploads['basedir'] ) . $img_relative_path;
			$img_full_url      = trailingslashit( $uploads['baseurl'] ) . $img_relative_path;
			$filetype          = wp_check_filetype_and_ext( $img_full_path, $img_file_name, self::get_mimes() );
		} else {
			return new \WP_Error(
				self::$plugin_info['name'] . '-attachment-subsize-not-found',
				sprintf( esc_html__( 'Image file sub-size: %s not found!' ), $img_size_name )
			);
		}

		if ( ! file_exists( $img_full_path ) ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-attachment-file-not-found',
				sprintf( esc_html__( 'image file %s not found!' ), $img_file_name )
			);
		}
		if ( 'original' === $img_size_name ) {
			$img_details['width']  = $img_meta['width'];
			$img_details['height'] = $img_meta['height'];
		} else {
			$img_details['width']  = ( 'original' === $img_size_name ) ? $img_meta['width'] : $img_meta['sizes'][ $img_size_name ]['width'];
			$img_details['height'] = ( 'original' === $img_size_name ) ? $img_meta['height'] : $img_meta['sizes'][ $img_size_name ]['height'];
		}

		if ( 'original' !== $img_size_name ) {
			$img_details['width_ratio']  = number_format( floatval( $img_details['width'] / $img_meta['width'] ), 2 );
			$img_details['height_ratio'] = number_format( floatval( $img_details['height'] / $img_meta['height'] ), 2 );
			$img_details['width_ratio']  = ( $img_details['width_ratio'] < 0.10 ) ? 0.10 : $img_details['width_ratio'];
			$img_details['height_ratio'] = ( $img_details['height_ratio'] < 0.10 ) ? 0.10 : $img_details['height_ratio'];
		}

		$img_file_filetype = wp_check_filetype( $img_file_name );

		$img_details['id']                     = $attachment_id;
		$img_details['size_name']              = $img_size_name;
		$img_details['path']                   = $img_full_path;
		$img_details['url']                    = $img_full_url;
		$img_details['filename']               = $img_file_name;
		$img_details['relative_path']          = $img_relative_subdirectory_path;
		$img_details['full_path_without_name'] = trailingslashit( dirname( $img_full_path ) );
		$img_details['ext']                    = $filetype['ext'];
		$img_details['file_ext']               = $img_file_filetype['ext'];
		$img_details['mime_type']              = $filetype['type'];
		$img_details['file_mime_type']         = $img_file_filetype['type'];
		$img_details['specs']                  = self::get_image_specs( $img_full_path );
		return $img_details;
	}

	/**
	 * Get Image as string.
	 *
	 * @param string $img_path
	 * @return string
	 */
	private static function get_img_string( $img_path ) {
		return file_get_contents( $img_path );
	}

	/**
	 * Is the image animated.
	 *
	 * @param string $img_path
	 * @return boolean
	 */
	public static function is_img_animated( $img_path ) {
		// Imagick Check.
		$is_animated = self::is_img_animated_imagick( $img_path );
		if ( is_bool( $is_animated ) ) {
			return $is_animated;
		}

		// Fallback to GD.
		return self::is_img_animated_gd( $img_path );
	}

	/**
	 * Is animated image using Imagick.
	 *
	 * @param string $img_path
	 * @return boolean|\WP_Error
	 */
	public static function is_img_animated_imagick( $img_path ) {
		if ( ! self::is_imagick_enabled() ) {
			return new \WP_Error(
				'image-read-failed',
				esc_html__( 'Imagick is not enabled' )
			);
		}

		try {
			$imgick = new \Imagick( $img_path );
			$count  = $imgick->count();
			$imgick->clear();
			return $count > 1;
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'image-read-failed',
				esc_html( $e->getMessage() )
			);
		}
	}

	/**
	 * Is animated image using GD.
	 *
	 * @param string $img_path
	 * @return boolean|\WP_Error
	 */
	public static function is_img_animated_gd( $image_path ) {
		if ( ! self::is_gd_enabled() ) {
			return new \WP_Error(
				'image-read-failed',
				esc_html__( 'GD is not enabled' )
			);
		}

		$extension = self::get_image_ext( $image_path );
		if ( ! $extension ) {
			return new \WP_Error(
				'image-read-failed',
				esc_html__( 'Failed to read image type' )
			);
		}

		if ( 'gif' === $extension ) {
			return self::is_animated_gif_gd( $image_path );
		}

		if ( 'webp' === $extension ) {
			return self::is_animated_webp_gd( $image_path );
		}

		return false;
	}

	/**
	 * Is animated GIF image using GD.
	 *
	 * @param string $image_path
	 * @return boolean
	 */
	private static function is_animated_gif_gd( $image_path ) {
		$fh = @fopen( $image_path, 'rb' );
		if ( ! ( $fh ) ) {
			return false;
		}
		$count = 0;
		while ( ! feof( $fh ) && $count < 2 ) {
			$chunk  = fread( $fh, 1024 * 100 );
			$count += preg_match_all( '#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches );
		}

		fclose( $fh );
		return $count > 1;
	}

	/**
	 * Is animated Webp image using GD.
	 *
	 * @param string $image_path
	 * @return boolean
	 */
	private static function is_animated_webp_gd( $image_path ) {
		$bin_string = file_get_contents( $image_path );
		// Check if Contains 'ANIM' and 'ANMF' chunks.
		if ( false === strpos( $bin_string, 'ANIM' ) ) {
			return false;
		}

		if ( substr_count( $bin_string, 'ANMF' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get GD Version.
	 *
	 * @return string
	 */
	public function get_gd_version() {
		$gd_info = gd_info();
		return $gd_info['GD Version'];
	}

	/**
	 * Get Imagick Version.
	 *
	 * @return string|false
	 */
	public function get_imagick_version() {
		try {
			$version = \Imagick::getVersion();
			if ( preg_match( '/((?:[0-9]+\.?)+)/', $version['versionString'], $matches ) ) {
				return $matches[0];
			}
			return $version['versionString'];
		} catch ( \Exception $e ) {

		}

		return false;
	}

	/**
	 * Reformed Get Image Size.
	 *
	 * @param string $img_path
	 * @return array|false
	 */
	public static function get_imagesize( $img_path ) {
		$img_size = wp_getimagesize( $img_path );

		if ( is_array( $img_size ) && ( 0 !== $img_size[0] ) && ( 0 !== $img_size[1] ) && ! empty( $img_size['mime'] ) ) {
			return $img_size;
		}


		// Failed, fallback to Imagick.
		if ( self::is_imagick_enabled() ) {
			try {
				$img_size         = array();
				$imgick           = new \Imagick( $img_path );
				$img_dim          = $imgick->getImageGeometry();
				$img_size[0]      = $img_dim['width'];
				$img_size[1]      = $img_dim['height'];
				$img_size['mime'] = $imgick->getImageMimeType();

				$imgick->clear();
			} catch ( \Exception $e ) {
				// Do nothing for now.
			}
		}

		return $img_size;
	}

	/**
	 * Get Mimies.
	 *
	 * @return array
	 */
	private static function get_mimes() {
		return array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'tiff|tif'     => 'image/tiff',
			'webp'         => 'image/webp',
			'ico'          => 'image/x-icon',
			'heic'         => 'image/heic',
			'avif'         => 'image/avif',
			'svg'          => 'image/svg+xml',
		);
	}

	/**
	 * Get New Table Row.
	 *
	 * @param int $image_id
	 * @return string
	 */
	private function get_new_table_row( $image_id ) {
		require_once \ABSPATH . 'wp-admin/includes/class-wp-media-list-table.php';
		
		$GLOBALS['typenow'] = 'attachment'; // compatibility with https://wordpress.org/plugins/folders/ plugin media Quick Convert.
		$media_table        = new \WP_Media_List_Table();
		$post               = get_post( $image_id );
		$post_owner         = ( get_current_user_id() === (int) $post->post_author ) ? 'self' : 'other';
		setup_postdata( $post );
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		ob_start();
		?>
		<tr id="post-<?php echo esc_attr( $post->ID ); ?>" class="<?php echo esc_attr( trim( ' author-' . $post_owner . ' status-' . $post->post_status ) ); ?>">
			<?php $media_table->single_row_columns( $post ); ?>
		</tr>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
}
