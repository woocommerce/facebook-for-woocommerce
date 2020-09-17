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

	/**
	 * Google product category field handler.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @type {WC_Facebook_Google_Product_Category_Fields} object
	 */
	window.WC_Facebook_Google_Product_Category_Fields = class WC_Facebook_Google_Product_Category_Fields {


		/**
		 * Handler constructor.
		 *
		 * @since 2.1.0-dev.1
		 *
		 * @param {Object[]} categories The full categories list, indexed by the category ID
		 * @param {string} categories[].label The category label
		 * @param {string[]} categories[].options The category's child categories' IDs
		 * @param {string} categories[].parent The category's parent category ID
		 * @param {string} input_id The element that should receive the latest concrete category ID
		 */
		constructor(categories, input_id) {

			this.categories = categories;

			this.input_id = input_id;

			$( '<div id="wc-facebook-google-product-category-fields"></div>' ).insertBefore( $( '#' + this.input_id ) );

			// TODO: add first two selects
		}


		/**
		 * Updates the subsequent selects whenever one of the selects changes.
		 *
		 * @since 2.1.0-dev.1
		 */
		onChange(element) {

			// TODO: implement
		}


		/**
		 * Adds a new select with the given options.
		 *
		 * @since 2.1.0-dev.1
		 *
		 * @param {Object.<string, string>} options an object with option IDs as keys and option labels as values
		 * @param {string} placeholder
		 */
		addSelect( options, placeholder ) {

			var $container = $( '#wc-facebook-google-product-category-fields' );
			var $select = $( '<select class="wc-enhanced-select wc-facebook-google-product-category-select"></select>' );

			$container.find( 'wc-facebook-google-product-category-select' ).addClass( 'locked' );
			$container.append( $( '<div class="wc-facebook-google-product-category-field">' ).append( $select ) );

			$select.attr( 'data-placeholder', placeholder ).append( $( '<option value=""></option>' ) );

			Object.keys( options ).forEach( ( key ) => {
				$select.append( $( '<option value="' + key + '">' + options[ key ] + '</option>' ) );
			} );

			$select.select2();
		}


		/**
		 * Gets an array of options for the given category ID.
		 *
		 * @since 2.1.0-dev.1
		 *
		 * @param {string} category_id The given category ID
		 * @return {Object.<string, string>} an object with option IDs as keys and option labels as values
		 */
		getOptions(category_id) {

			if ( 'undefined' === typeof category_id ) {
				return this.getTopLevelOptions();
			}

			if ( 'undefined' === typeof this.categories[ category_id ] ) {
				return [];
			}

			if ( 'undefined' === typeof this.categories[ category_id ]['options'] ) {
				return [];
			}

			return this.categories[ category_id ]['options'];
		}


		/**
		 * Gets an array of top level category options.
		 *
		 * @since 2.1.0-dev.1
		 *
		 * @return {Object.<string, string>} an object with option IDs as keys and option labels as values
		 */
		getTopLevelOptions() {

			let options = {};

			Object.keys( this.categories ).forEach( ( key ) => {

				if ( this.categories[ key ].parent ) {
					return;
				}

				options[ key ] = this.categories[ key ].label;
			} );

			return options;
		}


	}


} );
