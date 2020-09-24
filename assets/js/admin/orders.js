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


	/**
	 * Determines whether we need to show the Cancel Order modal.
	 *
	 * @since 2.0.1-dev.1
	 *
	 * @returns {boolean}
	 */
	function shouldShowCancelOrderModal() {

		if ( $( '#post' ).data( 'skip-cancel-modal' ) ) {
			return false;
		}

		if ( ! wc_facebook_commerce_orders.is_commerce_order ) {
			return false;
		}

		return 'wc-cancelled' === $( '#order_status' ).val();
	}


	const isCommerceOrder = Boolean( wc_facebook_commerce_orders.is_commerce_order );

	let $form               = $( 'form[id="post"]' );
	let $orderStatusField   = $( '#order_status' );
	let originalOrderStatus = $orderStatusField.val();

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
				$( '#refund_reason' ).val( $( '#wc_facebook_refund_reason_modal' ).val() );
				// submit the form
				$form.data( 'allow-submit', true ).submit();
			} );
	}


	$form.on( 'submit', function( event ) {

		if ( ! isCommerceOrder || $form.data('allow-submit') ) {
			return;
		}

		let newOrderStatusField = $orderStatusField.val();

		if ( 'wc-refunded' === newOrderStatusField && originalOrderStatus !== newOrderStatusField ) {
			displayRefundModal( event );
		}
	} );

} );
