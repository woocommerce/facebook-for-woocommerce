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
	const REQUEST_FEED_ACTION     = 'wc_facebook_get_feed_data';
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
	 * Temporary export file.
	 *
	 * @var string
	 */
	protected $temp_filename = 'temp_product_catalog.csv';


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

		//Just copied and not integrated yet.
		//add_action( 'woocommerce_api_' . self::REQUEST_FEED_ACTION, [ $this, 'handle_feed_data_request' ] );
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

	/**
	 * Return a filename.
	 *
	 * @return string
	 */
	public function get_temp_filename() {
		return sanitize_file_name( $this->temp_filename );
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
		// Parent class will write to the 
		return $this->get_feed_directory() . $this->get_temp_filename();
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

		/**
	 * Generate the CSV file.
	 *
	 * @since 3.1.0
	 */
	public function generate_file() {
		if ( 1 === $this->get_page() && file_exists( ( $this->get_file_path() ) ) ) {
			@unlink( $this->get_file_path() ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink, Generic.PHP.NoSilencedErrors.Discouraged,
		}
		$this->prepare_data_to_export();
		$this->write_csv_data( $this->get_csv_data() );
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
			$this->replace_feed_file_with_temp_file();
		} else {
			update_option(
				self::RUNNING_FEED_SETTINGS,
				$settings,
				false
			);
			as_enqueue_async_action( self::FEED_GENERATION_STEP );
		}
	}

	/**
	 * Last step of the feed generation procedure.
	 * We have been writing to the temporary file until now.
	 * We can safely delate old feed file and replace it with
	 * the content of temporary file.
	 */
	public function replace_feed_file_with_temp_file() {
		@rename(
			$this->get_feed_directory() . $this->get_temp_filename(),
			$this->get_feed_directory() . $this->get_filename()
		); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_rename, Generic.PHP.NoSilencedErrors.Discouraged,
	}

	public function prepare_feed_generation() {
		$this->prepare_feed_folder();
		$products_ids = \WC_Facebookcommerce_Utils::get_all_product_ids_for_sync();
		$products_ids = array_slice( $products_ids, 0, 25, true );
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
		as_enqueue_async_action( self::FEED_GENERATION_STEP );
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
	 * Handles the feed data request.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 */
	public function handle_feed_data_request() {

		\WC_Facebookcommerce_Utils::log( 'Facebook is requesting the product feed.' );

		$feed_handler = new \WC_Facebook_Product_Feed();
		$file_path    = $feed_handler->get_file_path();

		// regenerate if the file doesn't exist
		if ( ! empty( $_GET['regenerate'] ) || ! file_exists( $file_path ) ) {
			$feed_handler->generate_feed();
		}

		try {

			// bail early if the feed secret is not included or is not valid
			if ( Feed::get_feed_secret() !== Framework\SV_WC_Helper::get_requested_value( 'secret' ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Invalid feed secret provided.', 401 );
			}

			// bail early if the file can't be read
			if ( ! is_readable( $file_path ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'File is not readable.', 404 );
			}

			// set the download headers
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length:'. filesize( $file_path ) );

			$file = @fopen( $file_path, 'rb' );

			if ( ! $file ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Could not open feed file.', 500 );
			}

			// fpassthru might be disabled in some hosts (like Flywheel)
			if ( $this->is_fpassthru_disabled() || ! @fpassthru( $file ) ) {

				\WC_Facebookcommerce_Utils::log( 'fpassthru is disabled: getting file contents' );

				$contents = @stream_get_contents( $file );

				if ( ! $contents ) {
					throw new Framework\SV_WC_Plugin_Exception( 'Could not get feed file contents.', 500 );
				}

				echo $contents;
			}

		} catch ( \Exception $exception ) {

			\WC_Facebookcommerce_Utils::log( 'Could not serve product feed. ' . $exception->getMessage() . ' (' . $exception->getCode() . ')' );

			status_header( $exception->getCode() );
		}

		exit;
	}

	/**
	 * Checks whether fpassthru has been disabled in PHP.
	 *
	 * Helper method, do not open to public.
	 *
	 * @since 1.11.0
	 *
	 * @return bool
	 */
	private function is_fpassthru_disabled() {

		$disabled = false;

		if ( function_exists( 'ini_get' ) ) {

			$disabled_functions = @ini_get( 'disable_functions' );

			$disabled = is_string( $disabled_functions ) && in_array( 'fpassthru', explode( ',', $disabled_functions ), false );
		}

		return $disabled;
	}

	/**
	 * Gets the URL for retrieving the product feed data.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public static function get_feed_data_url() {

		$query_args = [
			'wc-api' => self::REQUEST_FEED_ACTION,
			'secret' => self::get_feed_secret(),
		];

		return add_query_arg( $query_args, home_url( '/' ) );
	}


	/**
	 * Gets the secret value that should be included in the Feed URL.
	 *
	 * Generates a new secret and stores it in the database if no value is set.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public static function get_feed_secret() {

		$secret = get_option( self::OPTION_FEED_URL_SECRET, '' );

		if  ( ! $secret ) {

			$secret = wp_hash( 'products-feed-' . time() );

			update_option( self::OPTION_FEED_URL_SECRET, $secret );
		}

		return $secret;
	}


}
