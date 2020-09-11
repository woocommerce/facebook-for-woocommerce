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
	class WC_Facebook_Google_Product_Category_Fields {


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

			// TODO: add wrapper div

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
		 * @param {string[]} options The select options, indexed by the option ID
		 */
		addSelect(options) {

			// TODO: implement
		}


		/**
		 * Gets an array of options for the given category ID.
		 *
		 * @since 2.1.0-dev.1
		 *
		 * @param {string} category_id The given category ID
		 * @return {string[]} the select options, indexed by the option ID
		 */
		getOptions(category_id) {

			// TODO: implement
		}


	}


} );
