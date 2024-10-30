<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR;

defined( 'ABSPATH' ) || exit;

use Symfony\Component\Process\Process;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\NoticeUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\GeneralUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\Img\ImgUtilsTrait;

/**
 * Image Optimizer Class.
 */
class ImageOptimizer extends Base {

	use ImgUtilsTrait;
	use NoticeUtilsTrait;
	use GeneralUtilsTrait;

	/**
	 * Singular instance.
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * Supported Image Types.
	 *
	 * @var array
	 */
	private static $img_types = array(
		'webp',
		'gif',
		'jpeg',
		'jpg',
		'png',
		'svg',
	);

	/**
	 * Image Types to Optimizers mapping.
	 *
	 * @var array
	 */
	private static $img_type_to_optimizers = array(
		'jpg'  => array( 'jpegoptim' ),
		'jpeg' => array( 'jpegoptim' ),
		'png'  => array( 'pngquant2', 'optipng' ),
		'svg'  => array( 'svgo' ),
		'gif'  => array( 'gifsicle' ),
		'webp' => array( 'cwebp' ),
	);

	/**
	 * Optimizers With Quality.
	 *
	 * @var array
	 */
	private static $optimizers_with_quality = array( 'Jpegoptim', 'pngquant2', 'optipng', 'cwebp' );

	/**
	 * Optimization History Key.
	 *
	 * @var string
	 */
	private static $optimization_history_key;

	/**
	 * Optimizers.
	 *
	 * @var array
	 */
	private static $optimizers = array(
		'jpegoptim' => 'JpegOptim',
		'pngquant2' => 'Pngquant 2',
		'optipng'   => 'Optipng',
		'svg'       => 'SVGO',
		'gif'       => 'Gifsicle',
		'webp'      => 'Cwebp',
	);

	/**
	 * Optimization History Key.
	 *
	 * @var array
	 */
	private static $optimization_history_keys = array(
		'old_size',
		'new_size',
		'sizes',
		'context',
		'date',
	);

	/**
	 * Init.
	 *
	 * @return ImageOptimizer
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
		$this->hooks();
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	private function setup() {
		self::$optimization_history_key = self::$plugin_info['classes_prefix'] . '-optimization-history';
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	private function hooks() {
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
		// TODO: Check if the image type is supported for optimization.
		return true;
	}

	/**
	 * Get Optimizers.
	 *
	 * @return array
	 */
	public static function get_optimizers() {
		return array(
			'jpegoptim' => array(
				'title'   => 'JpegOptim',
				'target'  => 'jpg | jpeg',
				'install' => array(
					'Ubuntu/Debian'      => 'sudo apt-get install jpegoptim',
					'MacOS'              => 'brew install jpegoptim',
					'Fedora/RHEL/CentOS' => 'sudo dnf install jpegoptim',
				),
			),
			'optipng'   => array(
				'title'   => 'Optipng',
				'target'  => 'png',
				'install' => array(
					'Ubuntu/Debian'      => 'sudo apt-get install optipng',
					'MacOS'              => 'brew install optipng',
					'Fedora/RHEL/CentOS' => 'brew dnf install optipng',
				),
			),
			'pngquant'  => array(
				'title'   => 'Pngquant 2',
				'target'  => 'png',
				'install' => array(
					'Ubuntu/Debian'      => 'sudo apt-get install pngquant',
					'MacOS'              => 'brew install pngquant',
					'Fedora/RHEL/CentOS' => 'sudo dnf install pngquant',
				),
			),
			'svgo'      => array(
				'title'   => 'SVGO 1',
				'target'  => 'svg',
				'install' => array(
					'Ubuntu/Debian'      => 'sudo npm install -g svgo',
					'MacOS'              => 'npm install -g svgo',
					'Fedora/RHEL/CentOS' => 'sudo npm install -g svgo',
				),
			),
			'gifsicle'  => array(
				'title'   => 'Gifsicle',
				'target'  => 'gif',
				'install' => array(
					'Ubuntu/Debian'      => 'sudo apt-get install gifsicle',
					'MacOS'              => 'brew install gifsicle',
					'Fedora/RHEL/CentOS' => 'sudo dnf install gifsicle',
				),
			),
			'cwebp'     => array(
				'title'   => 'Cwebp',
				'target'  => 'webp',
				'install' => array(
					'Ubuntu/Debian'      => 'sudo apt-get install webp',
					'MacOS'              => 'brew install webp',
					'Fedora/RHEL/CentOS' => 'sudo dnf install libwebp-tools',
				),
			),
		);
	}

	/**
	 * Check if optimizer is installed.
	 *
	 * @return string|true|\WP_Error
	 */
	public static function is_optimizer_installed( $optimizer, $return_error = false ) {
		$optimizers_commands = array(
			'optipng'   => array( 'optipng', '--version' ),
			'jpegoptim' => array( 'jpegoptim', '--version' ),
			'pngquant'  => array( 'pngquant', '--version' ),
			'gifsicle'  => array( 'gifsicle', '--version' ),
			'cwebp'     => array( 'cwebp', '-version' ),
			'svgo'      => array( 'svgo', '-version' ),
		);
		if ( empty( $optimizers_commands[ $optimizer ] ) ) {
			return false;
		}
		try {
			$process = self::run( $optimizers_commands[ $optimizer ] );
			if ( ! $process->isSuccessful() ) {
				if ( $return_error ) {
					return new \WP_Error(
						'qpdf-is-installed-check-failed',
						sprintf( esc_html( '%s' ), $process->getErrorOutput() )
					);
				}
				return false;
			}
			$result = $process->getOutput();
			$result = preg_split( '/\r\n|\r|\n/', $result, 2 )[0];
			return $result;
		} catch ( \Exception $e ) {
			if ( $return_error ) {
				return new \WP_Error(
					'qpdf-check-install-error',
					$e->getMessage()
				);
			}
			return false;
		}
	}

	/**
	 * Get Supported image type ( without optimizer check ).
	 *
	 * @return array
	 */
	public static function get_supported_image_types() {
		return self::$img_types;
	}

	/**
	 * Get Conversion History Keys.
	 *
	 * @return array
	 */
	public static function get_optimization_history_keys() {
		return self::$optimization_history_keys;
	}

	/**
	 * Get Supported Image Types Options.
	 *
	 * @return string[]
	 */
	public static function get_supported_image_types_options() {
		$select_options = array();

		foreach ( self::$img_types as $img_type ) {
			$select_options[ $img_type ] = ucfirst( $img_type );
		}

		return $select_options;
	}

	/**
	 * Run Process Command.
	 *
	 * @param array $command
	 * @return Process
	 */
	protected static function run( $command ) {
		$process = new Process( $command );
		$process->run();
		return $process;
	}

	/**
	 * Is Image type optimizable.
	 *
	 * @param string $img_type
	 * @return boolean
	 */
	public static function is_img_type_optimizable( $img_type ) {
		$type_optimizers = ! empty( self::$img_type_to_optimizers[ $img_type ] ) ? self::$img_type_to_optimizers[ $img_type ] : array();
		foreach ( $type_optimizers as $optimizer ) {
			if ( self::is_optimizer_installed( $optimizer ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Optimize Button.
	 *
	 * @param string $img_type
	 * @return mixed
	 */
	public static function optimize_btn( $attachment_id, $img_type, $_return = false ) {
		if ( $_return ) {
			ob_start();
		}

		if ( ! self::is_img_type_optimizable( $img_type ) ) {
			if ( $_return ) {
				return ob_get_clean();
			}
			return;
		}
		?>
		<div class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-optimize-btn' ); ?>" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
			<div style="display:flex;flex-direction:row;align-items:center;">
				<button disabled="disabled" style="margin-top:5px;margin-bottom:5px;" data-image_id="<?php echo esc_attr( $attachment_id ); ?>" type="submit" class="button button-primary <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-quick-optimizer-submit' ); ?>"><?php esc_html_e( 'Optimize (Pro)' ); ?></button>
			</div>
			
			<?php if ( self::is_img_optimized( $attachment_id ) ) : ?>
				<span style="background:#44bb44;color:#FFF;padding:5px;border-radius:5px;"><?php esc_html_e( 'Optimized', 'image-type-converter' ); ?></span>
			<?php endif; ?>
		</div>
		<?php
		if ( $_return ) {
			return ob_get_clean();
		}
	}


	/**
	 * Optimize Image.
	 *
	 * @param string $img_path
	 * @param array  $options
	 * @return string|\WP_Error
	 */
	public static function optimize( $img_path, $options = array() ) {
		try {
			$optimizer_chain = OptimizerChainFactory::create( $options, new OptimizerLogger() );
			if ( ! file_exists( $img_path ) ) {
				return new \WP_Error(
					self::$plugin_info['prefix'] . '-optimization-error',
					esc_html__( 'Image not found', 'image-type-converter' )
				);
			}

			$old_size  = wp_filesize( $img_path );
			$dest_path = self::generate_temp_img( $img_path );
			$optimizer_chain->optimize( $img_path, $dest_path );
			$new_size = wp_filesize( $dest_path );
			if ( $old_size < $new_size ) {
				// Optimization failed to create optimized image with lower size, delete result image and fallback to source.
				@unlink( $dest_path ); // phpcs:ignore
				return new \WP_Error(
					self::$plugin_info['prefix'] . '-optimize-image',
					esc_html__( 'Optimization resulted in bigger image', 'image-type-converter' )
				);
			} else {
				// Optimization passed, Copy result to source, delete result, setup dest path to original path.
				@copy( $dest_path, $img_path ); // phpcs:ignore
				@unlink( $dest_path ); // phpcs:ignore
				$dest_path = $img_path;
			}
		} catch ( \Exception $e ) {
			return new \WP_Error(
				self::$plugin_info['prefix'] . '-optimize-image',
				$e->getMessage()
			);
		}

		$dest_path = ! is_null( $dest_path ) ? $dest_path : $img_path;
		return $dest_path;
	}

	/**
	 * Full Optimize image.
	 *
	 * @param int   $img_id
	 * @param array $options
	 * @return string|\WP_Error
	 */
	public static function full_optimize( $img_id, $options = array(), $context = 'single' ) {
		$uploads      = wp_upload_dir();
		$img_metadata = wp_get_attachment_metadata( $img_id );
		$img_details  = self::get_image_file_details( $img_id );
		$img_path     = $img_details['path'];

		if ( ! self::is_img_type_optimizable( $img_details['ext'] ) ) {
			return new \WP_Error(
				self::$plugin_info['prefix'] . '-image-optimize',
				esc_html__( 'No optimizer for image type', 'image-type-converter' )
			);
		}

		if ( is_wp_error( $img_details ) ) {
			return $img_details;
		}

		$optimization_history = array(
			'date'     => current_time( 'timestamp' ),
			'old_size' => $img_details['specs']['size'],
			'sizes'    => array(),
			'context'  => $context,
		);

		self::optimize( $img_path, $options );
		$optimization_history['new_size'] = wp_filesize( $img_path );

		// Handle scaled image.
		if ( ! empty( $img_metadata['original_image'] ) ) {
			// Original already optimized, optimize the scaled one too.
			$scaled_path = trailingslashit( $uploads['basedir'] ) . $img_metadata['file'];
			if ( file_exists( $scaled_path ) ) {
				self::optimize( $scaled_path, $options );
			}
		}

		foreach ( $img_metadata['sizes'] as $size_name => &$size_arr ) {
			$subsize_path = trailingslashit( $uploads['basedir'] ) . str_replace( wp_basename( $img_metadata['file'] ), $size_arr['file'], $img_metadata['file'] );

			if ( ! file_exists( $subsize_path ) ) {
				continue;
			}

			$old_size        = wp_filesize( $subsize_path );
			$optimize_result = self::optimize( $subsize_path, $options );
			if ( ! is_wp_error( $optimize_result ) ) {
				$new_size                                    = wp_filesize( $subsize_path );
				$optimization_history['sizes'][ $size_name ] = array(
					'old_size' => $old_size,
					'new_size' => $new_size,
				);
			}
		}

		self::add_optimization_history( $img_id, $optimization_history );

		return $img_path;
	}

	/**
	 * Generate Temporary image Path.
	 *
	 * @param string $img_path
	 * @return string
	 */
	private static function generate_temp_img( $img_path ) {
		$dot_position = strrpos( $img_path, '.' );
		$base_name    = substr( $img_path, 0, $dot_position );
		$extension    = substr( $img_path, $dot_position );
		$random_token = wp_generate_password( 15, false, false );
		$temp_path    = $base_name . '-' . $random_token . $extension;
		return $temp_path;
	}

	/**
	 * Add optimization History.
	 *
	 * @param int   $img_id
	 * @param array $optimization_details
	 * @return void
	 */
	public static function add_optimization_history( $img_id, $optimization_details ) {
		$optimizations_history   = self::get_optimization_history( $img_id );
		$optimizations_history[] = $optimization_details;
		update_post_meta( $img_id, self::$optimization_history_key, $optimizations_history );
	}

	/**
	 * Get Optimization History.
	 *
	 * @param int $img_id
	 * @return array
	 */
	public static function get_optimization_history( $img_id ) {
		$optimization_history = get_post_meta( $img_id, self::$optimization_history_key, true );
		return ! empty( $optimization_history ) ? $optimization_history : array();
	}

	/**
	 * Check if image is already optimized.
	 *
	 * @param int $img_id
	 * @return bool
	 */
	public static function is_img_optimized( $img_id ) {
		return ! empty( self::get_optimization_history( $img_id ) );
	}
}
