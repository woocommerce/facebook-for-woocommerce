<?php

namespace SkyVerge\WooCommerce\Facebook\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for display and operations of product sync status metabox.
 *
 * @since 2.6.6
 */
class Product_Sync_Meta_Box {

	/**
	 * Register metabox assets and add the metabox.
	 */
	public static function register() {
		$ajax_data = array(
			'nonce' => wp_create_nonce( 'wc_facebook_metabox_jsx' ),
		);

		wp_enqueue_script(
			'wc_facebook_metabox_jsx',
			facebook_for_woocommerce()->get_asset_build_dir_url() . '/admin/metabox.js',
			array(),
			\WC_Facebookcommerce::PLUGIN_VERSION
		);

		wp_localize_script(
			'wc_facebook_metabox_jsx',
			'wc_facebook_metabox_jsx',
			$ajax_data
		);

		add_meta_box(
			'facebook_metabox',
			__( 'Facebook Product Sync', 'facebook-for-woocommerce' ),
			__CLASS__ . '::output',
			'product',
			'side'
		);
	}

	/**
	 * Renders the content of the product meta box.
	 *
	 * @since 2.6.6
	 */
	public static function output() {
		global $post;

		$fb_integration      = facebook_for_woocommerce()->get_integration();
		$fb_product          = new \WC_Facebook_Product( $post->ID );
		$fb_product_group_id = null;
		$should_sync         = true;
		$no_sync_reason      = '';

		if ( $fb_product->woo_product instanceof \WC_Product ) {
			try {
				facebook_for_woocommerce()->get_product_sync_validator( $fb_product->woo_product )->validate();
			} catch ( \Exception $e ) {
				$should_sync    = false;
				$no_sync_reason = $e->getMessage();
			}
		}

		if ( $should_sync || $fb_product->woo_product->is_type( 'variable' ) ) {
			$fb_product_group_id = $fb_integration->get_product_fbid( $fb_integration::FB_PRODUCT_GROUP_ID, $post->ID, $fb_product->woo_product );
		}
		?>
			<span id="fb_metadata">
		<?php

		if ( $fb_product_group_id ) {

			?>

			<?php echo esc_html__( 'Facebook ID:', 'facebook-for-woocommerce' ); ?>
			<a href="https://facebook.com/<?php echo esc_attr( $fb_product_group_id ); ?>" target="_blank"><?php echo esc_html( $fb_product_group_id ); ?></a>

			<?php if ( \WC_Facebookcommerce_Utils::is_variable_type( $fb_product->get_type() ) ) : ?>

				<?php
				$product_item_ids_by_variation_id = $fb_integration->get_variation_product_item_ids( $fb_product, $fb_product_group_id );
				if ( $product_item_ids_by_variation_id ) :
					?>

					<p>
						<?php echo esc_html__( 'Variant IDs:', 'facebook-for-woocommerce' ); ?><br/>

						<?php
						foreach ( $product_item_ids_by_variation_id as $variation_id => $product_item_id ) :
							$variation = wc_get_product( $variation_id );
							$show_link = true;

							try {
								facebook_for_woocommerce()->get_product_sync_validator( $variation )->validate();
							} catch ( \Exception $e ) {
								$info      = $e->getMessage();
								$show_link = false;
							}
							?>
							<?php echo esc_html( $variation_id ); ?>:
							<?php if ( $show_link ) : ?>
								<a href="https://facebook.com/<?php echo esc_attr( $product_item_id ); ?>" target="_blank"><?php echo esc_html( $product_item_id ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $info ); ?>
							<?php endif; ?>
							<br/>
						<?php endforeach; ?>
					</p>

				<?php endif; ?>

			<?php endif; ?>

				<input name="is_product_page" type="hidden" value="1"/>

				<p/>
				<a href="#" onclick="fb_reset_product( <?php echo esc_js( $post->ID ); ?> )">
					<?php echo esc_html__( 'Reset Facebook metadata', 'facebook-for-woocommerce' ); ?>
				</a>

				<p/>
				<a href="#" onclick="fb_delete_product( <?php echo esc_js( $post->ID ); ?> )">
					<?php echo esc_html__( 'Delete product(s) on Facebook', 'facebook-for-woocommerce' ); ?>
				</a>

			<?php

		} elseif ( ! $should_sync ) {
			?>
				<b><?php echo esc_html( $no_sync_reason ); ?></b>
			<?php
		} else {

			?>
				<b><?php echo esc_html__( 'This product is not yet synced to Facebook.', 'facebook-for-woocommerce' ); ?></b>
			<?php
		}

		?>
			</span>
		<?php
	}
}
