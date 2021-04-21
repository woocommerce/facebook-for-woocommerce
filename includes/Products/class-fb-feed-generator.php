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
	const FEED_SCHEDULE_ACTION    = 'wc_facebook_for_woocommerce_feed_schedule_action';
	const FEED_AJAX_GENERATE_FEED = 'facebook_for_woocommerce_do_ajax_feed';
	const RUNNING_FEED_SETTINGS   = 'wc_facebook_for_woocommerce_running_feed_settings';
	const FEED_SCHEDULE_SETTINGS  = 'wc_facebook_for_woocommerce_feed_schedule';
	const FEED_GENERATION_NONCE   = 'wc_facebook_for_woocommerce_feed_generation_nonce';
	const FEED_GENERATION_LIMIT   = 10;
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
	protected $limit = self::FEED_GENERATION_LIMIT;

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
		add_action( 'init', array( $this, 'maybe_schedule_feed_generation' ) );
		add_action( self::FEED_GENERATION_STEP, array( $this, 'execute_feed_generation_step' ) );
		add_action( self::FEED_SCHEDULE_ACTION, array( $this, 'prepare_feed_generation' ) );
		add_action( 'wp_ajax_' . self::FEED_AJAX_GENERATE_FEED, array( $this, 'ajax_feed_handle' ) );
	}

	public function maybe_schedule_feed_generation() {
		if ( false !== as_next_scheduled_action( self::FEED_SCHEDULE_ACTION ) ) {
			return;
		}
		$timestamp = strtotime( 'today midnight +1 day' );
		as_schedule_recurring_action( $timestamp, DAY_IN_SECONDS, self::FEED_SCHEDULE_ACTION );
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
		if ( ( ( $settings['page'] - 1 ) * $this->limit ) >= count( $settings['ids'] ) ) {
			$settings['done'] = true;
			$settings['end']  = time();
			update_option(
				self::RUNNING_FEED_SETTINGS,
				$settings,
				false
			);
			as_unschedule_action( self::FEED_GENERATION_STEP );
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
				'ids'   => $products_ids,
				'page'  => 1,
				'done'  => false,
				'start' => time(),
				'total' => count( $products_ids ),
			),
			false
		);
		as_unschedule_all_actions( self::FEED_GENERATION_STEP );
		as_schedule_recurring_action( time() + 30, MINUTE_IN_SECONDS, self::FEED_GENERATION_STEP );
	}

	public function ajax_feed_handle() {
		if ( 'true' === $_POST['generate'] ) {
			$this->prepare_feed_generation();
		}
		$settings = get_option( self::RUNNING_FEED_SETTINGS );

		if ( $settings['done'] ) {
			wp_send_json_success(
				array(
					'done'       => true,
					'percentage' => 100,
				)
			);
		} else {
			wp_send_json_success(
				array(
					'done'       => false,
					'percentage' => intval( ( ( $settings['page'] * $this->limit ) / $settings['total'] ) * 100 ),
				)
			);
		}
	}

	public static function is_generation_in_progress() {
		return false !== as_next_scheduled_action( self::FEED_GENERATION_STEP );
	}

	/**
	 * Prepare data for export.
	 *
	 * @since 3.1.0
	 */
	public function prepare_data_to_export() {
		$page = $this->get_page();
		$limit = $this->get_limit();
		$settings = get_option( self::RUNNING_FEED_SETTINGS );
		if ( empty( $settings['ids'] ) ) {
			return;
		}
		$batch = array_slice( $settings['ids'], ( $page - 1 ) * $limit, $limit );
		if ( empty( $batch ) ) {
			return;
		}
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

}
