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

use SkyVerge\WooCommerce\Facebook\Events\Normalizer;

if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) :

	/**
	 * FB Graph API helper functions
	 */
	class WC_Facebookcommerce_Utils {

		const FB_RETAILER_ID_PREFIX = 'wc_post_id_';
		const PLUGIN_VERSION        = \WC_Facebookcommerce::VERSION; // TODO: remove this in v2.0.0 {CW 2020-02-06}

		// TODO: this constant is no longer used and can probably be removed {WV 2020-01-21}
		const FB_VARIANT_IMAGE   = 'fb_image';
		const FB_VARIANT_SIZE    = 'size';
		const FB_VARIANT_COLOR   = 'color';
		const FB_VARIANT_COLOUR  = 'colour';
		const FB_VARIANT_PATTERN = 'pattern';
		const FB_VARIANT_GENDER  = 'gender';

		public static $ems        = null;
		public static $fbgraph    = null;
		public static $store_name = null;

		public static $validGenderArray =
		array(
			'male'   => 1,
			'female' => 1,
			'unisex' => 1,
		);
		/**
		 * WooCommerce 2.1 support for wc_enqueue_js
		 *
		 * @since 1.2.1
		 *
		 * @access public
		 * @param string $code
		 * @return void
		 */
		public static function wc_enqueue_js( $code ) {
			global $wc_queued_js;

			if ( function_exists( 'wc_enqueue_js' ) && empty( $wc_queued_js ) ) {
				wc_enqueue_js( $code );
			} else {
				$wc_queued_js = $code . "\n" . $wc_queued_js;
			}
		}

		/**
		 * Validate URLs, make relative URLs absolute
		 *
		 * @access public
		 * @param string $url
		 * @return string
		 */
		public static function make_url( $url ) {
			if (
			// The first check incorrectly fails for URLs with special chars.
			! filter_var( $url, FILTER_VALIDATE_URL ) &&
			substr( $url, 0, 4 ) !== 'http'
			) {
				return get_site_url() . $url;
			} else {
				return $url;
			}
		}

		/**
		 * Product ID for Dynamic Ads on Facebook can be SKU or wc_post_id_123
		 * This function should be used to get retailer_id based on a WC_Product
		 * from WooCommerce
		 *
		 * @access public
		 * @param WC_Product $woo_product
		 * @return string
		 */
		public static function get_fb_retailer_id( $woo_product ) {
			// Call $woo_product->get_id() instead of ->id to account for Variable
			// products, which have their own variant_ids.
			$woo_id = $woo_product->get_id();
			$sku = $woo_product->get_sku();
			$retailer_id = $sku ? $sku . '_' . $woo_id : self::FB_RETAILER_ID_PREFIX . $woo_id;

			switch ( get_option( \WC_Facebookcommerce_Integration::SETTING_FB_RETAILER_ID_TYPE )) {
				case \WC_Facebookcommerce_Integration::FB_RETAILER_ID_TYPE_SKU:
					$retailer_id = $sku;
					break;
				case \WC_Facebookcommerce_Integration::FB_RETAILER_ID_TYPE_PRODUCT_ID:
					$retailer_id = strval($woo_id);
					break;
				default:
					// \WC_Facebookcommerce_Integration::FB_RETAILER_ID_TYPE_SKU_PRODUCT_ID
					break;
			}

			return $retailer_id;
		}

		/**
		 * Return categories for products/pixel
		 *
		 * @access public
		 * @param String $id
		 * @return Array
		 */
		public static function get_product_categories( $wpid ) {
			$category_path          = wp_get_post_terms(
				$wpid,
				'product_cat',
				array( 'fields' => 'all' )
			);
			$content_category       = array_values(
				array_map(
					function( $item ) {
						return $item->name;
					},
					$category_path
				)
			);
			$content_category_slice = array_slice( $content_category, -1 );
			$categories             =
			empty( $content_category ) ? '""' : implode( ', ', $content_category );
			return array(
				'name'       => array_pop( $content_category_slice ),
				'categories' => $categories,
			);
		}

		/**
		 * Returns content id to match on for Pixel fires.
		 *
		 * @access public
		 * @param WC_Product $woo_product
		 * @return array
		 */
		public static function get_fb_content_ids( $woo_product ) {
			return array( self::get_fb_retailer_id( $woo_product ) );
		}

		/**
		 * Clean up strings for FB Graph POSTing.
		 * This function should will:
		 * 1. Replace newlines chars/nbsp with a real space
		 * 2. strip_tags()
		 * 3. trim()
		 *
		 * @access public
		 * @param String string
		 * @return string
		 */
		public static function clean_string( $string ) {
			$string = do_shortcode( $string );
			$string = str_replace( array( '&amp%3B', '&amp;' ), '&', $string );
			$string = str_replace( array( "\r", '&nbsp;', "\t" ), ' ', $string );
			$string = wp_strip_all_tags( $string, false ); // true == remove line breaks
			return $string;
		}

		/**
		 * Returns flat array of woo IDs for variable products, or
		 * an array with a single woo ID for simple products.
		 *
		 * @access public
		 * @param WC_Product $woo_product
		 * @return array
		 */
		public static function get_product_array( $woo_product ) {
			$result = array();
			if ( self::is_variable_type( $woo_product->get_type() ) ) {
				foreach ( $woo_product->get_children() as $item_id ) {
					array_push( $result, $item_id );
				}
				return $result;
			} else {
				return array( $woo_product->get_id() );
			}
		}

		/**
		 * Returns true if WooCommerce plugin found.
		 *
		 * @access public
		 * @return bool
		 */
		public static function isWoocommerceIntegration() {
			return class_exists( 'WooCommerce' );
		}

		/**
		 * Returns integration dependent name.
		 *
		 * @access public
		 * @return string
		 */
		public static function getIntegrationName() {
			if ( self::isWoocommerceIntegration() ) {
				return 'WooCommerce';
			} else {
				return 'WordPress';
			}
		}

		/**
		 * Returns user info for the current WP user.
		 *
		 * @access public
		 * @param AAMSettings $aam_settings
		 * @return array
		 */
		public static function get_user_info( $aam_settings ) {
			$current_user = wp_get_current_user();
			if ( 0 === $current_user->ID || $aam_settings == null || !$aam_settings->get_enable_automatic_matching() ) {
				// User not logged in or pixel not configured with automatic advance matching
				return array();
			} else {
				// Keys documented in
				// https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
				$user_data = array(
					'em' => $current_user->user_email,
					'fn' => $current_user->user_firstname,
					'ln' => $current_user->user_lastname,
					'external_id' => strval($current_user->ID),
				);
				$user_id = $current_user->ID;
				$user_data['ct'] = get_user_meta($user_id, 'billing_city', true);
				$user_data['zp'] = get_user_meta($user_id, 'billing_postcode', true);
				$user_data['country'] = get_user_meta($user_id, 'billing_country', true);
				$user_data['st'] = get_user_meta($user_id, 'billing_state', true);
				$user_data['ph'] = get_user_meta($user_id, 'billing_phone', true);
				// Each field that is not present in AAM settings or is empty is deleted from user data
				foreach ($user_data as $field => $value) {
					if( $value === null || $value === ''
						|| !in_array($field, $aam_settings->get_enabled_automatic_matching_fields())
					){
						unset($user_data[$field]);
					}
				}
				// Country is a special case, it is returned as country in AAM settings
				// But used as cn in pixel
				if(array_key_exists('country', $user_data)){
					$country = $user_data['country'];
					$user_data['cn'] = $country;
					unset($user_data['country']);
				}
				$user_data = Normalizer::normalize_array($user_data, true);
				return $user_data;
			}
		}

		/**
		 * Utility function for development logging.
		 */
		public static function fblog(
		$message,
		$object = array(),
		$error = false,
		$ems = '' ) {
			if ( $error ) {
				$object['plugin_version'] = self::PLUGIN_VERSION;
				$object['php_version']    = phpversion();
			}
			$message = json_encode(
				array(
					'message' => $message,
					'object'  => $object,
				)
			);
			$ems     = $ems ?: self::$ems;
			if ( $ems ) {
				self::$fbgraph->log(
					$ems,
					$message,
					$error
				);
			} else {
				error_log(
					'external merchant setting is null, something wrong here: ' .
					$message
				);
			}
		}

		/**
		 * Utility function for development Tip Events logging.
		 */
		public static function tip_events_log(
		$tip_id,
		$channel_id,
		$event,
		$ems = '' ) {

			$ems = $ems ?: self::$ems;
			if ( $ems ) {
				self::$fbgraph->log_tip_event(
					$tip_id,
					$channel_id,
					$event
				);
			} else {
				error_log( 'external merchant setting is null' );
			}
		}

		public static function is_variation_type( $type ) {
			return $type == 'variation' || $type == 'subscription_variation';
		}

		public static function is_variable_type( $type ) {
			return $type == 'variable' || $type == 'variable-subscription';
		}

		public static function check_woo_ajax_permissions( $action_text, $die ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				self::log(
					'Non manage_woocommerce user attempting to' . $action_text . '!',
					array(),
					true
				);
				if ( $die ) {
					wp_die();
				}
				return false;
			}
			return true;
		}

		/**
		 * Returns true if id is a positive non-zero integer
		 *
		 * @access public
		 * @param string $pixel_id
		 * @return bool
		 */
		public static function is_valid_id( $pixel_id ) {
			return isset( $pixel_id ) && is_numeric( $pixel_id ) && (int) $pixel_id > 0;
		}

		/**
		 * Helper function to query posts.
		 */
		public static function get_wp_posts(
		$product_group_id = null,
		$compare_condition = null,
		$post_type = 'product' ) {
			$args = array(
				'fields'         => 'ids',
				'meta_query'     => array(
					( ( $product_group_id ) ?
					array(
						'key'     => $product_group_id,
						'compare' => $compare_condition,
					) : array()
					  ),
				),
				'post_status'    => 'publish',
				'post_type'      => $post_type,
				'posts_per_page' => -1,
			);
			return get_posts( $args );
		}

		/**
		 * Helper log function for debugging
		 */
		public static function log( $message ) {

			// if this file is being included outside the plugin, or the plugin setting is disabled
			if ( ! function_exists( 'facebook_for_woocommerce' ) || ! facebook_for_woocommerce()->get_integration()->is_debug_mode_enabled() ) {
				return;
			}

			if ( is_array( $message ) || is_object( $message ) ) {
				$message = json_encode( $message );
			} else {
				$message = sanitize_textarea_field( $message );
			}

			facebook_for_woocommerce()->log( $message );
		}

		// Return store name with sanitized apostrophe
		public static function get_store_name() {
			if ( self::$store_name ) {
				return self::$store_name;
			}
			$name = trim(
				str_replace(
					"'",
					"\u{2019}",
					html_entity_decode(
						get_bloginfo( 'name' ),
						ENT_QUOTES,
						'UTF-8'
					)
				)
			);
			if ( $name ) {
				self::$store_name = $name;
				return $name;
			}
			// Fallback to site url
			$url = get_site_url();
			if ( $url ) {
				self::$store_name = parse_url( $url, PHP_URL_HOST );
				return self::$store_name;
			}
			// If site url doesn't exist, fall back to http host.
			if ( $_SERVER['HTTP_HOST'] ) {
				self::$store_name = $_SERVER['HTTP_HOST'];
				return self::$store_name;
			}

			// If http host doesn't exist, fall back to local host name.
			$url              = gethostname();
			self::$store_name = $url;
			return ( self::$store_name ) ? ( self::$store_name ) : 'A Store Has No Name';
		}

		/*
		* Change variant product field name from Woo taxonomy to FB name
		*/
		public static function sanitize_variant_name( $name ) {
			$name = str_replace( array( 'attribute_', 'pa_' ), '', strtolower( $name ) );

			// British spelling
			if ( $name === self::FB_VARIANT_COLOUR ) {
				$name = self::FB_VARIANT_COLOR;
			}

			switch ( $name ) {
				case self::FB_VARIANT_SIZE:
				case self::FB_VARIANT_COLOR:
				case self::FB_VARIANT_GENDER:
				case self::FB_VARIANT_PATTERN:
					break;
				default:
					$name = 'custom_data:' . strtolower( $name );
					break;
			}

			return $name;
		}

		public static function validateGender( $gender ) {
			if ( $gender && ! isset( self::$validGenderArray[ $gender ] ) ) {
				$first_char = strtolower( substr( $gender, 0, 1 ) );
				// Men, Man, Boys
				if ( $first_char === 'm' || $first_char === 'b' ) {
					return 'male';
				}
				// Women, Woman, Female, Ladies
				if ( $first_char === 'w' || $first_char === 'f' || $first_char === 'l' ) {
					return 'female';
				}
				if ( $first_char === 'u' ) {
					return 'unisex';
				}
				if ( strlen( $gender ) >= 3 ) {
					$gender = strtolower( substr( $gender, 0, 3 ) );
					if ( $gender === 'gir' || $gender === 'her' ) {
						return 'female';
					}
					if ( $gender === 'him' || $gender === 'his' || $gender == 'guy' ) {
						return 'male';
					}
				}
				return null;
			}
			return $gender;
		}

		public static function get_fbid_post_meta( $wp_id, $fbid_type ) {
			return get_post_meta( $wp_id, $fbid_type, true );
		}

		public static function is_all_caps( $value ) {
			if ( $value === null || $value === '' ) {
				return true;
			}
			if ( preg_match( '/[^\\p{Common}\\p{Latin}]/u', $value ) ) {
				// Contains non-western characters
				// So, it can't be all uppercase
				return false;
			}
			$latin_string = preg_replace( '/[^\\p{Latin}]/u', '', $value );
			if ( $latin_string === '' ) {
				// Symbols only
				return true;
			}
			return strtoupper( $latin_string ) === $latin_string;
		}

		public static function decode_json( $json_string, $assoc = false ) {
			// Plugin requires 5.6.0 but for some user use 5.5.9 JSON_BIGINT_AS_STRING
			// will cause 502 issue when redirect.
			return version_compare( phpversion(), '5.6.0' ) >= 0
			? json_decode( $json_string, $assoc, 512, JSON_BIGINT_AS_STRING )
			: json_decode( $json_string, $assoc, 512 );
		}

		public static function set_test_fail_reason( $msg, $trace ) {
			$reason_msg = get_transient( 'facebook_plugin_test_fail' );
			if ( $reason_msg ) {
				$msg = $reason_msg . PHP_EOL . $msg;
			}
			set_transient( 'facebook_plugin_test_fail', $msg );
			set_transient( 'facebook_plugin_test_stack_trace', $trace );
		}

		/**
		 * Helper function to check time cap.
		 */
		public static function check_time_cap( $from, $date_cap ) {
			if ( $from == null ) {
				return true;
			}
			$now         = new DateTime( current_time( 'mysql' ) );
			$diff_in_day = $now->diff( new DateTime( $from ) )->format( '%a' );
			return is_numeric( $diff_in_day ) && (int) $diff_in_day > $date_cap;
		}

		public static function get_cached_best_tip() {
			$cached_best_tip = self::decode_json(
				get_option( 'fb_info_banner_last_best_tip', '' )
			);
			return $cached_best_tip;
		}
	}

endif;
