<?php
/**
 * WP_Analytics_CLI
 */
class WP_Analytics_CLI extends WP_CLI_Command {

	function fetch_top() {
		$wp_analytics = WP_Analytics::get_instance();
		$wp_analytics->fetch_top();
		// $wp_analytics->fetch_top('day');
		// $wp_analytics->fetch_top('day', strtotime('yesterday'));
		// $wp_analytics->fetch_top('day', strtotime('-7 days'));
		// $wp_analytics->fetch_top('day', strtotime('last week'));
		// $wp_analytics->fetch_top('day', strtotime('last month'));
		// $wp_analytics->fetch_top('day', strtotime('-3 month'));
		// $wp_analytics->fetch_top('month', strtotime('last month'));
		// $wp_analytics->fetch_top('month', strtotime('-2 year'));
	}

}

WP_CLI::add_command( 'wp_analytics', 'WP_Analytics_CLI' );
