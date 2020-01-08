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
		add_filter( 'manage_product_posts_columns',       [ $this, 'add_product_list_table_column' ] );
		add_action( 'manage_product_posts_custom_column', [ $this, 'add_product_list_table_column_content' ] );

		// add input to filter products by Facebook sync status
		add_action( 'restrict_manage_posts', [ $this, 'add_products_by_sync_status_input_filter' ], 40 );
	}


	/**
	 * Adds a column for Facebook Sync in the products edit screen.
	 *
	 * @internal
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
	 * @internal
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


	/**
	 * Adds a dropdown input to let shop managers filter products by sync status.
	 *
	 * @internal
	 */
	public function add_products_by_sync_status_input_filter() {
		global $typenow;

		if ( 'product' !== $typenow ) {
			return;
		}

		$choice = isset( $_GET['fb_sync_status'] ) ? (string) $_GET['fb_sync_status'] : '';

		?>
		<select name="fb_sync_status">
			<option value="" <?php selected( $choice, '' ); ?>><?php esc_html_e( 'Filter by Facebook sync status', 'facebook-for-woocommerce' ); ?></option>
			<option value="yes" <?php selected( $choice, 'yes' ); ?>><?php esc_html_e( 'Synced to Facebook', 'facebook-for-woocommerce' ); ?></option>
			<option value="no" <?php selected( $choice, 'no' ); ?>><?php esc_html_e( 'Not synced to Facebook', 'facebook-for-woocommerce' ); ?></option>
		</select>
		<?php
	}


}
