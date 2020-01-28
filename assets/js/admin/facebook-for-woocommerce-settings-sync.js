/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

jQuery( document ).ready( function( $ ) {


	/**
	 * Gets any new excluded categories being added.
	 *
	 * @return {string[]}
	 */
	function getExcludedCategoriesAdded() {

		const newCategoryIDs = $( '#woocommerce_facebookcommerce_fb_sync_exclude_categories' ).val();
		let oldCategoryIDs   = [];

		if ( window.facebookAdsToolboxConfig.excludedCategoryIDs ) {
			oldCategoryIDs = window.facebookAdsToolboxConfig.excludedCategoryIDs;
		}

		// return IDs that are in the new value that were not in the saved value
		return $( newCategoryIDs ).not( oldCategoryIDs ).get();
	}


	/**
	 * Gets any new excluded tags being added.
	 *
	 * @return {string[]}
	 */
	function getExcludedTagsAdded() {

		const newTagIDs = $( '#woocommerce_facebookcommerce_fb_sync_exclude_tags' ).val();
		let oldTagIDs   = [];

		if ( window.facebookAdsToolboxConfig.excludedTagIDs ) {
			oldTagIDs = window.facebookAdsToolboxConfig.excludedTagIDs;
		}

		// return IDs that are in the new value that were not in the saved value
		return $( newTagIDs ).not( oldTagIDs ).get();
	}


	const pagenow = window.pagenow.length ? window.pagenow : '';

	// WooCommerce settings page
	if ( 'woocommerce_page_wc-settings' === pagenow ) {

		// toggle visibility of settings groups
		$( 'input[type="checkbox"].toggle-fields-group' ).on( 'change', function ( e ) {
			if ( $( this ).hasClass( 'product-sync-field' ) ) {
				if ( $( this ).is( ':checked' ) ) {
					$( '.product-sync-field' ).not( '.toggle-fields-group' ).closest( 'tr' ).show();
				} else {
					$( '.product-sync-field' ).not( '.toggle-fields-group' ).closest( 'tr' ).hide();
				}
			} else if ( $( this ).hasClass( 'messenger-field' ) ) {
				if ( $( this ).is( ':checked' ) ) {
					$( '.messenger-field' ).not( '.toggle-fields-group' ).closest( 'tr' ).show();
				} else {
					$( '.messenger-field' ).not( '.toggle-fields-group' ).closest( 'tr' ).hide();
				}
			}
		} ).trigger( 'change' );

		let submitSettingsSave = false;

		$( '.woocommerce-save-button' ).on( 'click', function ( e ) {

			if ( ! submitSettingsSave ) {
				e.preventDefault();
			} else {
				return true;
			}

			const $submitButton = $( this );

			const categoriesAdded = getExcludedCategoriesAdded(),
				  tagsAdded       = getExcludedTagsAdded();

			if ( categoriesAdded.length > 0 || tagsAdded.length > 0 ) {

				$.post( facebook_for_woocommerce_settings_sync.ajax_url, {
					action: 'facebook_for_woocommerce_set_excluded_terms_prompt',
					security: facebook_for_woocommerce_settings_sync.set_excluded_terms_prompt_nonce,
					categories: categoriesAdded,
					tags: tagsAdded,
				}, function ( response ) {

					if ( response && ! response.success ) {

						// close existing modals
						$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

						// open new modal, populate template with AJAX response data
						new $.WCBackboneModal.View( {
							target: 'facebook-for-woocommerce-modal',
							string: response.data,
						} );

						// exclude products: submit form as normal
						$( '#facebook-for-woocommerce-confirm-settings-change' ).on( 'click', function () {

							blockModal();

							submitSettingsSave = true;
							$submitButton.trigger( 'click' );
						} );

					} else {

						// no modal displayed: submit form as normal
						submitSettingsSave = true;
						$submitButton.trigger( 'click' );
					}
				} );

			} else {

				// no terms added: submit form as normal
				submitSettingsSave = true;
				$submitButton.trigger( 'click' );
			}
		} );

	}

} );
