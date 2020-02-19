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

if ( ! class_exists( 'WC_Facebook_Product_Feed' ) ) :

	/**
	 * Initial Sync by Facebook feed class
	 */
	class WC_Facebook_Product_Feed {

		const FACEBOOK_CATALOG_FEED_FILEPATH = '/wp-content/uploads/fbe_product_catalog.csv';
		const FACEBOOK_CATALOG_FEED_DRYFUN_FILEPATH = '/wp-content/uploads/fbe_feed_dryrun.txt';
		const FACEBOOK_FEED_RUNTIME_AVG = 'facebook_feed_runtime_avg';
		const FACEBOOK_THRESHOLD_FOR_DRY_RUN_FEED = 500;
		const FACEBOOK_GEN_FEED_BUFFER_TIME = 30;
		const FB_ADDITIONAL_IMAGES_FOR_FEED  = 5;
		const FEED_NAME                      = 'Initial product sync from WooCommerce. DO NOT DELETE.';
		const FB_PRODUCT_GROUP_ID            = 'fb_product_group_id';
		const FB_VISIBILITY                  = 'fb_visibility';

		private $has_default_product_count = 0;
		private $no_default_product_count  = 0;

		public function __construct(
		$facebook_catalog_id = null,
		$fbgraph = null,
		$feed_id = null ) {
			$this->facebook_catalog_id = $facebook_catalog_id;
			$this->fbgraph             = $fbgraph;
			$this->feed_id             = $feed_id;
		}

		public function gen_feed( $from_ping = false ) {
			try {
				$product_feed_full_file_name = $this->get_local_product_feed_file_path();
				$isStale = $this->checkIsFeedFileStale( $product_feed_full_file_name );
				if ( !$isStale ) {
					WC_Facebookcommerce_Utils::log( 'feed file is not stale, skip generation' );
				} else {
					if ( is_file( $product_feed_full_file_name ) ) {
						unlink( $product_feed_full_file_name );
					}
					$start_time = microtime( true );
					$this->generate_productfeed_file();
					$time_spent = microtime( true ) - $start_time;
					$this->estimateFeedGenerationTimeWithDecay( $time_spent );
					WC_Facebookcommerce_Utils::log( 'gen_feed success' );
				}
			} catch (Exception $e) {
				WC_Facebookcommerce_Utils::log( json_encode( $e->getMessage() ) );
			}
			if ( $from_ping ) {
				return;
			}
			return $this->sendFileResponse( $product_feed_full_file_name );
		}

		public function gen_feed_ping() {
			$time = $this->estimateFeedGenerationTime();
			$this->gen_feed( true );
			return $time;
		}

		private function sendFileResponse($filename) {
			if (!headers_sent()) {
				header('Content-Type: text/csv; charset=utf-8');
				header('Content-Disposition: attachment; filename="'.basename($filename.'"'));
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length:'.filesize($filename));

				if ( ob_get_level() ){
					ob_end_clean();
				}

				readfile($filename, 'rb');

				exit();
			}
		}

		public function sync_all_products_using_feed() {
			$start_time = microtime( true );
			$this->log_feed_progress( 'Sync all products using feed' );

			if ( ! is_writable( dirname( __FILE__ ) ) ) {
				$this->log_feed_progress(
					'Failure - Sync all products using feed, folder is not writable'
				);
				return false;
			}

			if ( ! $this->generate_productfeed_file() ) {
				$this->log_feed_progress(
					'Failure - Sync all products using feed, feed file not generated'
				);
				return false;
			}
			$this->log_feed_progress( 'Sync all products using feed, feed file generated' );

			$total_product_count        =
			$this->has_default_product_count + $this->no_default_product_count;
			$default_product_percentage =
			( $total_product_count == 0 || $this->has_default_product_count == 0 )
			? 0
			: $this->has_default_product_count / $total_product_count * 100;
			$time_spent                 = microtime( true ) - $start_time;
			$data                       = array();
			// Only log performance if this store has products in order to get average
			// performance.
			if ( $total_product_count != 0 ) {
				$data = array(
					'sync_time'                  => $time_spent,
					'total'                      => $total_product_count,
					'default_product_percentage' => $default_product_percentage,
				);
			}
			$this->log_feed_progress( 'Complete - Sync all products using feed.', $data );
			return true;
		}

		public function get_local_product_feed_file_path() {
			$index = strrpos( dirname( __FILE__ ), '/wp-content/' );
			$prefix = substr( dirname( __FILE__ ), 0, $index );
			$full_path = $prefix . self::FACEBOOK_CATALOG_FEED_FILEPATH;
			return $full_path;
		}

		private function get_local_dryrun_product_feed_file_path() {
			$index = strrpos( dirname( __FILE__ ), '/wp-content/' );
			$prefix = substr( dirname( __FILE__ ), 0, $index );
			$full_path = $prefix . self::FACEBOOK_CATALOG_FEED_DRYFUN_FILEPATH;
			return $full_path;
		}

		private function get_product_ids() {
			$post_ids           = $this->get_product_wpid();
			$all_parent_product = array_map(
				function( $post_id ) {
					if ( get_post_type( $post_id ) == 'product_variation' ) {
						return wp_get_post_parent_id( $post_id );
					}
				},
				$post_ids
			);
			$all_parent_product = array_filter( array_unique( $all_parent_product ) );
			$product_ids        = array_diff( $post_ids, $all_parent_product );
			return $product_ids;
		}

		public function generate_productfeed_file( $is_dryrun = false ) {
			$this->log_feed_progress( 'Generating product feed file' );
			$product_ids = $this->get_product_ids();
			$feed_file = $is_dryrun
				? fopen( $this->get_local_dryrun_product_feed_file_path(), 'ab' )
				: fopen( $this->get_local_product_feed_file_path(), 'w' );
			return $this->write_product_feed_file( $product_ids, $feed_file );
		}

		public function write_product_feed_file( $wp_ids, $feed_file ) {
			try {
				fwrite( $feed_file, $this->get_product_feed_header_row() );

				$product_group_attribute_variants = array();
				foreach ( $wp_ids as $wp_id ) {
					$woo_product = new WC_Facebook_Product( $wp_id );
					if ( $woo_product->is_hidden() ) {
						continue;
					}
					if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' &&
					! $woo_product->is_in_stock() ) {
						continue;
					}
					$product_data_as_feed_row = $this->prepare_product_for_feed(
						$woo_product,
						$product_group_attribute_variants
					);
					fwrite( $feed_file, $product_data_as_feed_row );
				}
				fclose( $feed_file );
				wp_reset_postdata();
				return true;
			} catch ( Exception $e ) {
				WC_Facebookcommerce_Utils::log( json_encode( $e->getMessage() ) );
				fclose( $feed_file );
				return false;
			}
		}

		public function estimateFeedGenerationTime() {
			// Estimate = MAX (Appx Time to Gen 500 Products + 30 , Last Runtime + 20)
			$time_estimate = $this->estimateGenerationTime();
			$time_previous_avg = $this->get_avg_feed_generation_time();
			return max( $time_estimate, $time_previous_avg );
		}

		private function estimateGenerationTime() {
			// Appx Time to Gen 500 products + 30
			$total_num_of_products = count( $this->get_product_ids() );
			$num_of_samples = $total_num_of_products <= self:: FACEBOOK_THRESHOLD_FOR_DRY_RUN_FEED
			  ? $total_num_of_products
			  : self:: FACEBOOK_THRESHOLD_FOR_DRY_RUN_FEED;
			if ( $num_of_samples == 0 ) {
			  return self::FACEBOOK_GEN_FEED_BUFFER_TIME;
			}

			$start_time = time();
			$this->generate_productfeed_file( true );
			$end_time = time();
			$time_spent = $end_time - $start_time;

			// Estimated Time =
			// 150% of Linear extrapolation of the time to generate 500 products
			// + 30 seconds of buffer time.
			$time_estimate = $time_spent * $total_num_of_products / $num_of_samples * 1.5 + self::FACEBOOK_GEN_FEED_BUFFER_TIME;
			WC_Facebookcommerce_Utils::log( 'Feed Generation Time Estimate: '. $time_estimate );
			return $time_estimate;
		}

		private function estimateFeedGenerationTimeWithDecay( $feed_gen_time ) {
			// Update feed generation online time estimate w/ 25% decay.
			$old_feed_gen_time = $this->get_avg_feed_generation_time();
			if ( $feed_gen_time < $old_feed_gen_time ) {
			  $feed_gen_time = $feed_gen_time * 0.25 + $old_feed_gen_time * 0.75;
			}
			$this->set_avg_feed_generation_time( $feed_gen_time );
		}

		private function checkIsFeedFileStale( $filename ) {
			$time_file_modified = file_exists( $filename )
				? filemtime( $filename )
				: 0;

			// if we get no file modified time, or the modified time is 8hours ago,
			// we count it as stale
			if ( !$time_file_modified ) {
			  return true;
			} else {
			  return time() - $time_file_modified > 8*3600;
			}
		}

		private function get_avg_feed_generation_time() {
			return (int)get_transient( self::FACEBOOK_FEED_RUNTIME_AVG );
		}

		private function set_avg_feed_generation_time( $time ) {
			set_transient( self::FACEBOOK_FEED_RUNTIME_AVG, $time );
		}

		public function get_product_feed_header_row() {
			return 'id,title,description,image_link,link,product_type,' .
			'brand,price,availability,item_group_id,checkout_url,' .
			'additional_image_link,sale_price_effective_date,sale_price,condition,' .
			'visibility,default_product,variant' . PHP_EOL;
		}

		/**
		 * Assemble product payload in feed upload for initial sync.
		 **/
		private function prepare_product_for_feed(
		$woo_product,
		&$attribute_variants ) {
			$product_data  = $woo_product->prepare_product( null, true );
			$item_group_id = $product_data['retailer_id'];
			// prepare variant column for variable products
			$product_data['variant'] = '';
			if (
			WC_Facebookcommerce_Utils::is_variation_type( $woo_product->get_type() )
			) {
				$parent_id = $woo_product->get_parent_id();

				if ( ! isset( $attribute_variants[ $parent_id ] ) ) {
					$parent_product = new WC_Facebook_Product( $parent_id );

					$gallery_urls                                  = array_filter( $parent_product->get_gallery_urls() );
					$variation_id                                  = $parent_product->find_matching_product_variation();
					$variants_for_group                            = $parent_product->prepare_variants_for_group( true );
					$parent_attribute_values                       = array();
					$parent_attribute_values['gallery_urls']       = $gallery_urls;
					$parent_attribute_values['default_variant_id'] = $variation_id;
					$parent_attribute_values['item_group_id']      =
					WC_Facebookcommerce_Utils::get_fb_retailer_id( $parent_product );
					foreach ( $variants_for_group as $variant ) {
						$parent_attribute_values[ $variant['product_field'] ] =
						$variant['options'];
					}
					// cache product group variants
					$attribute_variants[ $parent_id ] = $parent_attribute_values;
				}
				$parent_attribute_values = $attribute_variants[ $parent_id ];
				$variants_for_item       =
				$woo_product->prepare_variants_for_item( $product_data );
				$variant_feed_column     = array();
				foreach ( $variants_for_item as $variant_array ) {
					static::format_variant_for_feed(
						$variant_array['product_field'],
						$variant_array['options'][0],
						$parent_attribute_values,
						$variant_feed_column
					);
				}
				if ( isset( $product_data['custom_data'] ) ) {
					foreach ( $product_data['custom_data'] as $product_field => $value ) {
						static::format_variant_for_feed(
							$product_field,
							$value,
							$parent_attribute_values,
							$variant_feed_column
						);
					}
				}
				if ( ! empty( $variant_feed_column ) ) {
					$product_data['variant'] =
					'"' . implode( ',', $variant_feed_column ) . '"';
				}
				if ( isset( $parent_attribute_values['gallery_urls'] ) ) {
					$product_data['additional_image_urls'] =
					array_merge(
						$product_data['additional_image_urls'],
						$parent_attribute_values['gallery_urls']
					);
				}
				if ( isset( $parent_attribute_values['item_group_id'] ) ) {
					$item_group_id = $parent_attribute_values['item_group_id'];
				}

				$product_data['default_product'] =
				$parent_attribute_values['default_variant_id'] == $woo_product->id
				? 'default'
				: '';

				// If this group has default variant value, log this product item
				if ( isset( $parent_attribute_values['default_variant_id'] ) &&
				! empty( $parent_attribute_values['default_variant_id'] ) ) {
					$this->has_default_product_count++;
				} else {
					$this->no_default_product_count++;
				}
			}

			// log simple product
			if ( ! isset( $product_data['default_product'] ) ) {
				$this->no_default_product_count++;
				$product_data['default_product'];
			}

			return $product_data['retailer_id'] . ',' .
			static::format_string_for_feed( $product_data['name'] ) . ',' .
			static::format_string_for_feed( $product_data['description'] ) . ',' .
			$product_data['image_url'] . ',' .
			$product_data['url'] . ',' .
			static::format_string_for_feed( $product_data['category'] ) . ',' .
			static::format_string_for_feed( $product_data['brand'] ) . ',' .
			static::format_price_for_feed(
				$product_data['price'],
				$product_data['currency']
			) . ',' .
			$product_data['availability'] . ',' .
			$item_group_id . ',' .
			$product_data['checkout_url'] . ',' .
			static::format_additional_image_url(
				$product_data['additional_image_urls']
			) . ',' .
			$product_data['sale_price_start_date'] . '/' .
			$product_data['sale_price_end_date'] . ',' .
			static::format_price_for_feed(
				$product_data['sale_price'],
				$product_data['currency']
			) . ',' .
			'new' . ',' .
			$product_data['visibility'] . ',' .
			$product_data['default_product'] . ',' .
			$product_data['variant'] . PHP_EOL;
		}

		private static function format_additional_image_url( $product_image_urls ) {
			// returns the top 10 additional image urls separated by a comma
			// according to feed api rules
			$product_image_urls = array_slice(
				$product_image_urls,
				0,
				self::FB_ADDITIONAL_IMAGES_FOR_FEED
			);
			if ( $product_image_urls ) {
				return '"' . implode( ',', $product_image_urls ) . '"';
			} else {
				return '';
			}
		}

		private static function format_string_for_feed( $text ) {
			if ( (bool) $text ) {
				return '"' . str_replace( '"', "'", $text ) . '"';
			} else {
				return '';
			}
		}

		private static function format_price_for_feed( $value, $currency ) {
			return (string) ( round( $value / (float) 100, 2 ) ) . $currency;
		}

		private static function format_variant_for_feed(
		$product_field,
		$value,
		$parent_attribute_values,
		&$variant_feed_column ) {
			if ( ! array_key_exists( $product_field, $parent_attribute_values ) ) {
				return;
			}
			array_push(
				$variant_feed_column,
				$product_field . ':' .
				implode( '/', $parent_attribute_values[ $product_field ] ) . ':' .
				$value
			);
		}

		public function is_upload_complete( &$settings ) {
			$result = $this->fbgraph->get_upload_status( $settings['fb_upload_id'] );
			if ( is_wp_error( $result ) || ! isset( $result['body'] ) ) {
				 $this->log_feed_progress( json_encode( $result ) );
				 return 'error';
			}
			$decode_result = WC_Facebookcommerce_Utils::decode_json( $result['body'], true );
			$end_time      = $decode_result['end_time'];
			if ( isset( $end_time ) ) {
				$settings['upload_end_time'] = $end_time;
				return 'complete';
			} else {
				return 'in progress';
			}
		}

		// Log progress in local log file and FB.
		public function log_feed_progress( $msg, $object = array() ) {
			WC_Facebookcommerce_Utils::fblog( $msg, $object );
			$msg = empty( $object ) ? $msg : $msg . json_encode( $object );
			WC_Facebookcommerce_Utils::log( $msg );
		}

		public function get_product_wpid() {
			$post_ids = WC_Facebookcommerce_Utils::get_wp_posts(
				null,
				null,
				array( 'product', 'product_variation' )
			);
			return $post_ids;
		}
	}

endif;
