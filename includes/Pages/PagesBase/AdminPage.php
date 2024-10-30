<?php
namespace GPLSCore\GPLS_PLUGIN_WICOR\Pages\PagesBase;

use GPLSCore\GPLS_PLUGIN_WICOR\Base;
use GPLSCore\GPLS_PLUGIN_WICOR\Utils\GeneralUtilsTrait;
use GPLSCore\GPLS_PLUGIN_WICOR\Settings\SettingsBase\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Page Class
 */
abstract class AdminPage extends Base {
	use GeneralUtilsTrait;

	/**
	 * Page Properties Array.
	 *
	 * @var array
	 */
	protected $page_props = array(
		'page_title'     => '',
		'menu_title'     => '',
		'menu_slug'      => '',
		'position'       => 10,
		'icon_url'       => null,
		'cap'            => 'manage_options',
		'template_name'  => '',
		'is_woocommerce' => false,
		'tab_key'        => 'tab',
		'parent_slug'    => null,
	);

	/**
	 * Page Tabs.
	 *
	 * array(
	 *  'tab-name' => array(
	 *      'title'   => 'Tab Title',
	 *      'default' => true,
	 *   ),
	 *   ...
	 * );
	 *
	 * @var array
	 */
	protected $tabs = array();

	/**
	 * Admin Menu Pages Slugs.
	 *
	 *  Default: bottom of menu structure
	 *      2 – Dashboard
	 *      4 – Separator
	 *      5 – Posts
	 *      10 – Media
	 *      15 – Links
	 *      20 – Pages
	 *      25 – Comments
	 *      59 – Separator
	 *      60 – Appearance
	 *      65 – Plugins
	 *      70 – Users
	 *      75 – Tools
	 *      80 – Settings
	 *      99 – Separator

	 *  For the Network Admin menu, the values are different:
	 *      2 – Dashboard
	 *      4 – Separator
	 *      5 – Sites
	 *      10 – Users
	 *      15 – Themes
	 *      20 – Plugins
	 *      25 – Settings
	 *      30 – Updates
	 *      99 – Separator
	 *
	 * @var array
	 */
	protected $parent_pages_slugs = array(
		'index.php',                                // Dashboard.
		'edit.php',                                 // Posts.
		'upload.php',                               // Media.
		'edit.php?post_type=page',                  // Pages.
		'edit-comments.php',                        // Comments.
		'edit.php?post_type=custom_post_type_name', // Custom Post Type.
		'admin.php?page=wc-admin',                  // WooCommerce.
		'edit.php?post_type=product',               // Products.
		'themes.php',                               // Appearance.
		'plugins.php',                              // Plugins.
		'users.php',                                // Users.
		'tools.php',                                // Tools.
		'options-general.php',                      // Settings.
		'settings.php',                             // Network Settings.
	);

	/**
	 * Assets Files to include.
	 *
	 * @return void
	 */
	protected $assets = array();

	/**
	 * Core Assets Files to include.
	 *
	 * @return void
	 */
	protected $core_assets = array();

	/**
	 * Settings
	 *
	 * @var Settings
	 */
	public $settings = null;

	/**
	 * Page PATH.
	 *
	 * @var string
	 */
	protected $page_path;

	/**
	 * Templates Folder name
	 *
	 * @var string
	 */
	protected $templates_folder;

	/**
	 * Default Page Properties.
	 *
	 * @var array
	 */
	private $default_page_props = array(
		'is_woocommerce' => false,
		'cap'            => 'manage_options',
		'position'       => 10,
	);

	/**
	 * Prepare Page.
	 *
	 * @return void
	 */
	abstract protected function prepare();

	/**
	 * Initialize Page.
	 *
	 * @param object    $core
	 * @param array     $plugin_info
	 * @param AdminPage $parent_page
	 */
	public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Admin Page Constructor.
	 */
	protected function __construct() {
		if ( is_admin() ) {
			$this->main_setup();
			$this->main_hooks();
		}
	}

	/**
	 * Setup Parent.
	 *
	 * @return void
	 */
	public function main_setup() {
		$this->prepare();
		$this->page_props = array_merge( $this->default_page_props, $this->page_props );

		// Page PATH.
		if ( $this->page_props['is_woocommerce'] ) {
			$this->page_path                 = admin_url( 'admin.php?page=wc-settings&tab=' . $this->page_props['menu_slug'] );
			$this->page_props['parent_slug'] = 'admin.php';
		}
		if ( ! $this->page_path ) {
			$this->page_path = admin_url( ( $this->page_props['parent_slug'] ? $this->page_props['parent_slug'] : 'admin.php' ) . '?page=' . $this->page_props['menu_slug'] );
		}

		// Main Assets.
		$this->core_assets = array(
			array(
				'type'   => 'js',
				'handle' => 'jquery',
			),
			array(
				'type'   => 'js',
				'handle' => 'wp-hook',
			),
			array(
				'type'   => 'css',
				'handle' => self::$plugin_info['name'] . '-bootstrap-css',
				'url'    => self::$core->core_assets_lib( 'bootstrap', 'css' ),
			),
			array(
				'type'   => 'js',
				'handle' => self::$plugin_info['name'] . '-bootstrap-js',
				'url'    => self::$core->core_assets_lib( 'bootstrap.bundle', 'js' ),
			),
		);

		if ( empty( $this->tabs ) ) {
			$this->tabs['general'] = array(
				'default'  => true,
				'template' => ! empty( $this->page_props['template_name'] ) ? $this->page_props['template_name'] : '',
			);
		}

	}

	/**
	 * Add Page.
	 *
	 * @return void
	 */
	public function add_page() {

		if ( $this->page_props['is_woocommerce'] ) {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'woo_register_settings_tab' ), 100, 1 );
			add_action( 'woocommerce_settings_' . $this->page_props['menu_slug'], array( $this, 'woo_settings_tab_action' ), 10 );
			return;
		}

		if ( ! is_null( $this->page_props['parent_slug'] ) ) {
			add_submenu_page(
				$this->page_props['parent_slug'],
				$this->page_props['page_title'],
				$this->page_props['menu_title'],
				$this->page_props['cap'],
				$this->page_props['menu_slug'],
				array( $this, 'page_output_function' ),
				$this->page_props['position'],
			);
		} else {
			add_menu_page(
				$this->page_props['page_title'],
				$this->page_props['menu_title'],
				$this->page_props['cap'],
				$this->page_props['menu_slug'],
				array( $this, 'page_output_function' ),
				$this->page_props['icon_url'],
				$this->page_props['position'],
			);
		}
	}

	/**
	 * Add the Page to WooCommerce Settings Page.
	 *
	 * @return void
	 */
	public function woo_register_settings_tab( $settings_tabs ) {
		$settings_tabs[ $this->page_props['menu_slug'] ] = $this->page_props['menu_title'];
		return $settings_tabs;
	}

	/**
	 * SHow the Settings Tab Fields.
	 *
	 * @return void
	 */
	public function woo_settings_tab_action() {
		$tab_name = $this->get_current_tab();
		$tab      = $this->tabs[ $tab_name ];

		if ( ! empty( $this->page_props['tab_key'] ) && ! empty( $_GET[ $this->page_props['tab_key'] ] ) ) {
			$action      = sanitize_text_field( wp_unslash( $_GET[ $this->page_props['tab_key'] ] ) );
			$method_name = $action . '_tab';
			if ( method_exists( $this, $method_name ) ) {
				if ( empty( $tab['hide_tabs'] ) ) {
					$this->output_page_tabs_nav( true );
				}
				$this->{$method_name}();
			} else {
				$this->page_output_function();
			}
		} else {
			$this->page_output_function();
		}

		do_action( self::$plugin_info['name'] . '-' . $this->page_props['menu_slug'] . '-main-settings-tabs-action', self::$plugin_info );

		if ( ! empty( $tab['woo_hide_save_btn'] ) ) {
			$GLOBALS['hide_save_button'] = true;
		}
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	protected function main_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ), PHP_INT_MAX, 1 );

		if ( method_exists( $this, 'hooks' ) ) {
			$this->hooks();
		}
	}

	/**
	 * Get Default Tab.
	 *
	 * @return string|false
	 */
	private function get_default_tab() {
		foreach ( $this->tabs as $tab_name => $tab_arr ) {
			if ( ! empty( $tab_arr['default'] ) ) {
				return $tab_name;
			}
		}
		return false;
	}

	/**
	 * Page Output HTML function.
	 *
	 * @return void
	 */
	public function page_output_function( $reserved = '', $display_tabs = true, $show_notices = true ) {
		$tab_name = $this->get_current_tab();

		if ( empty( $tab_name ) || empty( $this->tabs[ $tab_name ] ) ) {
			return;
		}

		$tab = $this->tabs[ $tab_name ];
		?>
		<!-- Template Header -->
		<div class="wrap <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-page-template-wrapper gpls-general-page-template-wrapper' ); ?> mt-0 bg-light p-3 mt-5 min-vh-100">

			<?php
			if ( $display_tabs ) {
				$this->output_page_tabs_nav( true );
			}
			?>

			<?php
			if ( $show_notices ) {
				do_action( self::$plugin_info['name'] . '-general-top-notices' );
			}
			?>
			<!-- Template Title -->
			<?php if ( empty( $tab['hide_title'] ) && ! empty( $tab['title'] ) ) : ?>
				<h1 class="wp-heading-inline mb-4 shadow-sm p-2 bg-white"><?php echo wp_kses_post( $tab['title'] ); ?></h1>
			<?php endif; ?>

			<?php
			// Template Header.
			do_action( self::$plugin_info['name'] . '-' . $this->page_props['menu_slug'] . '-template-header', $this );
			?>

			<?php
			// Page Content.
			$this->_page_content( $tab_name, $tab );
			do_action( self::$plugin_info['name'] . '-' . $this->page_props['menu_slug'] . '-after-page-content', $this, $tab );
			?>

			<?php
			//Template Footer.
			do_action( self::$plugin_info['name'] . '-' . $this->page_props['menu_slug'] . '-template-footer', $this );
			?>
		</div>
		<?php
	}

	/**
	 * Page Content.
	 *
	 * @param string $tab_name
	 * @param array $tab
	 * @return void
	 */
	private function _page_content( $tab_name, $tab ) {

		// Print Settings.
		if ( ! empty( $tab['settings'] ) && ! empty( $this->settings ) && ! empty( $this->settings->get_fields()[ $tab_name ] ) ) {
			$this->settings->print_settings( $tab_name, false );
			$this->settings->nonce_field();
			$this->settings->save_field();
			return;
		}

		if ( ! empty( $tab['template'] ) ) {
			$args = array(
				'core'          => self::$core,
				'plugin_info'   => self::$plugin_info,
				'template_page' => $this,
			);

			// Template additional args.
			if ( ! empty( $tab['args'] ) ) {
				$args = array_merge( $args, $tab['args'] );
			}

			$template_path = ! empty( $this->templates_folder ) ? trailingslashit( $this->templates_folder ) . $tab['template'] : self::$plugin_info['path'] . 'includes/Templates/pages/' . $tab['template'];
			// Tab's Template.
			if ( file_exists( $template_path ) ) {
				load_template(
					$template_path,
					false,
					$args
				);
			}

			return;
		}

		// Tab Function.
		if ( $tab && method_exists( $this, $tab_name . '_tab' ) ) {
			$method_name = $tab_name . '_tab';
			$this->{$method_name}();
		}
	}

	/**
	 * Get Page Menu Slug.
	 *
	 * @return string
	 */
	public function get_menu_slug() {
		return $this->page_props['menu_slug'];
	}

	/**
	 * Get Current Page Path.
	 *
	 * @return string
	 */
	public function get_page_path() {
		return $this->page_path;
	}

	/**
	 * Get Page Templates Folder
	 *
	 * @return string
	 */
	public function get_templates_folder() {
		return $this->templates_folder;
	}

	/**
	 * Check if its current Page.
	 *
	 * @return boolean
	 */
	public function is_current_page( $custom_slug = null ) {
		$full_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		return ( ! empty( $full_url ) && ( 0 === strpos( esc_url_raw( $full_url ), ( is_null( $custom_slug ) ? $this->page_path : $custom_slug ) ) ) );
	}

	/**
	 * Set Page Assets
	 *
	 * @return void
	 */
	abstract protected function set_assets();

	/**
	 * Page assets.
	 *
	 * @return void
	 */
	public function assets( $suffix ) {
		if ( $this->is_current_page() || ( $suffix === get_plugin_page_hookname( $this->page_props['menu_slug'], $this->page_props['parent_slug'] ) ) ) {
			$this->set_assets();
			if ( ! empty( $this->settings ) ) {
				$this->assets = array_merge( $this->settings->get_settings_assets(), $this->assets );
			}
			$assets = array_merge( $this->assets, $this->core_assets );

			$this->handle_enqueue_assets( $assets );

			do_action( self::$plugin_info['name'] . '-admin-page-assets', $this );

		}
	}

	/**
	 * Output Page Tabs navbar.
	 *
	 * @return void|string
	 */
	public function output_page_tabs_nav( $echo = false ) {
		if ( empty( $this->tabs ) ) {
			return;
		}

		if ( count( $this->tabs ) < 2 ) {
			return;
		}

		if ( ! $echo ) {
			ob_start();
		}
		?>
		<div class="<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-page-template-tabs-nav gpls-general-page-template-tabs-nav' ); ?> mt-0 bg-light p-3 my-3 border-bottom shadow-sm">
			<ul class="list-group list-group-horizontal">
				<?php foreach ( $this->tabs as $tab_name => $tab ) : ?>
				<li class="list-group-item btn p-0 <?php echo esc_attr( $this->is_tab_active( $tab_name, $tab ) ? 'active' : '' ); ?>">
					<a
						class="list-group-item-link text-decoration-none fw-bold d-block px-3 py-2"
						href="<?php
						echo esc_url_raw(
							add_query_arg(
								array(
									$this->page_props['tab_key'] => $tab_name,
								),
								$this->page_path
							)
						);
						?>" >
					<?php echo wp_kses_post( ! empty( $tab['tab_title'] ) ? $tab['tab_title'] : $tab['title'] ); ?>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		if ( ! $echo ) {
			return ob_get_clean();
		}
	}

	/**
	 * Check if tab is active.
	 *
	 * @param string $tab_name
	 * @param array  $tab_arr
	 * @return boolean
	 */
	public function is_tab_active( $tab_name, $tab_arr = array() ) {
		if ( ! empty( $tab_arr ) && ! empty( $tab_arr['default'] ) && empty( $_GET[ $this->page_props['tab_key'] ] ) ) {
			return true;
		}

		if ( ! empty( $_GET[ $this->page_props['tab_key'] ] ) && $tab_name === sanitize_text_field( wp_unslash( $_GET[ $this->page_props['tab_key'] ] ) ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Get Current Tab.
	 *
	 * @return string
	 */
	public function get_current_tab() {
		$tab_name = ( ! empty( $this->page_props['tab_key'] ) && ! empty( $_GET[ $this->page_props['tab_key'] ] ) ) ? sanitize_text_field( wp_unslash( $_GET[ $this->page_props['tab_key'] ] ) ) : '';
		$tab_name = ! empty( $tab_name ) ? $tab_name : $this->get_default_tab();
		return $tab_name;
	}
}
