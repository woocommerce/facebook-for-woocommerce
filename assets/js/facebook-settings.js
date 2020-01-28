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

function toggleAdvancedOptions() {
	var opts = document.getElementById( "fbAdvancedOptions" );
	if ( ! fb_show_advanced_options) {
		opts.style.display = "block";
		document.getElementById( 'fbAdvancedOptionsText' ).innerHTML = 'Hide Advanced Settings';
	} else {
		opts.style.display = "none";
		document.getElementById( 'fbAdvancedOptionsText' ).innerHTML = 'Show Advanced Settings';
	}
	fb_show_advanced_options = ! fb_show_advanced_options;
}

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

function sync_confirm(verbose = null) {
	var msg = '';
	switch (verbose) {
		case 'fb_force_resync':
			msg = 'Your products will now be resynced with Facebook, ' +
			'this may take some time.';
		break;
		case 'fb_test_product_sync':
			msg = 'Launch Test?';
		break;
		default:
			msg = 'Facebook for WooCommerce automatically syncs your products on ' +
			'create/update. Are you sure you want to force product resync? ' +
			'This will query all published products and may take some time. ' +
			'You only need to do this if your products are out of sync ' +
			'or some of your products did not sync.';
	}
	if (confirm( msg )) {
		sync_all_products(
			window.facebookAdsToolboxConfig.feed.hasClientSideFeedUpload,
			verbose == 'fb_test_product_sync'
		);
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

function sync_all_products($using_feed = false, $is_test = false) {

	window.fb_connected = true;
	sync_in_progress();
	if ($using_feed) {
		window.facebookAdsToolboxConfig.feed.hasClientSideFeedUpload = true;
		window.feed_upload = true;
		ping_feed_status_queue();
		return $is_test ? ajax( 'ajax_test_sync_products_using_feed' )
		: ajax(
			'ajax_sync_all_fb_products_using_feed',
			{
				"_ajax_nonce": wc_facebook_settings_jsx.nonce,
			},
		);
	} else {
		return ajax(
			'ajax_sync_all_fb_products',
			{
        "_ajax_nonce": wc_facebook_settings_jsx.nonce,
      }
		);
	}
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
					sync_all_products( true );
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


// Remove reset/settings buttons during product sync
function sync_in_progress(){
	if (document.querySelector( '#settings' )) {
		document.querySelector( '#settings' ).style.display = '';
	}
	if (document.querySelector( '#connection_status' )) {
		document.querySelector( '#connection_status' ).style.display = '';
	}
	if (document.querySelector( '#sync_complete' )) {
		document.querySelector( '#sync_complete' ).style.display = 'none';
	}
	// Get rid of all the buttons
	if (document.querySelector( '#setting_button' )) {
		document.querySelector( '#setting_button' ).style['pointer-events'] = 'none';
	}
	if (document.querySelector( '#resync_products' )) {
		document.querySelector( '#resync_products' ).style['pointer-events'] = 'none';
	}
	if (document.querySelector( '#test_product_sync' )) {
		document.querySelector( '#test_product_sync' ).style.display = 'none';
	}
	// Set a product sync status
	if (document.querySelector( '#sync_progress' )) {
		document.querySelector( '#sync_progress' ).innerHTML =
		'Syncing... Keep this browser open <br/>' +
		'Until sync is complete<br/>' +
		'<div class="loader"></div>';
	}
}

function sync_not_in_progress(){
	// Reset to pre-setup state.
	if (document.querySelector( '#cta_button' )) {
		var cta_element                = document.querySelector( '#cta_button' );
		cta_element.innerHTML          = 'Create Ad';
		cta_element.style['font-size'] = '12px';
		cta_element.style.width        = '60px';
		if (window.facebookAdsToolboxConfig.diaSettingId) {
			cta_element.onclick = function() {
				window.open(
					'https://www.facebook.com/ads/dia/redirect/?settings_id=' +
					window.facebookAdsToolboxConfig.diaSettingId + '&version=2' +
					'&entry_point=admin_panel'
				);
			};
		} else {
			cta_element.style['pointer-events'] = 'none';
		}
	}
	if (document.querySelector( '#learnmore_button' )) {
		var learnmore_element = document.querySelector( '#learnmore_button' );
		if (window.facebookAdsToolboxConfig.diaSettingId) {
			learnmore_element.style.display = '';
		}
	}
	if (document.querySelector( '#setup_h1' )) {
		document.querySelector( '#setup_h1' ).innerHTML =
		'Reach the right people and sell more products';
	}
	if (document.querySelector( '#setup_l1' )) {
		document.querySelector( '#setup_l1' ).innerHTML =
		'Create an ad in a few steps';
	}
	if (document.querySelector( '#setup_l2' )) {
		document.querySelector( '#setup_l2' ).innerHTML =
		'Use built-in best practice for online sales';
	}
	if (document.querySelector( '#setup_l3' )) {
		document.querySelector( '#setup_l3' ).innerHTML =
		'Get reporting on sales and revenue';
	}
	if (document.querySelector( '#settings' )) {
		document.querySelector( '#settings' ).style.display = '';
	}
	// Enable buttons.
	if (document.querySelector( '#setting_button' )) {
		document.querySelector( '#setting_button' ).style['pointer-events'] = 'auto';
	}
	if (document.querySelector( '#resync_products' )) {
		document.querySelector( '#resync_products' ).style ['pointer-events'] = 'auto';
	}
	// Remove sync progress.
	if (document.querySelector( '#sync_progress' )) {
		document.querySelector( '#sync_progress' ).innerHTML = '';
	}
}


/**
 * Shows Facebook fancy box if the store is still not connected to Facebook.
 *
 * Also hides the integration settings fields.
 */
function not_connected() {

	jQuery( '#fbsetup' ).show();
	jQuery( '#integration-settings' ).hide();
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

function genFeed(message) {
	// no-op
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
	if (document.querySelector( '#token_text' )) {
		document.querySelector( '#token_text' ).innerHTML =
		` < strong > Your API key has been updated.<br / >
		Please refresh the page.< / strong > `;
	}
}

function setMsgerChatSetup(data) {
	if (data.hasOwnProperty( 'is_messenger_chat_plugin_enabled' )) {
		settings.is_messenger_chat_plugin_enabled =
		data.is_messenger_chat_plugin_enabled;
	}
	if (data.hasOwnProperty( 'facebook_jssdk_version' )) {
		settings.facebook_jssdk_version =
		data.facebook_jssdk_version;
	}
	if (data.hasOwnProperty( 'page_id' )) {
		settings.fb_page_id = data.page_id;
	}

	if (data.hasOwnProperty( 'customization' )) {
		var customization = data.customization;

		if (customization.hasOwnProperty( 'greetingTextCode' )) {
			settings.msger_chat_customization_greeting_text_code =
			customization.greetingTextCode;
		}
		if (customization.hasOwnProperty( 'locale' )) {
			settings.msger_chat_customization_locale =
			customization.locale;
		}
		if (customization.hasOwnProperty( 'themeColorCode' )) {
			settings.msger_chat_customization_theme_color_code =
			customization.themeColorCode;
		}
	}
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

// Only do pings for supporting older (pre 1.8) setups.
window.fb_pings =
(window.facebookAdsToolboxConfig.feed.hasClientSideFeedUpload) ?
null :
setInterval(
	function(){
		console.log( "Pinging queue..." );
		check_queues();
	},
	10000
);

function ping_feed_status_queue(count = 0) {
	window.fb_feed_pings = setInterval(
		function() {
			console.log( 'Pinging feed uploading queue...' );
			check_feed_upload_queue( count );
		},
		30000 * (1 << count)
	);
}

function product_sync_complete(sync_progress_element) {
	sync_not_in_progress();
	if (document.querySelector( '#sync_complete' )) {
		document.querySelector( '#sync_complete' ).style.display = '';
	}
	if (sync_progress_element) {
		sync_progress_element.innerHTML = '';
	}
	clearInterval( window.fb_pings );
}

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
			var sync_progress_element = document.querySelector( '#sync_progress' );
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
					if ( sync_progress_element ) {
						sync_progress_element.innerHTML =
						'<strong>Progress:</strong> ' + remaining + ' item' +
						( remaining > 1 ? 's' : '' ) + ' remaining.';
					}
					if ( remaining === 0 ) {
						product_sync_complete( sync_progress_element );
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
						  product_sync_complete( sync_progress_element );
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
			var sync_progress_element = document.querySelector( '#sync_progress' );
			var res                   = parse_response_check_connection( response );
			clearInterval( window.fb_feed_pings );
			if (res) {
				var status = res.status;
				switch (status) {
					case 'complete':
						window.feed_upload = false;
						if (window.is_test) {
							display_test_result();
						} else {
							product_sync_complete( sync_progress_element );
						}
				  break;
					case 'in progress':
						if (sync_progress_element) {
							sync_progress_element.innerHTML =
							'Syncing... Keep this browser open <br/>' +
							'Until sync is complete<br/>';
						}
						ping_feed_status_queue( check_num + 1 );
					  break;
					default:
						sync_progress_element.innerHTML =
						'<strong>Something wrong when uploading, please try again.</strong>';
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
			var sync_complete_element = document.querySelector( '#sync_complete' );
			var sync_progress_element = document.querySelector( '#sync_progress' );
			var res                   = parse_response_check_connection( response );
			if (res) {
				var status = res.pass;
				switch (status) {
					case 'true':
						sync_not_in_progress();
						if (sync_complete_element) {
							sync_complete_element.style.display = '';
							sync_complete_element.innerHTML     =
							'<strong>Status: </strong>Test Pass.';
						}
						if (sync_progress_element) {
							sync_progress_element.innerHTML = '';
						}
						window.is_test = false;
				  break;
					case 'in progress':
						if (sync_progress_element) {
							sync_progress_element.innerHTML =
							'<strong>Integration test in progress...</strong>';
						}
						ping_feed_status_queue();
					  break;
					default:
						window.debug_info = res.debug_info + '<br/>' + res.stack_trace;
						if (sync_complete_element) {
							sync_complete_element.style.display = '';
							sync_complete_element.innerHTML     =
							'<strong>Status: </strong>Test Fail.';
						}
						if (sync_progress_element) {
							sync_progress_element.innerHTML = '';
						}
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

function onSetDisableSyncOnDevEnvironment() {
	var isChecked = document.getElementsByClassName( 'disableOnDevEnvironment' )[0].checked;
	ajax(
		'ajax_update_fb_option',
		{
			"option": "fb_disable_sync_on_dev_environment",
			"option_value": isChecked ? 1 : 0,
			"_ajax_nonce": wc_facebook_settings_jsx.nonce,
		},
		null,
        function onSetDisableSyncOnDevEnvironmentFailCallback(error) {
		document.getElementsByClassName(
            'onSetDisableSyncOnDevEnvironment'
		)[0].checked = ! isChecked;
		console.log( 'Failed to disable sync on dev environment' );
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
