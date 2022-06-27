<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;

/**
 * Unit tests for Facebook Graph API calls.
 */
class WCFacebookCommerceIntegrationTest extends WP_UnitTestCase
{

	/** @var WC_Facebookcommerce_Integration */
	private $integration;

	private static $default_options = [
		WC_Facebookcommerce_Pixel::PIXEL_ID_KEY     => '0',
		WC_Facebookcommerce_Pixel::USE_PII_KEY      => true,
		WC_Facebookcommerce_Pixel::USE_S2S_KEY      => false,
		WC_Facebookcommerce_Pixel::ACCESS_TOKEN_KEY => '',
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void
	{
		parent::setUp();

		$this->integration = new WC_Facebookcommerce_Integration();

		/* Making sure no options are set before the test. */
		delete_option( WC_Facebookcommerce_Pixel::SETTINGS_KEY );
		delete_option( WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID );
	}

	public function test_init_pixel_for_non_admin_user_must_do_nothing() {
		$this->assertFalse( is_admin(), 'Current user must not be an admin user.' );
		$this->assertFalse( $this->integration->init_pixel() );
	}

	public function test_init_pixel_for_admin_user_must_init_pixel_default_options() {
		/* Setting up Admin user. */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		set_current_screen( 'edit-post' );

		$this->assertTrue( is_admin(), 'Current user must be an admin user.' );
		$this->assertTrue( $this->integration->init_pixel() );
		$this->assertEquals(
			self::$default_options,
			get_option( WC_Facebookcommerce_Pixel::SETTINGS_KEY )
		);
	}

	public function test_init_pixel_for_admin_user_must_init_pixel_migrating_wc_pixel_settings_to_wp_options() {
		/* Setting up Admin User. */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		set_current_screen( 'edit-post' );

		/* Setting up WC Facebook pixel id. */
		add_option( WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID, '112233445566778899' );

		/* Setting up initial options. */
		add_option(
			WC_Facebookcommerce_Pixel::SETTINGS_KEY,
			self::$default_options
		);

		$this->assertTrue( is_admin(), 'Current user must be an admin user.' );
		$this->assertFalse( has_filter( 'wc_facebook_pixel_id' ) );
		$this->assertTrue( $this->integration->init_pixel() );
		$this->assertEquals(
			[
				WC_Facebookcommerce_Pixel::PIXEL_ID_KEY     => '112233445566778899',
				WC_Facebookcommerce_Pixel::USE_PII_KEY      => true,
				WC_Facebookcommerce_Pixel::USE_S2S_KEY      => false,
				WC_Facebookcommerce_Pixel::ACCESS_TOKEN_KEY => '',
			],
			get_option( WC_Facebookcommerce_Pixel::SETTINGS_KEY )
		);
	}

	public function test_init_pixel_for_admin_user_must_init_pixel_overwrites_pixel_id_with_filter() {
		/* Setting up Admin User. */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		set_current_screen( 'edit-post' );

		/* Setting up initial options. */
		add_option(
			WC_Facebookcommerce_Pixel::SETTINGS_KEY,
			self::$default_options
		);

		add_filter(
			'wc_facebook_pixel_id',
			function ( $wc_facebook_pixel_id ) {
				return '998877665544332211';
			}
		);

		$this->assertTrue( is_admin(), 'Current user must be an admin user.' );
		$this->assertTrue( has_filter( 'wc_facebook_pixel_id' ) );
		$this->assertTrue( $this->integration->init_pixel() );
		$this->assertEquals(
			[
				WC_Facebookcommerce_Pixel::PIXEL_ID_KEY     => '998877665544332211',
				WC_Facebookcommerce_Pixel::USE_PII_KEY      => true,
				WC_Facebookcommerce_Pixel::USE_S2S_KEY      => false,
				WC_Facebookcommerce_Pixel::ACCESS_TOKEN_KEY => '',
			],
			get_option( WC_Facebookcommerce_Pixel::SETTINGS_KEY )
		);
	}

	public function test_init_pixel_for_admin_user_must_init_pixel_overwrites_use_pii_with_filter() {
		/* Setting up Admin User. */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		set_current_screen( 'edit-post' );

		/* Setting up initial options. */
		add_option(
			WC_Facebookcommerce_Pixel::SETTINGS_KEY,
			self::$default_options
		);

		add_filter(
			'wc_facebook_is_advanced_matching_enabled',
			function ( $use_pii ) {
				return false;
			}
		);

		$this->assertTrue( is_admin(), 'Current user must be an admin user.' );
		$this->assertTrue( has_filter( 'wc_facebook_is_advanced_matching_enabled' ) );
		$this->assertTrue( $this->integration->init_pixel() );
		$this->assertEquals(
			[
				WC_Facebookcommerce_Pixel::PIXEL_ID_KEY     => '0',
				WC_Facebookcommerce_Pixel::USE_PII_KEY      => false,
				WC_Facebookcommerce_Pixel::USE_S2S_KEY      => false,
				WC_Facebookcommerce_Pixel::ACCESS_TOKEN_KEY => '',
			],
			get_option( WC_Facebookcommerce_Pixel::SETTINGS_KEY )
		);
	}

	public function test_load_background_sync_process() {

		$this->integration->load_background_sync_process();

		$this->assertInstanceOf( WC_Facebookcommerce_Background_Process::class, $this->integration->background_processor );
		$this->assertEquals(
			10,
			has_action(
				'wp_ajax_ajax_fb_background_check_queue',
				[ $this->integration, 'ajax_fb_background_check_queue' ],
			)
		);
	}

	public function test_get_graph_api() {
		$this->assertInstanceOf( WC_Facebookcommerce_Graph_API::class, $this->integration->get_graph_api() );
	}

	public function test_get_variation_product_item_ids_from_meta() {

		/** @var WC_Product_Variable $parent */
		$product = WC_Helper_Product::create_variation_product();
		$product->add_meta_data( WC_Facebookcommerce_Integration::FB_PRODUCT_GROUP_ID, 'some-facebook-product-group-id' );
		$product->save_meta_data();

		$expected_output = [];
		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$variation->add_meta_data(
				WC_Facebookcommerce_Integration::FB_PRODUCT_ITEM_ID,
				'some-facebook-product-item-id-' . $variation_id
			);
			$variation->save_meta_data();

			$expected_output[ $variation_id ] = 'some-facebook-product-item-id-' . $variation_id;
		}
		/* From Product Meta or FB API. */
		$facebook_product_id = 'some-facebook-product-group-id';

		$output = $this->integration->get_variation_product_item_ids( $product, $facebook_product_id );

		$this->assertEquals( $expected_output, $output );
	}

	public function test_get_variation_product_item_ids_from_facebook_with_no_fb_retailer_id_filters() {

		/** @var WC_Product_Variable $parent */
		$product = WC_Helper_Product::create_variation_product();
		$product->add_meta_data( WC_Facebookcommerce_Integration::FB_PRODUCT_GROUP_ID, 'some-facebook-product-group-id' );
		$product->save_meta_data();

		$expected_output = [];
		$facebook_output = [];
		foreach ( $product->get_children() as $variation_id ) {
			$variation                        = wc_get_product( $variation_id );
			$expected_output[ $variation_id ] = 'some-facebook-api-product-item-id-' . $variation_id;
			$facebook_output[]                = [
				'id'          => 'some-facebook-api-product-item-id-' . $variation_id,
				'retailer_id' => $variation->get_sku() . '_' . $variation_id,
			];
		}
		/* From Product Meta or FB API. */
		$facebook_product_id = 'some-facebook-product-group-id';

		$this->integration->fbgraph = $this->createMock( WC_Facebookcommerce_Graph_API::class );
		$this->integration->fbgraph->expects( $this->once() )
			->method( 'get_product_group_product_ids' )
			->with( $facebook_product_id )
			->willReturn( [ 'data' => $facebook_output ] );

		$output = $this->integration->get_variation_product_item_ids( $product, $facebook_product_id );

		$this->assertFalse( has_filter( 'wc_facebook_fb_retailer_id' ) );
		$this->assertEquals( $expected_output, $output );
	}

	public function test_get_variation_product_item_ids_from_facebook_with_fb_retailer_id_filters() {

		/** @var WC_Product_Variable $parent */
		$product = WC_Helper_Product::create_variation_product();
		$product->add_meta_data( WC_Facebookcommerce_Integration::FB_PRODUCT_GROUP_ID, 'some-facebook-product-group-id' );
		$product->save_meta_data();

		$expected_output = [];
		$facebook_output = [];
		foreach ( $product->get_children() as $variation_id ) {
			$variation                        = wc_get_product( $variation_id );
			$expected_output[ $variation_id ] = 'some-facebook-api-product-item-id-' . $variation_id;
			$facebook_output[]                = [
				'id'          => 'some-facebook-api-product-item-id-' . $variation_id,
				'retailer_id' => $variation->get_sku() . '_' . $variation_id . '_modified',
			];
		}
		/* From Product Meta or FB API. */
		$facebook_product_id = 'some-facebook-product-group-id';

		$this->integration->fbgraph = $this->createMock( WC_Facebookcommerce_Graph_API::class );
		$this->integration->fbgraph->expects( $this->once() )
			->method( 'get_product_group_product_ids' )
			->with( $facebook_product_id )
			->willReturn( [ 'data' => $facebook_output ] );

		add_filter(
			'wc_facebook_fb_retailer_id',
			function ( $retailer_id ) {
				return $retailer_id . '_modified';
			}
		);

		$output = $this->integration->get_variation_product_item_ids( $product, $facebook_product_id );

		$this->assertTrue( has_filter( 'wc_facebook_fb_retailer_id' ) );
		$this->assertEquals( $expected_output, $output );
	}

	public function test_get_catalog_name_returns_catalog_name() {

		$catalog_id = 'some-facebook_catalog-id';

		$this->integration->get_catalog_name( $catalog_id );
	}

	public function test_get_catalog_name_handles_exception() {

		$catalog_id = 'some-facebook_catalog-id';

		$this->integration->get_catalog_name( $catalog_id );
	}
}
