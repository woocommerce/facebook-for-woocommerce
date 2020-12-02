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

use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\Facebook\Products\Feed;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;

if ( ! class_exists( 'WC_Facebook_Product_Feed' ) ) :

	/**
	 * Initial Sync by Facebook feed class
	 */
	class WC_Facebook_Product_Feed {


		/** @var string transient name for storing the average feed generation time */
		const TRANSIENT_AVERAGE_FEED_GENERATION_TIME = 'wc_facebook_average_feed_generation_time';

		/** @var string product catalog feed file directory inside the uploads folder */
		const UPLOADS_DIRECTORY = 'facebook_for_woocommerce';

		/** @var string product catalog feed file name - %s will be replaced with a hash */
		const FILE_NAME = 'product_catalog_%s.csv';


		const FACEBOOK_CATALOG_FEED_FILENAME = 'fae_product_catalog.csv';
		const FB_ADDITIONAL_IMAGES_FOR_FEED  = 5;
		const FEED_NAME                      = 'Initial product sync from WooCommerce. DO NOT DELETE.';
		const FB_PRODUCT_GROUP_ID            = 'fb_product_group_id';
		const FB_VISIBILITY                  = 'fb_visibility';

		private $has_default_product_count = 0;
		private $no_default_product_count  = 0;

		/**
		 * WC_Facebook_Product_Feed constructor.
		 *
		 * @param string|null $facebook_catalog_id Facebook catalog ID, if any
		 * @param \WC_Facebookcommerce_Graph_API|null $fbgraph Facebook Graph API instance
		 * @param string|null $feed_id Facebook feed ID, if any
		 */
		public function __construct( $facebook_catalog_id = null, $fbgraph = null, $feed_id = null ) {

			$this->facebook_catalog_id = $facebook_catalog_id;
			$this->fbgraph             = $fbgraph;
			$this->feed_id             = $feed_id;
		}


		/**
		 * Schedules a new feed generation.
		 *
		 * @since 1.11.0
		 */
		public function schedule_feed_generation() {

			// don't schedule another if one's already scheduled or in progress
			if ( false !== as_next_scheduled_action( 'wc_facebook_generate_product_catalog_feed', [], 'facebook-for-woocommerce' ) ) {
				return;
			}

			\WC_Facebookcommerce_Utils::log( 'Scheduling product catalog feed file generation' );

			// if async priority actions are supported (AS 3.0+)
			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action( 'wc_facebook_generate_product_catalog_feed', [], 'facebook-for-woocommerce' );
			} else {
				as_schedule_single_action( time(), 'wc_facebook_generate_product_catalog_feed', [], 'facebook-for-woocommerce' );
			}
		}


		/**
		 * Generates the product catalog feed.
		 *
		 * This replaces any previously generated feed file.
		 *
		 * @since 1.11.0
		 */
		public function generate_feed() {

			\WC_Facebookcommerce_Utils::log( 'Generating a fresh product feed file' );

			try {

				$start_time = microtime( true );

				$this->generate_productfeed_file();

				$generation_time = microtime( true ) - $start_time;

				$this->set_feed_generation_time_with_decay( $generation_time );

				\WC_Facebookcommerce_Utils::log( 'Product feed file generated' );

			} catch ( \Exception $exception ) {

				\WC_Facebookcommerce_Utils::log( $exception->getMessage() );
			}
		}


		/**
		 * Sets the average feed generation time with a 25% decay.
		 *
		 * @since 1.11.0
		 *
		 * @param float $generation_time last generation time
		 */
		private function set_feed_generation_time_with_decay( $generation_time ) {

			// update feed generation time estimate w/ 25% decay.
			$existing_generation_time = $this->get_average_feed_generation_time();

			if ( $generation_time < $existing_generation_time ) {
				$generation_time = $generation_time * 0.25 + $existing_generation_time * 0.75;
			}

			$this->set_average_feed_generation_time( $generation_time );
		}


		/**
		 * Sets the average feed generation time.
		 *
		 * @since 1.11.0
		 *
		 * @param float $time generation time
		 */
		private function set_average_feed_generation_time( $time ) {

			set_transient( self::TRANSIENT_AVERAGE_FEED_GENERATION_TIME, $time );
		}


		/**
		 * Gets the estimated feed generation time.
		 *
		 * Performs a dry run and returns either the dry run time or last average estimated time, whichever is higher.
		 *
		 * @since 1.11.0
		 *
		 * @return int
		 */
		public function get_estimated_feed_generation_time() {

			$estimate = $this->estimate_generation_time();
			$average  = $this->get_average_feed_generation_time();

			return (int) max( $estimate, $average );
		}


		/**
		 * Estimates the feed generation time.
		 *
		 * Runs a dry-run generation of a subset of products, then extrapolates that out to the full catalog size. Also
		 * adds a bit of buffer time.
		 *
		 * @since 1.11.0
		 *
		 * @return float
		 */
		private function estimate_generation_time() {

			$product_ids    = $this->get_product_ids();
			$total_products = count( $product_ids );
			$sample_size    = $this->get_feed_generation_estimate_sample_size();
			$buffer_time    = $this->get_feed_generation_buffer_time();

			if ( $total_products > 0 ) {

				if ( $total_products < $sample_size ) {

					$sample_size = $total_products;

				} else {

					$product_ids = array_slice( $product_ids, 0, $sample_size );
				}

				$start_time = microtime( true );

				$this->write_product_feed_file( $product_ids, true );

				$end_time = microtime( true );

				$time_spent = $end_time - $start_time;

				// estimated Time = 150% of Linear extrapolation of the time to generate n products +  buffer time.
				$time_estimate = $time_spent * $total_products / $sample_size * 1.5 + $buffer_time;

			} else {

				$time_estimate = $buffer_time;
			}

			WC_Facebookcommerce_Utils::log( 'Feed Generation Time Estimate: '. $time_estimate );

			return $time_estimate;
		}


		/**
		 * Gets the average feed generation time.
		 *
		 * @since 1.11.0
		 *
		 * @return float
		 */
		private function get_average_feed_generation_time() {

			return get_transient( self::TRANSIENT_AVERAGE_FEED_GENERATION_TIME );
		}


		/**
		 * Gets the number of products to use when estimating the feed file generation time.
		 *
		 * @since 1.11.0
		 *
		 * @return int
		 */
		private function get_feed_generation_estimate_sample_size() {

			/**
			 * Filters the number of products to use when estimating the feed file generation time.
			 *
			 * @since 1.11.0
			 *
			 * @param int $sample_size number of products to use when estimating the feed file generation time
			 */
			$sample_size = (int) apply_filters( 'wc_facebook_product_catalog_feed_generation_estimate_sample_size', 200 );

			return max( $sample_size, 100 );
		}


		/**
		 * Gets the number of seconds to add as a buffer when estimating the feed file generation time.
		 *
		 * @since 1.11.0
		 *
		 * @return int
		 */
		private function get_feed_generation_buffer_time() {

			/**
			 * Filters the number of seconds to add as a buffer when estimating the feed file generation time.
			 *
			 * @since 1.11.0
			 *
			 * @param int $time number of seconds to add as a buffer when estimating the feed file generation time
			 */
			$buffer_time = (int) apply_filters( 'wc_facebook_product_catalog_feed_generation_buffer_time', 30 );

			return max( $buffer_time, 5 );
		}


		/**
		 * Gets the product catalog feed file path.
		 *
		 * @since 1.11.0
		 *
		 * @return string
		 */
		public function get_file_path() {

			/**
			 * Filters the product catalog feed file path.
			 *
			 * @since 1.11.0
			 *
			 * @param string $file_path the file path
			 */
			return apply_filters( 'wc_facebook_product_catalog_feed_file_path', "{$this->get_file_directory()}/{$this->get_file_name()}" );
		}


		/**
		 * Gets the product catalog temporary feed file path.
		 *
		 * @since 1.11.3
		 *
		 * @return string
		 */
		public function get_temp_file_path() {

			/**
			 * Filters the product catalog temporary feed file path.
			 *
			 * @since 1.11.3
			 *
			 * @param string $file_path the temporary file path
			 */
			return apply_filters( 'wc_facebook_product_catalog_temp_feed_file_path', "{$this->get_file_directory()}/{$this->get_temp_file_name()}" );
		}


		/**
		 * Gets the product catalog feed file directory.
		 *
		 * @since 1.11.0
		 *
		 * @return string
		 */
		public function get_file_directory() {

			$uploads_directory = wp_upload_dir( null, false );

			return trailingslashit( $uploads_directory['basedir'] ) . self::UPLOADS_DIRECTORY;
		}


		/**
		 * Gets the product catalog feed file name.
		 *
		 * @since 1.11.0
		 *
		 * @return string
		 */
		public function get_file_name() {

			$file_name = sprintf( self::FILE_NAME, wp_hash( Feed::get_feed_secret() ) );

			/**
			 * Filters the product catalog feed file name.
			 *
			 * @since 1.11.0
			 *
			 * @param string $file_name the file name
			 */
			return apply_filters( 'wc_facebook_product_catalog_feed_file_name', $file_name );
		}


		/**
		 * Gets the product catalog temporary feed file name.
		 *
		 * @since 1.11.3
		 *
		 * @return string
		 */
		public function get_temp_file_name() {

			$file_name = sprintf( self::FILE_NAME, 'temp_' . wp_hash( Feed::get_feed_secret() ) );

			/**
			 * Filters the product catalog temporary feed file name.
			 *
			 * @since 1.11.3
			 *
			 * @param string $file_name the temporary file name
			 */
			return apply_filters( 'wc_facebook_product_catalog_temp_feed_file_name', $file_name );
		}


		public function sync_all_products_using_feed() {
			$start_time = microtime( true );
			$this->log_feed_progress( 'Sync all products using feed' );

			try {

				if ( ! $this->generate_productfeed_file() ) {
					throw new Framework\SV_WC_Plugin_Exception( 'Feed file not generated' );
				}

			} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

				$this->log_feed_progress(
					'Failure - Sync all products using feed. ' . $exception->getMessage()
				);
				return false;
			}

			$this->log_feed_progress( 'Sync all products using feed, feed file generated' );

			if ( ! $this->feed_id ) {
				$this->feed_id = $this->create_feed();
				if ( ! $this->feed_id ) {
					$this->log_feed_progress(
						'Failure - Sync all products using feed, facebook feed not created'
					);
					return false;
				}
				$this->log_feed_progress(
					'Sync all products using feed, facebook feed created'
				);
			} else {
				$this->log_feed_progress(
					'Sync all products using feed, facebook feed already exists.'
				);
			}

			$this->upload_id = $this->create_upload( $this->feed_id );
			if ( ! $this->upload_id ) {
				$this->log_feed_progress(
					'Failure - Sync all products using feed, facebook upload not created'
				);
				return false;
			}
			$this->log_feed_progress(
				'Sync all products using feed, facebook upload created'
			);

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


		/**
		 * Gets the product IDs that will be included in the feed file.
		 *
		 * @since 1.11.0
		 *
		 * @return int[]
		 */
		private function get_product_ids() {

			$post_ids = $this->get_product_wpid();

			// remove variations with unpublished parents
			$post_ids = array_filter( $post_ids,
				function ( $post_id ) {
					return ( 'product_variation' !== get_post_type( $post_id )
					         || 'publish' === get_post( wp_get_post_parent_id( $post_id ) )->post_status );
				}
			);

			$all_parent_product = array_map(
				function( $post_id ) {
					if ( 'product_variation' === get_post_type( $post_id ) ) {
						return wp_get_post_parent_id( $post_id );
					}
				},
				$post_ids
			);

			$all_parent_product = array_filter( array_unique( $all_parent_product ) );

			return array_diff( $post_ids, $all_parent_product );
		}


		/**
		 * Generates the product catalog feed file.
		 *
		 * @return bool
		 * @throws Framework\SV_WC_Plugin_Exception
		 */
		public function generate_productfeed_file() {

			if ( ! wp_mkdir_p( $this->get_file_directory() ) ) {
				throw new Framework\SV_WC_Plugin_Exception( __( 'Could not create product catalog feed directory', 'facebook-for-woocommerce' ), 500 );
			}

			$this->create_files_to_protect_product_feed_directory();

			return $this->write_product_feed_file( $this->get_product_ids() );
		}


		/**
		 * Creates files in the catalog feed directory to prevent directory listing and hotlinking.
		 *
		 * @since 1.11.0
		 */
		private function create_files_to_protect_product_feed_directory() {

			$catalog_feed_directory = trailingslashit( $this->get_file_directory() );

			$files = [
				[
					'base'    => $catalog_feed_directory,
					'file'    => 'index.html',
					'content' => '',
				],
				[
					'base'    => $catalog_feed_directory,
					'file'    => '.htaccess',
					'content' => 'deny from all',
				],
			];

			foreach ( $files as $file ) {

				if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {

					if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {

						fwrite( $file_handle, $file['content'] );
						fclose( $file_handle );
					}
				}
			}
		}


		/**
		 * Writes the product catalog feed file with data for the given product IDs.
		 *
		 * @since 1.11.0
		 *
		 * @param int[] $wp_ids product IDs
		 * @param bool $is_dry_run whether this is a dry run or the file should be written
		 * @return bool
		 */
		public function write_product_feed_file( $wp_ids, $is_dry_run = false ) {

			try {


				if ( ! $is_dry_run ) {

					$temp_file_path = $this->get_temp_file_path();
					$temp_feed_file = @fopen( $temp_file_path, 'w' );

					// check if we can open the temporary feed file
					if ( false === $temp_feed_file || ! is_writable( $temp_file_path ) ) {
						throw new Framework\SV_WC_Plugin_Exception( __( 'Could not open the product catalog temporary feed file for writing', 'facebook-for-woocommerce' ), 500 );
					}

					$file_path = $this->get_file_path();

					// check if we will be able to write to the final feed file
					if ( file_exists( $file_path ) && ! is_writable( $file_path ) ) {
						throw new Framework\SV_WC_Plugin_Exception( __( 'Could not open the product catalog feed file for writing', 'facebook-for-woocommerce' ), 500 );
					}

					fwrite( $temp_feed_file, $this->get_product_feed_header_row() );
				}

				$product_group_attribute_variants = array();

				foreach ( $wp_ids as $wp_id ) {

					$woo_product = new WC_Facebook_Product( $wp_id );

					// skip if we don't have a valid product object
					if ( ! $woo_product->woo_product instanceof \WC_Product ) {
						continue;
					}

					// skip if not enabled for sync
					if ( ! Products::product_should_be_synced( $woo_product->woo_product ) ) {
						continue;
					}

					$product_data_as_feed_row = $this->prepare_product_for_feed(
						$woo_product,
						$product_group_attribute_variants
					);

					if ( ! empty( $temp_feed_file ) ) {
						fwrite( $temp_feed_file, $product_data_as_feed_row );
					}
				}

				wp_reset_postdata();


				if ( ! empty( $temp_feed_file ) ) {
					fclose( $temp_feed_file );
				}

				if ( ! empty( $temp_file_path ) && ! empty( $file_path ) && ! empty( $temp_feed_file ) ) {

					$renamed = rename( $temp_file_path, $file_path );

					if ( empty( $renamed ) ) {
						throw new Framework\SV_WC_Plugin_Exception( __( 'Could not rename the product catalog feed file', 'facebook-for-woocommerce' ), 500 );
					}
				}

				$written = true;

			} catch ( Exception $e ) {

				WC_Facebookcommerce_Utils::log( json_encode( $e->getMessage() ) );

				$written = false;

				// close the temporary file
				if ( ! empty( $temp_feed_file ) && is_resource( $temp_feed_file ) ) {

					fclose( $temp_feed_file );
				}

				// delete the temporary file
				if ( ! empty( $temp_file_path ) && file_exists( $temp_file_path ) ) {

					unlink( $temp_file_path );
				}
			}

			return $written;
		}

		public function get_product_feed_header_row() {
			return 'id,title,description,image_link,link,product_type,' .
			'brand,price,availability,item_group_id,checkout_url,' .
			'additional_image_link,sale_price_effective_date,sale_price,condition,' .
			'visibility,gender,color,size,pattern,google_product_category,default_product,variant' . PHP_EOL;
		}


		/**
		 * Assembles product payload in feed upload for initial sync.
		 *
		 * @param \WC_Facebook_Product $woo_product WooCommerce product object normalized by Facebook
		 * @param array $attribute_variants passed by reference
		 * @return string product feed line data
		 */
		private function prepare_product_for_feed( $woo_product, &$attribute_variants ) {

			$product_data  = $woo_product->prepare_product( null, \WC_Facebook_Product::PRODUCT_PREP_TYPE_FEED );
			$item_group_id = $product_data['retailer_id'];

			// prepare variant column for variable products
			$product_data['variant'] = '';

			if ( $woo_product->is_type( 'variation' ) ) {

				$parent_id = $woo_product->get_parent_id();

				if ( ! isset( $attribute_variants[ $parent_id ] ) ) {

					$parent_product          = new \WC_Facebook_Product( $parent_id );
					$gallery_urls            = array_filter( $parent_product->get_gallery_urls() );
					$variation_id            = $parent_product->find_matching_product_variation();
					$variants_for_group      = $parent_product->prepare_variants_for_group( true );
					$parent_attribute_values = [
						'gallery_urls'       => $gallery_urls,
						'default_variant_id' => $variation_id,
						'item_group_id'      => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $parent_product ),
					];

					foreach ( $variants_for_group as $variant ) {
						if ( isset( $variant['product_field'], $variant['options'] ) ) {
							$parent_attribute_values[ $variant['product_field'] ] = $variant['options'];
						}
					}

					// cache product group variants
					$attribute_variants[ $parent_id ] = $parent_attribute_values;

				} else {

					$parent_attribute_values = $attribute_variants[ $parent_id ];
				}

				$variants_for_item    = $woo_product->prepare_variants_for_item( $product_data );
				$variant_feed_column  = [];

				foreach ( $variants_for_item as $variant_array ) {

					static::format_variant_for_feed(
						$variant_array['product_field'],
						$variant_array['options'][0],
						$parent_attribute_values,
						$variant_feed_column
					);
				}

				if ( isset( $product_data['custom_data'] ) && is_array( $product_data['custom_data'] ) ) {

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
					$product_data['variant'] = '"' . implode( ',', $variant_feed_column ) . '"';
				}

				if ( isset( $parent_attribute_values['gallery_urls'] ) ) {
					$product_data['additional_image_urls'] = array_merge( $product_data['additional_image_urls'], $parent_attribute_values['gallery_urls'] );
				}

				if ( isset( $parent_attribute_values['item_group_id'] ) ) {
					$item_group_id = $parent_attribute_values['item_group_id'];
				}

				$product_data['default_product'] = $parent_attribute_values['default_variant_id'] == $woo_product->id ? 'default' : '';

				// If this group has default variant value, log this product item
				if ( isset( $parent_attribute_values['default_variant_id'] ) && ! empty( $parent_attribute_values['default_variant_id'] ) ) {
					$this->has_default_product_count++;
				} else {
					$this->no_default_product_count++;
				}
			}

			// log simple product
			if ( ! isset( $product_data['default_product'] ) ) {

				$this->no_default_product_count++;

				$product_data['default_product'] = '';
			}

			// when dealing with the feed file, only set out-of-stock products as hidden
			if ( Products::product_should_be_deleted( $woo_product->woo_product ) ) {
				$product_data['visibility'] = \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN;
			}

			return $product_data['retailer_id'] . ',' .
			static::format_string_for_feed( static::get_value_from_product_data( $product_data, 'name' ) ) . ',' .
			static::format_string_for_feed( static::get_value_from_product_data( $product_data, 'description' ) ) . ',' .
			static::get_value_from_product_data( $product_data, 'image_url' ) . ',' .
			static::get_value_from_product_data( $product_data, 'url' ) . ',' .
			static::format_string_for_feed( static::get_value_from_product_data( $product_data, 'category' ) ) . ',' .
			static::format_string_for_feed( static::get_value_from_product_data( $product_data, 'brand' ) ) . ',' .
			static::format_price_for_feed(
				static::get_value_from_product_data( $product_data, 'price', 0 ),
				static::get_value_from_product_data( $product_data, 'currency' )
			) . ',' .
			static::get_value_from_product_data( $product_data, 'availability' ) . ',' .
			$item_group_id . ',' .
			static::get_value_from_product_data( $product_data, 'checkout_url' ) . ',' .
			static::format_additional_image_url( static::get_value_from_product_data( $product_data, 'additional_image_urls' ) ) . ',' .
			static::get_value_from_product_data( $product_data, 'sale_price_start_date' ) . '/' .
			static::get_value_from_product_data( $product_data, 'sale_price_end_date' ) . ',' .
			static::format_price_for_feed(
				static::get_value_from_product_data( $product_data, 'sale_price', 0 ),
				static::get_value_from_product_data( $product_data, 'currency' )
			) . ',' .
			'new' . ',' .
			static::get_value_from_product_data( $product_data, 'visibility' ) . ',' .
			static::get_value_from_product_data( $product_data, 'gender' ) . ',' .
			static::get_value_from_product_data( $product_data, 'color' ) . ',' .
			static::get_value_from_product_data( $product_data, 'size' ) . ',' .
			static::get_value_from_product_data( $product_data, 'pattern' ) . ',' .
			static::get_value_from_product_data( $product_data, 'google_product_category' ) . ',' .
			static::get_value_from_product_data( $product_data, 'default_product' ) . ',' .
			static::get_value_from_product_data( $product_data, 'variant' ) . PHP_EOL;
		}


		private function create_feed() {
			$result = $this->fbgraph->create_feed(
				$this->facebook_catalog_id,
				array( 'name' => self::FEED_NAME )
			);
			if ( is_wp_error( $result ) || ! isset( $result['body'] ) ) {
				$this->log_feed_progress( json_encode( $result ) );
				return null;
			}
			$decode_result = WC_Facebookcommerce_Utils::decode_json( $result['body'] );
			$feed_id       = $decode_result->id;
			if ( ! $feed_id ) {
				$this->log_feed_progress(
					'Response from creating feed not return feed id!'
				);
				return null;
			}
			return $feed_id;
		}

		private function create_upload( $facebook_feed_id ) {
			$result = $this->fbgraph->create_upload(
				$facebook_feed_id,
				$this->get_file_path()
			);
			if ( is_null( $result ) || ! isset( $result['id'] ) || ! $result['id'] ) {
				$this->log_feed_progress( json_encode( $result ) );
				return null;
			}
			$upload_id = $result['id'];
			return $upload_id;
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


		/**
		 * Gets the value from the product data.
		 *
		 * This method is used to avoid PHP undefined index notices.
		 *
		 * @since 2.1.0
		 *
		 * @param array $product_data the product data retrieved from a Woo product passed by reference
		 * @param string $index the data index
		 * @param mixed $return_if_not_set the value to be returned if product data has no index (default to '')
		 * @return mixed|string the data value or an empty string
		 */
		private static function get_value_from_product_data( &$product_data, $index, $return_if_not_set = '' ) {

			return isset( $product_data[ $index ] ) ? $product_data[ $index ] : $return_if_not_set;
		}


		/**
		 * Gets the status of the configured feed upload.
		 *
		 * The status indicator is one of 'in progress', 'complete', or 'error'.
		 *
		 * @param array $settings
		 * @return string
		 */
		public function is_upload_complete( &$settings ) {

			$upload_id = facebook_for_woocommerce()->get_integration()->get_upload_id();
			$result    = $this->fbgraph->get_upload_status( $upload_id );

			if ( is_wp_error( $result ) || ! isset( $result['body'] ) ) {

				 $this->log_feed_progress( json_encode( $result ) );

				 return 'error';
			}

			$response_body = json_decode( wp_remote_retrieve_body( $result ) );
			$upload_status = 'error';

			if ( isset( $response_body->end_time ) ) {

				$settings['upload_end_time'] = $response_body->end_time;

				$upload_status = 'complete';

			} else if ( 200 === (int) wp_remote_retrieve_response_code( $result ) ) {

				$upload_status = 'in progress';
			}

			return $upload_status;
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
