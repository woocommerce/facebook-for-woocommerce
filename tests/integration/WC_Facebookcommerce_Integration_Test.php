<?php

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


	/** @see \WC_Facebookcommerce_Integration::get_scheduled_resync_offset() */
	public function test_get_scheduled_resync_offset() {

		//$this->assertEquals( HOUR_IN_SECONDS, $this->integration->get_scheduled_resync_offset() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_scheduled_resync_offset() */
	public function test_get_scheduled_resync_offset_not_set() {

		//$this->integration->update_option( \WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET, '' );

		//$this->assertNull( $this->integration->get_scheduled_resync_offset() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_scheduled_resync_offset() */
	public function test_get_scheduled_resync_offset_filter() {

		//add_filter( 'wc_facebook_scheduled_resync_offset', function() {
		//	return HOUR_IN_SECONDS * 2;
		//} );

		//$this->assertEquals( HOUR_IN_SECONDS * 2, $this->integration->get_scheduled_resync_offset() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_locale() */
	public function test_get_messenger_locale() {

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

		$this->assertEquals( '#123', $this->integration->get_messenger_color_hex() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_messenger_color_hex() */
	public function test_get_messenger_color_hex_filter() {

		add_filter( 'wc_facebook_messenger_color_hex', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_messenger_color_hex() );
	}


	// /**
	//  * @see \WC_Facebookcommerce_Integration::is_configured()
	//  *
	//  * TODO: uncomment when FBE 2.0 modifications are available {WV 2020-04-22}
	//  *
	//  * @param string $access_token Facebook access token
	//  * @param string $page_id Facebok page ID
	//  * @param bool $expected whether Facebook for WooCommerce is configured or not
	//  *
	//  * @dataProvider provider_is_configured()
	//  */
	// public function test_is_configured( $access_token, $page_id, $expected ) {

	// 	$this->add_settings( [ \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID => $page_id ] );

	// 	$this->integration->update_page_access_token( $access_token );
	// 	$this->integration->init_settings();

	// 	$this->assertSame( $expected, $this->integration->is_configured() );
	// }


	// /** @see test_is_configured() */
	// public function provider_is_configured() {

	// 	return [
	// 		[ 'abc123', 'facebook-page-id', true ],
	// 		[ '',       'facebook-page-id', false ],
	// 		[ 'abc123', '',                 false ],
	// 		[ '',       '',                 false ],
	// 	];
	// }


	/**
	 * @see \WC_Facebookcommerce_Integration::is_configured()
	 *
	 * TODO: consider removing this test when FBE 2.0 modifications are available {WV 2020-04-22}
	 *
	 * @param string $external_merchant_settings_id Facebook external merchant settings ID
	 * @param bool $expected whether Facebook for WooCommerce is configured or not
	 *
	 * @dataProvider provider_is_configured_with_external_merchant_settings_id()
	 */
	public function test_is_configured_with_external_merchant_settings_id( $external_merchant_settings_id, $expected ) {

		$this->integration->update_external_merchant_settings_id( $external_merchant_settings_id );
		$this->integration->init_settings();

		$this->assertSame( $expected, $this->integration->is_configured() );
	}


	/** @see test_is_configured_with_external_merchant_settings_id() */
	public function provider_is_configured_with_external_merchant_settings_id() {

		return [
			[ 'external-merchant-settings-id', true ],
			[ '',                              false ],
		];
	}


	/** @see \WC_Facebookcommerce_Integration::is_advanced_matching_enabled() */
	public function test_is_advanced_matching_enabled() {

		$this->assertTrue( $this->integration->is_advanced_matching_enabled() );

		$this->integration->update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING, 'no' );

		$this->assertFalse( $this->integration->is_advanced_matching_enabled() );
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

		$this->assertTrue( $this->integration->is_product_sync_enabled() );

		$this->integration->update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC, 'no' );

		$this->assertFalse( $this->integration->is_product_sync_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_product_sync_enabled() */
	public function test_is_product_sync_enabled_filter() {

		add_filter( 'wc_facebook_is_product_sync_enabled', function() {
			return false;
		} );

		$this->assertFalse( $this->integration->is_product_sync_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_scheduled_resync_enabled() */
	public function test_is_scheduled_resync_enabled() {

		//$this->assertTrue( $this->integration->is_scheduled_resync_enabled() );

		//$this->integration->update_option( \WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET, '' );

		//$this->assertFalse( $this->integration->is_scheduled_resync_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_scheduled_resync_enabled() */
	public function test_is_scheduled_resync_enabled_filter() {

		//add_filter( 'wc_facebook_is_scheduled_resync_enabled', function() {
		//	return false;
		//} );

		//$this->assertFalse( $this->integration->is_scheduled_resync_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_messenger_enabled() */
	public function test_is_messenger_enabled() {

		$this->assertTrue( $this->integration->is_messenger_enabled() );

		$this->integration->update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, 'no' );

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

		$this->integration->update_option( \WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE, 'yes' );

		$this->assertTrue( $this->integration->is_debug_mode_enabled() );
	}


	/** @see \WC_Facebookcommerce_Integration::is_debug_mode_enabled() */
	public function test_is_debug_mode_enabled_filter() {

		add_filter( 'wc_facebook_is_debug_mode_enabled', function() {
			return true;
		} );

		$this->assertTrue( $this->integration->is_debug_mode_enabled() );
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::ajax_save_fb_settings()
	 *
	 * @param string $setting the ID of the setting being tested
	 * @param string $param the name of the submitted param
	 * @param string $submitted the value submitted
	 * @param string $expected the value that should be stored
	 *
	 * @dataProvider provider_ajax_save_fb_settings();
	 */
	public function test_ajax_save_fb_settings( $setting, $param, $submitted, $expected = null ) {

		// force wp_send_json() to call wp_die()
		add_filter( 'wp_doing_ajax', '__return_true' );

		// disable wp_die()
		add_filter( 'wp_die_ajax_handler', function() { return '__return_false'; } );

		// login as administrator
		$user = new WP_User( wp_insert_user( [ 'user_login' => 'admin_' . wp_rand(), 'user_pass' => 'password' ] ) );
		$user->add_role( 'administrator' );

		wp_set_current_user( $user->ID );

		// bypass nonce verification, setup plugin flag, and add submitted param
		$_REQUEST[ '_wpnonce' ]               = wp_create_nonce( 'wc_facebook_settings_jsx' );
		$_REQUEST['facebook_for_woocommerce'] = true;
		$_REQUEST[ $param ]                   = $submitted;

		// some settings won't be set unless an access is already stored
		$this->integration->update_page_access_token( 'abc123' );

		$this->integration->ajax_save_fb_settings();

		$this->assertEquals( null === $expected ? $submitted : $expected, $this->integration->get_option( $setting ) );
	}


	/** @see test_ajax_save_fb_settings() */
	public function provider_ajax_save_fb_settings() {

		return [
			[ \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID,        'pixel_id',                                    '9876'               ],
			[ \WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING, 'pixel_use_pii',                               'true'       , 'yes' ],
			[ \WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING, 'pixel_use_pii',                               'not-bool'   , 'no'  ],
			[ \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID,         'page_id',                                     '8765'               ],
			[ \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER,         'is_messenger_chat_plugin_enabled',            'true'       , 'yes' ],
			[ \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER,         'is_messenger_chat_plugin_enabled',            'not-bool'   , 'no'  ],
			[ \WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING,       'msger_chat_customization_greeting_text_code', 'Hello!'             ],
			[ \WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE,         'msger_chat_customization_locale',             'de_DE'              ],
			[ \WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX,      'msger_chat_customization_theme_color_code',   '#ee23fc'            ],
			[ \WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX,      'msger_chat_customization_theme_color_code',   'not-a-color', ''    ],
		];
	}


	/** @see \WC_Facebookcommerce_Integration::init_form_fields() */
	public function test_init_form_fields() {

		$this->integration->init_form_fields();

		$fields = $this->integration->get_form_fields();

		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE, $fields );
		//$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX, $fields );
		$this->assertArrayHasKey( \WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE, $fields );
	}


	/** @see \WC_Facebookcommerce_Integration::validate_resync_schedule_field() */
	public function test_validate_resync_schedule_field_resync_disabled() {

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_meridiem' => 'am',
		//];

		//$this->assertEquals( '', $this->integration->validate_resync_schedule_field( '', '' ) );
	}


	/** @see \WC_Facebookcommerce_Integration::validate_resync_schedule_field() */
	public function test_validate_resync_schedule_field_empty_resync_time() {

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_enabled'  => 1,
		//	'woocommerce_facebookcommerce_scheduled_resync_hours'    => '',
		//	'woocommerce_facebookcommerce_scheduled_resync_minutes'  => '',
		//	'woocommerce_facebookcommerce_scheduled_resync_meridiem' => 'am',
		//];

		//$this->assertEquals( '', $this->integration->validate_resync_schedule_field( '', '' ) );
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::validate_resync_schedule_field()
	 */
	public function test_validate_resync_schedule_field_invalid_resync_time() {

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_enabled'  => 1,
		//	'woocommerce_facebookcommerce_scheduled_resync_hours'    => '30',
		//	'woocommerce_facebookcommerce_scheduled_resync_minutes'  => '00',
		//	'woocommerce_facebookcommerce_scheduled_resync_meridiem' => 'am',
		//];

		//$this->expectException( \Exception::class );
		//$this->expectExceptionMessage( 'Invalid resync schedule time: 30:00 am' );

		//$this->integration->validate_resync_schedule_field( '', '' );
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::validate_resync_schedule_field()
	 */
	public function test_validate_resync_schedule_field_invalid_resync_meridiem() {

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_enabled'  => 1,
		//	'woocommerce_facebookcommerce_scheduled_resync_hours'    => '20',
		//	'woocommerce_facebookcommerce_scheduled_resync_minutes'  => '00',
		//	'woocommerce_facebookcommerce_scheduled_resync_meridiem' => 'am',
		//];

		//$this->expectException( \Exception::class );
		//$this->expectExceptionMessage( 'Invalid resync schedule time: 20:00 am' );

		//$this->integration->validate_resync_schedule_field( '', '' );
	}


	/** @see \WC_Facebookcommerce_Integration::validate_resync_schedule_field() */
	public function test_validate_resync_schedule_field_valid_resync_times() {

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_enabled'  => 1,
		//	'woocommerce_facebookcommerce_scheduled_resync_hours'    => '10',
		//	'woocommerce_facebookcommerce_scheduled_resync_minutes'  => '00',
		//	'woocommerce_facebookcommerce_scheduled_resync_meridiem' => 'am',
		//];

		//$this->assertEquals( 10 * HOUR_IN_SECONDS, $this->integration->validate_resync_schedule_field( '', '' ) );

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_enabled'  => 1,
		//	'woocommerce_facebookcommerce_scheduled_resync_hours'    => '10',
		//	'woocommerce_facebookcommerce_scheduled_resync_minutes'  => '00',
		//	'woocommerce_facebookcommerce_scheduled_resync_meridiem' => 'pm',
		//];

		//$this->assertEquals( 22 * HOUR_IN_SECONDS, $this->integration->validate_resync_schedule_field( '', '' ) );

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_enabled'  => 1,
		//	'woocommerce_facebookcommerce_scheduled_resync_hours'    => '6',
		//	'woocommerce_facebookcommerce_scheduled_resync_minutes'  => '',
		//	'woocommerce_facebookcommerce_scheduled_resync_meridiem' => 'pm',
		//];

		//$this->assertEquals( 18 * HOUR_IN_SECONDS, $this->integration->validate_resync_schedule_field( '', '' ) );

		//$_POST = [
		//	'woocommerce_facebookcommerce_scheduled_resync_enabled'  => 1,
		//	'woocommerce_facebookcommerce_scheduled_resync_hours'    => '18',
		//	'woocommerce_facebookcommerce_scheduled_resync_minutes'  => '',
		//];

		//$this->assertEquals( 18 * HOUR_IN_SECONDS, $this->integration->validate_resync_schedule_field( '', '' ) );
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::validate_messenger_greeting_field()
	 *
	 * @dataProvider validate_messenger_greeting_field_provider
	 *
	 * @param null|string $value value to validate
	 * @param string $expected expected result
	 * @param bool $exception whether an exception is expected
	 */
	public function test_validate_messenger_greeting_field( $value, $expected, $exception = false ) {

		$integration = $this->integration;

		$fields = $integration->get_form_fields();
		$key    = \WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING;
		$field  = $fields[ $key ];

		if ( $exception ) {

			$this->expectException( \SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception::class );

			$integration->get_field_value( $key, $field, [ $integration->get_field_key( $key ) => $value ] );

		} else {

			$this->assertSame( $expected, $integration->get_field_value( $key, $field, [ $integration->get_field_key( $key ) => $value ] ) );
		}
	}


	/**
	 * Provider for test_validate_messenger_greeting_field()
	 *
	 * @return array
	 */
	public function validate_messenger_greeting_field_provider() {

		return [
			[ null, '' ],
			[ 'This is a valid value', 'This is a valid value' ],
			[ 'This is a valid value that is exactly the max length and should still get saved.', 'This is a valid value that is exactly the max length and should still get saved.' ],
			[ 'This is a valid value with spèciäl characters and should still get saved okay???', 'This is a valid value with spèciäl characters and should still get saved okay???' ],
			[ 'This is a valid value that exceeds the max length and should definitely not get saved.', '', true ],
		];
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
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS => [ 1, 2 ],
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS      => [ 3, 4 ],
			\WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE      => \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_STANDARD,
			//\WC_Facebookcommerce_Integration::SETTING_SCHEDULED_RESYNC_OFFSET       => HOUR_IN_SECONDS,
			\WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE              => 'locale',
			\WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING            => 'How can we help you?',
			\WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX           => '#123',
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING      => 'yes',
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC           => 'yes',
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER              => 'yes',
		];

		update_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', array_merge( $defaults, $settings ) );
	}


}

