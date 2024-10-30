jQuery(document).ready(function($) {
	// We can also pass the url value separately from ajaxurl for front end AJAX implementations
	jQuery('#manually-trigger-cs-sync').click( function(e) {
		jQuery(this).addClass('disabled saving');

		var data = {
			'action': 'coursestorm_sync',
			'subdomain': jQuery('#coursestorm-settings-subdomain').val(),
			'original_subdomain': jQuery('#coursestorm-settings-original-subdomain').val(),
			'manual': true,
			'nonce': jQuery(this).data('nonce')
		};

		e.preventDefault();
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			jQuery('<div class="notice notice-info"><p>Class import pending. <a href="" onClick="location.reload();">Refresh to check import status</a></p></div>').insertAfter('#wpbody-content .wrap > h1');

			jQuery(this).removeClass('disabled saving');
		}.bind(this));
	} );

	jQuery('#manually-trigger-cs-settings-sync').click( function(e) {
		jQuery(this).addClass('disabled saving');

		var redirect = jQuery(this).data('redirect');
		
		var data = {
			'action': 'coursestorm_settings_sync',
			'subdomain': jQuery('#coursestorm-settings-subdomain').val(),
			'original_subdomain': jQuery('#coursestorm-settings-original-subdomain').val(),
			'display_status': true,
			'manual': true,
			'nonce': jQuery(this).data('nonce')
		};

		e.preventDefault();
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			window.location = redirect;
		}).fail(function() {
			window.location = redirect;
		});
	} );

	jQuery('.show-settings').click( function(e) {
		e.preventDefault();
		jQuery('.settings-form').fadeToggle();
	} );

	jQuery('.settings-form').submit( function(e) {
		e.preventDefault();

		jQuery('#submit').addClass('disabled saving').after('<span class="load-spinner"><img src="/wp-admin/images/wpspin_light-2x.gif" /></span>');

		var redirect = jQuery(this).data('redirect');

		var data = {
			'action': 'coursestorm_options_save',
			'subdomain': jQuery('#coursestorm-settings-subdomain').val(),
			'original_subdomain': jQuery('#coursestorm-settings-original-subdomain').val(),
			'cron_schedule': jQuery('#coursestorm-settings-cron_schedule').val(),
			'cart_options': {
				'view_cart_location': jQuery('#coursestorm-settings-view_cart_location').val()
			},
			'nonce': jQuery('#_wpnonce').val()
		};

		jQuery.post(ajax_object.ajax_url, data, function(response) {
			window.location = redirect;
		}.bind(this));
	})
});