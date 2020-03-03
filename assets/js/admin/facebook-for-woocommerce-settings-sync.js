/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

jQuery( document ).ready( function( $ ) {


	// run script only on Facebook Settings page
	if ( 'woocommerce_page_wc-settings' === window.pagenow.length ? window.pagenow : '' ) {
		return;
	}


	/**
	 * Gets any new excluded categories being added.
	 *
	 * @return {string[]}
	 */
	function getExcludedCategoriesAdded() {

		const newCategoryIDs = $( '#woocommerce_facebookcommerce_fb_sync_exclude_categories' ).val();
		let oldCategoryIDs   = [];

		if ( window.facebookAdsToolboxConfig && window.facebookAdsToolboxConfig.excludedCategoryIDs ) {
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

		if ( window.facebookAdsToolboxConfig && window.facebookAdsToolboxConfig.excludedTagIDs ) {
			oldTagIDs = window.facebookAdsToolboxConfig.excludedTagIDs;
		}

		// return IDs that are in the new value that were not in the saved value
		return $( newTagIDs ).not( oldTagIDs ).get();
	}


	/**
	 * Toggles availability of input in setting groups.
	 *
	 * @param {Object[]} $elements group of jQuery elements (fields or buttons) to toggle
	 * @param {boolean} enable whether fields in this group should be enabled or not
	 */
	function toggleSettingOptions( $elements, enable ) {

		$( $elements ).each( function() {

			let $element = $( this );

			if ( $( this ).hasClass( 'wc-enhanced-select' ) ) {
				$element = $( this ).next( 'span.select2-container' );
			}

			if ( enable ) {
				$element.css( 'pointer-events', 'all' ).css( 'opacity', '1.0' );
			} else {
				$element.css( 'pointer-events', 'none' ).css( 'opacity', '0.4' );
			}
		} );
	}


	// toggle availability of options withing field groups
	$( 'input[type="checkbox"].toggle-fields-group' ).on( 'change', function ( e ) {
		if ( $( this ).hasClass( 'product-sync-field' ) ) {
			toggleSettingOptions( $( '.product-sync-field' ).not( '.toggle-fields-group' ), $( this ).is( ':checked' ) );
		} else if ( $( this ).hasClass( 'messenger-field' ) ) {
			toggleSettingOptions( $( '.messenger-field' ).not( '.toggle-fields-group' ), $( this ).is( ':checked' ) );
		} else if ( $( this ).hasClass( 'resync-schedule-field' ) ) {
			toggleSettingOptions( $( '.resync-schedule-field' ).not( '.toggle-fields-group' ), $( this ).is( ':checked' ) );
		}
	} ).trigger( 'change' );


	// adds a leading zero to time picker fields
	$( '#woocommerce_facebookcommerce_scheduled_resync_hours, #woocommerce_facebookcommerce_scheduled_resync_minutes' ).on( 'input change keyup keydown keypress click', function() {

		let value = $( this ).val();

		if ( ! isNaN( value ) && 1 === value.length && value < 10 ) {
			$( this ).val( value.padStart( 2, '0' ) );
		}

	} ).trigger( 'change' );


	// adds a character counter on the Messenger greeting textarea
	$( 'textarea#woocommerce_facebookcommerce_messenger_greeting' ).on( 'focus change keyup keydown keypress', function() {

		const maxChars = parseInt( window.facebookAdsToolboxConfig.messengerGreetingMaxCharacters, 10 );
		let chars      = $( this ).val().length,
		    $counter   = $( 'span.characters-counter' ),
			$warning   = $counter.find( 'span' );

		$counter.html( chars + ' / ' + maxChars + '<br/>' ).append( $warning ).css( 'display', 'block' );

		if ( chars > maxChars ) {
			$counter.css( 'color', '#DC3232' ).find( 'span' ).show();
		} else {
			$counter.css( 'color', '#999999' ).find( 'span' ).hide();
		}
	} );


	let submitSettingsSave = false;

	$( '.woocommerce-save-button' ).on( 'click', function ( e ) {

		if ( ! submitSettingsSave ) {
			e.preventDefault();
		} else {
			return true;
		}

		const $submitButton   = $( this ),
		      categoriesAdded = getExcludedCategoriesAdded(),
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

} );
