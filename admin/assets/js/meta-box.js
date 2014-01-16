(function($) {
	"use strict";

	$(function() {

		// Place your administration-specific JavaScript here

		console.log("admin.js");

		if ($("#wp_analytics_meta_box").length) {

			$("#wp_analytics_meta_box .meta-box-sortables").tabs();
		}

	});

}(jQuery));