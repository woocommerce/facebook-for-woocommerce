<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

defined( 'ABSPATH' ) or exit;

/**
 * Admin handler.
 */
class Admin {


	/**
	 * Admin constructor.
	 */
	public function __construct() {

		// add column for displaying Facebook sync status
		add_action( 'manage_product_posts_columns',       [ $this, 'add_product_list_table_column' ] );
		add_action( 'manage_product_posts_custom_column', [ $this, 'add_product_list_table_column_content' ] );
	}


	/**
	 * Adds a column for Facebook Sync in the products edit screen.
	 *
	 * @param array $columns array of keys and labels
	 * @return array
	 */
	public function add_product_list_table_column( $columns ) {

		$columns['facebook'] = __( 'FB Sync Status', 'facebook-for-woocommerce' );

		return $columns;
	}


	/**
	 * Outputs sync information for products in the edit screen.
	 *
	 * @param string $column the current column in the posts table
	 */
	public function add_product_list_table_column_content( $column ) {
		global $post;

		if ( 'facebook' === $column ) {

			$product = wc_get_product( $post );

			if ( $product && Products::is_sync_enabled_for_product( $product ) ) {
				esc_html_e( 'Synced', 'facebook-for-woocommerce' );
			} else {
				esc_html_e( 'Not synced', 'facebook-for-woocommerce' );
			}
		}
	}


}
