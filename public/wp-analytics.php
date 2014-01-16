<?php
/**
 * Plugin Name.
 *
 * @package   WP_Analytics
 * @author    olivM <olivier.mourlevat@mahi-mahi.fr>
 * @license   GPL-2.0+
 * @link      http://mahi-mahi.fr/
 * @copyright 2013 Mahi-Mahi
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `wp-analytics-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package WP_Analytics
 * @author  olivM <olivier.mourlevat@mahi-mahi.fr>
 */
class WP_Analytics {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * @TODO - Rename "plugin-name" to the name your your plugin
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'wp-analytics';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	protected static $service = null;

	protected $debug = true;

	protected $table_name;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( '@TODO', array( $this, 'action_method_name' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		if ( defined('WP_CLI') && WP_CLI )
			require_once(constant('WP_Analytics_DIR').'/public/wp-cli.php');

		global $wpdb;
		$this->table_name = $wpdb->prefix.'analytics';

	}

	function wp_head() {

		if ( is_single() ):
			$this->update_stats($_SERVER['REQUEST_URI'], get_queried_object());
		endif;

	}

	function fetch_top($period = null) {
		global $wpdb;

		$this->log("WP_Analytics::fetch_top");

		$timeout = is_local() ? 5 : 500;

		if ( get_option('wp_analytics_fetch_top_'.$period) + $timeout > time() )
			return;

		update_option('wp_analytics_fetch_top_'.$period, time());

		switch($period):
			default:
			case 'day':
				$period_start = date('Y-m-d');
				$period_end = date('Y-m-d');
				$period_string = date('Ymd');
			break;
			case 'hour':
				$period_start = date('Y-m-d h:0:0');
				$period_end = date('Y-m-d h:59:59');
				$period_string = date('YmdH');
			break;
		endswitch;

		if ( $this->get_service() ):

			try {
				// nb pageviews for top 500 pages on particular period

				$results = $this->service->data_ga->get(
					$this->ga_account,
					$period_start,
					$period_end,
					'ga:pageviews',
					array(
						'dimensions'	=> 'ga:pagePath',
						'sort' 			=> '-ga:pageviews',
						'max-results'	=> is_local() ? 5 : 500
					)
				);

				$this->debug($results);

				foreach($results->rows as $row):
					$wpdb->replace($this->table_name, array(
						'url' => $row[0],
						'period' => $period_string,
						'count_value' => $row[1]
					));
				endforeach;

			} catch (apiServiceException $e) {
				// Handle API service exceptions.
				$error = $e->getMessage();
				$this->log($error);
			}
		endif;


	}

	// Force update stats on a particular url
	// also update content_kind, content_type and content_id
	// WARNING : dont spam this call !!!!

	private function update_stats($url = null, $queried_object = null) {
		$this->debug("update_stats($url);");
		$this->debug(get_queried_object());

		if ( $this->get_service() ):

			// @TODO

			switch($this->period):
				default:
				case 'day':
					$period_start = date('Y-m-d');
					$period_end = date('Y-m-d');
				break;
			endswitch;

			try {

				// nb pageviews for on url on particular period
				$results = $this->service->data_ga->get(
					$this->ga_account,
					$period_start,
					$period_end,
					'ga:pageviews',
					array(
						'filters' => 'ga:pagePath=='.$url
					)
				);

				$this->debug($results);

			} catch (apiServiceException $e) {
				// Handle API service exceptions.
				$error = $e->getMessage();
				$this->log($error);
			}


		endif;

	}

	private function get_service() {

		if ( $this->service === null ):

			$this->debug(constant('WP_Analytics_ConfigDir'));

			// Load Config file

			$config_file = constant('WP_Analytics_ConfigDir').'/config.php';

			if ( ! is_file($config_file) ):
				$this->log("configuration not found : ".constant('WP_Analytics_ConfigDir').'/config.php');
				return $this->service = false;
			endif;

			require_once($config_file);
			foreach($wp_analytics_config as $k => $v)
				$this->{$k} = $v;

			// Check Key File

			$key_file = constant('WP_Analytics_ConfigDir').'/privatekey.p12';

			if ( ! is_file($key_file) ):
				$this->log("key file not found : ".$key_file);
				return $this->service = false;
			endif;

			require_once(constant('WP_Analytics_DIR').'/includes/google-api-php-client/src/Google_Client.php');
			require_once(constant('WP_Analytics_DIR').'/includes/google-api-php-client/src/contrib/Google_AnalyticsService.php');

			// session_start();

			$client = new Google_Client();

			// $client->setApplicationName("WP Plugin");
			$client->setApplicationName("WP Analytics");

			if (isset($_SESSION['token'])) {
				$client->setAccessToken($_SESSION['token']);
			}

			$key = file_get_contents($key_file);
			$client->setClientId($this->client_id);
			$client->setAssertionCredentials(new Google_AssertionCredentials(
				$this->service_account_name,
				array('https://www.googleapis.com/auth/analytics'),
				$key)
			);
			$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
			$client->setUseObjects(true);

			$this->service = new Google_AnalyticsService($client);

		endif;

		return true;

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

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here


		/*
		CREATE TABLE `emn_analytics` (
			`url` varchar(255) NOT NULL,
			`content_kind` int(11) NOT NULL,
			`content_id` int(11) NOT NULL,
			`content_type` int(11) NOT NULL,
			`period` varchar(32) NOT NULL,
			`count_value` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`url`,`period`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		*/

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

}
