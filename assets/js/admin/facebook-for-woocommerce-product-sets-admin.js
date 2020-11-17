/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

jQuery( document ).ready( function( $ ) {

	jQuery( '.select2.wc-facebook' ).select2().addClass( 'visible' ).attr( 'disabled', false );
	jQuery( '.select2.updating-message' ).addClass( 'hidden' );

} );
