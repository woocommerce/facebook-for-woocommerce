/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */
jQuery( document ).ready( function() {


	/**
	 * Toggles (enables/disables) Facebook setting fields.
	 *
	 * @since x.y.z
	 *
	 * @param enabled
	 */
	function toggleFacebookSettings( enabled ) {

		jQuery( '.enable-if-sync-enabled' ).prop( 'disabled', ! enabled );
	}


	const syncEnabledCheckbox = jQuery( '#fb_sync_enabled' );

	syncEnabledCheckbox.on( 'click', function() {
		toggleFacebookSettings( jQuery( this ).prop( 'checked' ) );
	} );

	toggleFacebookSettings( syncEnabledCheckbox.prop( 'checked' ) );
} );


