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

		function showEnhancedAttributesForCategory(selectedCategory) {
			const attributeFormField =  function(attribute) {
				console.log("VALUE ", attribute.value);
				switch(attribute.type) {
					case 'enum':
						const values = attribute.values.map(function(val) {
							return $('<option/>').attr({
								'value': val,
								'selected': val === attribute.value
							}).text(val);
						});
						const select = $('<select/>').attr({
													name: 'fb_enhanced_attribute['+attribute.key+']',
													id: 'fb_enhanced_attribute['+attribute.key+']'
												});
						values.forEach(function(valEl) { select.append(valEl); });
						return select;
					case 'boolean':
						const list = $('<ul/>').addClass('wc-radios');
						const yes = $('<label/>').append(
													$('<input>').attr({
														'type': 'radio',
														'value': 'yes',
														'name': 'fb_enhanced_attribute['+attribute.key+']',
														'checked': 'yes' === attribute.value,
													})
												).append('Yes');
						const no = $('<label/>').append(
													$('<input>').attr({
														'type': 'radio',
														'value': 'no',
														'name': 'fb_enhanced_attribute['+attribute.key+']',
														'checked': 'no' === attribute.value,
													})
												).append('No');
						list.append(
							$('<li/>').append(yes)
						).append(
							$('<li/>').append(no)
						);
						return list;
					case 'text':
					default:
						return $('<input/>')
												.attr({
													type: 'text',
													name: 'fb_enhanced_attribute['+attribute.key+']',
													id: 'fb_enhanced_attribute['+attribute.key+']'
												}).val(attribute.value);
				}
			};

			const attributeFormFieldWrapper = function(attribute) {
				switch(attribute.type) {
					case 'boolean':
						return $('<fieldset/>')
										.addClass('form-field fb_enhanced_attribute')
										.append($('<legend/>').text(attribute.name));
					default:
						return $('<p/>')
											.addClass('form-field fb_enhanced_attribute')
											.append(
												$('<label/>')
													.attr('for', 'fb_enhanced_attribute['+attribute.key+']')
													.text(attribute.name)
											);
				}
			};

			const optionsGroupHeader = function(text) {
				return $('<h4/>')
									.text(text)
									.attr('style', 'margin-left: 5px;');
			}

			const optionsContainer = $('#facebook_options');
			$('.fb_enhanced_attribute_container', optionsContainer).remove();

			$.get( facebook_for_woocommerce_products_admin.ajax_url, {
				action:   'wc_facebook_category_attributes',
				security: '',
				category:  selectedCategory,
				item_id: parseInt( $( 'input#post_ID' ).val(), 10 ),
			}, function( response ) {
				if(response) {
					const primaryOptionsGroup = $('<div/>').addClass('options_group fb_enhanced_attribute_container');
					const secondaryOptionsGroup = $('<div/>').addClass('options_group fb_enhanced_attribute_container');
					primaryOptionsGroup.append(optionsGroupHeader('Primary Attributes'));
					secondaryOptionsGroup.append(optionsGroupHeader('Secondary Attributes'));
					response.primary.forEach(function(attribute){
						const wrapper = attributeFormFieldWrapper(attribute);
						const formField = attributeFormField(attribute);
						wrapper.append(formField);
						primaryOptionsGroup.append(wrapper);
					});
					response.secondary.forEach(function(attribute){
						const wrapper = attributeFormFieldWrapper(attribute);
						const formField = attributeFormField(attribute);
						wrapper.append(formField);
						secondaryOptionsGroup.append(wrapper);
					});
					optionsContainer
						.append(primaryOptionsGroup)
						.append(secondaryOptionsGroup);
				}
			} );
		}
		const currentlySelectedCategory = $( '#woocommerce-product-data select[name=fb_category]' ).val();
		if(currentlySelectedCategory){
			showEnhancedAttributesForCategory(currentlySelectedCategory);
		}


		/**
		 * Toggles (shows/hides) Sync and show option and changes the select value if needed.
		 *
		 * @since 2.0.0
		 *
		 * @param {boolean} show whether the Sync and show option should be displayed or not
		 * @param {jQuery} $select the sync mode select
		 */
		function toggleSyncAndShowOption( show, $select ) {

			if ( ! show ) {

				// hide Sync and show option
				$select.find( 'option[value=\'sync_and_show\']' ).hide();

				if ( 'sync_and_show' === $select.val() ) {
					// change selected option to Sync and hide
					$select.val( 'sync_and_hide' );
				}

			} else {

				// show Sync and Show option
				$select.find( 'option[value=\'sync_and_show\']' ).show();

				// restore originally selected option
				if ( $select.prop( 'original' ) ) {
					$select.val( $select.prop( 'original' ) );
				}
			}
		}


		// toggle Facebook settings fields for simple products
		const syncModeSelect   = $( '#wc_facebook_sync_mode' );
		const facebookSettingsPanel = syncModeSelect.closest( '.woocommerce_options_panel' );

		syncModeSelect.on( 'change', function() {

			toggleFacebookSettings( $( this ).val() !== 'sync_disabled', facebookSettingsPanel );
			syncModeSelect.prop( 'original', $( this ).val() );

		} ).trigger( 'change' );

		$( '#_virtual' ).on( 'change', function () {
			toggleSyncAndShowOption( ! $( this ).prop( 'checked' ), syncModeSelect );
		} ).trigger( 'change' );

		// toggle Facebook settings fields for variations
		$( '.woocommerce_variations' ).on( 'change', '.js-variable-fb-sync-toggle', function() {
			toggleFacebookSettings( $( this ).val() !== 'sync_disabled', $( this ).closest( '.wc-metabox-content' ) );
			$( this ).prop( 'original', $( this ).val() );
		} );

		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function () {

			$( '.js-variable-fb-sync-toggle' ).each( function () {
				toggleFacebookSettings( $( this ).val() !== 'sync_disabled', $( this ).closest( '.wc-metabox-content' ) );
				$( this ).prop( 'original', $( this ).val() );
			} );

			$( '.variable_is_virtual' ).on( 'change', function () {
				const jsSyncModeToggle = $( this ).closest( '.wc-metabox-content' ).find( '.js-variable-fb-sync-toggle' );
				toggleSyncAndShowOption( ! $( this ).prop( 'checked' ), jsSyncModeToggle );
			} );
		} );

		// show/hide Custom Image URL setting
		$( '#woocommerce-product-data' ).on( 'change', '.js-fb-product-image-source', function() {

			let $container  = $( this ).closest( '.woocommerce_options_panel, .wc-metabox-content' );
			let imageSource = $( this ).val();

			$container.find( '.product-image-source-field' ).closest( '.form-field' ).hide();
			$container.find( `.show-if-product-image-source-${imageSource}` ).closest( '.form-field' ).show();
		} );

		$( '.js-fb-product-image-source:checked:visible' ).trigger( 'change' );

		$( '#woocommerce-product-data select[name=fb_category]' ).on('change', function(e) {
			const selectedCategory = $(this).val();
			showEnhancedAttributesForCategory(selectedCategory);
		});

		// trigger settings fields modifiers when variations are loaded
		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
			$( '.js-variable-fb-sync-toggle:visible' ).trigger( 'change' );
			$( '.js-fb-product-image-source:checked:visible' ).trigger( 'change' );
			$( '.variable_is_virtual:visible' ).trigger( 'change' );
		} );


		let submitProductSave = false;

		$( 'form#post input[type="submit"]' ).on( 'click', function( e ) {

			if ( ! submitProductSave ) {
				e.preventDefault();
			} else {
				return true;
			}

			let $submitButton    = $( this ),
				productID        = parseInt( $( 'input#post_ID' ).val(), 10 ),
				productCat       = [],
				// this query will get tags when not using checkboxes
				productTag       = $( 'textarea[name="tax_input[product_tag]"]' ).length ? $( 'textarea[name="tax_input[product_tag]"]' ).val().split( ',' ) : [],
				syncEnabled      = $( '#wc_facebook_sync_mode' ).val() !== 'sync_disabled',
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
					if ( response && ! response.success && syncEnabled ) {

						// close existing modals
						$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

						// open new modal, populate template with AJAX response data
						new $.WCBackboneModal.View( {
							target: 'facebook-for-woocommerce-modal',
							string: response.data
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
