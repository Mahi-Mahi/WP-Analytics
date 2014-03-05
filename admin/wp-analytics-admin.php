<?php
/**
 * Plugin Name.
 *
 * @package   WP_Analytics_Admin
 * @author    olivM <olivier.mourlevat@mahi-mahi.fr>
 * @license   GPL-2.0+
 * @link      http://mahi-mahi.fr/
 * @copyright 2013 Mahi-Mahi
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `wp-analytics.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package WP_Analytics_Admin
 * @author  olivM <olivier.mourlevat@mahi-mahi.fr>
 */
class WP_Analytics_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 *
		 * @TODO:
		 *
		 * - Rename "WP_Analytics" to the name of your initial plugin class
		 *
		 */
		$plugin = WP_Analytics::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( '@TODO', array( $this, 'action_method_name' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		add_action( 'admin_footer', array( $this, 'admin_footer_set_missing_ids' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @TODO:
	 *
	 * - Rename "WP_Analytics" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WP_Analytics::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @TODO:
	 *
	 * - Rename "WP_Analytics" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		$screen = get_current_screen();

		if ( $screen->base == 'post' ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-meta-box-script', plugins_url( 'assets/js/meta-box.js', __FILE__ ), array( 'jquery', 'jquery-ui-tabs' ), WP_Analytics::VERSION );
		}

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), WP_Analytics::VERSION );
		}

		wp_enqueue_script( 'google-jsapi', 'https://www.google.com/jsapi');

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * @TODO:
		 *
		 * - Change 'Page Title' to the title of your plugin admin page
		 * - Change 'Menu Text' to the text for menu item for the plugin settings page
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'WP-Analytics', $this->plugin_slug ),
			__( 'WP-Analytics', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}


	public function add_meta_box( $post_type ) {
		$post_types = array('post', 'page');     //limit meta box to certain post types
		if ( in_array( $post_type, $post_types )) {

			add_meta_box(
				'wp_analytics_meta_box'
				,__( 'WP Analytics', $this->plugin_slug )
				,array( $this, 'render_meta_box_content' )
				,$post_type
				,'advanced'
				,'high'
			);

		}
	}


	public function admin_footer_set_missing_ids(){
		global $wpdb;

		if ( defined('WP_ANALYTICS_DISABLE_MISSING_URLS') )
			return;

		$plugin = WP_Analytics::get_instance();

		$nb_urls = defined('WP_ANALYTICS_NB_MISSING_URLS') ? constant('WP_ANALYTICS_NB_MISSING_URLS') : 5;

		$missing_urls = $wpdb->get_col("SELECT DISTINCT url FROM {$plugin->table_name} WHERE content_id IS NULL AND content_type IS NULL AND content_kind IS NULL ORDER BY RAND() LIMIT 0, ".$nb_urls);
		?>
		<script>
			var missing_urls = <?php print json_encode($missing_urls) ?>;
			jQuery.each(missing_urls, function(idx, url){
				jQuery.post(url,{
					action: 'wp_analytics_missing_url'
				});
			});
		</script>
		<?php
	}


	public function render_meta_box_content( $post ) {

		include(constant('WP_Analytics_DIR').'/admin/views/meta_box.php');

	}



	private function debug($s) {
		if ( $this->debug )
			$this->log($s);
	}

	private function log($s) {
		if ( is_string($s) ):
			$output = "[WP-Analytics] ".$s;
		else:
			$output = "[WP-Analytics] ".PHP_EOL;
			$output .= print_r($s, true);
		endif;

		if ( defined('WP_CLI') ):
			print $output."\n";
		else:
			error_log($output);
		endif;

	}
}


















