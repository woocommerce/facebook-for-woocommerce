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

	const WCFacebookCommerceOrderOperations = {
		/**
		 * Restrict order status options to only allowed options.
		 *
		 * @param {Object} $orderStatus Order select jQuery DOM object
		 */
		restrict_order_statuses: $orderStatus => {

			$orderStatus.find( 'option' ).each( function ( index, option ) {

				// check if option value in the allowed list or not
				if ( wc_facebook_commerce_orders.allowed_commerce_statuses.indexOf( option.value ) === -1 ) {
					// delete/remove option if not allowed
					option.remove();
				}
			} );
		},


		/**
		 * Enable or Disable order created fields.
		 *
		 * @param {Boolean} enable whether to enable date fields (true) or not (false)
		 */
		toggle_created_date_fields_status: enable => {
			$( '#order_data' ).find( 'input[name*=order_date]' ).prop( 'disabled', !enable ).toggleClass( 'disabled', !enable );
		},


		/**
		 * Disable order status field
		 *
		 * @param {Object} $orderStatus Order select jQuery DOM object
		 */
		disable_order_status_field: ( $orderStatus ) => {
			$orderStatus.prop( 'disabled', true ).addClass( 'disabled' );
		},


		/**
		 * Toggle customer field
		 *
		 * @param {Boolean} hide
		 */
		toggle_order_customer_field: ( hide ) => {
			$( '#order_data' ).find( '.form-field.wc-customer-user' ).toggleClass( 'hidden', hide );
		},


		/**
		 * Toggle customer field
		 *
		 * @param {Boolean} hide
		 */
		toggle_billing_and_shipping_fields: ( hide ) => {
			$( '#order_data' ).find( 'a.edit_address' ).toggleClass( 'hidden', hide );
		},


		/**
		 * Disable order status field
		 *
		 * @param {Object} $orderStatus Order select jQuery DOM object
		 */
		disable_pending_order_related_fields: ( $orderStatus ) => {

			WCFacebookCommerceOrderOperations.toggle_created_date_fields_status( false );
			WCFacebookCommerceOrderOperations.disable_order_status_field( $orderStatus );
			WCFacebookCommerceOrderOperations.toggle_order_customer_field( true );
			WCFacebookCommerceOrderOperations.toggle_billing_and_shipping_fields( true );
		}
	};

	if ( isCommerceOrder ) {

		let $orderStatus = $( '#order_status' );

		WCFacebookCommerceOrderOperations.restrict_order_statuses( $orderStatus );

		if ( 'pending' === wc_facebook_commerce_orders.order_status ) {
			WCFacebookCommerceOrderOperations.disable_pending_order_related_fields( $orderStatus );
		}

		if ( 'cancelled' === wc_facebook_commerce_orders.order_status ) {
			WCFacebookCommerceOrderOperations.disable_order_status_field( $orderStatus );
		}
	}

} );
