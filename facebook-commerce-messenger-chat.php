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
					/></div>
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

	}

endif;
