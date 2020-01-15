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
 * @since x.y.z
 */
class AJAX {


	/**
	 * AJAX handler constructor.
	 *
	 * @since x.y.z
	 */
	public function __construct() {

		add_action( 'wp_ajax_facebook_for_woocommerce_set_product_sync_bulk_action', [ $this, 'set_product_sync_bulk_action' ] );
	}


	/**
	 * Triggers a modal warning when the merchant toggles sync enabled status in bulk.
	 *
	 * @internal
	 *
	 * @since x.y.z
	 */
	public function set_product_sync_bulk_action() {

		check_ajax_referer( 'set-product-sync-bulk-action', 'security' );

		$product_ids = isset( $_POST['products'] ) ? (array)  $_POST['products'] : [];
		$toggle      = isset( $_POST['toggle'] )   ? (string) $_POST['toggle']   : '';

		if ( ! empty( $product_ids ) && ! empty( $toggle ) ) {

			// merchant wants to exclude products from sync: ask them what they want to do with their visibility status
			if ( 'facebook_exclude' === $toggle ) {

				ob_start();

				?>
				<button
					id="facebook-for-woocommerce-hide-products"
					class="button button-large facebook-for-woocommerce-toggle-product-visibility hide-products"
				><?php esc_html_e( 'Hide products', 'facebook-for-woocommerce' ); ?></button>
				<button
					id="facebook-for-woocommerce-do-not-hide-products"
					class="button button-large facebook-for-woocommerce-toggle-product-visibility show-products"
				><?php esc_html_e( 'Do not hide products', 'facebook-for-woocommerce' ); ?></button>
				<?php

				$buttons = ob_get_clean();

				wp_send_json_error( [
					'message' => __( 'The selected products will no longer be updated in your Facebook catalog. Would you like to hide these products from your Facebook shop?', 'facebook-for-woocommerce' ),
					'buttons' => $buttons,
				] );

			// merchant wants to enable sync in Facebook multiple products: we must check if they belong to excluded categories, and perhaps warn them
			} elseif ( 'facebook_include' === $toggle && ( $integration = facebook_for_woocommerce()->get_integration() ) ) {

				$excluded_categories = $integration->get_excluded_product_category_ids();
				$excluded_tags       = $integration->get_excluded_product_tag_ids();

				// bother to check only if there are excluded terms to begin with, before looping products
				if ( ! empty( $excluded_categories ) && ! empty( $excluded_tags ) ) {

					$has_excluded_term   = false;

					foreach ( $product_ids as $product_id ) {

						if ( $product = wc_get_product( $product_id ) ) {

							// product belongs to at least one excluded term
							if (    ( $excluded_categories && array_intersect( $product->get_category_ids(), $excluded_categories ) )
							     || ( $excluded_tags       && array_intersect( $product->get_tag_ids(),      $excluded_tags       ) ) ) {

								$has_excluded_term = true;
								break;
							}
						}
					}

					// show modal if there's at least one product that belongs to an excluded term
					if ( $has_excluded_term )  {

						ob_start();

						?>
						<button
							id="facebook-for-woocommerce-cancel-sync"
							class="button button-large"
							onclick="jQuery( '.modal-close' ).trigger( 'click' )"
						><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
						<a
							id="facebook-for-woocommerce-go-to-settings"
							class="button button-large"
							href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration' ) ); ?>"
						><?php esc_html_e( 'Go to settings', 'facebook-for-woocommerce' ); ?></a>
						<?php

						$buttons = ob_get_clean();

						wp_send_json_error( [
							'message' => __( 'One or more of the selected products belongs to a category or tag that is excluded from the Facebook catalog sync. To sync these products to Facebook, please remove the category or tag exclusion from the plugin settings.', 'facebook-for-woocommerce' ),
							'buttons' => $buttons,
						] );
					}
				}
			}
		}

		wp_send_json_success();
	}


}
