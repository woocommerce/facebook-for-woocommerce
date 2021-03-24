<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) {
	include_once 'includes/fbutils.php';
}

if ( ! class_exists( 'WC_Facebook_Product' ) ) :

	/**
	 * Custom FB Product proxy class
	 */
	class WC_Facebook_Product {
		// Used for the background sync
		const PRODUCT_PREP_TYPE_ITEMS_BATCH = 'items_batch';
		// Used for the background feed upload
		const PRODUCT_PREP_TYPE_FEED = 'feed';
		// Used for direct update and create calls
		const PRODUCT_PREP_TYPE_NORMAL = 'normal';

		// Should match facebook-commerce.php while we migrate that code over
		// to this object.
		const FB_PRODUCT_DESCRIPTION = 'fb_product_description';
		const FB_PRODUCT_PRICE       = 'fb_product_price';
		const FB_PRODUCT_IMAGE       = 'fb_product_image';
		const FB_VARIANT_IMAGE       = 'fb_image';
		const FB_VISIBILITY          = 'fb_visibility';
		const FB_REMOVE_FROM_SYNC    = 'fb_remove_from_sync';

		const MIN_DATE_1 = '1970-01-29';
		const MIN_DATE_2 = '1970-01-30';
		const MAX_DATE   = '2038-01-17';
		const MAX_TIME   = 'T23:59+00:00';
		const MIN_TIME   = 'T00:00+00:00';

		static $use_checkout_url = array(
			'simple'    => 1,
			'variable'  => 1,
			'variation' => 1,
		);

		public function __construct( $wpid, $parent_product = null ) {

			$this->id                     = $wpid;
			$this->fb_description         = '';
			$this->woo_product            = wc_get_product( $wpid );
			$this->gallery_urls           = null;
			$this->fb_use_parent_image    = null;
			$this->main_description       = '';
			$this->sync_short_description = \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT === facebook_for_woocommerce()->get_integration()->get_product_description_mode();

			if ( $meta = get_post_meta( $wpid, self::FB_VISIBILITY, true ) ) {
				$this->fb_visibility = wc_string_to_bool( $meta );
			} else {
				$this->fb_visibility = '';
				// for products that haven't synced yet
			}

			// Variable products should use some data from the parent_product
			// For performance reasons, that data shouldn't be regenerated every time.
			if ( $parent_product ) {
				$this->gallery_urls        = $parent_product->get_gallery_urls();
				$this->fb_use_parent_image = $parent_product->get_use_parent_image();
				$this->main_description    = $parent_product->get_fb_description();
			}
		}

		public function exists() {
			return ( $this->woo_product !== null && $this->woo_product !== false );
		}

		// Fall back to calling method on $woo_product
		public function __call( $function, $args ) {
			if ( $this->woo_product ) {
				return call_user_func_array( array( $this->woo_product, $function ), $args );
			} else {
				$e         = new Exception();
				$backtrace = var_export( $e->getTraceAsString(), true );
				WC_Facebookcommerce_Utils::fblog(
					"Calling $function on Null Woo Object. Trace:\n" . $backtrace,
					array(),
					true
				);
				return null;
			}
		}

		public function get_gallery_urls() {
			if ( $this->gallery_urls === null ) {
				if ( is_callable( array( $this, 'get_gallery_image_ids' ) ) ) {
					$image_ids = $this->get_gallery_image_ids();
				} else {
					$image_ids = $this->get_gallery_attachment_ids();
				}
				$gallery_urls = array();
				foreach ( $image_ids as $image_id ) {
					$image_url = wp_get_attachment_url( $image_id );
					if ( ! empty( $image_url ) ) {
						array_push(
							$gallery_urls,
							WC_Facebookcommerce_Utils::make_url( $image_url )
						);
					}
				}
				$this->gallery_urls = array_filter( $gallery_urls );
			}

			return $this->gallery_urls;
		}

		public function get_post_data() {
			if ( is_callable( 'get_post' ) ) {
				return get_post( $this->id );
			} else {
				return $this->get_post_data();
			}
		}

		public function get_fb_price( $for_items_batch = false ) {
			$product_price = Products::get_product_price( $this->woo_product );

			return $for_items_batch ? self::format_price_for_fb_items_batch( $product_price ) : $product_price;
		}

		private static function format_price_for_fb_items_batch( $price ) {
			// items_batch endpoint requires a string and a currency code
			$formatted = ( $price / 100.0 ) . ' ' . get_woocommerce_currency();
			return $formatted;
		}


		/**
		 * Determines whether the current product is a WooCommerce Bookings product.
		 *
		 * TODO: add an integration that filters the Facebook price instead {WV 2020-07-22}
		 *
		 * @since 2.0.0
		 *
		 * @return bool
		 */
		private function is_bookable_product() {

			return facebook_for_woocommerce()->is_plugin_active( 'woocommerce-bookings.php' ) && class_exists( 'WC_Product_Booking' ) && is_callable( 'is_wc_booking_product' ) && is_wc_booking_product( $this );
		}


		/**
		 * Gets a list of image URLs to use for this product in Facebook sync.
		 *
		 * @return array
		 */
		public function get_all_image_urls() {

			$image_urls = array();

			$product_image_url        = wp_get_attachment_url( $this->woo_product->get_image_id() );
			$parent_product_image_url = null;
			$custom_image_url         = $this->woo_product->get_meta( self::FB_PRODUCT_IMAGE );

			if ( $this->woo_product->is_type( 'variation' ) ) {

				if ( $parent_product = wc_get_product( $this->woo_product->get_parent_id() ) ) {

					$parent_product_image_url = wp_get_attachment_url( $parent_product->get_image_id() );
				}
			}

			switch ( $this->woo_product->get_meta( Products::PRODUCT_IMAGE_SOURCE_META_KEY ) ) {

				case Products::PRODUCT_IMAGE_SOURCE_CUSTOM:
					$image_urls = array( $custom_image_url, $product_image_url, $parent_product_image_url );
					break;

				case Products::PRODUCT_IMAGE_SOURCE_PARENT_PRODUCT:
					$image_urls = array( $parent_product_image_url, $product_image_url );
					break;

				case Products::PRODUCT_IMAGE_SOURCE_PRODUCT:
				default:
					$image_urls = array( $product_image_url, $parent_product_image_url );
					break;
			}

			$image_urls = array_merge( $image_urls, $this->get_gallery_urls() );
			$image_urls = array_filter( array_unique( $image_urls ) );

			if ( empty( $image_urls ) ) {
				$image_urls[] = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/woocommerce-placeholder.png';
			}

			return $image_urls;
		}


		/**
		 * Gets the list of additional image URLs for the product from the complete list of image URLs.
		 *
		 * It assumes the first URL will be used as the product image.
		 * It returns 20 or less image URLs because Facebook doesn't allow more items on the additional_image_urls field.
		 *
		 * @since 2.0.2
		 *
		 * @param array $image_urls all image URLs for the product
		 * @return array
		 */
		private function get_additional_image_urls( $image_urls ) {

			return array_slice( $image_urls, 1, 20 );
		}


		// Returns the parent image id for variable products only.
		public function get_parent_image_id() {
			if ( WC_Facebookcommerce_Utils::is_variation_type( $this->woo_product->get_type() ) ) {
				$parent_data = $this->get_parent_data();
				return $parent_data['image_id'];
			}
			return null;
		}

		public function set_description( $description ) {
			$description          = stripslashes(
				WC_Facebookcommerce_Utils::clean_string( $description )
			);
			$this->fb_description = $description;
			update_post_meta(
				$this->id,
				self::FB_PRODUCT_DESCRIPTION,
				$description
			);
		}

		public function set_product_image( $image ) {
			if ( $image !== null && strlen( $image ) !== 0 ) {
				$image = WC_Facebookcommerce_Utils::clean_string( $image );
				$image = WC_Facebookcommerce_Utils::make_url( $image );
				update_post_meta(
					$this->id,
					self::FB_PRODUCT_IMAGE,
					$image
				);
			}
		}

		public function set_price( $price ) {
			if ( is_numeric( $price ) ) {
				update_post_meta(
					$this->id,
					self::FB_PRODUCT_PRICE,
					$price
				);
			} else {
				delete_post_meta(
					$this->id,
					self::FB_PRODUCT_PRICE
				);
			}
		}

		public function get_use_parent_image() {
			if ( $this->fb_use_parent_image === null ) {
				$variant_image_setting     =
				get_post_meta( $this->id, self::FB_VARIANT_IMAGE, true );
				$this->fb_use_parent_image = ( $variant_image_setting ) ? true : false;
			}
			return $this->fb_use_parent_image;
		}

		public function set_use_parent_image( $setting ) {
			$this->fb_use_parent_image = ( $setting == 'yes' );
			update_post_meta(
				$this->id,
				self::FB_VARIANT_IMAGE,
				$this->fb_use_parent_image
			);
		}

		public function get_fb_description() {
			if ( $this->fb_description ) {
				return $this->fb_description;
			}

			$description = get_post_meta(
				$this->id,
				self::FB_PRODUCT_DESCRIPTION,
				true
			);

			if ( $description ) {
				return $description;
			}

			if ( WC_Facebookcommerce_Utils::is_variation_type( $this->woo_product->get_type() ) ) {

				$description = WC_Facebookcommerce_Utils::clean_string( $this->woo_product->get_description() );

				if ( $description ) {
					return $description;
				}
				if ( $this->main_description ) {
					return $this->main_description;
				}
			}

			$post = $this->get_post_data();

			$post_content = WC_Facebookcommerce_Utils::clean_string(
				$post->post_content
			);
			$post_excerpt = WC_Facebookcommerce_Utils::clean_string(
				$post->post_excerpt
			);
			$post_title   = WC_Facebookcommerce_Utils::clean_string(
				$post->post_title
			);

			// Sanitize description
			if ( $post_content ) {
				$description = $post_content;
			}
			if ( $this->sync_short_description || ( $description == '' && $post_excerpt ) ) {
				$description = $post_excerpt;
			}
			if ( $description == '' ) {
				$description = $post_title;
			}

			return $description;
		}

		public function add_sale_price( $product_data, $for_items_batch = false ) {

			// initialise sale price
			if ( $for_items_batch ) {
				$product_data['sale_price_effective_date'] = self::MIN_DATE_1 . self::MIN_TIME . '/' . self::MIN_DATE_2 . self::MAX_TIME;
			} else {
				$product_data['sale_price_start_date'] = self::MIN_DATE_1 . self::MIN_TIME;
				$product_data['sale_price_end_date']   = self::MIN_DATE_2 . self::MAX_TIME;
			}
			$product_data['sale_price'] = $product_data['price'];

			$sale_price = $this->woo_product->get_sale_price();

			// check if sale exist
			if ( ! is_numeric( $sale_price ) ) {
				return $product_data;
			}

			$sale_price =
			intval( round( $this->get_price_plus_tax( $sale_price ) * 100 ) );

			$sale_start =
			( $date     = get_post_meta( $this->id, '_sale_price_dates_from', true ) )
			? date_i18n( 'Y-m-d', $date ) . self::MIN_TIME
			: self::MIN_DATE_1 . self::MIN_TIME;

			$sale_end =
			( $date   = get_post_meta( $this->id, '_sale_price_dates_to', true ) )
			? date_i18n( 'Y-m-d', $date ) . self::MAX_TIME
			: self::MAX_DATE . self::MAX_TIME;

			// check if sale is expired and sale time range is valid
			if ( $for_items_batch ) {
				$product_data['sale_price_effective_date'] = $sale_start . '/' . $sale_end;
				$product_data['sale_price']                = self::format_price_for_fb_items_batch( $sale_price );
			} else {
				$product_data['sale_price_start_date'] = $sale_start;
				$product_data['sale_price_end_date']   = $sale_end;
				$product_data['sale_price']            = $sale_price;
			}

			return $product_data;
		}


		/**
		 * Determines whether a product should be excluded from all-products sync or the feed file.
		 *
		 * @see SkyVerge\WooCommerce\Facebook\Products\Sync::create_or_update_all_products()
		 * @see WC_Facebook_Product_Feed::write_product_feed_file()
		 *
		 * @deprecated 2.0.2
		 */
		public function is_hidden() {

			wc_deprecated_function( __METHOD__, '2.0.2', 'Products::product_should_be_synced()' );

			return $this->woo_product instanceof \WC_Product && ! Products::product_should_be_synced( $this->woo_product );
		}


		public function get_price_plus_tax( $price ) {
			$woo_product = $this->woo_product;
			// // wc_get_price_including_tax exist for Woo > 2.7
			if ( function_exists( 'wc_get_price_including_tax' ) ) {
				$args = array(
					'qty'   => 1,
					'price' => $price,
				);
				return get_option( 'woocommerce_tax_display_shop' ) === 'incl'
					  ? wc_get_price_including_tax( $woo_product, $args )
					  : wc_get_price_excluding_tax( $woo_product, $args );
			} else {
				return get_option( 'woocommerce_tax_display_shop' ) === 'incl'
					  ? $woo_product->get_price_including_tax( 1, $price )
					  : $woo_product->get_price_excluding_tax( 1, $price );
			}
		}

		public function get_grouped_product_option_names( $key, $option_values ) {
			// Convert all slug_names in $option_values into the visible names that
			// advertisers have set to be the display names for a given attribute value
			$terms = get_the_terms( $this->id, $key );
			return ! is_array( $terms ) ? [] : array_map(
				function ( $slug_name ) use ( $terms ) {
					foreach ( $terms as $term ) {
						if ( $term->slug === $slug_name ) {
							return $term->name;
						}
					}
					return $slug_name;
				},
				$option_values
			);
		}


		public function update_visibility( $is_product_page, $visible_box_checked ) {
			$visibility = get_post_meta( $this->id, self::FB_VISIBILITY, true );
			if ( $visibility && ! $is_product_page ) {
				// If the product was previously set to visible, keep it as visible
				// (unless we're on the product page)
				$this->fb_visibility = $visibility;
			} else {
				// If the product is not visible OR we're on the product page,
				// then update the visibility as needed.
				$this->fb_visibility = $visible_box_checked ? true : false;
				update_post_meta( $this->id, self::FB_VISIBILITY, $this->fb_visibility );
			}
		}

		// wrapper function to find item_id for default variation
		function find_matching_product_variation() {
			if ( is_callable( array( $this, 'get_default_attributes' ) ) ) {
				$default_attributes = $this->get_default_attributes();
			} else {
				$default_attributes = $this->get_variation_default_attributes();
			}

			if ( ! $default_attributes ) {
				return;
			}
			foreach ( $default_attributes as $key => $value ) {
				if ( strncmp( $key, 'attribute_', strlen( 'attribute_' ) ) === 0 ) {
					continue;
				}
				unset( $default_attributes[ $key ] );
				$default_attributes[ sprintf( 'attribute_%s', $key ) ] = $value;
			}
			if ( class_exists( 'WC_Data_Store' ) ) {
				// for >= woo 3.0.0
				$data_store = WC_Data_Store::load( 'product' );
				return $data_store->find_matching_product_variation(
					$this,
					$default_attributes
				);
			} else {
				return $this->get_matching_variation( $default_attributes );
			}
		}

		private function build_checkout_url( $product_url ) {
			// Use product_url for external/bundle product setting.
			$product_type = $this->get_type();
			if ( ! $product_type || ! isset( self::$use_checkout_url[ $product_type ] ) ) {
					$checkout_url = $product_url;
			} elseif ( wc_get_cart_url() ) {
				$char = '?';
				// Some merchant cart pages are actually a querystring
				if ( strpos( wc_get_cart_url(), '?' ) !== false ) {
					$char = '&';
				}

				$checkout_url = WC_Facebookcommerce_Utils::make_url(
					wc_get_cart_url() . $char
				);

				if ( WC_Facebookcommerce_Utils::is_variation_type( $this->get_type() ) ) {
					$query_data = array(
						'add-to-cart'  => $this->get_parent_id(),
						'variation_id' => $this->get_id(),
					);

					$query_data = array_merge(
						$query_data,
						$this->get_variation_attributes()
					);

				} else {
					$query_data = array(
						'add-to-cart' => $this->get_id(),
					);
				}

				$checkout_url = $checkout_url . http_build_query( $query_data );

			} else {
				$checkout_url = null;
			}//end if
		}

		/**
		 * Gets product data to send to Facebook.
		 *
		 * @param string $retailer_id the retailer ID of the product
		 * @param string $type_to_prepare_for whether the data is going to be used in a feed upload, an items_batch update or a direct api call
		 * @return array
		 */
		public function prepare_product( $retailer_id = null, $type_to_prepare_for = self::PRODUCT_PREP_TYPE_NORMAL ) {

			if ( ! $retailer_id ) {
				$retailer_id =
				WC_Facebookcommerce_Utils::get_fb_retailer_id( $this );
			}
			$image_urls = $this->get_all_image_urls();

			// Replace WordPress sanitization's ampersand with a real ampersand.
			$product_url = str_replace(
				'&amp%3B',
				'&',
				html_entity_decode( $this->get_permalink() )
			);

			$id = $this->get_id();
			if ( WC_Facebookcommerce_Utils::is_variation_type( $this->get_type() ) ) {
				$id = $this->get_parent_id();
			}
			$categories =
			WC_Facebookcommerce_Utils::get_product_categories( $id );

			$brand = get_the_term_list( $id, 'product_brand', '', ', ' );
			$brand = is_wp_error( $brand ) || ! $brand ? wp_strip_all_tags( WC_Facebookcommerce_Utils::get_store_name() ) : WC_Facebookcommerce_Utils::clean_string( $brand );

			if ( self::PRODUCT_PREP_TYPE_ITEMS_BATCH === $type_to_prepare_for ) {
				$product_data = array(
					'title'                 => WC_Facebookcommerce_Utils::clean_string(
						$this->get_title()
					),
					'description'           => $this->get_fb_description(),
					'image_link'            => $image_urls[0],
					'additional_image_link' => $this->get_additional_image_urls( $image_urls ),
					'link'                  => $product_url,
					'brand'                 => Framework\SV_WC_Helper::str_truncate( $brand, 100 ),
					'retailer_id'           => $retailer_id,
					'price'                 => $this->get_fb_price( true ),
					'availability'          => $this->is_in_stock() ? 'in stock' : 'out of stock',
					'visibility'            => Products::is_product_visible( $this->woo_product ) ? \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_VISIBLE : \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN,
				);
				$product_data = $this->add_sale_price( $product_data, true );
			} else {
				$product_data = array(
					'name'                  => WC_Facebookcommerce_Utils::clean_string(
						$this->get_title()
					),
					'description'           => $this->get_fb_description(),
					'image_url'             => $image_urls[0],
					'additional_image_urls' => $this->get_additional_image_urls( $image_urls ),
					'url'                   => $product_url,
					'category'              => $categories['categories'],
					'brand'                 => Framework\SV_WC_Helper::str_truncate( $brand, 100 ),
					'retailer_id'           => $retailer_id,
					'price'                 => $this->get_fb_price(),
					'currency'              => get_woocommerce_currency(),
					'availability'          => $this->is_in_stock() ? 'in stock' : 'out of stock',
					'visibility'            => Products::is_product_visible( $this->woo_product ) ? \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_VISIBLE : \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN,
				);
				$product_data = $this->add_sale_price( $product_data );
			}//end if

			$google_product_category = Products::get_google_product_category_id( $this->woo_product );
			// Currently only items batch and feed support enhanced catalog fields
			if ( self::PRODUCT_PREP_TYPE_NORMAL !== $type_to_prepare_for && $google_product_category ) {
				$product_data['google_product_category'] = $google_product_category;
				$product_data                            = $this->apply_enhanced_catalog_fields_from_attributes( $product_data, $google_product_category );
			}

			// add the Commerce values (only inventory for the moment)
			if ( Products::is_product_ready_for_commerce( $this->woo_product ) ) {
				$product_data['inventory'] = (int) max( 0, $this->woo_product->get_stock_quantity() );
			}

			// Only use checkout URLs if they exist.
			$checkout_url = $this->build_checkout_url( $product_url );
			if ( $checkout_url ) {
				$product_data['checkout_url'] = $checkout_url;
			}

			// IF using WPML, set the product to staging unless it is in the
			// default language. WPML >= 3.2 Supported.
			if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				if ( class_exists( 'WC_Facebook_WPML_Injector' ) && WC_Facebook_WPML_Injector::should_hide( $id ) ) {
					$product_data['visibility'] = \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN;
				}
			}

			// Exclude variations that are "virtual" products from export to Facebook &&
			// No Visibility Option for Variations
			if ( true === $this->get_virtual() ) {
				$product_data['visibility'] = \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN;
			}

			if ( self::PRODUCT_PREP_TYPE_FEED !== $type_to_prepare_for ) {
				$this->prepare_variants_for_item( $product_data );
			} elseif (
			WC_Facebookcommerce_Utils::is_all_caps( $product_data['description'] )
			) {
				$product_data['description'] =
				mb_strtolower( $product_data['description'] );
			}

			/**
			   * Filters the generated product data.
			   *
			   * @param int   $id           Woocommerce product id
			   * @param array $product_data An array of product data
			   */
			return apply_filters(
				'facebook_for_woocommerce_integration_prepare_product',
				$product_data,
				$id
			);
		}

		/**
		 * Adds enhanced catalog fields to product data array. Separated from
		 * the main function to make it easier to develop and debug, potentially
		 * worth refactoring into main prepare_product function when complete.
		 *
		 * @param array $product_data map
		 * @return array
		 */
		private function apply_enhanced_catalog_fields_from_attributes( $product_data, $google_category_id ) {
			$google_category_id = $product_data['google_product_category'];
			$category_handler   = facebook_for_woocommerce()->get_facebook_category_handler();
			if ( empty( $google_category_id ) || ! $category_handler->is_category( $google_category_id ) ) {
				return $product_data;
			}
			$enhanced_data = array();

			$category       = $category_handler->get_category_with_attrs( $google_category_id );
			$all_attributes = $this->get_matched_attributes_for_product( $this->woo_product, $category['attributes'] );

			foreach ( $all_attributes as $attribute ) {
				$value            = Products::get_enhanced_catalog_attribute( $attribute['key'], $this->woo_product );
				$convert_to_array = (
					isset( $attribute['can_have_multiple_values'] ) &&
					true === $attribute['can_have_multiple_values'] &&
					'string' === $attribute['type']
				);

				if ( ! empty( $value ) &&
					$category_handler->is_valid_value_for_attribute( $google_category_id, $attribute['key'], $value )
				) {
					if ( $convert_to_array ) {
						$value = array_map( 'trim', explode( ',', $value ) );
					}
					$enhanced_data[ $attribute['key'] ] = $value;
				}
			}

			return array_merge( $product_data, $enhanced_data );
		}


		/**
		 * Filters list of attributes to only those available for a given product
		 *
		 * @param \WC_Product $product WooCommerce Product
		 * @param array $all_attributes List of Enhanced Catalog attributes to match
		 * @return array
		 */
		public function get_matched_attributes_for_product( $product, $all_attributes ) {
			$matched_attributes = array();
			$sanitized_keys = array_map(
				function( $key ) {
						return \WC_Facebookcommerce_Utils::sanitize_variant_name( $key, false );
				},
				array_keys( $product->get_attributes() )
			);

			$matched_attributes = array_filter( $all_attributes,
				function( $attribute ) use ($sanitized_keys) {
					return in_array( $attribute['key'], $sanitized_keys );
				}
			);

			return $matched_attributes;
		}


		/**
		 * Normalizes variant data for Facebook.
		 *
		 * @param array $product_data variation product data
		 * @return array
		 */
		public function prepare_variants_for_item( &$product_data ) {

			/** @var \WC_Product_Variation $product */
			$product = $this;

			if ( ! $product->is_type( 'variation' ) ) {
				return array();
			}

			$attributes = $product->get_variation_attributes();

			if ( ! $attributes ) {
				return array();
			}

			$variant_names = array_keys( $attributes );
			$variant_data  = array();

			// Loop through variants (size, color, etc) if they exist
			// For each product field type, pull the single variant
			foreach ( $variant_names as $original_variant_name ) {

				// don't handle any attributes that are designated as Commerce attributes
				if ( in_array( str_replace( 'attribute_', '', strtolower( $original_variant_name ) ), Products::get_distinct_product_attributes( $this->woo_product ), true ) ) {
					continue;
				}

				// Retrieve label name for attribute
				$label = wc_attribute_label( $original_variant_name, $product );

				// Clean up variant name (e.g. pa_color should be color)
				$new_name = \WC_Facebookcommerce_Utils::sanitize_variant_name( $original_variant_name, false );

				// Sometimes WC returns an array, sometimes it's an assoc array, depending
				// on what type of taxonomy it's using.  array_values will guarantee we
				// only get a flat array of values.
				if ( $options = \WC_Facebookcommerce_Utils::get_variant_option_name( $this->id, $label, $attributes[ $original_variant_name ] ) ) {

					if ( is_array( $options ) ) {

						$option_values = array_values( $options );

					} else {

						$option_values = array( $options );

						// If this attribute has value 'any', options will be empty strings
						// Redirect to product page to select variants.
						// Reset checkout url since checkout_url (build from query data will
						// be invalid in this case.
						if ( count( $option_values ) === 1 && empty( $option_values[0] ) ) {
								$option_values[0]             = 'any';
								$product_data['checkout_url'] = $product_data['url'];
						}
					}

					if ( \WC_Facebookcommerce_Utils::FB_VARIANT_GENDER === $new_name && ! isset( $product_data[ \WC_Facebookcommerce_Utils::FB_VARIANT_GENDER ] ) ) {

						// If we can't validate the gender, this will be null.
						$product_data[ $new_name ] = \WC_Facebookcommerce_Utils::validateGender( $option_values[0] );
					}

					switch ( $new_name ) {

						case \WC_Facebookcommerce_Utils::FB_VARIANT_GENDER:
							// If we can't validate the GENDER field, we'll fall through to the
							// default case and set the gender into custom data.
							if ( $product_data[ $new_name ] ) {

								$variant_data[] = array(
									'product_field' => $new_name,
									'label'         => $label,
									'options'       => $option_values,
								);
							}

							break;

						default:
							// This is for any custom_data.
							if ( ! isset( $product_data['custom_data'] ) ) {
								$product_data['custom_data'] = array();
							}

							$product_data['custom_data'][ $new_name ] = urldecode( $option_values[0] );

							break;
					}//end switch
				} else {

					\WC_Facebookcommerce_Utils::log( $product->get_id() . ': No options for ' . $original_variant_name );
					continue;
				}//end if
			}//end foreach

			return $variant_data;
		}


		/**
		 * Normalizes variable product variations data for Facebook.
		 *
		 * @param bool $feed_data whether this is used for feed data
		 * @return array
		 */
		public function prepare_variants_for_group( $feed_data = false ) {

			/** @var \WC_Product_Variable $product */
			$product        = $this;
			$final_variants = array();

			try {

				if ( ! $product->is_type( 'variable' ) ) {
					throw new \Exception( 'prepare_variants_for_group called on non-variable product' );
				}

				$variation_attributes = $product->get_variation_attributes();

				if ( ! $variation_attributes ) {
					return array();
				}

				foreach ( array_keys( $product->get_attributes() ) as $name ) {

					$label = wc_attribute_label( $name, $product );

					if ( taxonomy_is_product_attribute( $name ) ) {
						$key = $name;
					} else {
						// variation_attributes keys are labels for custom attrs for some reason
						$key = $label;
					}

					if ( ! $key ) {
						throw new \Exception( "Critical error: can't get attribute name or label!" );
					}

					if ( isset( $variation_attributes[ $key ] ) ) {
						// array of the options (e.g. small, medium, large)
						$option_values = $variation_attributes[ $key ];
					} else {
						// skip variations without valid attribute options
						\WC_Facebookcommerce_Utils::log( $product->get_id() . ': No options for ' . $name );
						continue;
					}

					// If this is a variable product, check default attribute.
					// If it's being used, show it as the first option on Facebook.
					if ( $first_option = $product->get_variation_default_attribute( $key ) ) {

						$index = array_search( $first_option, $option_values, false );

						unset( $option_values[ $index ] );

						array_unshift( $option_values, $first_option );
					}

					if ( function_exists( 'taxonomy_is_product_attribute' ) && taxonomy_is_product_attribute( $name ) ) {
						$option_values = $this->get_grouped_product_option_names( $key, $option_values );
					}

					switch ( $name ) {

						case Products::get_product_color_attribute( $this->woo_product ):
							$name = WC_Facebookcommerce_Utils::FB_VARIANT_COLOR;
							break;

						case Products::get_product_size_attribute( $this->woo_product ):
							$name = WC_Facebookcommerce_Utils::FB_VARIANT_SIZE;
							break;

						case Products::get_product_pattern_attribute( $this->woo_product ):
							$name = WC_Facebookcommerce_Utils::FB_VARIANT_PATTERN;
							break;

						default:
							/**
							 * For API approach, product_field need to start with 'custom_data:'
							 *
							 * @link https://developers.facebook.com/docs/marketing-api/reference/product-variant/
							 */
							$name = \WC_Facebookcommerce_Utils::sanitize_variant_name( $name );
					}//end switch

					// for feed uploading, product field should remove prefix 'custom_data:'
					if ( $feed_data ) {
						$name = str_replace( 'custom_data:', '', $name );
					}

					$final_variants[] = array(
						'product_field' => $name,
						'label'         => $label,
						'options'       => $option_values,
					);
				}//end foreach
			} catch ( \Exception $e ) {

				\WC_Facebookcommerce_Utils::fblog( $e->getMessage() );

				return array();
			}//end try

			return $final_variants;
		}


	}

endif;
