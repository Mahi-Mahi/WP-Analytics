<?php

function wp_analytics_set_ids($url = null, $content_id = null, $content_type = 'post', $content_kind = 'post') {
	// TODO : handle different content_kind
	if ( ! $url )
		$url = $_SERVER['REQUEST_URI'];
	if ( ! $url )
		return;

	$url = preg_replace("#http://[^/]+/#", '/', $url);

	if ( ! $content_id )
		$content_id = get_the_ID();

	if ( ! $content_id )
		return;

	$wp_analytics = WP_Analytics::get_instance();

	$wp_analytics->set_ids($url, $content_id, $content_kind, $content_type);

}

function wp_analytics_get($period = '', $content_id = null, $content_kind = 'post', $content_type = 'post') {
	// TODO : handle different content_kind
	if ( ! $content_id )
		$content_id = get_the_ID();

	if ( ! $content_id )
		return;

	$wp_analytics = WP_Analytics::get_instance();

	return $wp_analytics->get($period, $content_id, $content_kind, $content_type);

}

function wp_analytics_gets($period = 'day', $content_id = null, $content_kind = 'post', $content_type = 'post') {
	// TODO : handle different content_kind
	if ( ! $content_id )
		$content_id = get_the_ID();

	if ( ! $content_id )
		return;

	$wp_analytics = WP_Analytics::get_instance();

	return $wp_analytics->gets($period, $content_id, $content_kind, $content_type);

}
