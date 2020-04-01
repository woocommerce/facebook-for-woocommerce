/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

var fb_sync_no_response_count = 0;
var fb_show_advanced_options  = false;


function openPopup() {
	var width          = 1153;
	var height         = 808;
	var topPos         = screen.height / 2 - height / 2;
	var leftPos        = screen.width / 2 - width / 2;
	window.originParam = window.location.protocol + '//' + window.location.host;
	var popupUrl;
	if (window.facebookAdsToolboxConfig.popupOrigin.includes( 'staticxx' )) {
		window.facebookAdsToolboxConfig.popupOrigin = 'https://www.facebook.com/';
	}
	window.facebookAdsToolboxConfig.popupOrigin = prepend_protocol(
		window.facebookAdsToolboxConfig.popupOrigin
	);
	popupUrl                                    = window.facebookAdsToolboxConfig.popupOrigin;

	var path = '/ads/dia';
	var page = window.open( popupUrl + '/login.php?display=popup&next=' + encodeURIComponent( popupUrl + path + '?origin=' + window.originParam + ' &merchant_settings_id=' + window.facebookAdsToolboxConfig.diaSettingId ), 'DiaWizard', ['toolbar=no', 'location=no', 'directories=no', 'status=no', 'menubar=no', 'scrollbars=no', 'resizable=no', 'copyhistory=no', 'width=' + width, 'height=' + height, 'top=' + topPos, 'left=' + leftPos].join( ',' ) );

	return function (type, params) {
		page.postMessage(
			{
				type: type,
				params: params
			},
			window.facebookAdsToolboxConfig.popupOrigin
		);
	};
}

function prepend_protocol(url) {
	// Preprend https if the url begis with //www.
	if (url.indexOf( '//www.' ) === 0) {
		url = 'https:' + url;
	}
	return url;
}


/**
 * Gets the input Element that holds the value for the Pixel ID setting.
 *
 * @returns {(Element|null)}
 */
function get_pixel_id_box() {

	return document.querySelector( '#woocommerce_facebookcommerce_facebook_pixel_id' );
}


/**
 * Gets the input Element that holds the value for the Use Advanced Matching setting.
 *
 * @returns {(Element|null)}
 */
function get_pixel_use_pii_id_box() {

	return document.querySelector( '#woocommerce_facebookcommerce_enable_advanced_matching' );
}


/**
 * Gets the input Element that holds the value for the Facebook page setting.
 *
 * @return {(Element|null)}
 */
function get_page_id_box() {

	return document.querySelector( '#woocommerce_facebookcommerce_facebook_page_id' );
}


/*
 *  Ajax helper function.
 *  Takes optional payload for POST and optional callback.
 */
function ajax(action, payload = null, callback = null, failcallback = null) {
	var data = Object.assign(
		{},
		{
			'action': action,
		},
		payload
	);

	// Since  Wordpress 2.8 ajaxurl is always defined in admin header and
	// points to admin-ajax.php
	jQuery.post(
		ajaxurl,
		data,
		function(response) {
			if (callback) {
					  callback( response );
			}
		}
	).fail(
		function(errorResponse){
			if (failcallback) {
					  failcallback( errorResponse );
			}
		}
	);
}

var settings       = {'facebook_for_woocommerce' : 1};
var pixel_settings = {'facebook_for_woocommerce' : 1};

function facebookConfig() {
	window.sendToFacebook = openPopup();
	window.diaConfig      = { 'clientSetup': window.facebookAdsToolboxConfig };
}

function fb_flush(){
	console.log( "Removing all FBIDs from all products!" );
	return ajax(
		 'ajax_reset_all_fb_products',
		 {"_ajax_nonce": wc_facebook_settings_jsx.nonce},
		 null,
		 function fb_flushFailCallback(error) {
			 console.log('Failed to reset all FB products');
		 }
	 );
}


/**
 * Shows a confirm dialog and starts product sync if the user selectes OK.
 *
 * @param {String} verbose an identifier for the confirmation message to display
 */
function sync_confirm(verbose = null) {

	let msg = '';

	switch (verbose) {

		case 'fb_force_resync':
			msg = facebook_for_woocommerce_settings_sync.i18n.confirm_resync;
		break;

		case 'fb_test_product_sync':
			msg = facebook_for_woocommerce_settings_sync.i18n.confirm_sync_test;
		break;

		default:
			msg = facebook_for_woocommerce_settings_sync.i18n.confirm_sync;
	}

	if ( confirm( msg ) ) {

		sync_all_products( window.facebookAdsToolboxConfig.feed.hasClientSideFeedUpload, verbose == 'fb_test_product_sync' );

		window.fb_sync_start_time = new Date().getTime();
	}
}


// Launch the confirm dialog immediately if the param is in the URL.
if (window.location.href.includes( "fb_force_resync" )) {
	window.onload = function() { sync_confirm( "fb_force_resync" ); };
} else if (window.location.href.includes( "fb_test_product_sync" )) {
	// Test products sync by feed.
	window.is_test = true;
	window.onload  = function() { sync_confirm( "fb_test_product_sync" ); };
}


/**
 * Sends Ajax request to the backend to initiate product sync.
 *
 * @param {boolean} feed whether products should be synced using feed or not
 * @param {boolean} test whether this is a sync test
 */
function sync_all_products($using_feed = false, $is_test = false) {

	window.fb_connected = true;
	sync_in_progress();

	let data = {};

	if ( $using_feed ) {

		window.facebookAdsToolboxConfig.feed.hasClientSideFeedUpload = true;
		window.feed_upload = true;

		ping_feed_status_queue();

		if ( $is_test ) {

			data = { action: 'ajax_test_sync_products_using_feed' };

		} else {

			data = {
				action:      'ajax_sync_all_fb_products_using_feed',
				_ajax_nonce: wc_facebook_settings_jsx.nonce,
			};

		}

	} else {

		check_background_processor_status();

		data = {
			action:      'ajax_sync_all_fb_products',
			_ajax_nonce: wc_facebook_settings_jsx.nonce,
		};
	}

	jQuery.post( ajaxurl, data ).then( function( response ) {

		// something is wrong if we are syncing products using feed and the response is empty or indicates a failure
		// we ignore empty responses if using the background processor because in those cases the request does not return a response when the operation is successful
		if ( ( ! response && $using_feed ) || ( response && false === response.success ) ) {

			// no need to check the queue or upload status
			clearInterval( window.fb_pings );
			clearInterval( window.fb_feed_pings );

			// enable Manage connection and Sync products buttons when sync stops
			jQuery( '#woocommerce-facebook-settings-manage-connection, #woocommerce-facebook-settings-sync-products' ).css( 'pointer-events', 'auto' );

			let message;

			if ( response && response.data && response.data.error ) {
				message = response.data.error;
			} else {
				message = facebook_for_woocommerce_settings_sync.i18n.general_error;
			}

			$( '#sync_progress' ).show().html( '<span style="color: #DC3232">' + message + '</span>' );
		}
	} );
}


// Reset all state
function delete_all_settings(callback = null, failcallback = null) {

	if (get_pixel_id_box()) {
		get_pixel_id_box().value = '';
	}
	if (get_pixel_use_pii_id_box()) {
		get_pixel_use_pii_id_box().checked = false;
	}

	if (get_page_id_box()) {
		get_page_id_box().value = '';
	}

	// reset messenger settings to their default values
	jQuery( '#woocommerce_facebookcommerce_enable_messenger' ).prop( 'checked', false ).trigger( 'change' );

	jQuery( '.messenger-field' ).each( function () {

		if ( typeof $( this ).data( 'default' ) !== 'undefined' ) {
			$( this ).val( $( this ).data( 'default' ) ).trigger( 'change' );
		}
	} );

	window.facebookAdsToolboxConfig.pixel.pixelId = '';
	window.facebookAdsToolboxConfig.diaSettingId  = '';
	window.fb_connected = false;

	not_connected();

	console.log( 'Deleting all settings and removing all FBIDs!' );
	return ajax(
		'ajax_delete_fb_settings',
		{
			"_ajax_nonce": wc_facebook_settings_jsx.nonce,
		},
		callback,
		failcallback
	);
}

// save_settings and save_settings_and_sync should only be called once
// after all variables are set up in the settings global variable
// if called multiple times, race conditions might occur
// ---
// It's also called again if the pixel id is ever changed or pixel pii is
// enabled or disabled.
function save_settings(callback = null, failcallback = null, localsettings = null){
	if ( ! localsettings) {
		localsettings = settings;
	}
	localsettings["_ajax_nonce"] = wc_facebook_settings_jsx.nonce;
	ajax(
		'ajax_save_fb_settings',
		localsettings,
		function(response){
			if (callback) {
				callback( response );
			}
		},
		function(errorResponse){
			if (failcallback) {
				failcallback( errorResponse );
			}
		}
	);
}

// save_settings wrapper for plugins as we do not need to:
// 1.  sync products again after plugin is configured
// 2.  check api_key, which is from facebook and is only necessary
// for following sync products
function save_settings_for_plugin(callback, failcallback) {
	save_settings(
		function(response){
			if (response && response.includes( 'settings_saved' )) {
				console.log( response );
				callback( response );
			} else {
				console.log( 'Fail response on save_settings_and_sync' );
				failcallback( response );
			}
		},
		function(errorResponse){
			console.log( 'Ajax error while saving settings:' + JSON.stringify( errorResponse ) );
			failcallback( errorResponse );
		}
	);
}

// see comments in save_settings function above
function save_settings_and_sync(message) {
	if ('api_key' in settings) {
		save_settings(
			function(response){
				if (response && response.includes( 'settings_saved' )) {
					console.log( response );
					// Final acks
					window.sendToFacebook( 'ack set pixel', message.params );
					window.sendToFacebook( 'ack set page access token', message.params );
					window.sendToFacebook( 'ack set merchant settings', message.params );
					// sync_all_products( true ); TODO: reinstate when switching back to FBE 2
				} else {
					window.sendToFacebook( 'fail save_settings', response );
					console.log( 'Fail response on save_settings_and_sync' );
				}
			},
			function(errorResponse){
				console.log( 'Ajax error while saving settings:' + JSON.stringify( errorResponse ) );
				window.sendToFacebook( 'fail save_settings_ajax', JSON.stringify( errorResponse ) );
			}
		);
	}
}


/**
 * Prepares UI for product sync.
 */
function sync_in_progress() {

	// temporarily disable Manage connection and Sync products buttons
	jQuery( '#woocommerce-facebook-settings-manage-connection, #woocommerce-facebook-settings-sync-products' ).css( 'pointer-events', 'none' );

	// set products sync status
	jQuery( '#sync_progress' ).show().html( facebook_for_woocommerce_settings_sync.i18n.sync_in_progress );
}


/**
 * Hides sync progress and enable Manage connection and Sync products buttons.
 */
function sync_not_in_progress() {

	// enable Manage connection and Sync products buttons when sync is complete
	jQuery( '#woocommerce-facebook-settings-manage-connection, #woocommerce-facebook-settings-sync-products' ).css( 'pointer-events', 'auto' );

	// Remove sync progress.
	jQuery( '#sync_progress' ).empty().hide();
}


/**
 * Shows Facebook fancy box if the store is still not connected to Facebook.
 *
 * Also hides the integration settings fields.
 */
function not_connected() {

	jQuery( '#fbsetup' ).show();
	jQuery( '#integration-settings' ).hide();
	jQuery( '.woocommerce-save-button' ).hide();
}

function addAnEventListener(obj,evt,func) {
	if ('addEventListener' in obj) {
		obj.addEventListener( evt,func, false );
	} else if ('attachEvent' in obj) {// IE
		obj.attachEvent( 'on' + evt,func );
	}
}

function setMerchantSettings(message) {
	if ( ! message.params.setting_id) {
		console.error( 'Facebook Extension Error: got no setting_id', message.params );
		window.sendToFacebook( 'fail set merchant settings', message.params );
		return;
	}

	settings.external_merchant_settings_id = message.params.setting_id;

	// Immediately set in case button is clicked again
	window.facebookAdsToolboxConfig.diaSettingId = message.params.setting_id;
	// Ack merchant settings happens after settings are saved
}

function setCatalog(message) {
	if ( ! message.params.catalog_id) {
		console.error( 'Facebook Extension Error: got no catalog_id', message.params );
		window.sendToFacebook( 'fail set catalog', message.params );
		return;
	}

	settings.product_catalog_id = message.params.catalog_id;

	window.sendToFacebook( 'ack set catalog', message.params );
}


function setPixel(message) {
	if ( ! message.params.pixel_id) {
		console.error( 'Facebook Ads Extension Error: got no pixel_id', message.params );
		window.sendToFacebook( 'fail set pixel', message.params );
		return;
	}
	if (get_pixel_id_box()) {
		get_pixel_id_box().value = message.params.pixel_id;
	}

	settings.pixel_id       = message.params.pixel_id;
	pixel_settings.pixel_id = settings.pixel_id;
	if (message.params.pixel_use_pii !== undefined) {
		if (get_pixel_use_pii_id_box()) {
			// !! will explicitly convert truthy/falsy values to a boolean
			get_pixel_use_pii_id_box().checked = ! ! message.params.pixel_use_pii;
		}
		settings.pixel_use_pii       = message.params.pixel_use_pii;
		pixel_settings.pixel_use_pii = settings.pixel_use_pii;
	}

	// We need this to support changing the pixel id after setup.
	save_settings(
		function(response){
			if (response && response.includes( 'settings_saved' )) {
				window.sendToFacebook( 'ack set pixel', message.params );
			} //may not get settings_saved if we try to save pixel before an API key
		},
		function(errorResponse){
			console.log( errorResponse );
			window.sendToFacebook( 'fail set pixel', errorResponse );
		},
		pixel_settings
	);
}

function genFeed( message ) {

	console.log( 'generating feed' );

	$.get( window.facebookAdsToolboxConfig.feedPrepared.feedUrl + '?regenerate=true' )
		.done( function( json ) {
			window.sendToFacebook( 'ack feed', message.params );
		} )
		.fail( function( xhr, ajaxOptions, thronwError ) {
			window.sendToFacebook( 'fail feed', message.params );
		} );
}

function setAccessTokenAndPageId(message) {
	if ( ! message.params.page_token) {
		console.error(
			'Facebook Ads Extension Error: got no page_token',
			message.params
		);
		window.sendToFacebook( 'fail set page access token', message.params );
		return;
	}

	if (get_page_id_box()) {
		get_page_id_box().value = message.params.page_id;
	}

	settings.api_key = message.params.page_token;
	settings.page_id = message.params.page_id;
	// Ack token in "save_settings_and_sync" for final ack

	window.facebookAdsToolboxConfig.tokenExpired = false;

	if ( document.querySelector( '#connection-message-invalid' ) ) {
		document.querySelector( '#connection-message-invalid' ).style.display = 'none';
	}

	if ( document.querySelector( '#connection-message-refresh' ) ) {
		document.querySelector( '#connection-message-refresh' ).style.display = 'block';
	}
}

function setMsgerChatSetup( data ) {

	if ( data.hasOwnProperty( 'is_messenger_chat_plugin_enabled' ) ) {

		settings.is_messenger_chat_plugin_enabled = data.is_messenger_chat_plugin_enabled;

		jQuery( '#woocommerce_facebookcommerce_enable_messenger' ).prop( 'checked', data.is_messenger_chat_plugin_enabled ).trigger( 'change' );
	}

	if (data.hasOwnProperty( 'facebook_jssdk_version' )) {
		settings.facebook_jssdk_version =
		data.facebook_jssdk_version;
	}
	if (data.hasOwnProperty( 'page_id' )) {
		settings.fb_page_id = data.page_id;
	}

	if ( data.hasOwnProperty( 'customization' ) ) {

		const customization = data.customization;

		if ( customization.hasOwnProperty( 'greetingTextCode' ) ) {

			settings.msger_chat_customization_greeting_text_code = customization.greetingTextCode;

			jQuery( '#woocommerce_facebookcommerce_messenger_greeting' ).val( customization.greetingTextCode ).trigger( 'change' );
		}

		if ( customization.hasOwnProperty( 'locale' ) ) {

			settings.msger_chat_customization_locale = customization.locale;

			jQuery( '#woocommerce_facebookcommerce_messenger_locale' ).val( customization.locale ).trigger( 'change' );
		}

		if ( customization.hasOwnProperty( 'themeColorCode' ) ) {

			settings.msger_chat_customization_theme_color_code = customization.themeColorCode;

			jQuery( '#woocommerce_facebookcommerce_messenger_color_hex' ).val( customization.themeColorCode ).trigger( 'change' );
		}
	}
}

function setFeedMigrated(message) {

	if ( ! message.params.hasOwnProperty( 'feed_migrated' ) )  {

		console.error(
			'Facebook Extension Error: feed migrated not received',
			message.params
		);

		window.sendToFacebook( 'fail set feed migrated', message.params );
		return;
	}

	settings.feed_migrated = message.params.feed_migrated;
	window.sendToFacebook( 'ack set feed migrated', message.params );
	window.facebookAdsToolboxConfig.feedPrepared.feedMigrated = message.params.feed_migrated;
}

function iFrameListener(event) {
	// Fix for web.facebook.com
	const origin = event.origin || event.originalEvent.origin;
	if (origin != window.facebookAdsToolboxConfig.popupOrigin &&
	urlFromSameDomain( origin, window.facebookAdsToolboxConfig.popupOrigin )) {
		window.facebookAdsToolboxConfig.popupOrigin = origin;
	}

	switch (event.data.type) {
		case 'reset':
			delete_all_settings(
				function(res){
					if (res && event.data.params) {
						if (res === 'Settings Deleted') {
							window.sendToFacebook( 'ack reset', event.data.params );
						} else {
							console.log( res );
							alert( res );
						}
					} else {
						console.log( "Got no response from delete_all_settings" );
					}
				},
				function(err){
					console.error( err );
				}
			);
		break;
		case 'get dia settings':
			window.sendToFacebook( 'dia settings', window.diaConfig );
		break;
		case 'set merchant settings':
			setMerchantSettings( event.data );
		break;
		case 'set catalog':
			setCatalog( event.data );
		break;
		case 'set pixel':
			setPixel( event.data );
		break;
		case 'set feed migrated':
			setFeedMigrated( event.data );
		break;
		case 'gen feed':
			genFeed();
		break;

		case 'set page access token':
			// should be last message received
			setAccessTokenAndPageId( event.data );
			save_settings_and_sync( event.data );

			// hide Facebook fancy box and show integration settings
			jQuery( '#fbsetup' ).hide();
			jQuery( '#integration-settings' ).show();
			jQuery( '.woocommerce-save-button' ).show();
		break;

		case 'set msger chat':
			setMsgerChatSetup( event.data.params );
			save_settings_for_plugin(
				function(response) {
					window.sendToFacebook( 'ack msger chat', event.data );
				},
				function(response) {
					window.sendToFacebook( 'fail ack msger chat', event.data );
				}
			);
		break;
	}
}

addAnEventListener( window,'message',iFrameListener );

function urlFromSameDomain(url1, url2) {
	if ( ! url1.startsWith( 'http' ) || ! url2.startsWith( 'http' )) {
		return false;
	}
	var u1     = parseURL( url1 );
	var u2     = parseURL( url2 );
	var u1host = u1.host.replace( /^\w+\./, 'www.' );
	var u2host = u2.host.replace( /^\w+\./, 'www.' );
	return u1.protocol === u2.protocol && u1host === u2host;
}

function parseURL(url) {
	var parser  = document.createElement( 'a' );
	parser.href = url;
	return parser;
}


/**
 * Setups an interval to check the status a product sync being executed in the background.
 *
 * @since 1.10.0
 */
function check_background_processor_status() {

	if ( ! window.facebookAdsToolboxConfig.feed.hasClientSideFeedUpload ) {

		// sanity check to remove any running intervals
		clearInterval( window.fb_pings );

		window.fb_pings = setInterval( function() {
			console.log( "Pinging queue..." );
			check_queues();
		}, 10000 );
	}
}


function ping_feed_status_queue(count = 0) {

	// sanity check to remove any running intervals
	clearInterval( window.fb_feed_pings );

	window.fb_feed_pings = setInterval(
		function() {
			console.log( 'Pinging feed uploading queue...' );
			check_feed_upload_queue( count );
		},
		30000 * (1 << count)
	);
}

function product_sync_complete( $sync_progress_element ) {

	sync_not_in_progress();

	$sync_progress_element.empty().hide();

	clearInterval( window.fb_pings );
}


/**
 * Checks the status a product sync being executed in the background.
 */
function check_queues() {
	ajax(
		'ajax_fb_background_check_queue',
		{
			"request_time": new Date().getTime(),
			"_ajax_nonce": wc_facebook_settings_jsx.nonce,
		},
		function( response ) {
			if ( window.feed_upload ) {
				clearInterval( window.fb_pings );
				return;
			}

			const $sync_progress_element = jQuery( '#sync_progress' );

			var res = parse_response_check_connection( response );
			if ( !res ) {
				if ( fb_sync_no_response_count++ > 5 ) {
					clearInterval( window.fb_pings );
				}
				return;
			}
			fb_sync_no_response_count = 0;

			if ( res ) {
				if ( !res.background ) {
					console.log( "No background sync found, disabling pings" );
					clearInterval( window.fb_pings );
				}

				var processing = !!res.processing; // explicit boolean conversion
				var remaining  = res.remaining;

				if ( processing ) {

					let message = '';

					if ( 1 === remaining ) {
						message = facebook_for_woocommerce_settings_sync.i18n.sync_remaining_items_singular;
					} else {
						message = facebook_for_woocommerce_settings_sync.i18n.sync_remaining_items_plural;
					}

					$sync_progress_element.show().html( message.replace( '{count}', remaining ) );

					if ( remaining === 0 ) {
						product_sync_complete( $sync_progress_element );
					}

				} else {
					// Not processing, none remaining.  Either long complete, or just completed
					if ( window.fb_sync_start_time && res.request_time ) {
						  var request_time = new Date( parseInt( res.request_time ) );
						if ( window.fb_sync_start_time > request_time ) {
							// Old ping, do nothing.
							console.log( "OLD PING" );
							return;
						}
					}

					if ( remaining === 0 ) {
						  product_sync_complete( $sync_progress_element );
					}
				}
			}
		}
	);
}

function parse_response_check_connection(res) {
	if (res) {
		console.log( res );
		var response = res.substring( res.indexOf( "{" ) ); // Trim leading extra chars (rnrnr)
		response     = JSON.parse( response );
		if ( ! response.connected && ! window.fb_connected) {
			not_connected();
			return null;
		}
		return response;
	}
	return null;
}

function check_feed_upload_queue(check_num) {
	ajax(
		'ajax_check_feed_upload_status',
		{
			"_ajax_nonce": wc_facebook_settings_jsx.nonce,
		},
		function(response) {
			const $sync_progress_element = jQuery( '#sync_progress' );

			var res = parse_response_check_connection( response );

			clearInterval( window.fb_feed_pings );

			if (res) {
				var status = res.status;
				switch (status) {
					case 'complete':
						window.feed_upload = false;
						if (window.is_test) {
							display_test_result();
						} else {
							product_sync_complete( $sync_progress_element );
						}
				  break;
					case 'in progress':

						$sync_progress_element.show().html( facebook_for_woocommerce_settings_sync.i18n.sync_in_progress );

						ping_feed_status_queue( check_num + 1 );
					break;

					default:

						// enable Manage connection and Sync products buttons when sync stops
						jQuery( '#woocommerce-facebook-settings-manage-connection, #woocommerce-facebook-settings-sync-products' ).css( 'pointer-events', 'auto' );

						$( '#sync_progress' ).show().html( '<span style="color: #DC3232">' + facebook_for_woocommerce_settings_sync.i18n.feed_upload_error + '</span>' );

						window.feed_upload              = false;
						if (window.is_test) {
							display_test_result();
						}
				}
			}
		}
	);
}

function display_test_result() {
	ajax(
		'ajax_display_test_result',
		{
			"_ajax_nonce": wc_facebook_settings_jsx.nonce
		},
		function(response) {
			const $sync_progress_element = jQuery( '#sync_progress' );

			var sync_complete_element = document.querySelector( '#sync_complete' );
			var res                   = parse_response_check_connection( response );
			if (res) {
				var status = res.pass;
				switch (status) {
					case 'true':
						sync_not_in_progress();
						if (sync_complete_element) {
							sync_complete_element.style.display = '';
							sync_complete_element.innerHTML     =  facebook_for_woocommerce_settings_sync.i18n.integration_test_sucessful;
						}

						$sync_progress_element.empty().hide();

						window.is_test = false;
				  break;
					case 'in progress':

						$sync_progress_element.show().html( facebook_for_woocommerce_settings_sync.i18n.integration_test_in_progress );

						ping_feed_status_queue();
					break;
					default:
						window.debug_info = res.debug_info + '<br/>' + res.stack_trace;
						if (sync_complete_element) {
							sync_complete_element.style.display = '';
							sync_complete_element.innerHTML     = facebook_for_woocommerce_settings_sync.i18n.integration_test_failed;
						}

						$sync_progress_element.empty().hide();

						if (document.querySelector( '#debug_info' )) {
							document.querySelector( '#debug_info' ).style.display = '';
						}
						window.is_test = false;
				}
			}
		}
	);
}

function show_debug_info() {
	var stack_trace_element = document.querySelector( '#stack_trace' );
	if (stack_trace_element) {
		stack_trace_element.innerHTML = window.debug_info;
	}
	if (document.querySelector( '#debug_info' )) {
		document.querySelector( '#debug_info' ).style.display = 'none';
	}
	window.debug_info = '';
}

function fbe_init_nux_messages() {
	var jQuery = window.jQuery;
	jQuery(
		function() {
			jQuery.each(
				jQuery( '.nux-message' ),
				function(_index, nux_msg) {
					var nux_msg_elem  = jQuery( nux_msg );
					var targetid      = nux_msg_elem.data( 'target' );
					var target_elem   = jQuery( '#' + targetid );
					var t_pos         = target_elem.position();
					var t_half_height = target_elem.height() / 2;
					var t_width       = target_elem.outerWidth();
					nux_msg_elem.css(
						{
							'top': '' + Math.ceil( t_pos.top + t_half_height ) + 'px',
							'left': '' + Math.ceil( t_pos.left + t_width ) + 'px',
							'display': 'block'
						}
					);
					jQuery( '.nux-message-close-btn', nux_msg_elem ).click(
						function() {
							jQuery( nux_msg ).hide();
						}
					);
				}
			);
		}
	);
}

function saveAutoSyncSchedule() {
	var isChecked = document.getElementsByClassName( 'autosyncCheck' )[0].checked;
	var timebox   = document.getElementsByClassName( 'autosyncTime' )[0];
	var button    = document.getElementsByClassName( 'autosyncSaveButton' )[0];
	var saved     = document.getElementsByClassName( 'autosyncSavedNotice' )[0];

	if ( ! isChecked) {
		timebox.setAttribute( 'disabled', true );
	} else {
		timebox.removeAttribute( 'disabled' );
		saved.style.transition = '';
		saved.style.opacity    = 1;
		// Fade out the small 'Saved' after 3 seconds.
		setTimeout(
			function() {
				saved.style.opacity    = 0;
				saved.style.transition = 'opacity 5s';}
			,
			3000
		);
	}

	ajax( 'ajax_schedule_force_resync',
	 {
		 "enabled": isChecked ? 1 : 0,
		 "time" : timebox.value,
		 "_ajax_nonce": wc_facebook_settings_jsx.nonce,
	 }
 );
}


function syncShortDescription() {
	var isChecked = document.getElementsByClassName( 'syncShortDescription' )[0].checked;
	ajax(
		'ajax_update_fb_option',
		{
			"option": "fb_sync_short_description",
			"option_value": isChecked ? 1 : 0,
			"_ajax_nonce": wc_facebook_settings_jsx.nonce,
		},
		null,
	function syncShortDescriptionFailCallback(error) {
		document.getElementsByClassName( 'syncShortDescription' )[0].checked = ! isChecked;
		console.log( 'Failed to sync Short Description' );
	}
	);
}
