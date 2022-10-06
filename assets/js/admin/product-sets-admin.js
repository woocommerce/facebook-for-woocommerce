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
				jQuery( '.select2.wc-facebook' ).select2().val( null ).trigger( 'change' );
			}
	    });

	}

	let $submitButton = $( 'form[id="addtag"] input[name="submit"]' );

	$submitButton.on( 'click', function( e ) {

		let $selectedCategories = $('#_wc_facebook_product_cats').val();
		let excludedCategoryIDs   = [];

		if ( window.facebook_for_woocommerce_product_sets && window.facebook_for_woocommerce_product_sets.excluded_category_ids ) {
			excludedCategoryIDs = window.facebook_for_woocommerce_product_sets.excluded_category_ids;
		}

		if ( $selectedCategories.length > 0 && excludedCategoryIDs.length > 0 ) {
			if ( hasExcludedCategories( $selectedCategories, excludedCategoryIDs ) ) {
				alert( facebook_for_woocommerce_product_sets.excluded_category_warning_message );
			}
		}

	});

} );

/**
 * Checks if selected categories contains any excluded categories.
 *
 * @param selectedCategories Array of submitted category ids
 * @param excludedCategoryIDs Array category ids excluded from sync.
 * @returns {boolean}
 */
function hasExcludedCategories( selectedCategories, excludedCategoryIDs ) {

	let counter = 0;

	for ( let i = 0; i < excludedCategoryIDs.length; i++ ) {
		if ( excludedCategoryIDs.includes( excludedCategoryIDs[i] ) ) counter++;
	}

	return counter > 0;

}
