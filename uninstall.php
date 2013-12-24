<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   WP_Analytics
 * @author    olivM <olivier.mourlevat@mahi-mahi.fr>
 * @license   GPL-2.0+
 * @link      http://mahi-mahi.fr/
 * @copyright 2013 Mahi-Mahi
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// @TODO: Define uninstall functionality here