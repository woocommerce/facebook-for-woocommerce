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
 *
 * @since x.y.z
 */
class Admin {


	/**
	 * Admin constructor.
	 *
	 * @since x.y.z
	 */
	public function __construct() {

		// add admin notification in case of site URL change
		add_action( 'admin_notices', [ $this, 'validate_cart_url' ] );

		// add columns for displaying Facebook sync enabled/disabled and shop visibility status
		add_filter( 'manage_product_posts_columns',       [ $this, 'add_product_list_table_columns' ] );
		add_action( 'manage_product_posts_custom_column', [ $this, 'add_product_list_table_columns_content' ] );

		// add input to filter products by Facebook sync enabled
		add_action( 'restrict_manage_posts', [ $this, 'add_products_by_sync_enabled_input_filter' ], 40 );
		add_filter( 'request',               [ $this, 'filter_products_by_sync_enabled' ] );

		// add bulk actions to manage products sync
		add_filter( 'bulk_actions-edit-product',        [ $this, 'add_products_sync_bulk_actions' ], 40 );
		add_action( 'handle_bulk_actions-edit-product', [ $this, 'handle_products_sync_bulk_actions' ] );
	}


	/**
	 * Adds Facebook-related columns in the products edit screen.
	 *
	 * @internal
	 *
	 * @param array $columns array of keys and labels
	 * @return array
	 */
	public function add_product_list_table_columns( $columns ) {

		$columns['facebook_sync_enabled']    = __( 'FB Sync Enabled', 'facebook-for-woocommerce' );
		$columns['facebook_shop_visibility'] = __( 'FB Shop Visibility', 'facebook-for-woocommerce' );

		return $columns;
	}


	/**
	 * Outputs sync information for products in the edit screen.
	 *
	 * @internal
	 *
	 * @param string $column the current column in the posts table
	 */
	public function add_product_list_table_columns_content( $column ) {
		global $post;

		if ( 'facebook_sync_enabled' === $column ) :

			$product = wc_get_product( $post );

			if ( $product && Products::is_sync_enabled_for_product( $product ) ) :
				esc_html_e( 'Enabled', 'facebook-for-woocommerce' );
			else :
				esc_html_e( 'Disabled', 'facebook-for-woocommerce' );
			endif;

		elseif ( 'facebook_shop_visibility' === $column ) :

			// TODO this script is re-enqueued on each row by design or it won't work, perhaps a refactor is in order later on {FN 2020-01-13}
			wp_enqueue_script( 'wc_facebook_product_jsx', plugins_url( '/assets/js/facebook-products.js?ts=' . time(), __DIR__ ) );
			wp_localize_script( 'wc_facebook_product_jsx', 'wc_facebook_product_jsx', [
				'nonce' => wp_create_nonce( 'wc_facebook_product_jsx' )
			] );

			$integration         = facebook_for_woocommerce()->get_integration();
			$product             = wc_get_product( $post );
			$fb_product          = new \WC_Facebook_Product( $post );
			$fb_product_group_id = $integration && $product && $integration->get_product_fbid( \WC_Facebookcommerce_Integration::FB_PRODUCT_GROUP_ID, $post->ID, $fb_product );

			if ( ! $fb_product_group_id ) :

				?><span>&ndash;</span><?php

			else :

				$visibility = $product->get_meta( \WC_Facebookcommerce_Integration::FB_VISIBILITY );

				// TODO: current JS code will change the button text and tooltip content without considering localization here {FN 2020-01-13}

				if ( ! $visibility ) {
					$data_tip_content = __( 'Product is synced but not marked as published (visible) on Facebook.', 'facebook-for-woocommerce' );
				} else {
					$data_tip_content = __( 'Product is synced and published (visible) on Facebook.', 'facebook-for-woocommerce' );
				}

				// TODO be mindful of classes and IDs for HTML below as it may have to be refactored if JS script changes for handling visibility {FN 2020-01-13}

				?>
				<span
					class="tips"
					id="tip_<?php echo esc_attr( $post->ID ); ?>"
					data-tip="<?php echo esc_attr( $data_tip_content ); ?>">
					<?php

					if ( ! $visibility ) :

						?>
						<a
							id="viz_<?php echo esc_attr( $post->ID ); ?>"
							class="button button-primary button-large"
							href="javascript:;"
							onclick="fb_toggle_visibility( <?php echo esc_attr( $post->ID ); ?>, true )">
							<?php esc_html_e( 'Show', 'facebook-for-woocommerce' ); ?>
						</a>
						<?php

					else :

						?>
						<a
							id="viz_<?php echo esc_attr( $post->ID ); ?>"
							class="button button-large"
							href="javascript:;"
							onclick="fb_toggle_visibility(<?php echo esc_attr( $post->ID ); ?>, false)">
							<?php esc_html_e( 'Hide', 'facebook-for-woocommerce' ); ?>
						</a>
						<?php

					endif;

					?>
				</span>
				<?php

			endif;

		endif;
	}


	/**
	 * Adds a dropdown input to let shop managers filter products by sync setting.
	 *
	 * @internal
	 */
	public function add_products_by_sync_enabled_input_filter() {
		global $typenow;

		if ( 'product' !== $typenow ) {
			return;
		}

		$choice = isset( $_GET['fb_sync_enabled'] ) ? (string) $_GET['fb_sync_enabled'] : '';

		?>
		<select name="fb_sync_enabled">
			<option value="" <?php selected( $choice, '' ); ?>><?php esc_html_e( 'Filter by Facebook sync setting', 'facebook-for-woocommerce' ); ?></option>
			<option value="yes" <?php selected( $choice, 'yes' ); ?>><?php esc_html_e( 'Facebook sync enabled', 'facebook-for-woocommerce' ); ?></option>
			<option value="no" <?php selected( $choice, 'no' ); ?>><?php esc_html_e( 'Facebook sync disabled', 'facebook-for-woocommerce' ); ?></option>
		</select>
		<?php
	}


	/**
	 * Filters products by Facebook sync setting.
	 *
	 * @internal
	 *
	 * @param array $query_vars product query vars for the edit screen
	 * @return array
	 */
	public function filter_products_by_sync_enabled( $query_vars ) {

		if ( isset( $_REQUEST['fb_sync_enabled'] ) && in_array( $_REQUEST['fb_sync_enabled'], [ 'yes', 'no' ], true ) ) {

			// by default use an "AND" clause if multiple conditions exist for a meta query
			if ( ! empty( $query_vars['meta_query'] ) ) {
				$query_vars['meta_query']['relation'] = 'AND';
			} else {
				$query_vars['meta_query'] = [];
			}

			// when checking for products with sync enabled we need to check both "yes" and meta not set, this requires adding an "OR" clause
			if ( 'yes' === $_REQUEST['fb_sync_enabled'] ) {

				$query_vars['meta_query']['relation'] = 'OR';
				$query_vars['meta_query'][]           = [
					'key'   => Products::SYNC_ENABLED_META_KEY,
					'value' => 'yes',
				];
				$query_vars['meta_query'][]           = [
					'key'     => Products::SYNC_ENABLED_META_KEY,
					'compare' => 'NOT EXISTS',
				];

				// check whether the product belongs to an excluded product category or tag
				$query_vars = $this->maybe_add_tax_query_for_excluded_taxonomies( $query_vars );

			} else {

				$query_vars['meta_query'][] = [
					'key'   => Products::SYNC_ENABLED_META_KEY,
					'value' => 'no',
				];
			}
		}

		return $query_vars;
	}


	/**
	 * Adds a tax query to filter out products in excluded product categories and product tags.
	 *
	 * @since x.y.z
	 *
	 * @param array $query_vars product query vars for the edit screen
	 * @return array
	 */
	private function maybe_add_tax_query_for_excluded_taxonomies( $query_vars ) {

		$integration = facebook_for_woocommerce()->get_integration();
		$tax_query   = [];

		if ( $integration ) {

			$excluded_categories_ids = $integration->get_excluded_product_category_ids();

			if ( $excluded_categories_ids ) {
				$tax_query[] = [
					'taxonomy' => 'product_cat',
					'terms'    => $excluded_categories_ids,
					'field'    => 'term_id',
					'operator' => 'NOT IN',
				];
			}

			$exlcuded_tags_ids = $integration->get_excluded_product_tag_ids();

			if ( $exlcuded_tags_ids ) {
				$tax_query[] = [
					'taxonomy' => 'product_tag',
					'terms'    => $exlcuded_tags_ids,
					'field'    => 'term_id',
					'operator' => 'NOT IN',
				];
			}
		}

		if ( $tax_query && empty( $query_vars['tax_query'] ) ) {
			$query_vars['tax_query'] = $tax_query;
		} elseif ( $tax_query && is_array( $query_vars ) ) {
			array_push( $query_vars['tax_query'], $tax_query );
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


	/**
	 * Prints a notice on products page in case the current cart URL is not the original sync URL.
	 *
	 * @internal
	 *
	 * TODO: update this method to use the notice handler once we framework the plugin {CW 2020-01-09}
	 *
	 * @since x.y.z
	 */
	public function validate_cart_url() {
		global $current_screen;

		if ( isset( $current_screen->id ) && in_array( $current_screen->id, [ 'edit-product', 'product' ], true ) ) :

			$cart_url = get_option( \WC_Facebookcommerce_Integration::FB_CART_URL, '' );

			if ( ! empty( $cart_url ) && $cart_url !== wc_get_cart_url() ) :

				?>
				<div class="notice notice-warning">
					<?php printf(
						/* translators: Placeholders: %1$s - Facebook for Woocommerce, %2$s - opening HTML <a> link tag, %3$s - closing HTML </a> link tag */
						'<p>' . esc_html__( '%1$s: One or more of your products is using a checkout URL that may be different than your shop checkout URL. %2$sRe-sync your products to update checkout URLs on Facebook%3$s.', 'facebook-for-woocommerce' ) . '</p>',
						'<strong>' . esc_html__( 'Facebook for WooCommerce', 'facebook-for-woocommerce' ) . '</strong>',
						'<a href="' . esc_url( WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL ) . '">',
						'</a>'
					); ?>
				</div>
				<?php

			endif;

		endif;
	}


}
