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

	public $table_name;

	protected $defined_periods =  array(
											'day' => array(
															'pattern' => '^[0-9]{8}$',
															'delay' => "-1 MONTH",
															'current_period' => 'Ymd',
															'adapted_period' => '^([\d]{4})([\d]{2})([\d]{2})$',
															'adapted_replace' => '$1-$2-$3 00:00:00',
															'displayed_period' => '%A %d %B %Y'
														),
											'month' => array(
															'pattern' => '^[0-9]{6}$',
															'delay' => "-1 YEAR",
															'current_period' => 'Ym',
															'adapted_period' => '^([\d]{4})([\d]{2})$',
															'adapted_replace' => '$1-$2-01 00:00:00',
															'displayed_period' => '%B %Y'
														),
											'year' => array(
															'pattern' => '^[0-9]{4}$',
															'current_period' => 'Y',
															'adapted_period' => '^([\d]{4})$',
															'adapted_replace' => '$1-01-01 00:00:00',
															'displayed_period' => '%Y'
														)
										);

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

		add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		add_action( 'wp_head', array( $this, 'set_missing_url_ids' ), 1 );

		if ( defined('WP_CLI') && WP_CLI )
			require_once(constant('WP_Analytics_DIR').'/public/wp-cli.php');

		require_once(constant('WP_Analytics_DIR').'/public/includes/functions.php');

		global $wpdb;
		$this->table_name = $wpdb->prefix.'analytics';

	}

	function wp_head() {

		if ( is_single() ):
			$this->update_stats($_SERVER['REQUEST_URI'], get_queried_object());
		endif;

	}


	protected function cleanup(){
		global $wpdb;

		foreach($this->defined_periods as $period):

			if ( isset($period['delay']) ):

				$sql = "DELETE FROM ".$this->table_name." WHERE `period` RLIKE '".$period['pattern']."' AND `period` < '".date('Ym', strtotime($period['delay']))."' ";

				$wpdb->query($sql);

			endif;

		endforeach;

		$sql = "DELETE FROM ".$this->table_name." WHERE `period` RLIKE '^[0-9]{8}\-[0-9\.]+$' AND `period` NOT LIKE '".date('Ymd')."-%' ";

		$wpdb->query($sql);
	}

	function fetch_top($period = '', $date = null) {
		global $wpdb;

		if ( ! $date )
			$date = time();

		$this->log("WP_Analytics::fetch_top($period, $time");

		switch($period):
			default:
			case '':
			case 'day':
				$period_start = date('Y-m-d', $date);
				$period_end = date('Y-m-d', $date);
				$period_string = date('Ymd', $date);
			break;
			case 'month':
				$period_start = date('Y-m-01', $date);
				$period_end = date('Y-m-t', $date);
				$period_string = date('Ym', $date);
			break;
			case 'year':
				$period_start = date('Y-01-01', $date);
				$period_end = date('Y-12-t', $date);
				$period_string = date('Y', $date);
			break;
		endswitch;

		$timeout = is_local() ? 5 : 3600;

		if ( get_option('wp_analytics_fetch_top_'.$period_string) + $timeout > time() )
			return;

		update_option('wp_analytics_fetch_top_'.$period, time());

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
						'max-results'	=> 500
					)
				);

				$this->debug($results);

				if ( is_array($results->rows) ):
					foreach($results->rows as $row):
						$wpdb->replace($this->table_name, array(
							'url' => $row[0],
							'period' => $period_string,
							'count_value' => $row[1]
						));
					endforeach;
				endif;

			} catch (apiServiceException $e) {
				// Handle API service exceptions.
				$error = $e->getMessage();
				$this->log($error);
			}
		endif;

		$this->cleanup();

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

	private function set_ids($url, $content_id, $content_type = 'post', $content_kind = 'post') {
		global $wpdb;
		logr("set_ids($url, $content_id, $content_type");
		$wpdb->query("UPDATE {$this->table_name} SET content_id = {$content_id}, content_type = '{$content_type}', content_kind = '{$content_kind}' WHERE url = '{$url}' ");
	}

	public function set_missing_url_ids(){

		if ( $_POST['action'] == 'wp_analytics_missing_url' ):
			$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

			if ( is_singular() ):
				$this->set_ids($url, get_the_ID(), get_queried_object()->post_type );
				exit();
			endif;

			if ( is_tax() ):
				$this->set_ids($url, get_queried_object()->term_id, get_queried_object()->taxonomy, 'term' );
				exit();
			endif;

			if ( is_date() ):
				$this->set_ids($url, preg_replace("#[^\d]#", '', $url), 'date', 'archive' );
				exit();
			endif;

			if ( is_home() or is_front_page() ):
				$this->set_ids($url, 0, 'page', 'post' );
				exit();
			endif;

			logr("wp_analytics_missing_url($url : NOT FOUND");

			exit();
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

	// GET

	public function get($period, $post_id, $content_kind = 'post', $content_type = 'post'){
		global $wpdb;

		$sql = "SELECT `count_value` FROM {$this->table_name} WHERE `content_id` = {$post_id} AND `period` = '{$period}' ";

		$val = $wpdb->get_var($sql);

		return $val;
	}

	public function gets($period, $post_id, $content_kind = 'post', $content_type = 'post'){
		global $wpdb;

		logr("gets($period, $post_id, $content_kind, $content_type");

		$period = $this->get_period($period);

		logr($period);

		$period_regex = $period['pattern'];

		$sql = "SELECT `period`, `count_value` FROM {$this->table_name} WHERE `content_id` = {$post_id} AND `period` RLIKE '{$period_regex}' ";

		logr($sql);

		$values = $wpdb->get_results($sql);

		return $values;

	}

	private function get_period($period) {
		return $this->defined_periods[$period];
	}


	// QUERY Filters

	public function posts_join($join, $wp_query){
		global $wpdb;

		if(isset($wp_query->query_vars['orderby']) && ($wp_query->query_vars['orderby']=='analytics')):

			$join .= " LEFT JOIN ".$this->table_name." ON {$wpdb->posts}.ID = ".$this->table_name.".content_id ";

		endif;

		return $join;
	}


	public function posts_where($where, $wp_query) {
		global $wpdb;

		if(isset($wp_query->query_vars['orderby']) && ($wp_query->query_vars['orderby']=='wpcount')):

			if ( isset($wp_query->query_vars['analytics_period']) ):
				$where .= " AND ".$this->table_name.".period = '".$wp_query->query_vars['analytics_period']."'";
			else:
				$where .= " AND ".$this->table_name.".period = ''";
			endif;

			$where .= " AND ".$this->table_name.".content_type = 'post' ";
			$where .= " AND ".$this->table_name.".content_kind = {$wpdb->posts}.post_type ";

		endif;

		return $where;
	}

	public function posts_orderby($orderby, $wp_query){

		if(isset($wp_query->query_vars['orderby']) && ($wp_query->query_vars['orderby']=='analytics')):

			$orderby = " ".$this->table_name.".count_value ".$wp_query->query_vars['order'];

		endif;

		return $orderby;
	}

	public function posts_groupby($groupby, $wp_query) {
		global $wpdb;

		if ( $wp_query->query_vars['post_type'] == $this->content_type && isset($wp_query->query_vars['wpcount_type']) && ($wp_query->query_vars['wpcount_type']==$this->count_type)):
				//$groupby = " {$wpdb->posts}.ID ";
		endif;

		return $groupby;
	}

	public function posts_fields($fields, $wp_query) {
		global $wpdb;

		if ( $wp_query->query_vars['post_type'] == $this->content_type && isset($wp_query->query_vars['wpcount_type']) && ($wp_query->query_vars['wpcount_type']==$this->count_type)):

			$alias = $this->generate_alias($wp_query->query_vars, 'post');

			if($wp_query->query_vars['wpcount_cheat'])
				$fields = " {$wpdb->posts}.*, (".$alias.".count_value + ".$alias.".cheat) as `count_value` ";
			else
				$fields = " {$wpdb->posts}.*, (".$alias.".count_value) as `count_value` ";

		endif;

		return $fields;
	}

	public function posts_distinct($distinct, $wp_query) {

		if ( isset($wp_query->query_vars['wpcount_type']) && ($wp_query->query_vars['wpcount_type']==$this->count_type)):
				//$distinct = " DISTINCT ";
		endif;

		return $distinct;
	}



	public function before_delete_post($post_id){
		global $wpdb;

		$post = get_post($post_id);
		$wpdb->query('DELETE FROM '.$this->table_name.' WHERE content_id='.$post_id.' AND content_kind = "'.$post->post_type.'" ');

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
		global $wpdb;

		$sql = "
		CREATE TABLE `{$this->table_name}` (
			`url` varchar(255) NOT NULL,
			`content_id` int(11) DEFAULT NULL,
			`content_type` varchar(32) DEFAULT NULL,
			`content_kind` varchar(32) DEFAULT NULL,
			`period` varchar(32) NOT NULL,
			`count_value` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`url`,`period`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

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
