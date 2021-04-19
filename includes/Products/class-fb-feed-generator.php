<?php
/**
 * Handles Facebook Product feed CSV file generation.
 *
 * @version 2.5.0
 */

namespace SkyVerge\WooCommerce\Facebook\Products;

use Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Product_CSV_Exporter', false ) ) {
	include_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';
}

/**
 * WC_Product_CSV_Exporter Class.
 */
class FB_Feed_Generator extends \WC_Product_CSV_Exporter {

	const FEED_GENERATION_TRIGGER = 'wc_facebook_for_woocommerce_feed_trigger';
	const FEED_GENERATION_STEP    = 'wc_facebook_for_woocommerce_feed_step';
	const FEED_ACTIONS_GROUP      = 'wc_facebook_for_woocommerce_feed_actions';
	const RUNNING_FEED_SETTINGS   = 'wc_facebook_for_woocommerce_running_feed_settings';
	/**
	 * Type of export used in filter names.
	 *
	 * @var string
	 */
	protected $export_type = 'product';


	/**
	 * Filename to export to.
	 *
	 * @var string
	 */
	protected $filename = 'product_catalog.csv';


	/**
	 * Batch limit.
	 *
	 * @var integer
	 */
	protected $limit = 10;

	/**
	 * Should meta be exported?
	 *
	 * @var boolean
	 */
	protected $enable_meta_export = false;

	/**
	 * Products belonging to what category should be exported.
	 *
	 * @var string
	 */
	protected $product_category_to_export = array();

	// Refactor feed handler into this class;
	protected $feed_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->feed_handler = new \WC_Facebook_Product_Feed();
		add_action( self::FEED_GENERATION_STEP, array( $this, 'execute_feed_generation_step' ) );
	}

	/**
	 * Should meta be exported?
	 *
	 * @param bool $enable_meta_export Should meta be exported.
	 *
	 * @since 3.1.0
	 */
	public function enable_meta_export( $enable_meta_export ) {
		$this->enable_meta_export = (bool) $enable_meta_export;
	}

	/**
	 * Product category to export.
	 *
	 * @param string $product_category_to_export Product category slug to export, empty string exports all.
	 *
	 * @since  3.5.0
	 * @return void
	 */
	public function set_product_category_to_export( $product_category_to_export ) {
		$this->product_category_to_export = array_map( 'sanitize_title_with_dashes', $product_category_to_export );
	}

	/**
	 * Return a filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( $this->filename );
	}

	public function get_feed_directory() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . 'facebook_for_woocommerce/';
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	protected function get_file_path() {
		return $this->get_feed_directory() . $this->get_filename();
	}

	protected $attribute_variants = array();

	/**
	 * Return an array of columns to export.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	public function get_default_column_names() {
		return array(
			'id'     => 'id',
			'title'  => 'title',
			'description' => 'description',
			'image_link' => 'image_link',
			'link' => 'link',
			'product_type' => 'product_type',
			'brand' => 'brand',
			'price' => 'price',
			'availability' => 'availability',
			'item_group_id' => 'item_group_id',
			'checkout_url' => 'checkout_url',
			'additional_image_link' => 'additional_image_link',
			'sale_price_effective_date' => 'sale_price_effective_date',
			'sale_price' => 'sale_price',
			'condition' => 'condition',
			'visibility' => 'visibility',
			'gender' => 'gender',
			'color' => 'color',
			'size' => 'size',
			'pattern' => 'pattern',
			'google_product_category' => 'google_product_category',
			'default_product' => 'default_product',
			'variant' => 'variant',
		);
	}

	public function prepare_feed_folder() {
		$catalog_feed_directory = trailingslashit( $this->get_feed_directory() );

		if ( ! wp_mkdir_p( $catalog_feed_directory ) ) {
			throw new Error( __( 'Could not create product catalog feed directory', 'facebook-for-woocommerce' ), 500 );
		}

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

	public function execute_feed_generation_step() {
		$settings = get_option( self::RUNNING_FEED_SETTINGS );
		$this->set_page( $settings['page'] );
		$this->generate_file();
		$settings['page'] += 1;
		if ( ( $settings['page'] * $this->limit ) >= count( $settings['ids'] ) ) {
			delete_option( self::RUNNING_FEED_SETTINGS );
			as_unschedule_all_actions( self::FEED_GENERATION_STEP );
		} else {
			update_option(
				self::RUNNING_FEED_SETTINGS,
				$settings,
				false
			);
		}
	}

	static function prepare_feed_generation() {
		$products_ids = \WC_Facebookcommerce_Utils::get_all_product_ids_for_sync();
		update_option(
			self::RUNNING_FEED_SETTINGS,
			array(
				'ids'  => $products_ids,
				'page' => 1,
			),
			false
		);
		as_schedule_recurring_action( time(), MINUTE_IN_SECONDS, self::FEED_GENERATION_STEP );
	}

	/**
	 * Prepare data for export.
	 *
	 * @since 3.1.0
	 */
	public function prepare_data_to_export() {
		$page = $this->get_page();
		$limit = $this->get_limit();
		$batch = array_slice( $all_products, ( $page - 1 ) * $limit, $limit );
		$args = array(
			'status'  => array( 'publish' ),
			'type'    => $this->product_types_to_export,
			'include' => array_values( $batch ),
			'limit'   => $limit,
			'return'  => 'objects',
		);

		$products = wc_get_products( $args );

		$prods = array();
		$this->row_data    = array();
		foreach ( $products as $product ) {
			$prods[] = $product->get_id();
			$this->row_data[] = $this->generate_row_data( $product );
		}

		$missing1 = array_diff( $batch, $prods );
		$missing2 = array_diff( $prods, $batch );
		$missing2[] = false;
	}

	/**
	 * Take a product and generate row data from it for export.
	 *
	 * @param WC_Product $product WC_Product object.
	 *
	 * @return array
	 */
	protected function generate_row_data( $product ) {
		$fb_product = new \WC_Facebook_Product( $product );
		return $this->feed_handler->prepare_product_for_feed(
			$fb_product,
			$this->attribute_variants
		);
	}

	/**
	 * Get published value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return int
	 */
	protected function get_column_value_published( $product ) {
		$statuses = array(
			'draft'   => -1,
			'private' => 0,
			'publish' => 1,
		);

		// Fix display for variations when parent product is a draft.
		if ( 'variation' === $product->get_type() ) {
			$parent = $product->get_parent_data();
			$status = 'draft' === $parent['status'] ? $parent['status'] : $product->get_status( 'edit' );
		} else {
			$status = $product->get_status( 'edit' );
		}

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : -1;
	}

	/**
	 * Get formatted sale price.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @return string
	 */
	protected function get_column_value_sale_price( $product ) {
		return wc_format_localized_price( $product->get_sale_price( 'view' ) );
	}

	/**
	 * Get formatted regular price.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @return string
	 */
	protected function get_column_value_regular_price( $product ) {
		return wc_format_localized_price( $product->get_regular_price() );
	}

	/**
	 * Get product_cat value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_category_ids( $product ) {
		$term_ids = $product->get_category_ids( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_cat' );
	}

	/**
	 * Get product_tag value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_tag_ids( $product ) {
		$term_ids = $product->get_tag_ids( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_tag' );
	}

	/**
	 * Get product_shipping_class value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_shipping_class_id( $product ) {
		$term_ids = $product->get_shipping_class_id( 'edit' );
		return $this->format_term_ids( $term_ids, 'product_shipping_class' );
	}

	/**
	 * Get images value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_images( $product ) {
		$image_ids = array_merge( array( $product->get_image_id( 'edit' ) ), $product->get_gallery_image_ids( 'edit' ) );
		$images    = array();

		foreach ( $image_ids as $image_id ) {
			$image = wp_get_attachment_image_src( $image_id, 'full' );

			if ( $image ) {
				$images[] = $image[0];
			}
		}

		return $this->implode_values( $images );
	}

	/**
	 * Prepare linked products for export.
	 *
	 * @param int[] $linked_products Array of linked product ids.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function prepare_linked_products_for_export( $linked_products ) {
		$product_list = array();

		foreach ( $linked_products as $linked_product ) {
			if ( $linked_product->get_sku() ) {
				$product_list[] = $linked_product->get_sku();
			} else {
				$product_list[] = 'id:' . $linked_product->get_id();
			}
		}

		return $this->implode_values( $product_list );
	}

	/**
	 * Get cross_sell_ids value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_cross_sell_ids( $product ) {
		return $this->prepare_linked_products_for_export( array_filter( array_map( 'wc_get_product', (array) $product->get_cross_sell_ids( 'edit' ) ) ) );
	}

	/**
	 * Get upsell_ids value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_upsell_ids( $product ) {
		return $this->prepare_linked_products_for_export( array_filter( array_map( 'wc_get_product', (array) $product->get_upsell_ids( 'edit' ) ) ) );
	}

	/**
	 * Get parent_id value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_parent_id( $product ) {
		if ( $product->get_parent_id( 'edit' ) ) {
			$parent = wc_get_product( $product->get_parent_id( 'edit' ) );
			if ( ! $parent ) {
				return '';
			}

			return $parent->get_sku( 'edit' ) ? $parent->get_sku( 'edit' ) : 'id:' . $parent->get_id();
		}
		return '';
	}

	/**
	 * Get grouped_products value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_grouped_products( $product ) {
		if ( 'grouped' !== $product->get_type() ) {
			return '';
		}

		$grouped_products = array();
		$child_ids        = $product->get_children( 'edit' );
		foreach ( $child_ids as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( ! $child ) {
				continue;
			}

			$grouped_products[] = $child->get_sku( 'edit' ) ? $child->get_sku( 'edit' ) : 'id:' . $child_id;
		}
		return $this->implode_values( $grouped_products );
	}

	/**
	 * Get download_limit value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_download_limit( $product ) {
		return $product->is_downloadable() && $product->get_download_limit( 'edit' ) ? $product->get_download_limit( 'edit' ) : '';
	}

	/**
	 * Get download_expiry value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_download_expiry( $product ) {
		return $product->is_downloadable() && $product->get_download_expiry( 'edit' ) ? $product->get_download_expiry( 'edit' ) : '';
	}

	/**
	 * Get stock value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_stock( $product ) {
		$manage_stock   = $product->get_manage_stock( 'edit' );
		$stock_quantity = $product->get_stock_quantity( 'edit' );

		if ( $product->is_type( 'variation' ) && 'parent' === $manage_stock ) {
			return 'parent';
		} elseif ( $manage_stock ) {
			return $stock_quantity;
		} else {
			return '';
		}
	}

	/**
	 * Get stock status value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_stock_status( $product ) {
		$status = $product->get_stock_status( 'edit' );

		if ( 'onbackorder' === $status ) {
			return 'backorder';
		}

		return 'instock' === $status ? 1 : 0;
	}

	/**
	 * Get backorders.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_backorders( $product ) {
		$backorders = $product->get_backorders( 'edit' );

		switch ( $backorders ) {
			case 'notify':
				return 'notify';
			default:
				return wc_string_to_bool( $backorders ) ? 1 : 0;
		}
	}

	/**
	 * Get low stock amount value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.5.0
	 * @return int|string Empty string if value not set
	 */
	protected function get_column_value_low_stock_amount( $product ) {
		return $product->managing_stock() && $product->get_low_stock_amount( 'edit' ) ? $product->get_low_stock_amount( 'edit' ) : '';
	}

	/**
	 * Get type value.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	protected function get_column_value_type( $product ) {
		$types   = array();
		$types[] = $product->get_type();

		if ( $product->is_downloadable() ) {
			$types[] = 'downloadable';
		}

		if ( $product->is_virtual() ) {
			$types[] = 'virtual';
		}

		return $this->implode_values( $types );
	}

	/**
	 * Filter description field for export.
	 * Convert newlines to '\n'.
	 *
	 * @param string $description Product description text to filter.
	 *
	 * @since  3.5.4
	 * @return string
	 */
	protected function filter_description_field( $description ) {
		$description = str_replace( '\n', "\\\\n", $description );
		$description = str_replace( "\n", '\n', $description );
		return $description;
	}

	/**
	 * Export attributes data.
	 *
	 * @param WC_Product $product Product being exported.
	 * @param array      $row     Row being exported.
	 *
	 * @since 3.1.0
	 */
	protected function prepare_attributes_for_export( $product, &$row ) {
		if ( $this->is_column_exporting( 'attributes' ) ) {
			$attributes         = $product->get_attributes();
			$default_attributes = $product->get_default_attributes();

			if ( count( $attributes ) ) {
				$i = 1;
				foreach ( $attributes as $attribute_name => $attribute ) {
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:name' . $i ] = sprintf( __( 'Attribute %d name', 'woocommerce' ), $i );
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:value' . $i ] = sprintf( __( 'Attribute %d value(s)', 'woocommerce' ), $i );
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:visible' . $i ] = sprintf( __( 'Attribute %d visible', 'woocommerce' ), $i );
					/* translators: %s: attribute number */
					$this->column_names[ 'attributes:taxonomy' . $i ] = sprintf( __( 'Attribute %d global', 'woocommerce' ), $i );

					if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
						$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute->get_name(), $product );

						if ( $attribute->is_taxonomy() ) {
							$terms  = $attribute->get_terms();
							$values = array();

							foreach ( $terms as $term ) {
								$values[] = $term->name;
							}

							$row[ 'attributes:value' . $i ]    = $this->implode_values( $values );
							$row[ 'attributes:taxonomy' . $i ] = 1;
						} else {
							$row[ 'attributes:value' . $i ]    = $this->implode_values( $attribute->get_options() );
							$row[ 'attributes:taxonomy' . $i ] = 0;
						}

						$row[ 'attributes:visible' . $i ] = $attribute->get_visible();
					} else {
						$row[ 'attributes:name' . $i ] = wc_attribute_label( $attribute_name, $product );

						if ( 0 === strpos( $attribute_name, 'pa_' ) ) {
							$option_term = get_term_by( 'slug', $attribute, $attribute_name ); // @codingStandardsIgnoreLine.
							$row[ 'attributes:value' . $i ]    = $option_term && ! is_wp_error( $option_term ) ? str_replace( ',', '\\,', $option_term->name ) : str_replace( ',', '\\,', $attribute );
							$row[ 'attributes:taxonomy' . $i ] = 1;
						} else {
							$row[ 'attributes:value' . $i ]    = str_replace( ',', '\\,', $attribute );
							$row[ 'attributes:taxonomy' . $i ] = 0;
						}

						$row[ 'attributes:visible' . $i ] = '';
					}

					if ( $product->is_type( 'variable' ) && isset( $default_attributes[ sanitize_title( $attribute_name ) ] ) ) {
						/* translators: %s: attribute number */
						$this->column_names[ 'attributes:default' . $i ] = sprintf( __( 'Attribute %d default', 'woocommerce' ), $i );
						$default_value                                   = $default_attributes[ sanitize_title( $attribute_name ) ];

						if ( 0 === strpos( $attribute_name, 'pa_' ) ) {
							$option_term = get_term_by( 'slug', $default_value, $attribute_name ); // @codingStandardsIgnoreLine.
							$row[ 'attributes:default' . $i ] = $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $default_value;
						} else {
							$row[ 'attributes:default' . $i ] = $default_value;
						}
					}
					$i++;
				}
			}
		}
	}

	/**
	 * Export meta data.
	 *
	 * @param WC_Product $product Product being exported.
	 * @param array      $row Row data.
	 *
	 * @since 3.1.0
	 */
	protected function prepare_meta_for_export( $product, &$row ) {
		if ( $this->enable_meta_export ) {
			$meta_data = $product->get_meta_data();

			if ( count( $meta_data ) ) {
				$meta_keys_to_skip = apply_filters( 'woocommerce_product_export_skip_meta_keys', array(), $product );

				$i = 1;
				foreach ( $meta_data as $meta ) {
					if ( in_array( $meta->key, $meta_keys_to_skip, true ) ) {
						continue;
					}

					// Allow 3rd parties to process the meta, e.g. to transform non-scalar values to scalar.
					$meta_value = apply_filters( 'woocommerce_product_export_meta_value', $meta->value, $meta, $product, $row );

					if ( ! is_scalar( $meta_value ) ) {
						continue;
					}

					$column_key = 'meta:' . esc_attr( $meta->key );
					/* translators: %s: meta data name */
					$this->column_names[ $column_key ] = sprintf( __( 'Meta: %s', 'woocommerce' ), $meta->key );
					$row[ $column_key ]                = $meta_value;
					$i ++;
				}
			}
		}
	}
}
