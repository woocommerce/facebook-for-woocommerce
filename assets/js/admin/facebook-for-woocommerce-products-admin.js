/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

jQuery( document ).ready( function( $ ) {

	const pagenow = window.pagenow.length ? window.pagenow : '',
	      typenow = window.typenow.length ? window.typenow : '';


	// products list edit screen
	if ( 'edit-product' === pagenow ) {


		let visibilityToggles = $( '.facebook-for-woocommerce-product-visibility-toggle' );

		// init visibility toggles tooltips
		visibilityToggles.tipTip( {
			attribute:  'title',
			edgeOffset: 5,
			fadeIn:     50,
			fadeOut:    50,
			delay:      200
		} );

		// handle FB Catalog Visibility buttons
		visibilityToggles.on( 'click', function( e ) {
			e.preventDefault();

			let action     = $( this ).data( 'action' ),
			    visibility = 'show' === action ? 'yes' : 'no',
			    productID  = parseInt( $( this ).data( 'product-id' ), 10 );

			if ( 'show' === action ) {
				$( this ).hide().next( 'button' ).show();
			} else if ( 'hide' === action ) {
				$( this ).hide().prev( 'button' ).show();
			}

			$.post( facebook_for_woocommerce_products_admin.ajax_url, {
				action:   'facebook_for_woocommerce_set_products_visibility',
				security: facebook_for_woocommerce_products_admin.set_product_visibility_nonce,
				products: [
					{
						product_id: productID,
						visibility: visibility
					}
				]
			} );
		} );


		// handle bulk actions
		let submitProductBulkAction = false;

		$( 'input#doaction, input#doaction2' ).on( 'click', function( e ) {

			if ( ! submitProductBulkAction ) {
				e.preventDefault();
			} else {
				return true;
			}

			let $submitButton    = $( this ),
				chosenBulkAction = $submitButton.prev( 'select' ).val();

			// TODO: also check `'facebook_exclude' === chosenBulkAction` once Catalog Visibility settings are available again {WV 2020-04-20}
			if ( 'facebook_include' === chosenBulkAction ) {

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

									let $toggle = $( '#post-' + this ).find( 'td.facebook_catalog_visibility button.facebook-for-woocommerce-product-visibility-hide' );

									if ( $toggle.is( ':visible' ) ) {
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
		 * @since 1.10.0
		 *
		 * @param {boolean} enabled whether the settings fields should be enabled or not
		 * @param {jQuery} $container a common ancestor of all the elements that can be enabled/disabled
		 */
		function toggleFacebookSettings( enabled, $container ) {

			$container.find( '.enable-if-sync-enabled' ).prop( 'disabled', ! enabled );
		}


		// toggle Facebook settings fields for simple products
		const syncModeSelect   = $( '#wc_facebook_sync_mode' );
		const facebookSettingsPanel = syncModeSelect.closest( '.woocommerce_options_panel' );

		syncModeSelect.on( 'change', function() {
			toggleFacebookSettings( $( this ).val() !== 'sync_disabled', facebookSettingsPanel );
		} );

		toggleFacebookSettings( syncModeSelect.val() !== 'sync_disabled', facebookSettingsPanel );

		// toggle Facebook settings fields for variations
		$( '.woocommerce_variations' ).on( 'change', '.js-variable-fb-sync-toggle', function() {
			toggleFacebookSettings( $( this ).val() !== 'sync_disabled', $( this ).closest( '.wc-metabox-content' ) );
		} );

		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function () {

			$( '.js-variable-fb-sync-toggle' ).each( function () {
				toggleFacebookSettings( $( this ).val() !== 'sync_disabled', $( this ).closest( '.wc-metabox-content' ) );
			} );
		} );

		// show/hide Custom Image URL setting
		$( '#woocommerce-product-data' ).on( 'change', '.js-fb-product-image-source', function() {

			let $container  = $( this ).closest( '.woocommerce_options_panel, .wc-metabox-content' );
			let imageSource = $( this ).val();

			$container.find( '.product-image-source-field' ).closest( '.form-field' ).hide();
			$container.find( `.show-if-product-image-source-${imageSource}` ).closest( '.form-field' ).show();
		} );

		$( '.js-fb-product-image-source:checked' ).trigger( 'change' );

		// trigger settings fields modifiers when variations are loaded
		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
			$( '.js-variable-fb-sync-toggle:visible' ).trigger( 'change' );
			$( '.js-fb-product-image-source:checked:visible' ).trigger( 'change' );
		} );

		let submitProductSave = false;

		$( 'form#post input[type="submit"]' ).on( 'click', function( e ) {

			if ( ! submitProductSave ) {
				e.preventDefault();
			} else {
				return true;
			}

			let $submitButton    = $( this ),
				$visibleCheckbox = $( 'input[name="fb_visibility"]' ),
				productID        = parseInt( $( 'input#post_ID' ).val(), 10 ),
				productCat       = [],
				// this query will get tags when not using checkboxes
				productTag       = $( 'textarea[name="tax_input[product_tag]"]' ).length ? $( 'textarea[name="tax_input[product_tag]"]' ).val().split( ',' ) : [],
				syncEnabled      = $( 'input#wc_facebook_sync_mode' ).val() !== 'sync_disabled',
				varSyncEnabled   = $( '.js-variable-fb-sync-toggle' ).val() !== 'sync_disabled';

			$( '#taxonomy-product_cat input[name="tax_input[product_cat][]"]:checked' ).each( function() {
				productCat.push( parseInt( $( this ).val(), 10 ) );
			} );

			// this query will get tags when using checkboxes
			$( '#taxonomy-product_tag input[name="tax_input[product_tag][]"]:checked' ).each( function() {
				productTag.push( parseInt( $( this ).val(), 10 ) );
			} );

			if ( productID > 0 ) {

				$.post( facebook_for_woocommerce_products_admin.ajax_url, {
					action:           'facebook_for_woocommerce_set_product_sync_prompt',
					security:         facebook_for_woocommerce_products_admin.set_product_sync_prompt_nonce,
					sync_enabled:     syncEnabled    ? 'enabled' : 'disabled',
					var_sync_enabled: varSyncEnabled ? 'enabled' : 'disabled',
					product:          productID,
					categories:       productCat,
					tags:             productTag
				}, function( response ) {

					// open modal if visibility checkbox is checked or if there are conflicting terms set for sync exclusion
					if ( response && ! response.success && ( syncEnabled || ( ! syncEnabled && $visibleCheckbox.length && $visibleCheckbox.is( ':checked' ) ) ) ) {

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
								$visibleCheckbox.prop( 'checked', false );
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


	// product list screen or individual product edit screen
	if ( 'product' === pagenow || 'edit-product' === pagenow ) {

		// handle the "Do not show this notice again" button
		$( '.js-wc-plugin-framework-admin-notice' ).on( 'click', '.notice-dismiss-permanently', function() {

			var $notice = $( this ).closest( '.js-wc-plugin-framework-admin-notice' );

			$.get( ajaxurl, {
				action:      'wc_plugin_framework_' + $( $notice ).data( 'plugin-id' ) + '_dismiss_notice',
				messageid:   $( $notice ).data( 'message-id' ),
				permanently: true
			} );

			$notice.fadeOut();
		} );
	}


} );
