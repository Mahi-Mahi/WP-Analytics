<?php
/**
 * WP_Analytics_CLI
 */
class WP_Analytics_CLI extends WP_CLI_Command {

	function fetch_top() {
		$wp_analytics = WP_Analytics::get_instance();
		$wp_analytics->fetch_top();
	}

}

WP_CLI::add_command( 'wp_analytics', 'WP_Analytics_CLI' );

