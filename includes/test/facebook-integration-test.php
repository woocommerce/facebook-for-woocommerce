<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 *
 * usage:
 * 1. set WP_DEBUG = true and WP_DEBUG_DISPLAY = false
 * 2. append "&fb_test_product_sync=true" to the url when you are on facebook-for-woocommerce setting pages
 * 3. refresh the page to launch test
 * https://codex.wordpress.org/WP_DEBUG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/fbutils.php';
require_once 'fbproductfeed-test.php';

if ( ! class_exists( 'WC_Facebook_Integration_Test' ) ) :

	/**
	 * This tests the upload of test objects into Facebook using the plugin's
	 * infrastructure and checks to see if the product field have been correctly
	 * uploaded into FB.
	 */
	class WC_Facebook_Integration_Test {

		const FB_PRODUCT_GROUP_ID = 'fb_product_group_id';
		const FB_PRODUCT_ITEM_ID  = 'fb_product_item_id';
		const MAX_SLEEP_IN_SEC    = 90;
		const MAX_TIME            = 'T23:59+00:00';
		const MIN_TIME            = 'T00:00+00:00';
		/** Class Instance */
		private static $instance;

		/** @var WC_Facebookcommerce_Integration full integration object */
		public static $commerce  = null;
		public static $fbgraph   = null;
		public static $test_mode = false;

		// simple products' id and variable products' parent_id
		public static $wp_post_ids = array();
		// FB product item retailer id.
		public static $retailer_ids = array();
		// product and product_variation post id for test
		public $product_post_wpid = null;
		public static $test_pass  = 1;

		/**
		 * Get the class instance
		 */
		public static function get_instance( $commerce ) {
			return null === self::$instance
			? ( self::$instance = new self( $commerce ) )
			: self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct( $commerce ) {

			self::$commerce = $commerce;

			add_action(
				'wp_ajax_ajax_test_sync_products_using_feed',
				array( $this, 'ajax_test_sync_products_using_feed' )
			);
		}

		/**
		 * Test visible products by uploading feed.
		 **/
		function ajax_test_sync_products_using_feed() {
			self::$test_mode = true;
			// test ajax reset all products in db
			$reset = self::$commerce->reset_all_products();
			if ( $reset ) {
				WC_Facebookcommerce_Utils::log( 'Test - Removing FBIDs from all products' );
				$this->product_post_wpid = $this->create_data();
				if ( empty( $this->product_post_wpid ) ) {
					self::$test_pass = 0;
					WC_Facebookcommerce_Utils::log(
						'Test - Fail to create test product by inserting posts.'
					);
					WC_Facebookcommerce_Utils::set_test_fail_reason(
						'Fail to create test products by inserting posts.',
						( new Exception() )->getTraceAsString()
					);
					update_option( 'fb_test_pass', false );
					wp_die();
					return;
				}
				$this->set_product_wpid( $this->product_post_wpid );
				$upload_success =
				self::$commerce->ajax_sync_all_fb_products_using_feed( true );
				if ( $upload_success ) {
					// verification Step.
					// Wait till FB finish backend creation to prevent race condition.
					$time_start = microtime( true );
					while ( ( microtime( true ) - $time_start ) < self::MAX_SLEEP_IN_SEC ) {
						$complete = self::$commerce->fbproductfeed->is_upload_complete(
							self::$commerce->settings
						);
						if ( $complete ) {
							  break;
						} else {
								  $this->sleep_til_upload_complete( 10 );
						}
					}
					$this->sleep_til_upload_complete( 60 );
					$check_product_create = $this->check_product_create();
					if ( ! $check_product_create ) {
						self::$test_pass = 0;
					} else {
						WC_Facebookcommerce_Utils::log(
							'Test - Products create successfully.'
						);
					}
					// Clean up whatever has been created.
					// Test on_product_delete API hook.
					$clean_up = $this->clean_up();
					if ( ! $clean_up ) {
						self::$test_pass = 0;
						WC_Facebookcommerce_Utils::log(
							'Test - Fail to delete product from FB'
						);
						WC_Facebookcommerce_Utils::set_test_fail_reason(
							'Fail to delete product from FB',
							( new Exception() )->getTraceAsString()
						);
					} else {
						WC_Facebookcommerce_Utils::log(
							'Test - Delete product from FB successfully'
						);
					}
				} else {
					self::$test_pass = 0;
					WC_Facebookcommerce_Utils::log(
						'Test - Sync all products using feed, curl failed.'
					);
					WC_Facebookcommerce_Utils::set_test_fail_reason(
						'Sync all products using feed, curl failed',
						( new Exception() )->getTraceAsString()
					);
				}
			} else {
				self::$test_pass = 0;
				WC_Facebookcommerce_Utils::log(
					'Test - Fail to remove FBIDs from local DB'
				);
				WC_Facebookcommerce_Utils::set_test_fail_reason(
					'Fail to remove FBIDs from local DB',
					( new Exception() )->getTraceAsString()
				);
			}
			update_option( 'fb_test_pass', self::$test_pass );
			wp_die();
			return;
		}

		function check_product_create() {
			if ( count( self::$retailer_ids ) < 3 ) {
				WC_Facebookcommerce_Utils::log( 'Test - Failed to create 3 product items.' );
				WC_Facebookcommerce_Utils::set_test_fail_reason(
					'Failed to create 3 product items.',
					( new Exception() )->getTraceAsString()
				);
				return false;
			}

			if ( count( self::$retailer_ids ) > 3 ) {
				WC_Facebookcommerce_Utils::log(
					'Test - Failed to skip invisible products.'
				);
				WC_Facebookcommerce_Utils::set_test_fail_reason(
					'Failed to skip invisible products.',
					( new Exception() )->getTraceAsString()
				);
				return false;
			}

			// Check 3 products have been created.
			for ( $i = 0; $i < 3; $i++ ) {
				$product_type = $i == 0 ? 'Simple' : 'Variable';
				$retailer_id  = self::$retailer_ids[ $i ];
				$item_fbid    =
				$this->check_fbid_api( self::FB_PRODUCT_ITEM_ID, $retailer_id );
				$group_fbid   =
				$this->check_fbid_api( self::FB_PRODUCT_GROUP_ID, $retailer_id );
				if ( ! $item_fbid || ! $group_fbid ) {
					WC_Facebookcommerce_Utils::log(
						'Test - ' . $product_type .
						' product failed to create.'
					);
					WC_Facebookcommerce_Utils::set_test_fail_reason(
						$product_type .
						' product failed to create.',
						( new Exception() )->getTraceAsString()
					);
					return false;
				}
			}

			// Check product detailed as expected.
			$data                  = array(
				'name'                  => 'a simple product for test',
				'price'                 => '20.00',
				'description'           => 'This is to test a simple product.',
				'sale_price'            => '10.00',
				'sale_price_dates_from' =>
				  date_i18n( 'Y-m-d', strtotime( 'now' ) ) . self::MIN_TIME,
				'sale_price_dates_to'   =>
				  date_i18n( 'Y-m-d', strtotime( '+10 day' ) ) . self::MAX_TIME,
				'visibility'            => 'published',
			);
			$simple_product_result =
			$this->check_product_info( self::$retailer_ids[0], false, $data );
			if ( ! $simple_product_result ) {
				WC_Facebookcommerce_Utils::log(
					'Test - Simple product failed to match ' .
					'product details.'
				);
				WC_Facebookcommerce_Utils::set_test_fail_reason(
					'Simple product failed to'
					. ' match product details.',
					( new Exception() )->getTraceAsString()
				);
				return false;
			}

			$data                    = array(
				'name'                          => 'a variable product for test',
				'price'                         => '30.00',
				'description'                   => 'This is to test a variable product. - Red',
				'additional_variant_attributes' => array( 'value' => 'Red' ),
				'visibility'                    => 'published',
			);
			$variable_product_result =
			$this->check_product_info( self::$retailer_ids[1], true, $data );
			if ( ! $variable_product_result ) {
				WC_Facebookcommerce_Utils::log(
					'Test - Variable product failed to match product details.'
				);
				WC_Facebookcommerce_Utils::set_test_fail_reason(
					'Variable product failed to match product details.',
					( new Exception() )->getTraceAsString()
				);
				return false;
			}
			return true;
		}

		function check_fbid_api( $fbid_type, $fb_retailer_id ) {
			$product_fbid_result = self::$fbgraph->get_facebook_id(
				self::$commerce->get_product_catalog_id(),
				$fb_retailer_id,
				true
			);

			if ( is_wp_error( $product_fbid_result ) ) {
				WC_Facebookcommerce_Utils::log(
					'Test - ' . $product_fbid_result->get_error_message()
				);
					WC_Facebookcommerce_Utils::set_test_fail_reason(
						'There was an issue connecting to the Facebook API: ' .
						$product_fbid_result->get_error_message(),
						( new Exception() )->getTraceAsString()
					);
					return false;
			}

			if ( $product_fbid_result && isset( $product_fbid_result['body'] ) ) {
				$body = WC_Facebookcommerce_Utils::decode_json(
					$product_fbid_result['body'],
					true
				);
				if ( $body && isset( $body['id'] ) ) {
					if ( $fbid_type == self::FB_PRODUCT_GROUP_ID ) {
						$fb_id =
						isset( $body['product_group'] )
						? $body['product_group']['id']
						: false;
					} else {
						$fb_id = $body['id'];
					}
					return $fb_id;
				}
			}

			return false;
		}

		function check_product_info( $retailer_id, $has_variant, $data ) {
			$prod_info_result = self::$fbgraph->check_product_info(
				self::$commerce->get_product_catalog_id(),
				$retailer_id,
				$has_variant
			);
			if ( is_wp_error( $prod_info_result ) ) {
				WC_Facebookcommerce_Utils::log(
					'Test - ' . $prod_info_result->get_error_message()
				);
					WC_Facebookcommerce_Utils::set_test_fail_reason(
						'There was an issue connecting to the Facebook API: ' .
						$prod_info_result->get_error_message(),
						( new Exception() )->getTraceAsString()
					);
					return false;
			}

			$match = true;
			if ( $prod_info_result && isset( $prod_info_result['body'] ) ) {
				$body = WC_Facebookcommerce_Utils::decode_json(
					$prod_info_result['body'],
					true
				);
				if ( ! $body ) {
							return false;
				}
				if ( $body['name'] != $data['name'] ) {
					WC_Facebookcommerce_Utils::log(
						'Test - ' . $retailer_id . " doesn\'t match name."
					);
					  $match = false;
				}

				if ( $body['description'] != $data['description'] ) {
					WC_Facebookcommerce_Utils::log(
						'Test - ' . $retailer_id . " doesn\'t match description."
					);
					$match = false;
				}
				// Woo doesn't have API to return currency symbol.
				// FB graph API only support to response with a currency symbol price.
				// No php built-in function to support cast html number to symbol.
				// Compare numeric price only.
				$price = floatval( preg_replace( '/[^\d\.]+/', '', $body['price'] ) );
				if ( $price != $data['price'] ) {
					WC_Facebookcommerce_Utils::log(
						'Test - ' . $retailer_id . " doesn\'t match price."
					);
					$match = false;
				}
				// Check sale price and dates.
				if ( isset( $data['sale_price'] ) ) {
					$sale_price = floatval(
						preg_replace( '/[^\d\.]+/', '', $body['sale_price'] )
					);
					if ( $sale_price != $data['sale_price'] ) {
							WC_Facebookcommerce_Utils::log(
								'Test - ' . $retailer_id . " doesn\'t match sale price."
							);
						  $match = false;
					}
					if ( $body['sale_price_start_date'] != $data['sale_price_dates_from'] ) {
						WC_Facebookcommerce_Utils::log(
							'Test - ' . $retailer_id . " doesn\'t match sale price start date"
						);
						$match = false;
					}
					if ( $body['sale_price_end_date'] != $data['sale_price_dates_to'] ) {
						WC_Facebookcommerce_Utils::log(
							'Test - ' . $retailer_id . " doesn\'t match sale price end date."
						);
						$match = false;
					}
				}

				if ( $body['visibility'] != $data['visibility'] ) {
					WC_Facebookcommerce_Utils::log(
						'Test - ' . $retailer_id . " doesn\'t match visibility."
					);
					$match = false;
				}

				if ( $has_variant &&
				( ! isset( $body['additional_variant_attributes'] ) ||
				$body['additional_variant_attributes'][0]['value'] !=
				$data['additional_variant_attributes']['value'] ) ) {

					WC_Facebookcommerce_Utils::log(
						'Test - ' . $retailer_id . " doesn\'t match variation."
					);
					$match = false;
				}
			}
			return $match;
		}

		// Don't early return to prevent haunting product id.
		function clean_up() {
			$failure = false;
			foreach ( self::$wp_post_ids as $post_id ) {
				$delete_post_result = wp_delete_post( $post_id );
				// return false or null if failed.
				if ( ! $delete_post_result ) {
					WC_Facebookcommerce_Utils::log( 'Test - Fail to delete post ' . $post_id );
					WC_Facebookcommerce_Utils::set_test_fail_reason(
						'Fail to delete post ' . $post_id,
						( new Exception() )->getTraceAsString()
					);
					$failure = true;
				}
			}
			self::$wp_post_ids = array();

			$this->sleep_til_upload_complete( 60 );
			foreach ( self::$retailer_ids as $retailer_id ) {
				$item_fbid  =
				$this->check_fbid_api( self::FB_PRODUCT_ITEM_ID, $retailer_id );
				$group_fbid =
				$this->check_fbid_api( self::FB_PRODUCT_GROUP_ID, $retailer_id );
				if ( $item_fbid || $group_fbid ) {
					WC_Facebookcommerce_Utils::log(
						'Test - Failed to delete product ' .
						$retailer_id . ' via plugin deletion hook.'
					);
					WC_Facebookcommerce_Utils::set_test_fail_reason(
						'Failed to delete product ' . $retailer_id .
						' via plugin deletion hook.',
						( new Exception() )->getTraceAsString()
					);
					$failure = true;
				}
			}
			self::$retailer_ids = array();

			return ! $failure;
		}

		function create_data() {
			$prod_and_variant_wpid = array();
			// Gets term object from Accessories in the database.
			$term = get_term_by( 'name', 'Accessories', 'product_cat' );
			// Accessories should be a default category.
			// If not exist, set categories term first.
			if ( ! $term ) {
				$term = wp_insert_term(
					'Accessories', // the term
					'product_cat', // the taxonomy
					array(
						'slug' => 'accessories',
					)
				);
			}
			$data                  = array(
				'post_content'          => 'This is to test a simple product.',
				'post_title'            => 'a simple product for test',
				'post_status'           => 'publish',
				'post_type'             => 'product',
				'term'                  => $term,
				'price'                 => 20,
				'sale_price'            => 10,
				'sale_price_dates_from' => strtotime( 'now' ),
				'sale_price_dates_to'   => strtotime( '+10 day' ),
			);
			$simple_product_result =
			$this->create_test_simple_product( $data, $prod_and_variant_wpid );

			if ( ! $simple_product_result ) {
				return false;
			}

			// Test an invisible product - invisible products won't be synced by feed.
			$data['visibility']    = false;
			$simple_product_result =
			$this->create_test_simple_product( $data, $prod_and_variant_wpid );

			if ( ! $simple_product_result ) {
				return false;
			}

			$data['post_content'] = 'This is to test a variable product.';
			$data['post_title']   = 'a variable product for test';
			$data['price']        = 30;

			// Test variable products.
			$variable_product_result =
			$this->create_test_variable_product( $data, $prod_and_variant_wpid );
			if ( ! $variable_product_result ) {
				return false;
			}
			return $prod_and_variant_wpid;
		}

		function create_test_simple_product( $data, &$prod_and_variant_wpid ) {
			$post_id = $this->fb_insert_post( $data, 'Simple' );
			if ( ! $post_id ) {
				return false;
			}
			array_push( $prod_and_variant_wpid, $post_id );
			update_post_meta( $post_id, '_regular_price', $data['price'] );
			update_post_meta( $post_id, '_sale_price', $data['sale_price'] );
			update_post_meta( $post_id, '_sale_price_dates_from', $data['sale_price_dates_from'] );
			update_post_meta( $post_id, '_sale_price_dates_to', $data['sale_price_dates_to'] );

			wp_set_object_terms( $post_id, 'simple', 'product_type' );
			// Invisible products won't be synced by feed.
			if ( isset( $data['visibility'] ) ) {
				$terms = array( 'exclude-from-catalog', 'exclude-from-search' );
				wp_set_object_terms( $post_id, $terms, 'product_visibility' );
			} else {
				array_push( self::$wp_post_ids, $post_id );
				array_push( self::$retailer_ids, 'wc_post_id_' . $post_id );
			}

			$product = wc_get_product( $post_id );
			$product->set_stock_status( 'instock' );
			wp_set_object_terms( $post_id, $data['term']->term_id, 'product_cat' );
			return true;
		}

		function create_test_variable_product( $data, &$prod_and_variant_wpid ) {
			$post_id = $this->fb_insert_post( $data, 'Variable' );
			if ( ! $post_id ) {
				return false;
			}

			wp_set_object_terms( $post_id, 'variable', 'product_type' );
			array_push( $prod_and_variant_wpid, $post_id );
			array_push( self::$wp_post_ids, $post_id );
			// Gets term object from Accessories in the database.
			$term = get_term_by( 'name', 'Accessories', 'product_cat' );
			wp_set_object_terms( $post_id, $term->term_id, 'product_cat' );

			// Set up attributes.
			$avail_attribute_values = array(
				'Red',
				'Blue',
			);
			wp_set_object_terms( $post_id, $avail_attribute_values, 'pa_color' );
			$thedata = array(
				'pa_color' => array(
					'name'         => 'pa_color',
					'value'        => '',
					'is_visible'   => '1',
					'is_variation' => '1',
					'is_taxonomy'  => '1',
				),
			);
			update_post_meta( $post_id, '_product_attributes', $thedata );

			// Insert variations.
			$variation_data = array(
				'post_content' => 'This is to test a variable product. - Red',
				'post_status'  => 'publish',
				'post_type'    => 'product_variation',
				'post_parent'  => $post_id,
				'price'        => 30,
			);
			$variation_red  = $this->fb_insert_post( $variation_data, 'Variation' );
			if ( ! $variation_red ) {
				return;
			}

			$this->fb_update_variation_meta(
				$prod_and_variant_wpid,
				$variation_red,
				'Red',
				$variation_data
			);

			$variation_data['post_content'] = 'a variable product for test - Blue';
			$variation_blue                 = $this->fb_insert_post( $variation_data, 'Variatoin' );
			if ( ! $variation_blue ) {
				  return false;
			}
			$this->fb_update_variation_meta(
				$prod_and_variant_wpid,
				$variation_blue,
				'Blue',
				$variation_data
			);
			$product = wc_get_product( $variation_blue );
			$product->set_stock_status( 'instock' );
			wp_set_object_terms( $variation_blue, 'variation', 'product_type' );
			return true;
		}

		function fb_update_variation_meta(
		&$prod_and_variant_wpid,
		$variation_id,
		$value,
		$data ) {
			array_push( $prod_and_variant_wpid, $variation_id );
			array_push( self::$retailer_ids, 'wc_post_id_' . $variation_id );

			$attribute_term = get_term_by( 'name', $value, 'pa_color' );

			update_post_meta( $variation_id, 'attribute_pa_color', $attribute_term->slug );
			update_post_meta( $variation_id, '_price', $data['price'] );
			update_post_meta( $variation_id, '_regular_price', $data['price'] );
			wp_set_object_terms( $variation_id, 'variation', 'product_type' );
			$product = wc_get_product( $variation_id );
			$product->set_stock_status( 'instock' );
		}

		function fb_insert_post( $data, $p_type ) {
			$postarr = array_intersect_key(
				$data,
				array_flip(
					array(
						'post_content',
						'post_title',
						'post_status',
						'post_type',
						'post_parent',
					)
				)
			);
			$post_id = wp_insert_post( $postarr );
			if ( is_wp_error( $post_id ) ) {
				WC_Facebookcommerce_Utils::log(
					'Test - ' . $p_type .
					' product wp_insert_post' . 'failed: ' . json_encode( $post_id )
				);
					return false;
			} else {
				return $post_id;
			}
		}

		/**
		 * IMPORTANT! Wait for Ents creation and prevent race condition.
		 **/
		function sleep_til_upload_complete( $sec ) {
			sleep( $sec );
		}

		function set_product_wpid( $product_post_wpid ) {
			WC_Facebook_Product_Feed_Test_Mock::$product_post_wpid = $product_post_wpid;
		}
	}

endif;
