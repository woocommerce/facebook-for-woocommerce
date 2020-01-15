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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product_ids = isset( $_POST['products'] ) ? $_POST['products'] : [];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$toggle      = isset( $_POST['toggle'] )   ? $_POST['toggle']   : '';

		if ( ! empty( $product_ids ) && ! empty( $toggle ) ) {

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
			}
		}

		wp_send_json_success();
	}


}
