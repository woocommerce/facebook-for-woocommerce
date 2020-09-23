/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

jQuery( document ).ready( ( $ ) => {

	'use strict';

	const isCommerceOrder = Boolean( wc_facebook_commerce_orders.is_commerce_order );

	let $form               = $( 'form[id="post"]' );
	let $orderStatusField   = $( '#order_status' );


	/**
	 * Displays the refund modal on form submit.
	 *
	 * @param {Event} event
	 */
	function displayRefundModal( event ) {

		event.preventDefault();

		$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

		new $.WCBackboneModal.View( {
			target: 'facebook-for-woocommerce-modal',
			string: {
				message: wc_facebook_commerce_orders.refund_modal_message,
				buttons: wc_facebook_commerce_orders.refund_modal_buttons
			}
		} );

		$( document.body )
			.off( 'wc_backbone_modal_response.facebook_for_commerce' )
			.on( 'wc_backbone_modal_response.facebook_for_commerce', function() {
				// copy the value of the modal select to the WC field
				$( '#refund_reason ' ).val( $( '#wc_facebook_refund_reason' ).val() );
				$form.data( 'allow-submit', true ).find( ':submit' ).trigger( 'click' );
			} );
	}


	$form.on( 'submit', function( event ) {

		if ( ! isCommerceOrder || $form.data('allow-submit') ) {
			return;
		}

		if ( 'wc-refunded' === $orderStatusField.val() ) {
			displayRefundModal( event );
		}
	} );

} );
