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
		add_filter( 'request',               [ $this, 'filter_products_by_sync_status' ] );

		// add bulk actions to manage products sync status
		add_filter( 'bulk_actions-edit-product',        [ $this, 'add_products_sync_bulk_actions' ], 40 );
		add_action( 'handle_bulk_actions-edit-product', [ $this, 'handle_products_sync_bulk_actions' ] );
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


	/**
	 * Filters products by Facebook sync status.
	 *
	 * @internal
	 *
	 * @param array $query_vars product query vars for the edit screen
	 * @return array
	 */
	public function filter_products_by_sync_status( $query_vars ) {

		if ( isset( $_REQUEST['fb_sync_status'] ) && in_array( $_REQUEST['fb_sync_status'], [ 'yes', 'no' ], true ) ) {

			// by default use an "AND" clause if multiple conditions exist for a meta query
			if ( ! empty( $query_vars['meta_query'] ) ) {
				$query_vars['meta_query']['relation'] = 'AND';
			} else {
				$query_vars['meta_query'] = [];
			}

			if ( 'yes' === $_REQUEST['fb_sync_status'] ) {
				$query_vars['meta_query'][] = [
					'key'   => '_wc_facebook_sync',
					'value' => 'yes',
				];
			} else {

				// when checking for products not synced we need to check both "no" and meta not set, this requires adding an "OR" clause
				$query_vars['meta_query']['relation'] = 'OR';
				$query_vars['meta_query'][]           = [
					'key'   => '_wc_facebook_sync',
					'value' => 'no',
				];
				$query_vars['meta_query'][]           = [
					'key'     => '_wc_facebook_sync',
					'compare' => 'NOT EXISTS',
				];
			}
		}

		return $query_vars;
	}


	/**
	 * Adds bulk actions in the products edit screen.
	 *
	 * @internal
	 *
	 * @param array $bulk_actions array of bulk action keys and labels
	 * @return array
	 */
	public function add_products_sync_bulk_actions( $bulk_actions ) {

		$bulk_actions['facebook_include'] = __( 'Include in Facebook sync', 'facebook-for-woocommerce' );
		$bulk_actions['facebook_exclude'] = __( 'Exclude from Facebook sync', 'facebook-for-woocommerce' );

		return $bulk_actions;
	}


	/**
	 * Handles a Facebook product sync bulk action.
	 *
	 * @internal
	 *
	 * @param string $redirect admin URL used by WordPress to redirect after performing the bulk action
	 * @return string
	 */
	public function handle_products_sync_bulk_actions( $redirect ) {

		// primary dropdown at the top of the list table
		$action = isset( $_REQUEST['action'] ) && -1 !== (int) $_REQUEST['action'] ? $_REQUEST['action'] : null;

		// secondary dropdown at the bottom of the list table
		if ( ! $action ) {
			$action = isset( $_REQUEST['action2'] ) && -1 !== (int) $_REQUEST['action2'] ? $_REQUEST['action2'] : null;
		}

		if ( $action && in_array( $action, [ 'facebook_include', 'facebook_exclude' ], true ) ) {

			$products    = [];
			$product_ids = isset( $_REQUEST['post'] ) && is_array( $_REQUEST['post'] ) ? array_map( 'absint', $_REQUEST['post'] ) : [];

			if ( ! empty( $product_ids ) ) {

				foreach ( $product_ids as $product_id ) {

					if ( $product = wc_get_product( $product_id ) ) {

						$products[] = $product;
					}
				}

				if ( 'facebook_include' === $action ) {
					Products::enable_sync_for_products( $products );
				} elseif ( 'facebook_exclude' === $action ) {
					Products::disable_sync_for_products( $products );
				}
			}
		}

		return $redirect;
	}


}
