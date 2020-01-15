/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */
jQuery( document ).ready( function( $ ) {


	/**
	 * Toggles (enables/disables) Facebook setting fields.
	 *
	 * @since x.y.z
	 *
	 * @param {boolean} enabled whether the settings fields should be enabled or not
	 * @param {jQuery} $container a common ancestor of all the elements that can be enabled/disabled
	 */
	function toggleFacebookSettings( enabled, $container ) {

		$container.find( '.enable-if-sync-enabled' ).prop( 'disabled', ! enabled );
	}


	const syncEnabledCheckbox   = $( '#fb_sync_enabled' );
	const facebookSettingsPanel = syncEnabledCheckbox.closest( '.woocommerce_options_panel' );

	syncEnabledCheckbox.on( 'click', function() {
		toggleFacebookSettings( $( this ).prop( 'checked' ), facebookSettingsPanel );
	} );

	toggleFacebookSettings( syncEnabledCheckbox.prop( 'checked' ), facebookSettingsPanel );
} );


