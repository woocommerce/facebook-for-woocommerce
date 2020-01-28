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


		/** @var string[] list of supported locales */
		private static $supported_locales = [
			'af_ZA',
			'ar_AR',
			'as_IN',
			'az_AZ',
			'be_BY',
			'bg_BG',
			'bn_IN',
			'br_FR',
			'bs_BA',
			'ca_ES',
			'cb_IQ',
			'co_FR',
			'cs_CZ',
			'cx_PH',
			'cy_GB',
			'da_DK',
			'de_DE',
			'el_GR',
			'en_GB',
			'en_US',
			'es_ES',
			'es_LA',
			'et_EE',
			'eu_ES',
			'fa_IR',
			'ff_NG',
			'fi_FI',
			'fo_FO',
			'fr_CA',
			'fr_FR',
			'fy_NL',
			'ga_IE',
			'gl_ES',
			'gn_PY',
			'gu_IN',
			'ha_NG',
			'he_IL',
			'hi_IN',
			'hr_HR',
			'hu_HU',
			'hy_AM',
			'id_ID',
			'is_IS',
			'it_IT',
			'ja_JP',
			'ja_KS',
			'jv_ID',
			'ka_GE',
			'kk_KZ',
			'km_KH',
			'kn_IN',
			'ko_KR',
			'ku_TR',
			'lt_LT',
			'lv_LV',
			'mg_MG',
			'mk_MK',
			'ml_IN',
			'mn_MN',
			'mr_IN',
			'ms_MY',
			'mt_MT',
			'my_MM',
			'nb_NO',
			'ne_NP',
			'nl_BE',
			'nl_NL',
			'nn_NO',
			'or_IN',
			'pa_IN',
			'pl_PL',
			'ps_AF',
			'pt_BR',
			'pt_PT',
			'qz_MM',
			'ro_RO',
			'ru_RU',
			'rw_RW',
			'sc_IT',
			'si_LK',
			'sk_SK',
			'sl_SI',
			'so_SO',
			'sq_AL',
			'sr_RS',
			'sv_SE',
			'sw_KE',
			'sz_PL',
			'ta_IN',
			'te_IN',
			'tg_TJ',
			'th_TH',
			'tl_PH',
			'tr_TR',
			'tz_MA',
			'uk_UA',
			'ur_PK',
			'uz_UZ',
			'vi_VN',
			'zh_CN',
			'zh_HK',
			'zh_TW',
		];


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
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$this->theme_color_code ? 'theme_color="' . esc_attr( $this->theme_color_code ) . '"' : '',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$this->greeting_text_code ? 'logged_in_greeting="' . esc_attr( $this->greeting_text_code ) . '"' : '',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$this->greeting_text_code ? 'logged_out_greeting="' . esc_attr( $this->greeting_text_code ) . '"' : '',
					esc_js( $this->jssdk_version ),
					esc_js( $this->locale ?: 'en_US' )
				);

			endif;
		}


		/**
		 * Gets the locales supported by Facebook Messenger.
		 *
		 * Returns the format $locale => $name for display in options.
		 * @link https://developers.facebook.com/docs/messenger-platform/messenger-profile/supported-locales/
		 * If the Locale extension is not available, will attempt to match locales to WordPress available language names.
		 *
		 * @since x.y.z
		 *
		 * @return array associative array of locale codes and names
		 */
		public static function get_supported_locales() {
			global $wp_version;

			$locales = [];

			if ( class_exists( 'Locale' ) ) {

				foreach ( self::$supported_locales as $locale ) {

					if ( $name = \Locale::getDisplayName( $locale, substr( $locale, 0, 2 ) ) ) {
						$locales[ $locale ] = ucfirst( $name );
					}
				}

			} else {

				/** @see \wp_get_available_translations() which is not always available in all contexts */
				$translations = get_site_transient( 'available_translations' );

				if ( ( ! is_array( $translations ) || empty( $translations ) ) && function_exists( 'translations_api' ) ) {

					$api = translations_api( 'core', [ 'version' => $wp_version ] );

					if ( ! is_wp_error( $api ) && ! empty( $api['translations'] ) ) {

						foreach ( $api['translations'] as $translation ) {
							$translations[ $translation['language'] ] = $translation;
						}
					}
				}

				foreach ( self::$supported_locales as $locale ) {

					if ( isset( $translations[ $locale ]['native_name'] ) ) {

						$locales[ $locale ] = $translations[ $locale ]['native_name'];

					} else { // generic match e.g. <it>_IT, <it>_CH (any language in the the <it> group )

						$matched_locale = substr( $locale, 0, 2 );

						if ( isset( $translations[ $matched_locale ]['native_name'] ) ) {
							$locales[ $locale ] = $translations[ $matched_locale ]['native_name'];
						}
					}
				}

				// always include US English
				$locales['en_US'] = _x( 'English (United States)', 'language', 'facebook-for-woocommerce' );
			}

			/**
			 * Filters the locales supported by Facebook Messenger.
			 *
			 * @since x.y.z
			 *
			 * @param array $locales locales supported by Facebook Messenger, in $locale => $name format
			 */
			$locales = (array) apply_filters( 'wc_facebook_messenger_supported_locales', array_unique( $locales ) );

			natcasesort( $locales );

			return $locales;
		}


	}

endif;
