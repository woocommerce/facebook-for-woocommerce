/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

// TODO the following code needs to be wrapped into document ready but it's currently used from inline HTML {FN 2020-01-15}

/*
 *  Ajax helper function.
 *  Takes optional payload for POST and optional callback.
 */
function ajax(action, payload = null, cb = null, failcb = null) {
	var data = Object.assign( {},
		{
			'action': action,
		}, payload
	);

	// Since  Wordpress 2.8 ajaxurl is always defined in admin header and
	// points to admin-ajax.php
	jQuery.post(
		ajaxurl,
		data,
		function(response) {
			if (cb) {
				cb( response );
			}
		}
	).fail(
		function(errorResponse){
			if (failcb) {
				failcb( errorResponse );
			}
		}
	);
}

function fb_toggle_visibility( productID, published ) {
	var buttonId = document.querySelector( "#viz_" + productID );
	var tooltip  = document.querySelector( "#tip_" + productID );

	if (published) {
		tooltip.setAttribute(
			'data-tip',
			'Product is synced and published (visible) on Facebook.'
		);
		buttonId.setAttribute( 'onclick','fb_toggle_visibility(' + productID + ', false)' );
		buttonId.innerHTML = 'Hide';
		buttonId.setAttribute( 'class', 'button' );
	} else {
		tooltip.setAttribute(
			'data-tip',
			'Product is synced but not marked as published (visible) on Facebook.'
		);
		buttonId.setAttribute( 'onclick','fb_toggle_visibility(' + productID + ', true)' );
		buttonId.innerHTML = 'Show';
		buttonId.setAttribute( 'class', 'button button-primary button-large' );
	}

	// Reset tooltip
	jQuery(
		function($) {
			$( '.tips' ).tipTip(
				{
					'attribute': 'data-tip',
					'fadeIn': 50,
					'fadeOut': 50,
					'delay': 200
				}
			);
		}
	);

	return jQuery.post( ajaxurl, {
		action:   'facebook_for_woocommerce_set_products_visibility',
		security: facebook_for_woocommerce_products_admin.set_product_visibility_nonce,
		products: [
			{
				product_id: productID,
				visibility: published ? 'yes' : 'no'
			}
		]
	} );
}

jQuery( document ).ready( function( $ ) {

	const pagenow = window.pagenow.length ? window.pagenow : '',
	      typenow = window.typenow.length ? window.typenow : '';


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


	// products list edit screen
	if ( 'edit-product' === pagenow ) {

		let submitProductBulkAction = false;

		$( 'input#doaction, input#doaction2' ).on( 'click', function( e ) {

			if ( ! submitProductBulkAction ) {
				e.preventDefault();
			} else {
				return true;
			}

			let $submitButton    = $( this ),
				chosenBulkAction = $submitButton.prev( 'select' ).val();

			if ( 'facebook_exclude' === chosenBulkAction || 'facebook_include' === chosenBulkAction ) {

				let products = [];

				$.each( $( 'input[name="post[]"]:checked' ), function() {
					products.push( parseInt( $( this ).val(), 10 ) );
				} );

				$.post( facebook_for_woocommerce_products_admin.ajax_url, {
					action:   'facebook_for_woocommerce_set_product_sync_bulk_action_prompt',
					security: facebook_for_woocommerce_products_admin.set_product_sync_bulk_action_prompt_nonce,
					toggle:   chosenBulkAction,
					products: products
				}, function( response ) {

					if ( response && ! response.success ) {

						// close existing modals
						$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

						// open new modal, populate template with AJAX response data
						new $.WCBackboneModal.View( {
							target: 'facebook-for-woocommerce-modal',
							string: response.data
						} );

						// exclude from sync: offer to handle product visibility
						$( '.facebook-for-woocommerce-toggle-product-visibility' ).on( 'click', function( e) {

							blockModal();

							if ( $( this ).hasClass( 'hide-products' ) ) {

								$.each( products, function() {

									let $toggle = $( '#post-' + this ).find( 'td.facebook_shop_visibility a' );

									if ( 'visible' === $toggle.data( 'product-visibility' ) ) {
										$toggle.trigger( 'click' );
									}
								} );
							}

							// submit form after modal prompt action
							submitProductBulkAction = true;
							$submitButton.trigger( 'click' );
						} );

					} else {

						// no modal displayed: submit form as normal
						submitProductBulkAction = true;
						$submitButton.trigger( 'click' );
					}
				} );

			} else {

				// no modal displayed: submit form as normal
				submitProductBulkAction = true;
				$submitButton.trigger( 'click' );
			}
		} );
	}


	// individual product edit screen
	if ( 'product' === pagenow ) {

		/**
		 * Toggles (enables/disables) Facebook setting fields.
		 *
		 * @since x.y.z
		 *
		 * @param {boolean} enabled
		 */
		function toggleFacebookSettings( enabled ) {

			$( '.enable-if-sync-enabled' ).prop( 'disabled', ! enabled );
		}

		const syncEnabledCheckbox = $( '#fb_sync_enabled' );

		syncEnabledCheckbox.on( 'click', function() {
			toggleFacebookSettings( $( this ).prop( 'checked' ) );
		} );

		toggleFacebookSettings( syncEnabledCheckbox.prop( 'checked' ) );

		let submitProductSave = false;

		$( 'form#post input[type="submit"]' ).on( 'click', function( e ) {

			if ( ! submitProductSave ) {
				e.preventDefault();
			} else {
				return true;
			}

			let $submitButton = $( this ),
				productID     = parseInt( $( 'input#post_ID' ).val(), 10 ),
				syncEnabled   = $( 'input#fb_sync_enabled' ).prop( 'checked' );

			if ( productID > 0 ) {

				$.post( facebook_for_woocommerce_products_admin.ajax_url, {
					action:      'facebook_for_woocommerce_set_product_sync_prompt',
					security:     facebook_for_woocommerce_products_admin.set_product_sync_prompt_nonce,
					sync_enabled: syncEnabled ? 'enabled' : 'disabled',
					product:      productID
				}, function( response ) {

					if ( response && ! response.success ) {

						// close existing modals
						$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

						// open new modal, populate template with AJAX response data
						new $.WCBackboneModal.View( {
							target: 'facebook-for-woocommerce-modal',
							string: response.data
						} );

						// exclude from sync: offer to handle product visibility
						$( '.facebook-for-woocommerce-toggle-product-visibility' ).on( 'click', function( e) {

							blockModal();

							if ( $( this ).hasClass( 'hide-products' ) ) {

								$.post( facebook_for_woocommerce_products_admin.ajax_url, {
									action:   'facebook_for_woocommerce_set_products_visibility',
									security: facebook_for_woocommerce_products_admin.set_product_visibility_nonce,
									products: [
										{
											product_id: productID,
											visibility: 'no'
										}
									]
								} );
							}

							// no modal displayed: submit form as normal
							submitProductSave = true;
							$submitButton.trigger( 'click' );
						} );

					} else {

						// no modal displayed: submit form as normal
						submitProductSave = true;
						$submitButton.trigger( 'click' );
					}
				} );

			} else {

				// no modal displayed: submit form as normal
				submitProductSave = true;
				$submitButton.trigger( 'click' );
			}

		} );
	}

} );
