<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Facebook_WPML_Injector' ) ) :

	class FB_WPML_Language_Status {
		const VISIBLE    = 1;
		const HIDDEN     = 2;
		const NOT_SYNCED = 0;
	}

	class WC_Facebook_WPML_Injector {
		public static $settings     = null;
		public static $default_lang = null;
		const OPTION                = 'fb_wmpl_language_visibility';

		public function __construct() {
			add_action( 'icl_menu_footer', array( $this, 'wpml_support' ) );
			add_action( 'icl_ajx_custom_call', array( $this, 'wpml_ajax_support' ), 10, 2 );
			self::$settings     = get_option( self::OPTION );
			self::$default_lang = apply_filters( 'wpml_default_language', null );
		}

		public static function should_hide( $wp_id ) {
			$product_lang = apply_filters( 'wpml_post_language_details', null, $wp_id );
			$settings     = self::$settings;
			if ( $product_lang && isset( $product_lang['language_code'] ) ) {
				$product_lang = $product_lang['language_code'];
			}

			// Option doesn't exist : Backwards Compatibility
			if ( ! $settings ) {
				return ( $product_lang && self::$default_lang !== $product_lang );
			}
			// Hide products from non-active languages.
			if ( ! isset( $settings[ $product_lang ] ) ) {
				return true;
			}
			return $settings[ $product_lang ] !== FB_WPML_Language_Status::VISIBLE;
		}

		public function wpml_ajax_support( $call, $REQUEST ) {
			global $sitepress;
			if ( isset( $REQUEST['icl_ajx_action'] ) ) {
				$call = $REQUEST['icl_ajx_action'];
			}
			if ( $call === 'icl_fb_woo' ) {
				$active_languages = array_keys( $sitepress->get_active_languages() );
				$settings         = array();
				foreach ( $active_languages as $lang ) {
					$settings[ $lang ] = $REQUEST[ $lang ] === 'on' ?
					FB_WPML_Language_Status::VISIBLE : FB_WPML_Language_Status::HIDDEN;
				}

				update_option( 'fb_wmpl_language_visibility', $settings, false );
				self::$settings = $settings;
			}
		}

		public function wpml_support() {
			global $sitepress;

			// there is no nonce to check here and the value of $_GET['page] is being compared against a known and safe string
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['page'] ) && false !== strpos( esc_url_raw( wp_unslash( $_GET['page'] ) ), 'languages.php' ) ) {

				$active_languages = $sitepress->get_active_languages();
				$settings         = get_option( self::OPTION );

				// Default setting is only show default lang.
				if ( ! $settings ) {
					$settings                        = array_fill_keys(
						array_keys( $active_languages ),
						FB_WPML_Language_Status::HIDDEN
					);
					$settings[ self::$default_lang ] = FB_WPML_Language_Status::VISIBLE;
				}
				$ajax_response = sprintf(
					'Saved. You should now ' .
					' <a href="%s&fb_force_resync=true">Re-Sync</a>' .
					' your products with Facebook. ',
					WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL
				);

				?>
				<div id="lang-sec-fb" class="wpml-section wpml-section-languages">
					<div class="wpml-section-header">
						<h3><?php esc_html_e( 'Facebook Visibility', 'facebook-for-woocommerce' ); ?></h3>
					</div>
					<div class="wpml-section-content">
						WooCommerce Products with languages that are selected
						here will be visible to customers who see your Facebook Shop.

						<div class="wpml-section-content-inner">
							<form id="icl_fb_woo" name="icl_fb_woo" action="">
								<?php
								foreach ( $settings as $language => $set ) {
									$is_checked = $set === FB_WPML_Language_Status::VISIBLE ?
									'checked' : '';
									$str        = '
									<p><label>
										<input type="checkbox" id="icl_fb_woo_chk" name="' . $language . '" ' . $is_checked . '>
										' . $active_languages[ $language ]['native_name'] . '
									</label></p>
									';
									echo $str;
								}
								?>
								<p class="buttons-wrap">
									<span class="icl_ajx_response_fb" id="icl_ajx_response_fb" hidden="true">
									<?php echo $ajax_response; ?>
									</span>
									<input class="button button-primary"
										name="save"
										value="<?php _e( 'Save', 'sitepress' ); ?>"
										type="submit" />
								</p>
							</form>
							<script type="text/javascript">
								addLoadEvent(function(){
								jQuery('#icl_fb_woo').submit(iclSaveForm);
								jQuery('#icl_fb_woo').submit(function(){
									jQuery('#icl_ajx_response_fb').show();
								});
								});
							</script>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}

endif;
