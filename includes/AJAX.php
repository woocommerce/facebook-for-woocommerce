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
 * AJAX handler.
 *
 * @since 1.10.0
 */
class AJAX {


	/**
	 * AJAX handler constructor.
	 *
	 * @since 1.10.0
	 */
	public function __construct() {

		// maybe output a modal prompt when toggling product sync in bulk or individual product actions
		add_action( 'wp_ajax_facebook_for_woocommerce_set_product_sync_prompt',             [ $this, 'handle_set_product_sync_prompt' ] );
		add_action( 'wp_ajax_facebook_for_woocommerce_set_product_sync_bulk_action_prompt', [ $this, 'handle_set_product_sync_bulk_action_prompt' ] );

		// maybe output a modal prompt when setting excluded terms
		add_action( 'wp_ajax_facebook_for_woocommerce_set_excluded_terms_prompt', [ $this, 'handle_set_excluded_terms_prompt' ] );

		// set product visibility in Facebook
		add_action( 'wp_ajax_facebook_for_woocommerce_set_products_visibility', [ $this, 'set_products_visibility' ] );
	}


	/**
	 * Maybe triggers a modal warning when the merchant toggles sync enabled status on a product.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function handle_set_product_sync_prompt() {

		check_ajax_referer( 'set-product-sync-prompt', 'security' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_id       = isset( $_POST['product'] )          ? (int) $_POST['product']             : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sync_enabled     = isset( $_POST['sync_enabled'] )     ? (string) $_POST['sync_enabled']     : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$var_sync_enabled = isset( $_POST['var_sync_enabled'] ) ? (string) $_POST['var_sync_enabled'] : '';
	    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_cats     = isset( $_POST['categories'] )       ? (array) $_POST['categories']        : [];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_tags     = isset( $_POST['tags'] )             ? (array) $_POST['tags']              : [];

		if ( $product_id > 0 && in_array( $var_sync_enabled, [ 'enabled', 'disabled' ], true ) && in_array( $sync_enabled, [ 'enabled', 'disabled' ], true ) ) {

			$product = wc_get_product( $product_id );

			if ( $product instanceof \WC_Product ) {

				if ( 'disabled' === $sync_enabled && Products::is_sync_enabled_for_product( $product ) ) {

					ob_start();

					?>
					<button
						id="facebook-for-woocommerce-hide-products"
						class="button button-large button-primary facebook-for-woocommerce-toggle-product-visibility hide-products"
					><?php esc_html_e( 'Hide Product', 'facebook-for-woocommerce' ); ?></button>
					<button
						id="facebook-for-woocommerce-do-not-hide-products"
						class="button button-large button-primary facebook-for-woocommerce-toggle-product-visibility show-products"
					><?php esc_html_e( 'Do Not Hide Product', 'facebook-for-woocommerce' ); ?></button>
					<?php

					$buttons = ob_get_clean();

					wp_send_json_error( [
						'message' => __( 'This product will no longer be updated in your Facebook catalog. Would you like to hide this product from your Facebook shop?', 'facebook-for-woocommerce' ),
						'buttons' => $buttons,
					] );

				} elseif ( ( 'enabled' === $sync_enabled && ! $product->is_type( 'variable' ) ) || ( 'enabled' === $var_sync_enabled && $product->is_type( 'variable' ) ) ) {

					$has_excluded_terms = false;

					if ( $integration = facebook_for_woocommerce()->get_integration() ) {

						// try with categories first, since we have already IDs
						$has_excluded_terms = ! empty( $product_cats ) && array_intersect( $product_cats, $integration->get_excluded_product_category_ids() );

						// try next with tags, but WordPress only gives us tag names
						if ( ! $has_excluded_terms && ! empty( $product_tags ) ) {

							$product_tag_ids = [];

							foreach ( $product_tags as $product_tag_name ) {

								if ( $term = get_term_by( 'name', $product_tag_name, 'product_tag' ) ) {

									$product_tag_ids[] = $term->term_id;
								}
							}

							$has_excluded_terms = ! empty( $product_tag_ids ) && array_intersect( $product_tag_ids, $integration->get_excluded_product_tag_ids() );
						}
					}

					if ( $has_excluded_terms ) {

						ob_start();

						?>
						<a
							id="facebook-for-woocommerce-go-to-settings"
							class="button button-large"
							href="<?php echo esc_url( add_query_arg( 'section', \WC_Facebookcommerce::INTEGRATION_ID, admin_url( 'admin.php?page=wc-settings&tab=integration' ) ) ); ?>"
						><?php esc_html_e( 'Go to Settings', 'facebook-for-woocommerce' ); ?></a>
						<button
							id="facebook-for-woocommerce-cancel-sync"
							class="button button-large button-primary"
							onclick="jQuery( '.modal-close' ).trigger( 'click' )"
						><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
						<?php

						$buttons = ob_get_clean();

						wp_send_json_error( [
							'message' => sprintf(
								/* translators: Placeholder %s - <br/> tag */
								__( 'This product belongs to a category or tag that is excluded from the Facebook catalog sync. It will not sync to Facebook. %sTo sync this product to Facebook, click Go to Settings and remove the category or tag exclusion or click Cancel and update the product\'s category / tag assignments.', 'facebook-for-woocommerce' ),
								'<br/><br/>'
							),
							'buttons' => $buttons,
						] );
					}
				}
			}
		}

		wp_send_json_success();
	}


	/**
	 * Maybe triggers a modal warning when the merchant toggles sync enabled status in bulk.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function handle_set_product_sync_bulk_action_prompt() {

		check_ajax_referer( 'set-product-sync-bulk-action-prompt', 'security' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_ids = isset( $_POST['products'] ) ? (array)  $_POST['products'] : [];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$toggle      = isset( $_POST['toggle'] )   ? (string) $_POST['toggle']   : '';

		if ( ! empty( $product_ids ) && ! empty( $toggle ) ) {

			// merchant wants to exclude products from sync: ask them what they want to do with their visibility status
			if ( 'facebook_exclude' === $toggle ) {

				ob_start();

				?>
				<button
					id="facebook-for-woocommerce-hide-products"
					class="button button-large button-primary facebook-for-woocommerce-toggle-product-visibility hide-products"
				><?php esc_html_e( 'Hide Products', 'facebook-for-woocommerce' ); ?></button>
				<button
					id="facebook-for-woocommerce-do-not-hide-products"
					class="button button-large button-primary facebook-for-woocommerce-toggle-product-visibility show-products"
				><?php esc_html_e( 'Do Not Hide Products', 'facebook-for-woocommerce' ); ?></button>
				<?php

				$buttons = ob_get_clean();

				wp_send_json_error( [
					'message' => __( 'The selected products will no longer be updated in your Facebook catalog. Would you like to hide these products from your Facebook shop?', 'facebook-for-woocommerce' ),
					'buttons' => $buttons,
				] );

			// merchant wants to enable sync in Facebook multiple products: we must check if they belong to excluded categories, and perhaps warn them
			} elseif ( 'facebook_include' === $toggle && ( $integration = facebook_for_woocommerce()->get_integration() ) ) {

				$has_excluded_term = false;

				foreach ( $product_ids as $product_id ) {

					$product = wc_get_product( $product_id );

					// product belongs to at least one excluded term: break the loop
					if ( $product instanceof \WC_Product && Products::is_sync_excluded_for_product_terms( $product ) ) {

						$has_excluded_term = true;
						break;
					}
				}

				// show modal if there's at least one product that belongs to an excluded term
				if ( $has_excluded_term )  {

					ob_start();

					?>
					<a
						id="facebook-for-woocommerce-go-to-settings"
						class="button button-large"
						href="<?php echo esc_url( add_query_arg( 'section', \WC_Facebookcommerce::INTEGRATION_ID, admin_url( 'admin.php?page=wc-settings&tab=integration' ) ) ); ?>"
					><?php esc_html_e( 'Go to Settings', 'facebook-for-woocommerce' ); ?></a>
					<button
						id="facebook-for-woocommerce-cancel-sync"
						class="button button-large button-primary"
						onclick="jQuery( '.modal-close' ).trigger( 'click' )"
					><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
					<?php

					$buttons = ob_get_clean();

					wp_send_json_error( [
						'message' => __( 'One or more of the selected products belongs to a category or tag that is excluded from the Facebook catalog sync. To sync these products to Facebook, please remove the category or tag exclusion from the plugin settings.', 'facebook-for-woocommerce' ),
						'buttons' => $buttons,
					] );
				}
			}
		}

		wp_send_json_success();
	}


	/**
	 * Maybe triggers a modal warning when the merchant adds terms to the excluded terms.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function handle_set_excluded_terms_prompt() {

		check_ajax_referer( 'set-excluded-terms-prompt', 'security' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$posted_categories = isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$posted_tags = isset( $_POST['tags'] ) ? wp_unslash( $_POST['tags'] ) : [];

		$new_category_ids = [];
		$new_tag_ids      = [];

		if ( ! empty( $posted_categories ) ) {
			foreach ( $posted_categories as $posted_category_id ) {
				$new_category_ids[] = sanitize_text_field( $posted_category_id );
			}
		}

		if ( ! empty( $posted_tags ) ) {
			foreach ( $posted_tags as $posted_tag_id ) {
				$new_tag_ids[] = sanitize_text_field( $posted_tag_id );
			}
		}

		// query for products with sync enabled, belonging to the added term IDs and not belonging to the term IDs that are already stored in the setting
		$products = $this->get_products_to_be_excluded( $new_category_ids, $new_tag_ids );

		if ( ! empty( $products ) ) {

			ob_start();

			?>
			<button
				id="facebook-for-woocommerce-confirm-settings-change"
				class="button button-large button-primary facebook-for-woocommerce-confirm-settings-change"
			><?php esc_html_e( 'Exclude Products', 'facebook-for-woocommerce' ); ?></button>

			<!-- TODO: restore for FBE 2.0
			<button
				id="facebook-for-woocommerce-confirm-settings-change-hide-products"
				class="button button-large button-primary facebook-for-woocommerce-confirm-settings-change hide-products"
			><?php esc_html_e( 'Exclude Products and Hide in Facebook', 'facebook-for-woocommerce' ); ?></button> -->

			<button
				id="facebook-for-woocommerce-cancel-settings-change"
				class="button button-large button-primary"
				onclick="jQuery( '.modal-close' ).trigger( 'click' )"
			><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
			<?php

			$buttons = ob_get_clean();

			wp_send_json_error( [
				'message' => sprintf(
					/* translators: Placeholder %s - <br/> tags */
					__( 'The categories and/or tags that you have selected to exclude from sync contain products that are currently synced to Facebook.%sTo exclude these products from the Facebook sync, click Exclude Products. To review the category / tag exclusion settings, click Cancel.', 'facebook-for-woocommerce' ),
					'<br/><br/>'
				),
				'buttons' => $buttons,
			] );

		} else {

			// the modal should not be displayed
			wp_send_json_success();
		}
	}


	/**
	 * Get the IDs of the products that would be excluded with the new settings.
	 *
	 * Queries products with sync enabled, belonging to the added term IDs
	 * and not belonging to the term IDs that are already stored in the setting.
	 *
	 * @since 1.10.0
	 *
	 * @param string[] $new_excluded_categories
	 * @param string[] $new_excluded_tags
	 * @return int[]
	 */
	private function get_products_to_be_excluded( $new_excluded_categories = [], $new_excluded_tags = [] ) {

		// products with sync enabled
		$sync_enabled_meta_query = [
			'relation' => 'OR',
			[
				'key'   => Products::SYNC_ENABLED_META_KEY,
				'value' => 'yes',
			],
			[
				'key'     => Products::SYNC_ENABLED_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];

		$products_query_vars = [
			'post_type'  => 'product',
			'fields'     => 'ids',
			'meta_query' => $sync_enabled_meta_query,
		];

		if ( ! empty( $new_excluded_categories ) ) {

			// products that belong to the new excluded categories
			$categories_tax_query = [
				'taxonomy' => 'product_cat',
				'terms'    => $new_excluded_categories,
			];

			if ( $integration = facebook_for_woocommerce()->get_integration() ) {

				// products that do not belong to the saved excluded categories
				$saved_excluded_categories = $integration->get_excluded_product_category_ids();

				if ( ! empty( $saved_excluded_categories ) ) {

					$categories_tax_query = [
						'relation' => 'AND',
						$categories_tax_query,
						[
							'taxonomy' => 'product_cat',
							'terms'    => $saved_excluded_categories,
							'operator' => 'NOT IN',
						],
					];
				}
			}

			$products_query_vars['tax_query'] = $categories_tax_query;
		}

		if ( ! empty( $new_excluded_tags ) ) {

			// products that belong to the new excluded tags
			$tags_tax_query = [
				'taxonomy' => 'product_tag',
				'terms'    => $new_excluded_tags,
			];

			if ( $integration = facebook_for_woocommerce()->get_integration() ) {

				$save_excluded_tags = $integration->get_excluded_product_tag_ids();

				if ( ! empty( $save_excluded_tags ) ) {

					// products that do not belong to the saved excluded tags
					$tags_tax_query = [
						'relation' => 'AND',
						$tags_tax_query,
						[
							'taxonomy' => 'product_tag',
							'terms'    => $save_excluded_tags,
							'operator' => 'NOT IN',
						],
					];
				}
			}

			if ( empty( $products_query_vars['tax_query'] ) ) {

				$products_query_vars['tax_query'] = $tags_tax_query;

			} else {

				$products_query_vars['tax_query'] = [
					'relation' => 'OR',
					$products_query_vars,
					$tags_tax_query,
				];
			}
		}

		$products_query = new \WP_Query( $products_query_vars );

		return $products_query->posts;
	}


	/**
	 * Gets an array of product IDs data for handling visibility (helper method).
	 *
	 * @see \SkyVerge\WooCommerce\Facebook\AJAX::set_products_visibility()
	 *
	 * @since 1.10.2
	 *
	 * @param array $terms_data term data with product IDs and visibility
	 * @return array product IDs and visibility data
	 */
	private function get_product_ids_for_visibility_from_terms( $terms_data ) {

		$products  = [];

		foreach ( $terms_data as $term_data ) {

			if ( ! isset( $term_data['term_id'], $term_data['taxonomy'], $term_data['visibility'] ) ) {
				continue;
			}

			if ( 'product_cat' === $term_data['taxonomy'] ) {
				$tax_query_arg = 'category';
			} elseif( 'product_tag' === $term_data['taxonomy'] ) {
				$tax_query_arg = 'tag';
			} else {
				continue;
			}

			$term = get_term_by( 'id', $term_data['term_id'], $term_data['taxonomy'] );

			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$found_products = wc_get_products( [
				'limit'        => -1,
				'return'       => 'ids',
				$tax_query_arg => [ $term->slug ],
			] );

			foreach ( $found_products as $product_id ) {

				$products[] = [
					'product_id' => $product_id,
					'visibility' => $term_data['visibility']
				];
			}
		}

		return $products;
	}


	/**
	 * Sets products visibility in Facebook.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function set_products_visibility() {

		check_ajax_referer( 'set-products-visibility', 'security' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing

		$integration = facebook_for_woocommerce()->get_integration();
		$products    = isset( $_POST['products'] ) ? (array) $_POST['products'] : [];

		if ( ! empty( $_POST['product_categories'] ) && is_array( $_POST['product_categories'] ) ) {
			$products = array_merge( $products, $this->get_product_ids_for_visibility_from_terms( $_POST['product_categories'] ) );
		}

		if ( ! empty( $_POST['product_tags'] ) && is_array( $_POST['product_tags'] ) ) {
			$products = array_merge( $products, $this->get_product_ids_for_visibility_from_terms( $_POST['product_tags'] ) );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$error = 'No products found to set visibility for.'; // console error message

		if ( $integration && ! empty( $products ) ) {

			$processed_products = [];

			foreach ( $products as $product_data ) {

				$product_id = isset( $product_data['product_id'] ) ? absint( $product_data['product_id'] ) : 0;

				// bail if already processed
				if ( in_array( $product_id, $processed_products, true ) ) {
					continue;
				}

				$visibility_meta_value = isset( $product_data['visibility'] ) ? wc_string_to_bool( $product_data['visibility'] ) : null;

				// bail if visibility value is not valid
				if ( ! is_bool( $visibility_meta_value ) ) {
					continue;
				}

				$visibility_api_value = $visibility_meta_value ? $integration::FB_SHOP_PRODUCT_VISIBLE : $integration::FB_SHOP_PRODUCT_HIDDEN;

				$product = $product_id > 0 ? wc_get_product( $product_id ) : null;

				if ( $product instanceof \WC_Product ) {

					// also extend toggle to child variations
					if ( $product->is_type( 'variable' ) ) {

						foreach ( $product->get_children() as $variation_id ) {

							// bail if already processed
							if ( in_array( $variation_id, $processed_products, true ) ) {
								continue;
							}

							if ( $variation_product = wc_get_product( $variation_id ) ) {

								$fb_item_id = $integration->get_product_fbid( \WC_Facebookcommerce_Integration::FB_PRODUCT_ITEM_ID, $variation_product->get_id() );
								$fb_request = $integration->fbgraph->update_product_item( $fb_item_id, [
									'visibility' => $visibility_api_value,
								] );

								if ( $integration->check_api_result( $fb_request ) ) {
									Products::set_product_visibility( $variation_product, $visibility_meta_value );
								}

								$processed_products[] = $variation_id;
							}
						}

						Products::set_product_visibility( $product, $visibility_meta_value );

					} else {

						$fb_item_id = $integration->get_product_fbid( \WC_Facebookcommerce_Integration::FB_PRODUCT_ITEM_ID, $product->get_id() );
						$fb_request = $integration->fbgraph->update_product_item( $fb_item_id, [
							'visibility' => $visibility_api_value,
						] );

						if ( $integration->check_api_result( $fb_request ) ) {
							Products::set_product_visibility( $product, $visibility_meta_value );
						}
					}

					$processed_products[] = $product_id;
				}
			}

			wp_send_json_success( $processed_products );
		}

		wp_send_json_error( $error );
	}


}
