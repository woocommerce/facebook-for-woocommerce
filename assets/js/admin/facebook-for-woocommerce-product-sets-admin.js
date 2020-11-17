/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

jQuery( document ).ready( function( $ ) {

	if ( jQuery( '.select2.wc-facebook' ).length ) {
		wcFacebookProductSetInit();
	}

	function wcFacebookProductSetInit() {

		jQuery( '.select2.wc-facebook' ).select2().addClass( 'visible' ).attr( 'disabled', false );
		jQuery( '.select2.updating-message' ).addClass( 'hidden' );

		jQuery( document ).ajaxSuccess( function( e, request, settings ) {
			var obj = new URLSearchParams( settings.data )
			if ( obj.has( 'action' ) && 'add-tag' === obj.get( 'action' ) && obj.has( 'taxonomy' ) && 'fb_product_set' === obj.get( 'taxonomy' ) ) {
	console.log('YEAH');
				jQuery( '.select2.wc-facebook' ).select2().val( null ).trigger( 'change' );
			}
	    });

	}

} );
