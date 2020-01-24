<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

if ( ! class_exists( 'WC_Facebookcommerce_MessengerChat' ) ) :

	if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) {
		include_once 'includes/fbutils.php';
	}

	class WC_Facebookcommerce_MessengerChat {

		public function __construct( $settings ) {
			$this->enabled = isset( $settings['is_messenger_chat_plugin_enabled'] )
			? $settings['is_messenger_chat_plugin_enabled']
			: 'no';

			$this->page_id = isset( $settings['fb_page_id'] )
			? $settings['fb_page_id']
			: '';

			$this->jssdk_version = isset( $settings['facebook_jssdk_version'] )
			? $settings['facebook_jssdk_version']
			: '';

			$this->greeting_text_code = isset( $settings['msger_chat_customization_greeting_text_code'] )
			? $settings['msger_chat_customization_greeting_text_code']
			: null;

			$this->locale = isset( $settings['msger_chat_customization_locale'] )
			? $settings['msger_chat_customization_locale']
			: null;

			$this->theme_color_code = isset( $settings['msger_chat_customization_theme_color_code'] )
			? $settings['msger_chat_customization_theme_color_code']
			: null;

			add_action( 'wp_footer', array( $this, 'inject_messenger_chat_plugin' ) );
		}


		/**
		 * Outputs the Facebook Messenger chat script.
		 *
		 * @internal
		 */
		public function inject_messenger_chat_plugin() {

			if ( $this->enabled === 'yes' ) :

				printf( "
					<div
						attribution=\"fbe_woocommerce\"
						class=\"fb-customerchat\"
						page_id=\"%s\"
						%s
						%s
						%s
					></div>
					<!-- Facebook JSSDK -->
					<script>
					  window.fbAsyncInit = function() {
					    FB.init({
					      appId            : '',
					      autoLogAppEvents : true,
					      xfbml            : true,
					      version          : '%s'
					    });
					  };

					  (function(d, s, id){
					      var js, fjs = d.getElementsByTagName(s)[0];
					      if (d.getElementById(id)) {return;}
					      js = d.createElement(s); js.id = id;
					      js.src = 'https://connect.facebook.net/%s/sdk/xfbml.customerchat.js';
					      fjs.parentNode.insertBefore(js, fjs);
					    }(document, 'script', 'facebook-jssdk'));
					</script>
					<div></div>
					",
					esc_attr( $this->page_id ),
					esc_html( $this->theme_color_code   ? sprintf( 'theme_color="%s"', esc_attr( $this->theme_color_code ) ) : '' ),
					esc_html( $this->greeting_text_code ? sprintf( 'logged_in_greeting="%s"', esc_attr( $this->greeting_text_code ) ) : '' ),
					esc_html( $this->greeting_text_code ? sprintf( 'logged_out_greeting="%s"', esc_attr( $this->greeting_text_code ) ) : '' ),
					esc_js( $this->jssdk_version ),
					esc_js( $this->locale ?: 'en_US' )
				);

			endif;
		}


		/**
		 * Gets the locales supported by Facebook Messenger.
		 *
		 * Returns the format $locale => $name for display in options.
		 *
		 * @link https://developers.facebook.com/docs/messenger-platform/messenger-profile/supported-locales/
		 *
		 * TODO: fill in each locale with the appropriate language + country name {CW 2020-01-23}
		 *
		 * @since x.y.z
		 *
		 * @return array
		 */
		public static function get_supported_locales() {

			$locales = [
				'en_US' => _x( 'English', 'language', 'facebook-for-woocommerce' ),
				'ca_ES' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'cs_CZ' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'cx_PH' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'cy_GB' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'da_DK' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'de_DE' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'eu_ES' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'es_LA' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'es_ES' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'gn_PY' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'fi_FI' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'fr_FR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'gl_ES' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'hu_HU' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'it_IT' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ja_JP' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ko_KR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'nb_NO' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'nn_NO' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'nl_NL' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'fy_NL' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'pl_PL' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'pt_BR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'pt_PT' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ro_RO' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ru_RU' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sk_SK' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sl_SI' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sv_SE' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'th_TH' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'tr_TR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ku_TR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'zh_CN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'zh_HK' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'zh_TW' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'af_ZA' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sq_AL' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'hy_AM' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'az_AZ' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'be_BY' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'bn_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'bs_BA' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'bg_BG' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'hr_HR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'nl_BE' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'en_GB' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'et_EE' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'fo_FO' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'fr_CA' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ka_GE' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'el_GR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'gu_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'hi_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'is_IS' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'id_ID' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ga_IE' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'jv_ID' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'kn_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'kk_KZ' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'lv_LV' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'lt_LT' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'mk_MK' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'mg_MG' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ms_MY' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'mt_MT' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'mr_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'mn_MN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ne_NP' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'pa_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sr_RS' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'so_SO' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sw_KE' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'tl_PH' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ta_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'te_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ml_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'uk_UA' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'uz_UZ' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'vi_VN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'km_KH' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'tg_TJ' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ar_AR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'he_IL' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ur_PK' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'fa_IR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ps_AF' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'my_MM' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'qz_MM' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'or_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'si_LK' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'rw_RW' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'cb_IQ' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ha_NG' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ja_KS' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'br_FR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'tz_MA' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'co_FR' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'as_IN' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'ff_NG' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sc_IT' => _x( '', 'language', 'facebook-for-woocommerce' ),
				'sz_PL' => _x( '', 'language', 'facebook-for-woocommerce' ),
			];

			// fill any empty locale names
			foreach ( $locales as $locale => $name ) {

				if ( empty( $name ) ) {
					$locales[ $locale ] = $locale;
				}
			}

			/**
			 * Filters the locales supported by Facebook Messenger.
			 *
			 * @since x.y.z
			 *
			 * @param array $locales locales supported by Facebook Messenger, in $locale => $name format
			 */
			return apply_filters( 'wc_facebook_messenger_supported_locales', $locales );
		}


	}

endif;
