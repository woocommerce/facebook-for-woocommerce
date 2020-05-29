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

use SkyVerge\WooCommerce\Facebook\Admin\Settings_Screens\Product_Sync;

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

		// sync all products via AJAX
		add_action( 'wp_ajax_wc_facebook_sync_products', [ $this, 'sync_products' ] );

		// get the current sync status
		add_action( 'wp_ajax_wc_facebook_get_sync_status', [ $this, 'get_sync_status' ] );
	}


	/**
	 * Syncs all products via AJAX.
	 *
	 * @internal
	 *
	 * @since 2.0.0-dev.1
	 */
	public function sync_products() {

		check_admin_referer( Product_Sync::ACTION_SYNC_PRODUCTS, 'nonce' );

		facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_all_products();

		wp_send_json_success();
	}


	/**
	 * Gets the current sync status.
	 *
	 * @internal
	 *
	 * @since 2.0.0-dev.1
	 */
	public function get_sync_status() {

		check_admin_referer( Product_Sync::ACTION_GET_SYNC_STATUS, 'nonce' );

		$remaining_products = 0;
		
		$jobs = facebook_for_woocommerce()->get_products_sync_background_handler()->get_jobs( [
			'status' => 'processing',
		] );

		if ( ! empty( $jobs ) ) {

			$job = $jobs[0];

			$remaining_products = ! empty( $job->total ) ? $job->total : count( $job->requests );

			if ( ! empty( $job->progress ) ) {
				$remaining_products -= $job->progress;
			}

		}

		wp_send_json_success( $remaining_products );
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

				if ( ( 'enabled' === $sync_enabled && ! $product->is_type( 'variable' ) ) || ( 'enabled' === $var_sync_enabled && $product->is_type( 'variable' ) ) ) {

					$has_excluded_terms = false;

					if ( $integration = facebook_for_woocommerce()->get_integration() ) {

						// try with categories first, since we have already IDs
						$has_excluded_terms = ! empty( $product_cats ) && array_intersect( $product_cats, $integration->get_excluded_product_category_ids() );

						// try next with tags, but WordPress only gives us tag names
						if ( ! $has_excluded_terms && ! empty( $product_tags ) ) {

							$product_tag_ids = [];

							foreach ( $product_tags as $product_tag_name_or_id ) {

								if ( $term = get_term_by( 'name', $product_tag_name_or_id, 'product_tag' ) ) {

									$product_tag_ids[] = $term->term_id;

								} elseif ( $term = get_term( (int) $product_tag_name_or_id, 'product_tag' ) ) {

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

		if ( ! empty( $product_ids ) && ! empty( $toggle ) && 'facebook_include' === $toggle ) {

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


}
