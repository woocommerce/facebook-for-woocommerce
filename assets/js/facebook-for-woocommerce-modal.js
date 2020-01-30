/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

$ = jQuery;

/**
 * Determines if the current modal is blocked.
 *
 * @returns {boolean}
 */
function isModalBlocked() {
	let $modal = $( '.wc-backbone-modal-content' );
	return $modal.is( '.processing') || $modal.parents( '.processing' ).length;
}


/**
 * Blocks the current modal.
 */
function blockModal() {
	if ( ! isModalBlocked() ) {
		return $( '.wc-backbone-modal-content' ).addClass( 'processing' ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		} );
	}
}


/**
 * Unblocks the current modal.
 */
function unBlockModal() {
	$( '.wc-backbone-modal-content' ).removeClass( 'processing' ).unblock();
}
