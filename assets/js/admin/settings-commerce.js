/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

 jQuery( document ).ready( function( $ ) {

	let $form = $( 'form.wc-facebook-settings' );
	let $defaultCategoryField = $( '#wc_facebook_google_product_category_id' );
	let defaultCategoryId = $defaultCategoryField.val();

	$form.on( 'submit', function( event ) {

		if ( $form.data( 'allow-submit' ) || ! $defaultCategoryField.val() || $defaultCategoryField.val() === defaultCategoryId ) {
			return;
		}

		event.preventDefault();

		$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

		new $.WCBackboneModal.View( {
			target: 'facebook-for-woocommerce-modal',
			string: {
				message: facebook_for_woocommerce_settings_commerce.default_google_product_category_modal_message,
				buttons: facebook_for_woocommerce_settings_commerce.default_google_product_category_modal_buttons
			}
		} );

		$( document.body )
			.off( 'wc_backbone_modal_response.facebook_for_commerce' )
			.on( 'wc_backbone_modal_response.facebook_for_commerce', function() {
				$form.data( 'allow-submit', true ).find( ':submit' ).trigger( 'click' );
			} );
	} );

 } );
