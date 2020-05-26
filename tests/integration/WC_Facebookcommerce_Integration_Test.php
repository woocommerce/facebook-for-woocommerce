<?php

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * Tests the integration class.
 */
class WC_Facebookcommerce_Integration_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \WC_Facebookcommerce_Integration integration instance */
	private $integration;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		$this->integration = facebook_for_woocommerce()->get_integration();

		$this->add_options();
		$this->add_settings();

		$this->integration->init_settings();
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see \WC_Facebookcommerce_Integration::get_page_access_token() */
	public function test_get_page_access_token() {

		// we have to call the setter here because although the option is set, the getter reads from the property first
		$this->integration->update_page_access_token( 'abc123' );
		$this->assertEquals( 'abc123', $this->integration->get_page_access_token() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_page_access_token() */
	public function test_get_page_access_token_filter() {

		add_filter( 'wc_facebook_page_access_token', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_page_access_token() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_product_catalog_id() */
	public function test_get_product_catalog_id() {

		// we have to call the setter here because although the option is set, the getter reads from the property first
		$this->integration->update_product_catalog_id( 'def456' );
		$this->assertEquals( 'def456', $this->integration->get_product_catalog_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_product_catalog_id() */
	public function test_get_product_catalog_id_filter() {

		add_filter( 'wc_facebook_product_catalog_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_product_catalog_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_external_merchant_settings_id() */
	public function test_get_external_merchant_settings_id() {

		$this->assertEquals( 'ghi789', $this->integration->get_external_merchant_settings_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_external_merchant_settings_id() */
	public function test_get_external_merchant_settings_id_filter() {

		add_filter( 'wc_facebook_external_merchant_settings_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_external_merchant_settings_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_feed_id() */
	public function test_get_feed_id() {

		$this->assertEquals( 'jkl012', $this->integration->get_feed_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_feed_id() */
	public function test_get_feed_id_filter() {

		add_filter( 'wc_facebook_feed_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_feed_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_upload_id() */
	public function test_get_upload_id() {

		$this->assertEquals( 'lorem123', $this->integration->get_upload_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_upload_id() */
	public function test_get_upload_id_filter() {

		add_filter( 'wc_facebook_upload_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_upload_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_pixel_install_time() */
	public function test_get_pixel_install_time() {

		$this->assertSame( 123, $this->integration->get_pixel_install_time() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_pixel_install_time() */
	public function test_get_pixel_install_time_filter() {

		add_filter( 'wc_facebook_pixel_install_time', function() {
			return 321;
		} );

		$this->assertSame( 321, $this->integration->get_pixel_install_time() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_js_sdk_version() */
	public function test_get_js_sdk_version() {

		$this->assertSame( 'v2.9', $this->integration->get_js_sdk_version() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_js_sdk_version() */
	public function test_get_js_sdk_version_filter() {

		add_filter( 'wc_facebook_js_sdk_version', function() {
			return 'v4.0';
		} );

		$this->assertSame( 'v4.0', $this->integration->get_js_sdk_version() );
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_page_access_token()
	 *
	 * @param string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_page_access_token
	 */
	public function test_update_page_access_token( $value, $expected ) {

		$this->integration->update_page_access_token( $value );

		$this->assertEquals( $expected, $this->integration->get_page_access_token() );
		$this->assertEquals( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN ) );
	}


	/** @see test_update_page_access_token() */
	public function provider_update_page_access_token() {

		return [
			[ 'new-token', 'new-token' ],
			[ [ 1, 2 ], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_product_catalog_id()
	 *
	 * @param string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_product_catalog_id
	 */
	public function test_update_product_catalog_id( $value, $expected ) {

		$this->integration->update_product_catalog_id( $value );

		$this->assertEquals( $expected, $this->integration->get_product_catalog_id() );
		$this->assertEquals( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID ) );
	}


	/** @see test_update_product_catalog_id() */
	public function provider_update_product_catalog_id() {

		return [
			[ 'new-id', 'new-id' ],
			[ [ 1, 2 ], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_external_merchant_settings_id()
	 *
	 * @param string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_external_merchant_settings_id
	 */
	public function test_update_external_merchant_settings_id( $value, $expected ) {

		$this->integration->update_external_merchant_settings_id( $value );

		$this->assertEquals( $expected, $this->integration->get_external_merchant_settings_id() );
		$this->assertEquals( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID ) );
	}


	/** @see test_update_external_merchant_settings_id() */
	public function provider_update_external_merchant_settings_id() {

		return [
			[ 'new-id', 'new-id' ],
			[ [ 1, 2 ], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_feed_id()
	 *
	 * @param string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_feed_id
	 */
	public function test_update_feed_id( $value, $expected ) {

		$this->integration->update_feed_id( $value );

		$this->assertEquals( $expected, $this->integration->get_feed_id() );
		$this->assertEquals( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_FEED_ID ) );
	}


	/** @see test_update_feed_id() */
	public function provider_update_feed_id() {

		return [
			[ 'new-id', 'new-id' ],
			[ [ 1, 2 ], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_upload_id()
	 *
	 * @param string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_feed_id
	 */
	public function test_update_upload_id( $value, $expected ) {

		$this->integration->update_upload_id( $value );

		$this->assertEquals( $expected, $this->integration->get_upload_id() );
		$this->assertEquals( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_UPLOAD_ID ) );
	}


	/** @see test_update_upload_id() */
	public function provider_update_upload_id() {

		return [
			[ 'new-id', 'new-id' ],
			[ [ 1, 2 ], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_pixel_install_time()
	 *
	 * @param int|string|null|array $value value to set
	 * @param int $expected expected return value
	 * @param int|string $expected_option expected stored value
	 *
	 * @dataProvider provider_update_pixel_install_time
	 */
	public function test_update_pixel_install_time( $value, $expected, $expected_option ) {

		$this->integration->update_pixel_install_time( $value );

		$this->assertSame( $expected, $this->integration->get_pixel_install_time() );
		$this->assertEquals( $expected_option, get_option( \WC_Facebookcommerce_Integration::OPTION_PIXEL_INSTALL_TIME ) );
	}


	/** @see test_update_pixel_install_time() */
	public function provider_update_pixel_install_time() {

		return [
			[ 1234, 1234, 1234 ],
			[ 'non-int', 0, '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_js_sdk_version()
	 *
	 * @param int|string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_js_sdk_version
	 */
	public function test_update_js_sdk_version( $value, $expected ) {

		$this->integration->update_js_sdk_version( $value );

		$this->assertSame( $expected, $this->integration->get_js_sdk_version() );
		$this->assertSame( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_JS_SDK_VERSION ) );
	}


	/** @see test_update_js_sdk_version */
	public function provider_update_js_sdk_version() {

		return [
			[ 'v3.2', 'v3.2' ],
			[ 3.2, '' ],
		];
	}


	/** @see \WC_Facebookcommerce_Integration::get_facebook_page_id() */
	public function test_get_facebook_page_id() {

		$this->assertEquals( 'facebook-page-id', $this->integration->get_facebook_page_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_facebook_page_id() */
	public function test_get_facebook_page_id_filter() {

		add_filter( 'wc_facebook_page_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_facebook_page_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_facebook_pixel_id() */
	public function test_get_facebook_pixel_id() {

		$this->assertEquals( 'facebook-pixel-id', $this->integration->get_facebook_pixel_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_facebook_pixel_id() */
	public function test_get_facebook_pixel_id_filter() {

		add_filter( 'wc_facebook_pixel_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_facebook_pixel_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_excluded_product_category_ids() */
	public function test_get_excluded_product_category_ids() {

		update_option( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, [ '123', '456' ] );

		$ids = $this->integration->get_excluded_product_category_ids();

		$this->assertIsArray( $ids );
		$this->assertNotEmpty( $ids );
	}


	/** @see \WC_Facebookcommerce_Integration::get_excluded_product_category_ids() */
	public function test_get_excluded_product_category_ids_filter() {

		add_filter( 'wc_facebook_excluded_product_category_ids', function() {
			return [];
		} );

		$this->assertEmpty( $this->integration->get_excluded_product_category_ids() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_excluded_product_tag_ids() */
	public function test_get_excluded_product_tag_ids() {

		update_option( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS, [ '123', '456' ] );

		$ids = $this->integration->get_excluded_product_tag_ids();

		$this->assertIsArray( $ids );
		$this->assertNotEmpty( $ids );
	}


	/** @see \WC_Facebookcommerce_Integration::get_excluded_product_tag_ids() */
	public function test_get_excluded_product_tag_ids_filter() {

		add_filter( 'wc_facebook_excluded_product_tag_ids', function() {
			return [];
		} );

		$this->assertEmpty( $this->integration->get_excluded_product_tag_ids() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_product_description_mode() */
	public function test_get_product_description_mode() {

		$this->assertEquals( \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_STANDARD, $this->integration->get_product_description_mode() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_product_description_mode() */
	public function test_get_product_description_mode_filter() {

		add_filter( 'wc_facebook_product_description_mode', function() {
			return \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT;
		} );

		$this->assertEquals( \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT, $this->integration->get_product_description_mode() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_locale() */
	public function test_get_messenger_locale() {

		update_option( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE, 'locale' );

		$this->assertEquals( 'locale', $this->integration->get_messenger_locale() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_locale() */
	public function test_get_messenger_locale_filter() {

		add_filter( 'wc_facebook_messenger_locale', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_messenger_locale() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_greeting() */
	public function test_get_messenger_greeting() {

		update_option( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING, 'How can we help you?' );

		$this->assertEquals( 'How can we help you?', $this->integration->get_messenger_greeting() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_greeting() */
	public function test_get_messenger_greeting_filter() {

		add_filter( 'wc_facebook_messenger_greeting', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_messenger_greeting() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_greeting_max_characters() */
	public function test_get_messenger_greeting_max_characters() {

		$this->assertEquals( 80, $this->integration->get_messenger_greeting_max_characters() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_greeting_max_characters() */
	public function test_get_messenger_greeting_max_characters_filter() {

		add_filter( 'wc_facebook_messenger_greeting_max_characters', function() {
			return 20;
		} );

		$this->assertEquals( 20, $this->integration->get_messenger_greeting_max_characters() );

		// ensure the value is never corrupted
		add_filter( 'wc_facebook_messenger_greeting_max_characters', function() {
			return 'bad value';
		} );

		$this->assertEquals( 80, $this->integration->get_messenger_greeting_max_characters() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_color_hex() */
	public function test_get_messenger_color_hex() {

		update_option( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX, '#123' );

		$this->assertEquals( '#123', $this->integration->get_messenger_color_hex() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_color_hex() */
	public function test_get_messenger_color_hex_filter() {

		add_filter( 'wc_facebook_messenger_color_hex', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_messenger_color_hex() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_page() */
	public function test_get_page() {

		if ( ! class_exists( API\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Response.php';
		}

		if ( ! class_exists( API\Pages\Read\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Pages/Read/Response.php';
		}

		if ( ! class_exists( API::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API.php';
		}

		$response_data = [ 'name' => 'Test Page', 'link' => 'https://example.org' ];
		$response      = new API\Pages\Read\Response( json_encode( $response_data ) );
		$api           = $this->make( API::class, [ 'get_page' => $response ] );

		$expected_result = [ 'name' => 'Test Page', 'url' => 'https://example.org' ];

		$this->check_get_page( $api, 'access_token', $expected_result );
	}


	/**
	 * Tests that that \WC_Facebookcommerce_Integration::get_page() returns the expected result for the given access token and API response.
	 *
	 * @param API $api API stub
	 * @param string $access_token configured access token
	 * @param array $expected_result expected return value
	 */
	private function check_get_page( $api, $access_token, $expected_result ) {

		facebook_for_woocommerce()->get_connection_handler()->update_access_token( $access_token );

		// replace the API instance with our stub
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		// remove saved page information
		$property = new ReflectionProperty( \WC_Facebookcommerce_Integration::class, 'page' );
		$property->setAccessible( true );
		$property->setValue( $this->integration, null );

		// make \WC_Facebookcommerce_Integration::get_page() accessible
		$method = new ReflectionMethod( \WC_Facebookcommerce_Integration::class, 'get_page' );
		$method->setAccessible( true );

		$this->assertEquals( $expected_result, $method->invoke( $this->integration ) );
	}


	/** @see \WC_Facebookcommerce_Integration::get_page() */
	public function test_get_page_with_exception() {

		if ( ! class_exists( API::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API.php';
		}

		$api = $this->make( API::class, [ 'get_page' => static function() {
			throw new Framework\SV_WC_API_Exception();
		} ] );

		// it should return an empty array if no page information can't be retrieved
		$expected_result = [];

		$this->check_get_page( $api, 'access_token', $expected_result );
	}


	/** @see \WC_Facebookcommerce_Integration::get_page() */
	public function test_get_page_if_plugin_is_not_configured() {

		// irrelevant because the API wont be used
		$api = null;

		// remove the access token to prevent the plugin from trying to use the API to retrieve page information
		$access_token = null;

		// it should return an empty array if there is no page information available
		$expected_result = [];

		$this->check_get_page( $api, $access_token, $expected_result );
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::get_page_name()
	 *
	 * @param array $page stored page information
	 * @param string $page_name expected page name
	 *
	 * @dataProvider provider_get_page_name
	 */
	public function test_get_page_name( $page, $page_name ) {

		$property = new ReflectionProperty( \WC_Facebookcommerce_Integration::class, 'page' );
		$property->setAccessible( true );
		$property->setValue( $this->integration, $page );

		$this->assertEquals( $page_name, $this->integration->get_page_name() );
	}


	/** @see test_get_page_name() */
	public function provider_get_page_name() {

		return [
			[ [ 'name' => 'Test Page', 'url' => 'https://example.org' ], 'Test Page' ],
			[ [], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::get_page_url()
	 *
	 * @param array $page stored page information
	 * @param string $page_url expected page URL
	 *
	 * @dataProvider provider_get_page_url
	 */
	public function test_get_page_url( $page, $page_url ) {

		$property = new ReflectionProperty( \WC_Facebookcommerce_Integration::class, 'page' );
		$property->setAccessible( true );
		$property->setValue( $this->integration, $page );

		$this->assertEquals( $page_url, $this->integration->get_page_url() );
	}


	/** @see test_get_page_url() */
	public function provider_get_page_url() {

		return [
			[ [ 'name' => 'Test Page', 'url' => 'https://example.org' ], 'https://example.org' ],
			[ [], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::is_configured()
	 *
	 * @param string $access_token Facebook access token
	 * @param string $page_id Facebok page ID
	 * @param bool $expected whether Facebook for WooCommerce is configured or not
	 *
	 * @dataProvider provider_is_configured()
	 */
	public function test_is_configured( $access_token, $page_id, $expected ) {

		$this->add_settings( [ \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID => $page_id ] );

		facebook_for_woocommerce()->get_connection_handler()->update_access_token( $access_token );

		$this->assertSame( $expected, $this->integration->is_configured() );
	}


	/** @see test_is_configured() */
	public function provider_is_configured() {

		// TODO: uncomment the third case after we start retrieving Page IDs using Handlers\Connection::get_asset_ids() {WV-2020-05-13}

		return [
			[ 'abc123', 'facebook-page-id', true ],
			[ '',       'facebook-page-id', false ],
			// [ 'abc123', '',                 false ],
			[ '',       '',                 false ],
		];
	}


	/** @see \WC_Facebookcommerce_Integration::is_use_s2s_enabled() */
	public function test_is_use_s2s_enabled() {
		//For now we are testing that the class returns the default value
		$this->assertFalse( $this->integration->is_use_s2s_enabled() );

	}


	/** @see \WC_Facebookcommerce_Integration::get_access_token() */
	public function test_get_access_token() {
		//For now we are testing that the class returns the default value
		$this->assertEmpty( $this->integration->get_access_token() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_advanced_matching_enabled() */
	public function test_is_advanced_matching_enabled_filter() {

		add_filter( 'wc_facebook_is_advanced_matching_enabled', function() {
			return false;
		} );

		$this->assertFalse( $this->integration->is_advanced_matching_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_product_sync_enabled() */
	public function test_is_product_sync_enabled() {

		update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC, 'yes' );

		$this->assertTrue( $this->integration->is_product_sync_enabled() );

		update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC, 'no' );

		$this->assertFalse( $this->integration->is_product_sync_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_product_sync_enabled() */
	public function test_is_product_sync_enabled_filter() {

		add_filter( 'wc_facebook_is_product_sync_enabled', function() {
			return false;
		} );

		$this->assertFalse( $this->integration->is_product_sync_enabled() );
	}

	/**
	 * @see product_should_be_synced
	 *
	 * @param bool $sync_enabled whether product sync is enabled at the plugin level
	 * @param \WC_Product $product product object
	 * @param bool $should_be_synced expected return value
	 *
	 * @dataProvider provider_product_should_be_synced
	 */
	public function test_product_should_be_synced( $sync_enabled, $product, $should_be_synced ) {

		add_filter( 'wc_facebook_is_product_sync_enabled', function() use ( $sync_enabled) {
			return $sync_enabled;
		} );

		$method = new ReflectionMethod( $this->integration, 'product_should_be_synced' );
		$method->setAccessible( true );

		$this->assertSame( $should_be_synced, $method->invoke( $this->integration, $product ) );
	}


	/** @see test_product_should_be_synced */
	public function provider_product_should_be_synced() {

		$draft_product = new \WC_Product();
		$draft_product->set_status( 'draft');
		$draft_product->save();

		$product_with_sync_disabled = new \WC_Product();
		$product_with_sync_disabled->update_meta_data( Products::SYNC_ENABLED_META_KEY, 'no' );
		$product_with_sync_disabled->save_meta_data();
		$product_with_sync_disabled->save();

		$published_product = new \WC_Product();
		$published_product->set_status( 'publish' );
		$published_product->save();

		return [
			'sync disabled'              => [ false, null, false ],
			'not a product'              => [ true, null, false ],
			'draft product'              => [ true, $draft_product, false ],
			'product with sync disabled' => [ true, $product_with_sync_disabled, false ],
			'published product'          => [ true, $published_product, true ],
		];
	}


	/** @see \WC_Facebookcommerce_Integration::is_messenger_enabled() */
	public function test_is_messenger_enabled() {

		update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, 'yes' );

		$this->assertTrue( $this->integration->is_messenger_enabled() );

		update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, 'no' );

		$this->assertFalse( $this->integration->is_messenger_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_messenger_enabled() */
	public function test_is_messenger_enabled_filter() {

		add_filter( 'wc_facebook_is_messenger_enabled', function() {
			return false;
		} );

		$this->assertFalse( $this->integration->is_messenger_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_debug_mode_enabled() */
	public function test_is_debug_mode_enabled() {

		// defaults to false
		$this->assertFalse( $this->integration->is_debug_mode_enabled() );

		update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE, 'yes' );

		$this->assertTrue( $this->integration->is_debug_mode_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_debug_mode_enabled() */
	public function test_is_debug_mode_enabled_filter() {

		add_filter( 'wc_facebook_is_debug_mode_enabled', function() {
			return true;
		} );

		$this->assertTrue( $this->integration->is_debug_mode_enabled() );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Adds configured options.
	 */
	private function add_options() {

		update_option( WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN, 'abc123' );
		update_option( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, 'def456' );
		update_option( WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, 'ghi789' );
		update_option( WC_Facebookcommerce_Integration::OPTION_FEED_ID, 'jkl012' );
		update_option( WC_Facebookcommerce_Integration::OPTION_UPLOAD_ID, 'lorem123' );
		update_option( WC_Facebookcommerce_Integration::OPTION_PIXEL_INSTALL_TIME, 123 );
		update_option( WC_Facebookcommerce_Integration::OPTION_JS_SDK_VERSION, 'v2.9' );

		// TODO: remove once these properties are no longer set directly in the constructor
		$this->integration->external_merchant_settings_id = null;
	}


	/**
	 * Adds the integration settings.
	 */
	private function add_settings( $settings = [] ) {

		$defaults = [
			\WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID              => 'facebook-page-id',
			\WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID             => 'facebook-pixel-id',
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING      => 'yes',
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC           => 'yes',
		];

		update_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', array_merge( $defaults, $settings ) );
	}


}
