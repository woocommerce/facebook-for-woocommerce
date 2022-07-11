<?php
declare( strict_types=1 );

require_once __DIR__ . '/../../includes/fbgraph.php';

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_API_Exception;

/**
 * Unit tests for Facebook Graph API calls.
 */
class WCFacebookCommerceGraphAPITest extends WP_UnitTestCase {

	/** @var WC_Facebookcommerce_Graph_API */
	private $api;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api = new WC_Facebookcommerce_Graph_API( 'test-api-key-9678djyad552' );
	}

	/**
	 * Test Authorisation header added with a proper key set into.
	 *
	 * @return void
	 * @throws SV_WC_API_Exception Throws exception in case of connection error.
	 */
	public function test_api_has_authorisation_header_with_proper_api_key() {
		$api      = new WC_Facebookcommerce_Graph_API( 'test-api-key-09869asfdasf56' );
		$response = function( $result, $parsed_args ) {
			$this->assertArrayHasKey( 'headers', $parsed_args );
			$this->assertArrayHasKey( 'Authorization', $parsed_args['headers'] );
			$this->assertEquals( 'Bearer test-api-key-09869asfdasf56', $parsed_args['headers']['Authorization'] );
			return [];
		};
		add_filter( 'pre_http_request', $response, 10, 2 );

		/* Call any api, does not matter much, all the api calls must have Auth header. */
		$api->is_product_catalog_valid( 'product-catalog-id-654129' );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_process_response_body_parses_response_body() {
		$expected = [
			'id'     => '2536275516506259',
			'name'   => 'Facebook for WooCommerce 2 - Catalog',
			'custom' => 'John Doe',
		];

		$response = function() {
			return [ 'body' => '{"name":"Facebook for WooCommerce 2 - Catalog","custom":"John Doe","id":"2536275516506259"}' ];
		};
		add_filter( 'pre_http_request', $response );

		$data = $this->api->get_catalog( '2536275516506259' );

		$this->assertEquals( $expected, $data );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_process_response_body_throws_an_exception_when_gets_connection_wp_error() {
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'WP Error Message' );

		$response = function() {
			return new WP_Error( 007, 'WP Error Message' );
		};
		add_filter( 'pre_http_request', $response );

		$this->api->get_catalog( '2536275516506259' );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_process_response_body_throws_an_exception_when_there_is_no_response_body() {
		$this->expectException( JsonException::class );
		$this->expectExceptionMessage( 'Syntax error' );

		$response = function() {
			return [];
		};
		add_filter( 'pre_http_request', $response );

		$this->api->get_catalog( '2536275516506259' );
	}

	/**
	 * Tests if product catalog valid api return true in case it gets a successful response from Facebook.
	 *
	 * @return void
	 * @throws SV_WC_API_Exception Throws exception in case of connection error.
	 */
	public function test_is_product_catalog_valid_returns_true() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-654129', $url );
			return [
				'response' => [
					'code' => 200,
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$is_valid = $this->api->is_product_catalog_valid( 'product-catalog-id-654129' );

		$this->assertTrue( $is_valid );
	}

	/**
	 * Tests if product catalog valid api return false in case it gets a failed response from Facebook.
	 *
	 * @return void
	 * @throws SV_WC_API_Exception Throws exception in case of connection error.
	 */
	public function test_is_product_catalog_valid_returns_false() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-654129', $url );
			return [
				'response' => [
					'code' => 400,
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$is_valid = $this->api->is_product_catalog_valid( 'product-catalog-id-654129' );

		$this->assertFalse( $is_valid );
	}

	/**
	 * Test if the method throws an exception in case of connection error.
	 *
	 * @return void
	 * @throws SV_WC_API_Exception Throws exception in case of connection error.
	 */
	public function test_is_product_catalog_valid_throws_an_error() {
		$this->expectException( SV_WC_API_Exception::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'message' );

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-2174129410', $url );
			return new WP_Error( 007, 'message' );
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$this->api->is_product_catalog_valid( 'product-catalog-id-2174129410' );
	}

	/**
	 * Tests a call for catalog name and id.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_get_catalog_returns_catalog_id_and_name() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2536275516506259?fields=name', $url );
			return [
				'body'     => '{"name":"Facebook for WooCommerce 2 - Catalog","id":"2536275516506259"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$data = $this->api->get_catalog( '2536275516506259' );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );

		$this->assertEquals( 'Facebook for WooCommerce 2 - Catalog', $data['name'] );
		$this->assertEquals( '2536275516506259', $data['id'] );
	}

	/**
	 * Tests a call for Facebook user id.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_get_user_must_return_facebook_user_id() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/me', $url );
			return [
				'body'     => '{"id":"2525362755165069"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$data = $this->api->get_user();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( '2525362755165069', $data['id'] );
	}

	/**
	 * Tests a call to delete permissions.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_revoke_user_permission_must_result_success() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'DELETE', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2525362755165069/permissions/manage_business_extension', $url );
			return [
				'body'     => '{"success":true}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->revoke_user_permission( '2525362755165069', 'manage_business_extension' );

		$this->assertArrayhasKey( 'success', $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Tests update item commands to Facebook.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_send_item_updates_returns_handles() {
		$items = [
			'allow_upsert' => true,
			'requests'     => [
				[
					'method' => 'UPDATE',
					'data'   => [
						'id'                           => 'woo-vneck-tee-red_26',
						'title'                        => 'V-Neck T-Shirt',
						'description'                  => 'short product description \u05ea\u05d9\u05d0\u05d5\u05e8 \u05de\u05d5\u05e6\u05e8 \u05e7\u05e6\u05e8',
						'image_link'                   => 'https://wordpress-facebook.ddev.site/wp-content/uploads/2022/04/vneck-tee-2.jpg',
						'link'                         => 'https://wordpress-facebook.ddev.site/product/v-neck-t-shirt/?attribute_pa_color=red&attribute_pa_size=customvariable-size&attribute_semicolon=Third%3Ahigh',
						'price'                        => '20 TRY',
						'availability'                 => 'in stock',
						'visibility'                   => 'published',
						'sale_price_effective_date'    => '1970-01-29T00:00+00:00/1970-01-30T23:59+00:00',
						'sale_price'                   => '20 TRY',
						'google_product_category'      => '1604',
						'size'                         => 'Custom:variable size',
						'color'                        => 'Red',
						'item_group_id'                => 'woo-vneck-tee_12',
						'condition'                    => 'new',
						'additional_variant_attribute' => 'Semicolon:Third high',
					],
				],
				[
					'method' => 'UPDATE',
					'data'   => [
						'id'                           => 'woo-vneck-tee-green_27',
						'title'                        => 'V-Neck T-Shirt',
						'description'                  => 'short product description \u05ea\u05d9\u05d0\u05d5\u05e8 \u05de\u05d5\u05e6\u05e8 \u05e7\u05e6\u05e8',
						'image_link'                   => 'https://wordpress-facebook.ddev.site/wp-content/uploads/2022/04/vneck-tee-green-1.jpg',
						'link'                         => 'https://wordpress-facebook.ddev.site/product/v-neck-t-shirt/?attribute_pa_color=green&attribute_pa_size=customvariable-size&attribute_semicolon=Second%3Amid',
						'price'                        => '20 TRY',
						'availability'                 => 'in stock',
						'visibility'                   => 'published',
						'sale_price_effective_date'    => '1970-01-29T00:00+00:00/1970-01-30T23:59+00:00',
						'sale_price'                   => '20 TRY',
						'google_product_category'      => '1604',
						'size'                         => 'Custom:variable size',
						'color'                        => 'Green',
						'item_group_id'                => 'woo-vneck-tee_12',
						'condition'                    => 'new',
						'additional_variant_attribute' => 'Semicolon:Second mid',
					],
				],
			],
			'item_type'    => 'PRODUCT_ITEM',
		];

		$response = function( $result, $parsed_args, $url ) use ( $items ) {
			$this->assertEquals( 'POST', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2536275516506259/items_batch', $url );
			$body = [
				'allow_upsert' => true,
				'requests'     => json_encode( $items ),
				'item_type'    => 'PRODUCT_ITEM',
			];
			$this->assertEquals( $body, $parsed_args['body'] );
			return [
				'body'     => '{"handles":["AcyF-IFFFMif2xx6oUlkHF7qbutTBr0Q2jjWRNfDNXD_VjontQqZp79tt0GL03L3nqoYRrv5RpqDaC8WCoB0jLtG"]}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->send_item_updates( '2536275516506259', $items );

		$this->assertArrayHasKey( 'handles', $result );
		$this->assertEquals( [ 'AcyF-IFFFMif2xx6oUlkHF7qbutTBr0Q2jjWRNfDNXD_VjontQqZp79tt0GL03L3nqoYRrv5RpqDaC8WCoB0jLtG' ], $result['handles'] );
	}

	/**
	 * Tests sending Facebook pixel events to Facebook.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_send_pixel_events_sends_pixel_events() {
		$data = [
			'action_source'    => 'website',
			'event_time'       => '1652769366',
			'event_id'         => '4061a42a-4b12-479e-be51-25ad8a19f640',
			'event_name'       => 'Purchase',
			'event_source_url' => 'https://wordpress-facebook.ddev.site/checkout/',
			'custom_data'      => [
				'num_items'        => '1',
				'content_ids'      => [ 'woo-belt_17' ],
				'content_name'     => [ 'Belt' ],
				'content_type'     => 'product',
				'contents'         => [ '{"id":"woo-belt_17","quantity":1}' ],
				'value'            => '55.00',
				'currency'         => 'TRY',
				'content_category' => 'Accessories',
			],
			'user_data'        => [
				'client_ip_address' => '172.20.0.1',
				'client_user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
				'em'                => 'a95869aaee45119b0a46d9b3d5f2d788cc25995af4291fc0841afa71097004e3',
				'external_id'       => 'b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b',
				'ct'                => '6195198aeec54576f52474bf92cb02ec1c5e117d1dd9ddbceb08f5bfc545a0b8',
				'zp'                => 'b83c588da0c6931625f42e0948054a3ade722bfd02c27816305742ed7390ac6c',
				'st'                => '6959097001d10501ac7d54c0bdb8db61420f658f2922cc26e46d536119a31126',
				'ph'                => '63640264849a87c90356129d99ea165e37aa5fabc1fea46906df1a7ca50db492',
				'country'           => '79adb2a2fce5c6ba215fe5f27f532d4e7edbac4b6a5e09e1ef3a08084a904621',
				'click_id'          => 'fb.1.1650980285743.IwAR3UfvR6kpWQLdR7trcx0Xbc-G6P-4pNota-g8WGnBmtA6w_JMfSBvjZZrM',
				'browser_id'        => 'fb.1.1650401327550.1150300680',
			],
		];

		$events = [ new \SkyVerge\WooCommerce\Facebook\Events\Event( $data ) ];

		$response = function( $result, $parsed_args, $url ) use ( $events ) {
			$this->assertEquals( 'POST', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/1964583793745557/events', $url );

			$body = [
				'data'          => array_map(
					function ( $item ) {
						$event_data = $item->get_data();
						if ( isset( $event_data['user_data']['click_id'] ) ) {
							$event_data['user_data']['fbc'] = $event_data['user_data']['click_id'];
							unset( $event_data['user_data']['click_id'] );
						}
						if ( isset( $event_data['user_data']['browser_id'] ) ) {
							$event_data['user_data']['fbp'] = $event_data['user_data']['browser_id'];
							unset( $event_data['user_data']['browser_id'] );
						}
						return $event_data;
					},
					$events
				),
				'partner_agent' => 'woocommerce-' . WC()->version . '-' . WC_Facebook_Loader::PLUGIN_VERSION,
			];
			$this->assertEquals( $body, $parsed_args['body'] );
			return [
				'body'     => '{"events_received":1,"messages":[],"fbtrace_id":"ACkWGi-ptHPA897dD0liZEg"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->send_pixel_events( '1964583793745557', $events );

		$this->assertFalse( has_filter( 'wc_facebook_api_pixel_event_request_data' ) );
		$this->assertArrayHasKey( 'events_received', $result );
		$this->assertEquals( 1, $result['events_received'] );
	}

	/**
	 * Tests filter on Facebook pixel even data applied.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_send_pixel_events_applies_filter_to_pixel_events_data() {
		$data = [
			'action_source' => 'website',
			'custom_data'   => [
				'value' => '55.00',
			],
		];

		$events = [ new \SkyVerge\WooCommerce\Facebook\Events\Event( $data ) ];

		$filter = function( $data ) {
			$data['data'][0]['action_source']        = 'universe';
			$data['data'][0]['custom_data']['value'] = '1,000,000.00';
			return $data;
		};
		add_filter( 'wc_facebook_api_pixel_event_request_data', $filter );

		$response = function( $result, $parsed_args, $url ) use ( $events ) {
			$this->assertEquals( 'universe', $parsed_args['body']['data'][0]['action_source'] );
			$this->assertEquals( '1,000,000.00', $parsed_args['body']['data'][0]['custom_data']['value'] );
			return [
				'body'     => '{"events_received":1,"messages":[],"fbtrace_id":"ACkWGi-ptHPA897dD0liZEg"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->send_pixel_events( '1964583793745557', $events );

		$this->assertTrue( has_filter( 'wc_facebook_api_pixel_event_request_data' ) );
		$this->assertArrayHasKey( 'events_received', $result );
		$this->assertEquals( 1, $result['events_received'] );
	}

	/**
	 * Test fetch Facebook business configuration settings.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_get_business_configuration_returns_data() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/fbe_business?fbe_external_business_id=wordpress-facebook-627c01b68bc60', $url );
			return [
				'body'     => '{"business":{"name":"WordPress-Facebook"},"catalogs":[{"feature_instance_id":"392979412771234","enabled":true}],"catalog_feed_scheduled":{"enabled":false},"fb_shops":[{"feature_instance_id":"342416671202958","enabled":true}],"ig_cta":{"enabled":false},"ig_shopping":{"enabled":false},"messenger_chat":{"enabled":false},"messenger_menu":{"enabled":false},"page_card":{"enabled":false},"page_cta":{"enabled":false},"page_post":{"enabled":false},"page_shop":{"enabled":false},"pixels":[{"feature_instance_id":"528503782251953","enabled":true}],"thread_intent":{"enabled":false}}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->get_business_configuration( 'wordpress-facebook-627c01b68bc60' );

		$this->assertArrayHasKey( 'ig_shopping', $result );
		$this->assertArrayHasKey( 'ig_cta', $result );
		$this->assertArrayHasKey( 'messenger_chat', $result );

		$this->assertArrayHasKey( 'enabled', $result['ig_shopping'] );
		$this->assertArrayHasKey( 'enabled', $result['ig_cta'] );
		$this->assertArrayHasKey( 'enabled', $result['messenger_chat'] );
	}

	/**
	 * Test update Facebook messenger configuration api call.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_update_messenger_configuration_sends_configuration_updates() {
		$configuration = [
			'enabled'        => false,
			'default_locale' => '',
			'domains'        => [],
		];

		$response = function( $result, $parsed_args, $url ) use ( $configuration ) {
			$this->assertEquals( 'POST', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/fbe_business?fbe_external_business_id=wordpress-facebook-6283669706474', $url );

			$body = [
				'fbe_external_business_id' => 'wordpress-facebook-6283669706474',
				'messenger_chat'           => [
					'enabled' => false,
					'domains' => [],
				],
			];
			$this->assertEquals( $body, $parsed_args['body'] );

			return [
				'body'     => '{"success":true}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->update_messenger_configuration( 'wordpress-facebook-6283669706474', $configuration );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test Facebook business ids fetching.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_get_installation_ids_returns_data() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/fbe_business/fbe_installs?fbe_external_business_id=wordpress-facebook-6283669706474', $url );

			return [
				'body'     => '{"data":[{"business_manager_id":"973766133343161","commerce_merchant_settings_id":"400812858215678","onsite_eligible":false,"pixel_id":"1964583793745557","profiles":["100564162645958"],"ad_account_id":"0","catalog_id":"2536275516506259","pages":["100564162645958"],"token_type":"User","installed_features":[{"feature_instance_id":"2581241938677946","feature_type":"messenger_chat","connected_assets":{"page_id":"100564162645958"},"additional_info":{"onsite_eligible":false}},{"feature_instance_id":"342416671202958","feature_type":"fb_shop","connected_assets":{"catalog_id":"2536275516506259","commerce_merchant_settings_id":"400812858215678","page_id":"100564162645958"},"additional_info":{"onsite_eligible":false}},{"feature_instance_id":"1468417443607539","feature_type":"pixel","connected_assets":{"page_id":"100564162645958","pixel_id":"1964583793745557"},"additional_info":{"onsite_eligible":false}},{"feature_instance_id":"1150084395846296","feature_type":"catalog","connected_assets":{"catalog_id":"2536275516506259","page_id":"100564162645958","pixel_id":"1964583793745557"},"additional_info":{"onsite_eligible":false}}]}]}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->get_installation_ids( 'wordpress-facebook-6283669706474' );

		$this->assertArrayHasKey( 'data', $result );
		$this->assertIsArray( $result['data'] );

		$data = current( $result['data'] );

		$this->assertArrayHasKey( 'pages', $data );
		$this->assertArrayHasKey( 'pixel_id', $data );
		$this->assertArrayHasKey( 'catalog_id', $data );
		$this->assertArrayHasKey( 'business_manager_id', $data );
		$this->assertArrayHasKey( 'ad_account_id', $data );
		$this->assertArrayHasKey( 'commerce_merchant_settings_id', $data );

		$this->assertIsArray( $data['pages'] );

		$this->assertEquals( '100564162645958', $data['pages'][0] );
		$this->assertEquals( '1964583793745557', $data['pixel_id'] );
		$this->assertEquals( '2536275516506259', $data['catalog_id'] );
		$this->assertEquals( '973766133343161', $data['business_manager_id'] );
		$this->assertEquals( '0', $data['ad_account_id'] );
		$this->assertEquals( '400812858215678', $data['commerce_merchant_settings_id'] );
	}

	/**
	 * Test fetching Facebook pages and corresponding access tokens.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_retrieve_page_access_token_retrieves_a_token() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/me/accounts', $url );

			return [
				'body'     => '{"data":[{"access_token":"EAAGvQJc4NAQBAJCNYEmiQhS9tEL0RBtyZAkuYZAbhHCdPymmakc2L3cwCCfY6fh2bD7u7LA7hapY6IfRw5xQqpO324K749GHl46NUNByhbKDBXfUq33JM5lIOucbdZBAc6FrqkZBleLZBaCjVWBsQ1ticFay9iNmw9tMSIml4i6MRyPw4t4dXmK5LQZCD1oUzKeYkCICnEOgZDZD","category":"E-commerce website","category_list":[{"id":"1756049968005436","name":"E-commerce website"}],"name":"Dima for WooCommerce Second Page","id":"100564162645958","tasks":["ANALYZE","ADVERTISE","MESSAGING","MODERATE","CREATE_CONTENT","MANAGE"]},{"access_token":"EAAGvQJc4NAQBAGpwt4W1JYnG6OvLZCXWOpv713bWRDdWtEjy8c8bHonrZCKW0Q7sYf4a1AR0rW2C0p8XqOWwroQnZBP1peH986oB9fjxy8WCZBOb9bM3j50532TBWTT9ehDthXbJyheaTugj1qhmttfehS3nmGmG8gN3dGSwfqUcIDBgCG5CZC0vR22cajhUfaV2CfJ2qUgZDZD","category":"E-commerce website","category_list":[{"id":"1756049968005436","name":"E-commerce website"}],"name":"My Local Woo Commerce Store Page","id":"109649988385192","tasks":["ANALYZE","ADVERTISE","MESSAGING","MODERATE","CREATE_CONTENT","MANAGE"]}],"paging":{"cursors":{"before":"MTAwNTY0MTYyNjQ1OTU4","after":"MTA5NjQ5OTg4Mzg1MTky"}}}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->retrieve_page_access_token();

		$this->assertArrayHasKey( 'data', $result );
		$this->assertIsArray( $result['data'] );
		$this->assertCount( 2, $result['data'] );

		$this->assertEquals( '100564162645958', $result['data'][0]['id'] );
		$this->assertEquals( '109649988385192', $result['data'][1]['id'] );

		$this->assertEquals( 'EAAGvQJc4NAQBAJCNYEmiQhS9tEL0RBtyZAkuYZAbhHCdPymmakc2L3cwCCfY6fh2bD7u7LA7hapY6IfRw5xQqpO324K749GHl46NUNByhbKDBXfUq33JM5lIOucbdZBAc6FrqkZBleLZBaCjVWBsQ1ticFay9iNmw9tMSIml4i6MRyPw4t4dXmK5LQZCD1oUzKeYkCICnEOgZDZD', $result['data'][0]['access_token'] );
		$this->assertEquals( 'EAAGvQJc4NAQBAGpwt4W1JYnG6OvLZCXWOpv713bWRDdWtEjy8c8bHonrZCKW0Q7sYf4a1AR0rW2C0p8XqOWwroQnZBP1peH986oB9fjxy8WCZBOb9bM3j50532TBWTT9ehDthXbJyheaTugj1qhmttfehS3nmGmG8gN3dGSwfqUcIDBgCG5CZC0vR22cajhUfaV2CfJ2qUgZDZD', $result['data'][1]['access_token'] );
	}

	/**
	 * Test fetching group products.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_get_product_group_product_ids() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/5904678649559740/products?fields=id,retailer_id&limit=1000', $url );

			return [
				'body'     => '{"data":[{"id":"7487461394628803","retailer_id":"woo-vneck-tee-green_106"},{"id":"5904678682893070","retailer_id":"woo-vneck-tee_91"},{"id":"4890491151060707","retailer_id":"woo-vneck-tee-blue_107"},{"id":"4121381831320113","retailer_id":"woo-vneck-tee-red_105"}],"paging":{"cursors":{"before":"QVFIUl9LZAlBVdjVFT0VvNF96RVg4R1QtYXpKRVdZAaDg1SmU4YTNuMmx2NVZAxTXV2czBtYkxUemI4amhSZAFpkX0ZAZAcVF3ejlmNVpUZAHEyMTdUcFBySDExZAmZAn","after":"QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3"},"next":"https:\/\/graph.facebook.com\/v12.0\/5904678649559740\/products?fields=id\u00252Cretailer_id&limit=1000&after=QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3","previous":"https:\/\/graph.facebook.com\/v12.0\/5904678649559740\/products?fields=id\u00252Cretailer_id&limit=1000&before=QVFIUl9LZAlBVdjVFT0VvNF96RVg4R1QtYXpKRVdZAaDg1SmU4YTNuMmx2NVZAxTXV2czBtYkxUemI4amhSZAFpkX0ZAZAcVF3ejlmNVpUZAHEyMTdUcFBySDExZAmZAn"}}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->get_product_group_product_ids( '5904678649559740' );

		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'paging', $result );
	}

	/**
	 * Test fetching next page of results address from a paginated Facebook result set.
	 *
	 * @return void
	 * @throws JsonException Throws exception in case JSON parsing failure.
	 */
	public function test_get_paging_next() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/5904678649559740/products?fields=id,retailer_id&limit=1000', $url );

			return [
				'body'     => '{"data":[{"id":"7487461394628803","retailer_id":"woo-vneck-tee-green_106"},{"id":"5904678682893070","retailer_id":"woo-vneck-tee_91"},{"id":"4890491151060707","retailer_id":"woo-vneck-tee-blue_107"},{"id":"4121381831320113","retailer_id":"woo-vneck-tee-red_105"}],"paging":{"cursors":{"before":"QVFIUl9LZAlBVdjVFT0VvNF96RVg4R1QtYXpKRVdZAaDg1SmU4YTNuMmx2NVZAxTXV2czBtYkxUemI4amhSZAFpkX0ZAZAcVF3ejlmNVpUZAHEyMTdUcFBySDExZAmZAn","after":"QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3"},"next":"https:\/\/graph.facebook.com\/v12.0\/5904678649559740\/products?fields=id\u00252Cretailer_id&limit=1000&after=QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3","previous":"https:\/\/graph.facebook.com\/v12.0\/5904678649559740\/products?fields=id\u00252Cretailer_id&limit=1000&before=QVFIUl9LZAlBVdjVFT0VvNF96RVg4R1QtYXpKRVdZAaDg1SmU4YTNuMmx2NVZAxTXV2czBtYkxUemI4amhSZAFpkX0ZAZAcVF3ejlmNVpUZAHEyMTdUcFBySDExZAmZAn"}}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->get_product_group_product_ids( '5904678649559740' );

		$next = WC_Facebookcommerce_Graph_API::get_paging_next( $result );

		$this->assertEquals( 'https://graph.facebook.com/v12.0/5904678649559740/products?fields=id%2Cretailer_id&limit=1000&after=QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3', $next );

		$next = WC_Facebookcommerce_Graph_API::get_paging_next( $result, 0 );

		$this->assertEquals( '', $next );
	}

	/**
	 * Test fetching data from Facebook using paging next url from the previous paginated response.
	 *
	 * @return void
	 * @throws Exception|JsonException Connection or response parsing exceptions.
	 */
	public function test_next() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/5904678649559740/products?fields=id\u00252Cretailer_id&limit=1000&after=QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3', $url );

			return [
				'body'     => '{"data":[{"id":"7487461394628803","retailer_id":"woo-vneck-tee-green_106"},{"id":"5904678682893070","retailer_id":"woo-vneck-tee_91"},{"id":"4890491151060707","retailer_id":"woo-vneck-tee-blue_107"},{"id":"4121381831320113","retailer_id":"woo-vneck-tee-red_105"}],"paging":{"cursors":{"before":"QVFIUl9LZAlBVdjVFT0VvNF96RVg4R1QtYXpKRVdZAaDg1SmU4YTNuMmx2NVZAxTXV2czBtYkxUemI4amhSZAFpkX0ZAZAcVF3ejlmNVpUZAHEyMTdUcFBySDExZAmZAn","after":"QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3"},"next":"https:\/\/graph.facebook.com\/v12.0\/5904678649559740\/products?fields=id\u00252Cretailer_id&limit=1000&after=QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3","previous":"https:\/\/graph.facebook.com\/v12.0\/5904678649559740\/products?fields=id\u00252Cretailer_id&limit=1000&before=QVFIUl9LZAlBVdjVFT0VvNF96RVg4R1QtYXpKRVdZAaDg1SmU4YTNuMmx2NVZAxTXV2czBtYkxUemI4amhSZAFpkX0ZAZAcVF3ejlmNVpUZAHEyMTdUcFBySDExZAmZAn"}}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$response = $this->api->next( 'https://graph.facebook.com/v12.0/5904678649559740/products?fields=id\u00252Cretailer_id&limit=1000&after=QVFIUm5WY2Y4V1NRbHRlU1RkOVk3MkRPdFB0ZAHJFSXRHT3ZADMG9FXzZAYaDQtMG9Odkt0YlB4Mi1IcktwbXJVcEI1TU1HTkI1eFBuWjZASVlltanVJcGxzbkt3' );

		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'paging', $response );
	}

	public function test_get_facebook_id_returns_facebook_product_id() {
		$facebook_catalog_id  = '726635365295186';
		$facebook_retailer_id = 'wc_post_id_127';

		$expected = [
			'id'            => 'product-id',
			'product_group' => [
				'id' => 'product-group-id',
			],
		];

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/catalog:726635365295186:d2NfcG9zdF9pZF8xMjc=/?fields=id,product_group{id}', $url );
			return [
				'body'     => '{"id":"product-id","product_group":{"id":"product-group-id"}}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$facebook_product_id = $this->api->get_facebook_id( $facebook_catalog_id, $facebook_retailer_id );

		$this->assertEquals( $expected, $facebook_product_id );
	}
}
