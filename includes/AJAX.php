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
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * AJAX handler.
 *
 * @since 1.10.0
 */
class AJAX {


	/** @var string the product attribute search AJAX action */
	const ACTION_SEARCH_PRODUCT_ATTRIBUTES = 'wc_facebook_search_product_attributes';

	/** @var string facebook order cancel AJAX action */
	const ACTION_CANCEL_ORDER = 'wc_facebook_cancel_order';

	/** @var string the complete order AJAX action */
	const ACTION_COMPLETE_ORDER = 'wc_facebook_complete_order';


	/**
	 * AJAX handler constructor.
	 *
	 * @since 1.10.0
	 */
	public function __construct() {

		// maybe output a modal prompt when toggling product sync in bulk or individual product actions
		add_action( 'wp_ajax_facebook_for_woocommerce_set_product_sync_prompt', array( $this, 'handle_set_product_sync_prompt' ) );
		add_action( 'wp_ajax_facebook_for_woocommerce_set_product_sync_bulk_action_prompt', array( $this, 'handle_set_product_sync_bulk_action_prompt' ) );

		// maybe output a modal prompt when setting excluded terms
		add_action( 'wp_ajax_facebook_for_woocommerce_set_excluded_terms_prompt', array( $this, 'handle_set_excluded_terms_prompt' ) );

		// sync all products via AJAX
		add_action( 'wp_ajax_wc_facebook_sync_products', array( $this, 'sync_products' ) );

		// get the current sync status
		add_action( 'wp_ajax_wc_facebook_get_sync_status', array( $this, 'get_sync_status' ) );

		// search a product's attributes for the given term
		add_action( 'wp_ajax_' . self::ACTION_SEARCH_PRODUCT_ATTRIBUTES, array( $this, 'admin_search_product_attributes' ) );

		// complete a Facebook order for the given order ID
		add_action( 'wp_ajax_' . self::ACTION_COMPLETE_ORDER, array( $this, 'admin_complete_order' ) );

		// cancel facebook order by the given order ID
		add_action( 'wp_ajax_' . self::ACTION_CANCEL_ORDER, array( $this, 'admin_cancel_order' ) );
	}


	/**
	 * Cancels a Facebook order by the given order ID.
	 *
	 * @internal
	 *
	 * @since 2.1.0
	 */
	public function admin_cancel_order() {

		$order = null;

		try {

			if ( ! wp_verify_nonce( Framework\SV_WC_Helper::get_posted_value( 'security' ), self::ACTION_CANCEL_ORDER ) ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid nonce.', 'facebook-for-woocommerce' ) );
			}

			$order_id    = Framework\SV_WC_Helper::get_posted_value( 'order_id' );
			$reason_code = Framework\SV_WC_Helper::get_posted_value( 'reason_code' );

			if ( empty( $order_id ) ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Order ID is required.', 'facebook-for-woocommerce' ) );
			}

			if ( empty( $reason_code ) ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Cancel reason is required.', 'facebook-for-woocommerce' ) );
			}

			$order = wc_get_order( absint( $order_id ) );

			if ( false === $order ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'A valid Order ID is required.', 'facebook-for-woocommerce' ) );
			}

			facebook_for_woocommerce()->get_commerce_handler()->get_orders_handler()->cancel_order( $order, $reason_code );

			wp_send_json_success();

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			wp_send_json_error( $exception->getMessage() );
		}
	}


	/**
	 * Searches a product's attributes for the given term.
	 *
	 * @internal
	 *
	 * @since 2.1.0
	 */
	public function admin_search_product_attributes() {

		try {

			if ( ! wp_verify_nonce( Framework\SV_WC_Helper::get_requested_value( 'security' ), self::ACTION_SEARCH_PRODUCT_ATTRIBUTES ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Invalid nonce' );
			}

			$term = Framework\SV_WC_Helper::get_requested_value( 'term' );

			if ( ! $term ) {
				throw new Framework\SV_WC_Plugin_Exception( 'A search term is required' );
			}

			$product = wc_get_product( (int) Framework\SV_WC_Helper::get_requested_value( 'request_data' ) );

			if ( ! $product instanceof \WC_Product ) {
				throw new Framework\SV_WC_Plugin_Exception( 'A valid product ID is required' );
			}

			$attributes = Admin\Products::get_available_product_attribute_names( $product );

			// filter out any attributes whose slug or proper name don't at least partially match the search term
			$results = array_filter(
				$attributes,
				function( $name, $slug ) use ( $term ) {

					return false !== stripos( $name, $term ) || false !== stripos( $slug, $term );

				},
				ARRAY_FILTER_USE_BOTH
			);

			wp_send_json( $results );

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			die();
		}
	}


	/**
	 * Completes a Facebook order for the given order ID.
	 *
	 * @internal
	 *
	 * @since 2.1.0
	 */
	public function admin_complete_order() {

		try {

			if ( ! wp_verify_nonce( Framework\SV_WC_Helper::get_posted_value( 'nonce' ), self::ACTION_COMPLETE_ORDER ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Invalid nonce', 403 );
			}

			$order_id        = (int) Framework\SV_WC_Helper::get_posted_value( 'order_id' );
			$tracking_number = wc_clean( Framework\SV_WC_Helper::get_posted_value( 'tracking_number' ) );
			$carrier_code    = wc_clean( Framework\SV_WC_Helper::get_posted_value( 'carrier_code' ) );

			if ( empty( $order_id ) ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Order ID is required', 'facebook-for-woocommerce' ) );
			}

			if ( empty( $tracking_number ) ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Tracking number is required', 'facebook-for-woocommerce' ) );
			}

			if ( empty( $carrier_code ) ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Carrier code is required', 'facebook-for-woocommerce' ) );
			}

			$order = wc_get_order( $order_id );

			if ( ! $order instanceof \WC_Order ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Order not found', 'facebook-for-woocommerce' ) );
			}

			facebook_for_woocommerce()->get_commerce_handler()->get_orders_handler()->fulfill_order( $order, $tracking_number, $carrier_code );

			wp_send_json_success();

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			wp_send_json_error( $exception->getMessage(), $exception->getCode() );
		}
	}


	/**
	 * Syncs all products via AJAX.
	 *
	 * @internal
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	public function get_sync_status() {

		check_admin_referer( Product_Sync::ACTION_GET_SYNC_STATUS, 'nonce' );

		$remaining_products = 0;

		$jobs = facebook_for_woocommerce()->get_products_sync_background_handler()->get_jobs(
			array(
				'status' => 'processing',
			)
		);

		if ( ! empty( $jobs ) ) {

			// there should only be one processing job at a time, pluck the latest to convey status
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
		$product_id = isset( $_POST['product'] ) ? (int) $_POST['product'] : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sync_enabled = isset( $_POST['sync_enabled'] ) ? (string) $_POST['sync_enabled'] : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$var_sync_enabled = isset( $_POST['var_sync_enabled'] ) ? (string) $_POST['var_sync_enabled'] : '';
	    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_cats = isset( $_POST['categories'] ) ? (array) $_POST['categories'] : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_tags = isset( $_POST['tags'] ) ? (array) $_POST['tags'] : array();

		if ( $product_id > 0 && in_array( $var_sync_enabled, array( 'enabled', 'disabled' ), true ) && in_array( $sync_enabled, array( 'enabled', 'disabled' ), true ) ) {

			$product = wc_get_product( $product_id );

			if ( $product instanceof \WC_Product ) {

				if ( ( 'enabled' === $sync_enabled && ! $product->is_type( 'variable' ) ) || ( 'enabled' === $var_sync_enabled && $product->is_type( 'variable' ) ) ) {

					$has_excluded_terms = false;

					if ( $integration = facebook_for_woocommerce()->get_integration() ) {

						// try with categories first, since we have already IDs
						$has_excluded_terms = ! empty( $product_cats ) && array_intersect( $product_cats, $integration->get_excluded_product_category_ids() );

						// the form post can send an array with empty items, so filter them out
						$product_tags = array_filter( $product_tags );

						// try next with tags, but WordPress only gives us tag names
						if ( ! $has_excluded_terms && ! empty( $product_tags ) ) {

							$product_tag_ids = array();

							foreach ( $product_tags as $product_tag_name_or_id ) {

								$term = get_term_by( 'name', $product_tag_name_or_id, 'product_tag' );

								if ( $term instanceof \WP_Term ) {

									$product_tag_ids[] = $term->term_id;

								} else {

									$term = get_term( (int) $product_tag_name_or_id, 'product_tag' );

									if ( $term instanceof \WP_Term ) {
										$product_tag_ids[] = $term->term_id;
									}
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
							href="<?php echo esc_url( add_query_arg( 'tab', Product_Sync::ID, facebook_for_woocommerce()->get_settings_url() ) ); ?>"
						><?php esc_html_e( 'Go to Settings', 'facebook-for-woocommerce' ); ?></a>
						<button
							id="facebook-for-woocommerce-cancel-sync"
							class="button button-large button-primary"
							onclick="jQuery( '.modal-close' ).trigger( 'click' )"
						><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
						<?php

						$buttons = ob_get_clean();

						wp_send_json_error(
							array(
								'message' => sprintf(
								 /* translators: Placeholder %s - <br/> tag */
									__( 'This product belongs to a category or tag that is excluded from the Facebook catalog sync. It will not sync to Facebook. %sTo sync this product to Facebook, click Go to Settings and remove the category or tag exclusion or click Cancel and update the product\'s category / tag assignments.', 'facebook-for-woocommerce' ),
									'<br/><br/>'
								),
								'buttons' => $buttons,
							)
						);
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
		$product_ids = isset( $_POST['products'] ) ? (array) $_POST['products'] : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$toggle = isset( $_POST['toggle'] ) ? (string) $_POST['toggle'] : '';

		if ( ! empty( $product_ids ) && ! empty( $toggle ) && 'facebook_include' === $toggle ) {

			$has_excluded_term = false;

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );

				if ( $product instanceof \WC_Product && ! facebook_for_woocommerce()->get_product_sync_validator( $product )->passes_product_terms_check() ) {
					$has_excluded_term = true;
					break;
				}
			}

			// show modal if there's at least one product that belongs to an excluded term
			if ( $has_excluded_term ) {

				ob_start();

				?>
				<a
					id="facebook-for-woocommerce-go-to-settings"
					class="button button-large"
					href="<?php echo esc_url( add_query_arg( 'tab', Product_Sync::ID, facebook_for_woocommerce()->get_settings_url() ) ); ?>"
				><?php esc_html_e( 'Go to Settings', 'facebook-for-woocommerce' ); ?></a>
				<button
					id="facebook-for-woocommerce-cancel-sync"
					class="button button-large button-primary"
					onclick="jQuery( '.modal-close' ).trigger( 'click' )"
				><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
				<?php

				$buttons = ob_get_clean();

				wp_send_json_error(
					array(
						'message' => __( 'One or more of the selected products belongs to a category or tag that is excluded from the Facebook catalog sync. To sync these products to Facebook, please remove the category or tag exclusion from the plugin settings.', 'facebook-for-woocommerce' ),
						'buttons' => $buttons,
					)
				);
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
		$posted_categories = isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$posted_tags = isset( $_POST['tags'] ) ? wp_unslash( $_POST['tags'] ) : array();

		$new_category_ids = array();
		$new_tag_ids      = array();

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

			wp_send_json_error(
				array(
					'message' => sprintf(
					 /* translators: Placeholder %s - <br/> tags */
						__( 'The categories and/or tags that you have selected to exclude from sync contain products that are currently synced to Facebook.%sTo exclude these products from the Facebook sync, click Exclude Products. To review the category / tag exclusion settings, click Cancel.', 'facebook-for-woocommerce' ),
						'<br/><br/>'
					),
					'buttons' => $buttons,
				)
			);

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
	private function get_products_to_be_excluded( $new_excluded_categories = array(), $new_excluded_tags = array() ) {

		// products with sync enabled
		$sync_enabled_meta_query = array(
			'relation' => 'OR',
			array(
				'key'   => Products::SYNC_ENABLED_META_KEY,
				'value' => 'yes',
			),
			array(
				'key'     => Products::SYNC_ENABLED_META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);

		$products_query_vars = array(
			'post_type'  => 'product',
			'fields'     => 'ids',
			'meta_query' => $sync_enabled_meta_query,
		);

		if ( ! empty( $new_excluded_categories ) ) {

			// products that belong to the new excluded categories
			$categories_tax_query = array(
				'taxonomy' => 'product_cat',
				'terms'    => $new_excluded_categories,
			);

			if ( $integration = facebook_for_woocommerce()->get_integration() ) {

				// products that do not belong to the saved excluded categories
				$saved_excluded_categories = $integration->get_excluded_product_category_ids();

				if ( ! empty( $saved_excluded_categories ) ) {

					$categories_tax_query = array(
						'relation' => 'AND',
						$categories_tax_query,
						array(
							'taxonomy' => 'product_cat',
							'terms'    => $saved_excluded_categories,
							'operator' => 'NOT IN',
						),
					);
				}
			}

			$products_query_vars['tax_query'] = $categories_tax_query;
		}

		if ( ! empty( $new_excluded_tags ) ) {

			// products that belong to the new excluded tags
			$tags_tax_query = array(
				'taxonomy' => 'product_tag',
				'terms'    => $new_excluded_tags,
			);

			if ( $integration = facebook_for_woocommerce()->get_integration() ) {

				$save_excluded_tags = $integration->get_excluded_product_tag_ids();

				if ( ! empty( $save_excluded_tags ) ) {

					// products that do not belong to the saved excluded tags
					$tags_tax_query = array(
						'relation' => 'AND',
						$tags_tax_query,
						array(
							'taxonomy' => 'product_tag',
							'terms'    => $save_excluded_tags,
							'operator' => 'NOT IN',
						),
					);
				}
			}

			if ( empty( $products_query_vars['tax_query'] ) ) {

				$products_query_vars['tax_query'] = $tags_tax_query;

			} else {

				$products_query_vars['tax_query'] = array(
					'relation' => 'OR',
					$products_query_vars,
					$tags_tax_query,
				);
			}
		}

		$products_query = new \WP_Query( $products_query_vars );

		return $products_query->posts;
	}


}
