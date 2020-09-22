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

	window.WCFacebookCommerceOrderOperations = {
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
		}
	};

	if ( isCommerceOrder ) {

		WCFacebookCommerceOrderOperations.restrict_order_statuses( $( '#order_status' ) );

	}

} );
