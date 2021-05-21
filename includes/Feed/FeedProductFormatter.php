<?php
namespace SkyVerge\WooCommerce\Facebook\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SkyVerge\WooCommerce\Facebook\Products;

/**
 * Class responsible for formatting product for feed file.
 */
class FeedProductFormatter {

	const MAX_IMAGES_PER_PRODUCT = 5;

	/**
	 * Formats a product to be synced to Facebook.
	 *
	 * @see FeedDataExporter::attribute_variants for explanation why $attribute_variants are passed as reference.
	 * @since 2.6.0
	 * @param \WC_Facebook_Product $fb_product WooCommerce product object normalized by Facebook.
	 * @param array                $attribute_variants Array of variants attributes.
	 * @return array[string] product feed line data
	 */
	public function prepare_product_for_feed( $fb_product, &$attribute_variants ) {

		$product_data  = $fb_product->prepare_product( null, \WC_Facebook_Product::PRODUCT_PREP_TYPE_FEED );
		$item_group_id = $product_data['retailer_id'];

		// Prepare variant column for variable products.
		$product_data['variant'] = '';

		if ( $fb_product->is_type( 'variation' ) ) {
			$product_data = $this->prepare_variation_for_feed( $product_data, $fb_product, $attribute_variants );
		}

		// Log simple product.
		if ( ! isset( $product_data['default_product'] ) ) {
			$product_data['default_product'] = '';
		}

		if ( isset( $product_data['item_group_id'] ) ) {
			$item_group_id = $product_data['item_group_id'];
		}

		// When dealing with the feed file, only set out-of-stock products as hidden.
		if ( Products::product_should_be_deleted( $fb_product->woo_product ) ) {
			$product_data['visibility'] = \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN;
		}
		$feed_row                              = array();
		$feed_row['id']                        = $product_data['retailer_id'];
		$feed_row['title']                     = $fb_product->woo_product->get_title();
		$feed_row['description']               = $this->get_value_from_product_data( $product_data, 'description' );
		$feed_row['image_link']                = $this->get_value_from_product_data( $product_data, 'image_url' );
		$feed_row['link']                      = $this->get_value_from_product_data( $product_data, 'url' );
		$feed_row['product_type']              = $this->get_value_from_product_data( $product_data, 'category' );
		$feed_row['brand']                     = $this->get_value_from_product_data( $product_data, 'brand' );
		$feed_row['price']                     = $this->format_price_for_feed(
			$this->get_value_from_product_data( $product_data, 'price', 0 ),
			$this->get_value_from_product_data( $product_data, 'currency' )
		);
		$feed_row['availability']              = $this->get_value_from_product_data( $product_data, 'availability' );
		$feed_row['item_group_id']             = $item_group_id;
		$feed_row['checkout_url']              = $this->get_value_from_product_data( $product_data, 'checkout_url' );
		$feed_row['additional_image_link']     = $this->format_additional_image_url( $this->get_value_from_product_data( $product_data, 'additional_image_urls' ) );
		$feed_row['sale_price_effective_date'] = $this->get_value_from_product_data( $product_data, 'sale_price_start_date' ) . '/' . $this->get_value_from_product_data( $product_data, 'sale_price_end_date' );
		$feed_row['sale_price']                = $this->format_price_for_feed(
			$this->get_value_from_product_data( $product_data, 'sale_price', 0 ),
			$this->get_value_from_product_data( $product_data, 'currency' )
		);
		$feed_row['condition']                 = 'new';
		$feed_row['visibility']                = $this->get_value_from_product_data( $product_data, 'visibility' );
		$feed_row['gender']                    = $this->get_value_from_product_data( $product_data, 'gender' );
		$feed_row['color']                     = $this->get_value_from_product_data( $product_data, 'color' );
		$feed_row['size']                      = $this->get_value_from_product_data( $product_data, 'size' );
		$feed_row['pattern']                   = $this->get_value_from_product_data( $product_data, 'pattern' );
		$feed_row['google_product_category']   = $this->get_value_from_product_data( $product_data, 'google_product_category' );
		$feed_row['default_product']           = $this->get_value_from_product_data( $product_data, 'default_product' );
		$feed_row['variant']                   = $this->get_value_from_product_data( $product_data, 'variant' );

		return $feed_row;
	}

	/**
	 * Prepare variation information
	 *
	 * @since 2.6.0
	 * @param array                $product_data Product information for the feed.
	 * @param \WC_Facebook_Product $fb_product WooCommerce product object normalized by Facebook.
	 * @param array                $attribute_variants Array of variants attributes.
	 * @return array[string] product feed data
	 */
	private function prepare_variation_for_feed( $product_data, $fb_product, &$attribute_variants ) {
		$parent_id = $fb_product->get_parent_id();

		if ( ! isset( $attribute_variants[ $parent_id ] ) ) {

			$parent_product          = new \WC_Facebook_Product( $parent_id );
			$gallery_urls            = array_filter( $parent_product->get_gallery_urls() );
			$variation_id            = $parent_product->find_matching_product_variation();
			$variants_for_group      = $parent_product->prepare_variants_for_group( true );
			$parent_attribute_values = array(
				'gallery_urls'       => $gallery_urls,
				'default_variant_id' => $variation_id,
				'item_group_id'      => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $parent_product->woo_product ),
			);

			foreach ( $variants_for_group as $variant ) {
				if ( isset( $variant['product_field'], $variant['options'] ) ) {
					$parent_attribute_values[ $variant['product_field'] ] = $variant['options'];
				}
			}

			// Cache product group variants.
			$attribute_variants[ $parent_id ] = $parent_attribute_values;

		} else {

			$parent_attribute_values = $attribute_variants[ $parent_id ];
		}

		$variants_for_item   = $fb_product->prepare_variants_for_item( $product_data );
		$variant_feed_column = array();

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
			$product_data['item_group_id'] = $parent_attribute_values['item_group_id'];
		}

		$product_data['default_product'] = $parent_attribute_values['default_variant_id'] == $fb_product->id ? 'default' : '';

		return $product_data;
	}


	/**
	 * Format additional urls into the feed requirements.
	 *
	 * @since 2.6.0
	 * @param array $product_image_urls Array of urls to images.
	 * @return string Stringified list of urls.
	 */
	private function format_additional_image_url( $product_image_urls ) {
		// Returns the top 5 additional image urls separated by a comma according to feed api rules.
		$product_image_urls = array_slice(
			$product_image_urls,
			0,
			self::MAX_IMAGES_PER_PRODUCT
		);
		if ( $product_image_urls ) {
			return '"' . implode( ',', $product_image_urls ) . '"';
		} else {
			return '';
		}
	}

	/**
	 * Change WC price format to Feed price format.
	 *
	 * @since 2.6.0
	 * @param float  $value Value to format.
	 * @param string $currency Currency of value.
	 * @return string Formatted price value.
	 */
	private function format_price_for_feed( $value, $currency ) {
		return (string) ( round( $value / (float) 100, 2 ) ) . $currency;
	}

	/**
	 * Variant values formatting for the feed.
	 *
	 * @since 2.6.0
	 * @param string $product_field Field to search for.
	 * @param string $value Value of the field.
	 * @param array  $parent_attribute_values Array of attributes.
	 * @param array  $variant_feed_column Array of variants for feed column.
	 */
	private static function format_variant_for_feed( $product_field, $value, $parent_attribute_values, &$variant_feed_column ) {
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
	 * @param array  $product_data the product data retrieved from a Woo product passed by reference.
	 * @param string $index The data index.
	 * @param mixed  $return_if_not_set The value to be returned if product data has no index (default to '').
	 * @return mixed|string the data value or an empty string
	 */
	private function get_value_from_product_data( $product_data, $index, $return_if_not_set = '' ) {
		return isset( $product_data[ $index ] ) ? $product_data[ $index ] : $return_if_not_set;
	}
}
