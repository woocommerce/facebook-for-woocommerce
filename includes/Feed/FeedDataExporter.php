<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

defined( 'ABSPATH' ) || exit;

/**
 * Class FeedDataExporter
 *
 * @since 2.5.0
 */
class FeedDataExporter {

	// TODO Refactor feed handler into this class.
	/**
	 * Various feed utilities.
	 *
	 * @var FeedHandler Various feed utilities.
	 */
	protected $feed_handler;

	/**
	 * Cached attributes variants.
	 *
	 * @since 2.5.0
	 * @var array $attribute_variants Array of variants attributes.
	 */
	protected $attribute_variants = array();

	const CURRENTLY_PROCESSING_COUNT = 'facebook_for_woocommerce_feed_processing_count';


	/**
	 * Return an array of columns to export.
	 *
	 * @return array
	 * @since  2.5.0
	 * @link https://www.facebook.com/business/help/1898524300466211?id=725943027795860
	 * @link https://www.facebook.com/business/help/120325381656392?id=725943027795860 Required fields for products
	 * @link https://developers.facebook.com/docs/marketing-api/catalog/reference/#da-commerce full list of optional fields for products
	 */
	public function get_column_names() {
		return array(
			// Required
			'id'                        => 'id',
			'title'                     => 'title',
			'description'               => 'description',
			'availability'              => 'availability',
			'condition'                 => 'condition',
			'price'                     => 'price',
			'link'                      => 'link',
			'image_link'                => 'image_link',
			'brand'                     => 'brand',
			// Optional
			'item_group_id'             => 'item_group_id',
			'visibility'                => 'visibility',
			'sale_price'                => 'sale_price',
			'google_product_category'   => 'google_product_category',
			'product_type'              => 'product_type',
			'additional_image_link'     => 'additional_image_link',
			'sale_price_effective_date' => 'sale_price_effective_date',
			'gender'                    => 'gender',
			'color'                     => 'color',
			'size'                      => 'size',
			'pattern'                   => 'pattern',
			// Not available in Graph API
			'checkout_url'              => 'checkout_url',
			'default_product'           => 'default_product',
			'variant'                   => 'variant',
		);
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->feed_handler = new \WC_Facebook_Product_Feed();
	}

	/**
	 * Get the total number of items for processing.
	 *
	 * @since 2.5.0
	 * @return int
	 */
	public function calculate_number_of_items_for_processing() {
		$products_ids = \WC_Facebookcommerce_Utils::get_all_product_ids_for_sync();
		update_option( self::CURRENTLY_PROCESSING_COUNT, count( $products_ids ) );
	}

	static function get_number_of_items_for_processing() {
		return get_option( self::CURRENTLY_PROCESSING_COUNT, 0 );
	}

	/**
	 * Export column headers in CSV format.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function generate_header() {
		$columns    = $this->get_column_names();
		$export_row = array();
		$buffer     = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		ob_start();

		foreach ( $columns as $column_id => $column_name ) {
			$export_row[] = $this->format_data( $column_name );
		}

		fputcsv( $buffer, $export_row, ',', '"', "\0" ); // @codingStandardsIgnoreLine

		$header = ob_get_clean();
		fclose( $buffer );
		return $header;
	}

	/**
	 * Take a product and generate row data from it for export.
	 *
	 * @since 2.5.0
	 * @param WC_Product $product WC_Product object.
	 * @return array
	 */
	public function generate_row_data( $product ) {
		$fb_product = new \WC_Facebook_Product( $product );
		return $this->feed_handler->prepare_product_for_feed(
			$fb_product,
			$this->attribute_variants
		);
	}

	/**
	 * Format exported products data into CSV compatible form.
	 *
	 * @since 2.5.0
	 * @param array $rows Rows of product information to export.
	 */
	public function format_items_for_feed( $rows ) {
		$buffer = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		ob_start();

		foreach ( $rows as $row ) {
			$this->export_row( $row, $buffer );
		}

		$items = ob_get_clean();
		fclose( $buffer );
		return $items;
	}

	/**
	 * Export rows to an array ready for the CSV.
	 *
	 * @since 2.5.0
	 * @param array    $row_data Data to export.
	 * @param resource $buffer Output buffer.
	 */
	public function export_row( $row_data, $buffer ) {
		$columns    = $this->get_column_names();
		$export_row = array();

		foreach ( $columns as $column_id => $column_name ) {
			if ( isset( $row_data[ $column_id ] ) ) {
				$export_row[] = $this->format_data( $row_data[ $column_id ] );
			} else {
				$export_row[] = '';
			}
		}

		fputcsv( $buffer, $export_row, ',', '"', "\0" ); // @codingStandardsIgnoreLine
	}

	/**
	 * Format and escape data ready for the CSV file.
	 *
	 * @since 2.5.0
	 * @param  string $data Data to format.
	 * @return string
	 */
	public function format_data( $data ) {
		if ( ! is_scalar( $data ) ) {
			if ( is_a( $data, 'WC_Datetime' ) ) {
				$data = $data->date( 'Y-m-d G:i:s' );
			} else {
				$data = ''; // Not supported.
			}
		} elseif ( is_bool( $data ) ) {
			$data = $data ? 1 : 0;
		}

		$use_mb = function_exists( 'mb_convert_encoding' );

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			$data     = 'UTF-8' === $encoding ? $data : utf8_encode( $data );
		}

		return $data;
	}

}
