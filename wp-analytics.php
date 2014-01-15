<?php
/**
 * @package   wp-analytics
 * @author    olivM <olivier.mourlevat@gmail.com>
 * @license   GPL-2.0+
 * @link      http://mahi-mahi.fr
 * @copyright 2013 Mahi-Mahi
 *
 * @wordpress-plugin
 * Plugin Name:       WP-Analytics
 * Plugin URI:        http://wordpress.org/extend/plugins/wp-ga
 * Description:       Get data from Google Analytics
 * Version:           1.0.0
 * Author:            olivM
 * Text Domain:       plugin-name-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/Mahi-Mahi/wp-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace `wp-analytics.php` with the name of the plugin's class file
 *
 */
require_once( plugin_dir_path( __FILE__ ) . 'public/wp-analytics.php' );

define('WP_Analytics_DIR', constant('WP_PLUGIN_DIR').'/'.basename(dirname(__FILE__)));
define('WP_Analytics_PATH', '/'.str_replace(constant('ABSPATH'), '', constant('WP_Analytics_DIR')));
define('WP_Analytics_URL', constant('WP_PLUGIN_URL').'/'.basename(dirname(__FILE__)));

define('WP_Analytics_ConfigDir', constant('WP_CONTENT_DIR').'/'.basename(dirname(__FILE__)));


/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 * @TODO:
 *
 * - replace WP_Analytics with the name of the class defined in
 *   `wp-analytics.php`
 */
register_activation_hook( __FILE__, array( 'WP_Analytics', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Analytics', 'deactivate' ) );

/*
 * @TODO:
 *
 * - replace WP_Analytics with the name of the class defined in
 *   `wp-analytics.php`
 */
add_action( 'plugins_loaded', array( 'WP_Analytics', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace `class-plugin-admin.php` with the name of the plugin's admin file
 * - replace WP_Analytics_Admin with the name of the class defined in
 *   `wp-analytics-admin.php`
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/wp-analytics-admin.php' );
	add_action( 'plugins_loaded', array( 'WP_Analytics_Admin', 'get_instance' ) );

}
