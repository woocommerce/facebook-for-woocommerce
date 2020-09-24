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

	let $form             = $( 'form[id="post"]' );
	let $orderStatusField = $( '#order_status' );
	let shipmentTracking  = wc_facebook_commerce_orders.shipment_tracking;
	let trackingNumber    = Array.isArray( shipmentTracking ) && shipmentTracking[ 0 ] ? shipmentTracking[ 0 ] : shipmentTracking;
	let completeModel;


	/**
	 * Displays the order complete modal on order form submit
	 */
	function displayCompleteModal() {

		if ( completeModel ) {
			completeModel.remove();
		}

		completeModel = new $.WCBackboneModal.View( {
			target: 'facebook-for-woocommerce-modal',
			string: {
				message: wc_facebook_commerce_orders.complete_modal_message,
				buttons: wc_facebook_commerce_orders.complete_modal_buttons
			},
			events: {
				'click .modal-close': 'closeButton',
				'touchstart #btn-ok': 'addButton',
				'keydown'           : 'keyboardActions',
				'click #btn-ok'     : function () {
					makeCompleteAjaxRequest( completeModel );
				}
			}
		} );
	}


	/**
	 * Make complete order AJAX Request
	 *
	 * @param {Object} modal
	 */
	function makeCompleteAjaxRequest( modal = null ) {

		console.log( 'Complete AJAX' );

		if ( modal ) {
			modal.remove();
		}
	}


	$form.on( 'submit', function ( event ) {

		if ( !isCommerceOrder || $form.data( 'allow-submit' ) ) {
			return;
		}

		if ( 'wc-completed' === $orderStatusField.val() ) {

			event.preventDefault();

			if ( trackingNumber.length ) {
				makeCompleteAjaxRequest();
			} else {
				displayCompleteModal();
			}
		}
	} );

} );
