<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

defined( 'ABSPATH' ) || exit;

use SkyVerge\WooCommerce\Facebook\Feed\FeedProductFormatter;

/**
 * Class FeedDataExporter
 *
 * @since 2.6.0
 */
class FeedDataExporter {

	/**
	 * Various feed utilities.
	 *
	 * @var FeedProductFormatter Various feed utilities.
	 */
	protected $product_formatter;

	/**
	 * Cached attributes variants.
	 *
	 * @since 2.6.0
	 * @var array $attribute_variants Array of variants attributes.
	 */
	protected $attribute_variants = array();


	/**
	 * Return an array of columns to export.
	 *
	 * @since  2.6.0
	 * @return array
	 */
	public function get_column_names() {
		return array(
			'id'                        => 'id',
			'title'                     => 'title',
			'description'               => 'description',
			'image_link'                => 'image_link',
			'link'                      => 'link',
			'product_type'              => 'product_type',
			'brand'                     => 'brand',
			'price'                     => 'price',
			'availability'              => 'availability',
			'item_group_id'             => 'item_group_id',
			'checkout_url'              => 'checkout_url',
			'additional_image_link'     => 'additional_image_link',
			'sale_price_effective_date' => 'sale_price_effective_date',
			'sale_price'                => 'sale_price',
			'condition'                 => 'condition',
			'visibility'                => 'visibility',
			'gender'                    => 'gender',
			'color'                     => 'color',
			'size'                      => 'size',
			'pattern'                   => 'pattern',
			'google_product_category'   => 'google_product_category',
			'default_product'           => 'default_product',
			'variant'                   => 'variant',
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
	 * @since 2.6.0
	 * @param WC_Product $product WC_Product object.
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
	 * @since 2.6.0
	 * @param  string $data Data to format.
	 * @return string
	 */
	public function format_data( $data ) {
		$use_mb = function_exists( 'mb_convert_encoding' );

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			$data     = 'UTF-8' === $encoding ? $data : utf8_encode( $data );
		}

		return $data;
	}

}