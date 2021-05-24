<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

defined( 'ABSPATH' ) || exit;

use SkyVerge\WooCommerce\Facebook\Feed\FeedProductFormatter;

/**
 * A class responsible for manipulating output from FeedProductFormatter to technical requirements of the Facebook Feed CSV format.
 *
 * @since 2.6.0
 */
class FeedDataExporter {

	/**
	 * Various feed utilities for extracting product information from WC_Product to feed format.
	 *
	 * @var FeedProductFormatter Various feed utilities.
	 */
	protected $product_formatter;

	/**
	 * Cached attributes variants.
	 * Collecting parent information may be costly. There is a big change that a batch of products
	 * will consist of a group of variations items sharing the same parent. In that case we can cache
	 * variants attributes and re-used them for each child.
	 *
	 * @since 2.6.0
	 * @var array $attribute_variants Array of variants attributes.
	 * @see FeedProductFormatter::prepare_product_for_feed()
	 */
	protected $attribute_variants = array();


	/**
	 * Return an array of columns to export.
	 *
	 * @since  2.6.0
	 * @return array
	 */
	private function get_column_names() {
		return array(
			'id',
			'title',
			'description',
			'image_link',
			'link',
			'product_type',
			'brand',
			'price',
			'availability',
			'item_group_id',
			'checkout_url',
			'additional_image_link',
			'sale_price_effective_date',
			'sale_price',
			'condition',
			'visibility',
			'gender',
			'color',
			'size',
			'pattern',
			'google_product_category',
			'default_product',
			'variant',
		);
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->product_formatter = new FeedProductFormatter();
	}

	/**
	 * Export column headers in CSV format.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function generate_header() {
		$columns = $this->get_column_names();
		$header  = array_combine( $columns, $columns );
		return $this->format_items_for_feed( array( $header ) );
	}

	/**
	 * Take a product and generate row data from it for export.
	 *
	 * @since 2.6.0
	 * @param \WC_Product $product WC_Product object.
	 * @return array
	 */
	public function generate_row_data( $product ) {
		$fb_product = new \WC_Facebook_Product( $product );
		return $this->product_formatter->prepare_product_for_feed(
			$fb_product,
			$this->attribute_variants
		);
	}

	/**
	 * Format exported products data into CSV compatible form.
	 *
	 * @since 2.6.0
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
	 * @since 2.6.0
	 * @param array    $row_data Data to export.
	 * @param resource $buffer Output buffer.
	 */
	private function export_row( $row_data, $buffer ) {
		$columns    = $this->get_column_names();
		$export_row = array();

		foreach ( $columns as $column_name ) {
			if ( isset( $row_data[ $column_name ] ) ) {
				$export_row[] = $this->format_data( $row_data[ $column_name ] );
			} else {
				$export_row[] = '';
			}
		}

		fputcsv( $buffer, $export_row, ',', '"', "\0" );
	}

	/**
	 * Format and escape data ready for the CSV file.
	 *
	 * @since 2.6.0
	 * @param  string $data Data to format.
	 * @return string
	 */
	private function format_data( $data ) {
		$use_mb = function_exists( 'mb_convert_encoding' );

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			$data     = 'UTF-8' === $encoding ? $data : utf8_encode( $data );
		}

		return $data;
	}

}
