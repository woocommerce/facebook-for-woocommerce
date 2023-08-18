<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use WooCommerce\Facebook\Locale;

if ( ! class_exists( 'WC_Facebookcommerce_MessengerChat' ) ) :

	if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) {
		include_once 'includes/fbutils.php';
	}

	/**
	 * Messenger chat handler class.
	 */
	class WC_Facebookcommerce_MessengerChat {

		/** @var string Facebook Page ID. */
		private $page_id;

		/** @var string|null JS SDK Version. */
		private $jssdk_version;

		/**
		 * Class constructor.
		 *
		 * @param array $settings FB page settings array.
		 */
		public function __construct( $settings ) {

			$this->page_id = isset( $settings['fb_page_id'] )
				? $settings['fb_page_id']
				: '';

			$this->jssdk_version = isset( $settings['facebook_jssdk_version'] )
				? $settings['facebook_jssdk_version']
				: '';

			add_action( 'wp_footer', array( $this, 'inject_messenger_chat_plugin' ) );
		}

		/**
		 * __get method for backward compatibility.
		 *
		 * @param string $key property name
		 * @return mixed
		 * @since 3.0.32
		 */
		public function __get( $key ) {
			// Add warning for private properties.
			if ( in_array( $key, array( 'page_id', 'jssdk_version' ), true ) ) {
				/* translators: %s property name. */
				_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The %s property is private and should not be accessed outside its class.', 'facebook-for-woocommerce' ), esc_html( $key ) ), '3.0.32' );
				return $this->$key;
			}

			return null;
		}

		/**
		 * Outputs the Facebook Messenger chat script.
		 *
		 * @internal
		 */
		public function inject_messenger_chat_plugin() {

			if ( facebook_for_woocommerce()->get_integration()->is_messenger_enabled() ) :

				printf(
					"
					<div
						attribution=\"fbe_woocommerce\"
						class=\"fb-customerchat\"
						page_id=\"%s\"
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
					esc_js( $this->jssdk_version ?: 'v5.0' ),
					esc_js( facebook_for_woocommerce()->get_integration()->get_messenger_locale() ?: 'en_US' )
				);

			endif;
		}

	}

endif;
